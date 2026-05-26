<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout;

use Doctrine\Common\Collections\ArrayCollection;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingRate;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayload;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssemblerInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ShippingOptionsCalculator;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final class ShippingOptionsCalculatorTest extends TestCase
{
    /** @var CartContextInterface&MockObject */
    private CartContextInterface $cartContext;

    /** @var OrderProcessorInterface&MockObject */
    private OrderProcessorInterface $orderProcessor;

    /** @var ShippingMethodsResolverInterface&MockObject */
    private ShippingMethodsResolverInterface $shippingMethodsResolver;

    /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface>&MockObject */
    private ShippingMethodRepositoryInterface $shippingMethodRepository;

    /** @var ExpressCheckoutAddressNormalizerInterface&MockObject */
    private ExpressCheckoutAddressNormalizerInterface $addressNormalizer;

    /** @var ExpressCheckoutPayloadReaderInterface&MockObject */
    private ExpressCheckoutPayloadReaderInterface $payloadReader;

    /** @var ShippingRateAssemblerInterface&MockObject */
    private ShippingRateAssemblerInterface $shippingRateAssembler;

    private ShippingOptionsCalculator $calculator;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->shippingMethodsResolver = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->shippingMethodRepository = $this->createMock(ShippingMethodRepositoryInterface::class);
        $this->addressNormalizer = $this->createMock(ExpressCheckoutAddressNormalizerInterface::class);
        $this->payloadReader = $this->createMock(ExpressCheckoutPayloadReaderInterface::class);
        $this->shippingRateAssembler = $this->createMock(ShippingRateAssemblerInterface::class);

        $this->calculator = new ShippingOptionsCalculator(
            $this->cartContext,
            $this->orderProcessor,
            $this->shippingMethodsResolver,
            $this->shippingMethodRepository,
            $this->addressNormalizer,
            $this->payloadReader,
            $this->shippingRateAssembler,
        );
    }

    public function test_it_throws_when_cart_is_missing(): void
    {
        $this->cartContext->method('getCart')->willThrowException(new CartNotFoundException());

        $this->expectException(CartUnavailableException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_throws_when_cart_is_empty(): void
    {
        $cart = $this->createMock(OrderInterface::class);
        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn(true);
        $cart->method('getItems')->willReturn($items);

        $this->cartContext->method('getCart')->willReturn($cart);

        $this->expectException(CartUnavailableException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_throws_when_address_is_missing_from_the_payload(): void
    {
        $this->cartContext->method('getCart')->willReturn($this->createReadyCart());
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([]));

        $this->expectException(InvalidPayloadException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_returns_empty_rates_when_cart_has_no_shipment(): void
    {
        $cart = $this->createReadyCart();
        $shippingAddress = $this->createMock(AddressInterface::class);

        $cart->expects(self::once())->method('setShippingAddress')->with($shippingAddress);
        $cart->method('getBillingAddress')->willReturn(null);
        $cart->expects(self::once())->method('setBillingAddress');

        $cart->method('getShipments')->willReturn(new ArrayCollection([]));
        $cart->method('getTotal')->willReturn(2100);
        $cart->method('getItemsSubtotal')->willReturn(2000);
        $cart->method('getTaxExcludedTotal')->willReturn(100);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($shippingAddress);

        $this->orderProcessor->expects(self::once())->method('process')->with($cart);
        $this->shippingRateAssembler->expects(self::never())->method('assemble');

        $options = $this->calculator->calculate(new Request());

        self::assertSame([], $options->shippingRates);
        self::assertCount(2, $options->lineItems);
        self::assertSame('Subtotal', $options->lineItems[0]->name);
        self::assertSame(2000, $options->lineItems[0]->amount);
        self::assertSame('Tax', $options->lineItems[1]->name);
        self::assertSame(100, $options->lineItems[1]->amount);
        // No shipment → totalAmount = cart.getTotal(); frontend rejects the change anyway.
        self::assertSame(2100, $options->totalAmount);
    }

    public function test_it_skips_reprocess_when_cart_is_already_aligned_with_first_rate(): void
    {
        // Cart was already processed with supportedMethods[0] = UPS (which is what
        // Stripe ECE will preview as the default rate). No need to re-process — cart.getTotal()
        // already reflects the previewed shipping method.
        // Real user numbers (Summer Bloom Jeans, US/NY, UPS, tax-excluded channel):
        // items net $18.58 + tax $1.30 + shipping $9.91 → cart total $29.79.
        $shipment = $this->createMock(ShipmentInterface::class);
        $supportedMethod = $this->createShippingMethod('ups');
        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getTotal')->willReturn(2979);
        $cart->method('getItemsSubtotal')->willReturn(1858);
        $cart->method('getTaxExcludedTotal')->willReturn(130);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));

        $this->shippingMethodsResolver->method('getSupportedMethods')->with($shipment)->willReturn([$supportedMethod]);
        // originalMethod == supportedMethods[0] → previewMethod equals originalMethod → skip second pass.
        $shipment->method('getMethod')->willReturn($supportedMethod);

        $rate = new ExpressCheckoutShippingRate('ups', 'UPS', 991, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$rate]);

        $this->orderProcessor->expects(self::once())->method('process')->with($cart);

        $shipment->expects(self::once())->method('setMethod')->with($supportedMethod);

        $options = $this->calculator->calculate(new Request());

        self::assertSame([$rate], $options->shippingRates);
        self::assertSame(1858, $options->lineItems[0]->amount);
        self::assertSame(130, $options->lineItems[1]->amount);
        self::assertSame(2979, $options->totalAmount);
    }

    public function test_it_reprocesses_cart_when_previewed_method_differs_from_original(): void
    {
        // shippingaddresschange path with no shippingRateId: shipment has no method set yet,
        // so previewMethod = supportedMethods[0] (FedEx) differs from originalMethod (null).
        // The calculator runs a second OrderProcessor pass so cart.getTotal() reflects FedEx
        // (rather than the pre-process state) — keeping cart.getTotal() the authoritative
        // value the backend will charge.
        $shipment = $this->createMock(ShipmentInterface::class);
        $fedex = $this->createShippingMethod('fedex');
        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        // After the second process pass with FedEx selected.
        $cart->method('getTotal')->willReturn(2845);
        $cart->method('getItemsSubtotal')->willReturn(1858);
        $cart->method('getTaxExcludedTotal')->willReturn(130);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$fedex]);
        $shipment->method('getMethod')->willReturn(null);

        $fedexRate = new ExpressCheckoutShippingRate('fedex', 'FedEx', 857, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$fedexRate]);

        $this->orderProcessor->expects(self::exactly(2))->method('process')->with($cart);
        $shipment->expects(self::once())->method('setMethod')->with($fedex);

        $options = $this->calculator->calculate(new Request());

        self::assertSame(2845, $options->totalAmount);
        self::assertSame(1858, $options->lineItems[0]->amount);
        self::assertSame(130, $options->lineItems[1]->amount);
    }

    public function test_it_skips_tax_line_when_subtotal_already_includes_tax(): void
    {
        // Tax-included channel: tax_rate.includedInPrice=true → unit_price is gross,
        // tax adjustment is NEUTRAL (skipped from cart.getTotal()). The customer sees
        // $19.88 gross in the catalog; the wallet popup should mirror that, not break
        // it into a confusing $18.58 + $1.30.
        $shipment = $this->createMock(ShipmentInterface::class);
        $ups = $this->createShippingMethod('ups');
        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        // Same product ($19.88 gross) + same UPS shipping, but tax baked into unit price.
        // In tax-included mode, all tax adjustments are NEUTRAL → getTaxExcludedTotal() == 0.
        $cart->method('getTotal')->willReturn(2979);
        $cart->method('getItemsSubtotal')->willReturn(1988);
        $cart->method('getTaxExcludedTotal')->willReturn(0);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$ups]);
        $shipment->method('getMethod')->willReturn(null);

        $rate = new ExpressCheckoutShippingRate('ups', 'UPS', 991, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$rate]);

        // Two passes: initial + after aligning shipment with rates[0].
        $this->orderProcessor->expects(self::exactly(2))->method('process')->with($cart);

        $options = $this->calculator->calculate(new Request());

        // Only Subtotal line — Tax would be 0 because tax is already in the gross unit price.
        self::assertCount(1, $options->lineItems);
        self::assertSame('Subtotal', $options->lineItems[0]->name);
        self::assertSame(1988, $options->lineItems[0]->amount);
        self::assertSame(2979, $options->totalAmount);
    }

    public function test_it_keeps_shipping_tax_in_total_when_shipping_is_taxable(): void
    {
        // Taxable shipping: the shipping method belongs to a tax category, so the
        // second OrderProcessor pass produces an additional shipping tax adjustment
        // that ends up in cart.getTotal() and (recursively) in getTaxExcludedTotal().
        // totalAmount = cart.getTotal() captures the full amount, including shipping tax,
        // exactly as Sylius will charge it via the PaymentIntent.
        $shipment = $this->createMock(ShipmentInterface::class);
        $ups = $this->createShippingMethod('ups');
        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getTotal')->willReturn(3029);
        $cart->method('getItemsSubtotal')->willReturn(1858);
        // items_tax 130 + shipping_tax 50 — both non-neutral, both included recursively.
        $cart->method('getTaxExcludedTotal')->willReturn(180);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$ups]);
        $shipment->method('getMethod')->willReturn(null);

        $rate = new ExpressCheckoutShippingRate('ups', 'UPS', 991, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$rate]);

        $this->orderProcessor->expects(self::exactly(2))->method('process')->with($cart);

        $options = $this->calculator->calculate(new Request());

        self::assertSame(1858, $options->lineItems[0]->amount);
        self::assertSame(180, $options->lineItems[1]->amount);
        // cart.getTotal() — single source of truth, including shipping tax.
        self::assertSame(3029, $options->totalAmount);
    }

    public function test_it_reprocesses_the_order_when_a_shipping_rate_is_chosen(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $chosen = $this->createShippingMethod('chosen');
        $other = $this->createShippingMethod('other');

        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        // After re-processing with chosen rate: cart total reflects items + tax + chosen shipping.
        $cart->method('getTotal')->willReturn(2916);
        $cart->method('getItemsSubtotal')->willReturn(1858);
        $cart->method('getTaxExcludedTotal')->willReturn(130);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'address' => ['country' => 'US'],
            'shippingRateId' => 'chosen',
        ]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$other, $chosen]);
        $shipment->method('getMethod')->willReturn(null);

        $otherRate = new ExpressCheckoutShippingRate('other', 'Other', 991, 'usd');
        $chosenRate = new ExpressCheckoutShippingRate('chosen', 'Chosen', 928, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$otherRate, $chosenRate]);

        // Confirm path: TWO OrderProcessor passes — initial + after the chosen rate is applied.
        $this->orderProcessor->expects(self::exactly(2))->method('process')->with($cart);

        $shipment->expects(self::once())->method('setMethod')->with($chosen);

        $options = $this->calculator->calculate(new Request());

        // shippingratechange path: cart was re-processed with the chosen method, so totalAmount == cart.getTotal().
        self::assertSame(2916, $options->totalAmount);
    }

    public function test_it_falls_back_to_the_repository_when_chosen_method_is_not_in_supported_set(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $supported = $this->createShippingMethod('ups');
        $external = $this->createShippingMethod('external');

        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getTotal')->willReturn(0);
        $cart->method('getItemsSubtotal')->willReturn(0);
        $cart->method('getTaxExcludedTotal')->willReturn(0);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'address' => ['country' => 'US'],
            'shippingRateId' => 'external',
        ]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$supported]);
        $this->shippingMethodRepository->method('findOneBy')->with(['code' => 'external'])->willReturn($external);
        $this->shippingRateAssembler->method('assemble')->willReturn([]);
        $shipment->method('getMethod')->willReturn(null);

        $shipment->expects(self::once())->method('setMethod')->with($external);
        $this->orderProcessor->expects(self::exactly(2))->method('process');

        $this->calculator->calculate(new Request());
    }

    private function createReadyCart(): OrderInterface&MockObject
    {
        $cart = $this->createMock(OrderInterface::class);
        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn(false);
        $cart->method('getItems')->willReturn($items);

        return $cart;
    }

    private function createShippingMethod(string $code): ShippingMethodInterface&MockObject
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCode')->willReturn($code);

        return $method;
    }
}
