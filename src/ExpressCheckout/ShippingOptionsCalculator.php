<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutLineItem;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingOptions;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssemblerInterface;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ShippingOptionsCalculator implements ShippingOptionsCalculatorInterface
{
    /** @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
    public function __construct(
        private CartContextInterface $cartContext,
        private OrderProcessorInterface $orderProcessor,
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private ExpressCheckoutAddressNormalizerInterface $addressNormalizer,
        private ExpressCheckoutPayloadReaderInterface $payloadReader,
        private ShippingRateAssemblerInterface $shippingRateAssembler,
    ) {
    }

    public function calculate(Request $request): ExpressCheckoutShippingOptions
    {
        $cart = $this->resolveCart();

        $payload = $this->payloadReader->read($request);
        $addressFields = $payload->getAddress();
        if (null === $addressFields) {
            throw InvalidPayloadException::missingAddress();
        }

        $shippingAddress = $this->addressNormalizer->normalizeAddress($addressFields);
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress(clone $shippingAddress);

        $this->orderProcessor->process($cart);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            return new ExpressCheckoutShippingOptions(
                shippingRates: [],
                lineItems: $this->buildLineItems($cart),
                totalAmount: $cart->getTotal(),
            );
        }

        $supportedMethods = $this->shippingMethodsResolver->getSupportedMethods($shipment);
        $originalMethod = $shipment->getMethod();

        $rates = $this->shippingRateAssembler->assemble($shipment, $supportedMethods, $cart->getCurrencyCode());

        $shippingRateId = $payload->getShippingRateId();
        $chosenMethod = null !== $shippingRateId ? $this->resolveChosenMethod($shippingRateId, $supportedMethods) : null;

        // Align the shipment with the rate the wallet will display: the customer-picked
        // rate when provided, otherwise rates[0] which is what Stripe ECE defaults to.
        $previewMethod = $chosenMethod ?? $supportedMethods[0] ?? $originalMethod;
        $shipment->setMethod($previewMethod);

        if ($previewMethod !== $originalMethod) {
            $this->orderProcessor->process($cart);
        }

        return new ExpressCheckoutShippingOptions(
            shippingRates: $rates,
            lineItems: $this->buildLineItems($cart),
            totalAmount: $cart->getTotal(),
        );
    }

    private function resolveCart(): OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            throw CartUnavailableException::notFound();
        }

        if (!$cart instanceof OrderInterface) {
            throw CartUnavailableException::notFound();
        }

        if ($cart->getItems()->isEmpty()) {
            throw CartUnavailableException::empty();
        }

        return $cart;
    }

    /** @param iterable<ShippingMethodInterface> $supportedMethods */
    private function resolveChosenMethod(string $shippingRateId, iterable $supportedMethods): ?ShippingMethodInterface
    {
        foreach ($supportedMethods as $method) {
            if ($method->getCode() === $shippingRateId) {
                return $method;
            }
        }

        /** @var ShippingMethodInterface|null $method */
        $method = $this->shippingMethodRepository->findOneBy(['code' => $shippingRateId]);

        return $method;
    }

    /**
     * Line items rendered by Stripe's Express Checkout Element next to the wallet
     * "Pay" button. Shipping must NOT be included — Stripe adds the cost of the
     * customer-selected `shippingRate` on top of `sum(lineItems)`.
     *
     * Mode-agnostic decomposition using Sylius's canonical helpers:
     *
     *  - `Subtotal` = `Order::getItemsSubtotal()` (sum of `unit_price + unit_promotion`).
     *    In tax-excluded channels this is items net; in tax-included channels it is
     *    items gross — matching what the customer sees in the catalog.
     *  - `Tax` = `Order::getTaxExcludedTotal()` (sum of NON-neutral tax adjustments,
     *    i.e. the tax that Sylius adds on top of unit prices). In excluded channels
     *    this is the visible tax line; in included channels it is 0 (tax adjustments
     *    are neutral, already baked into the unit prices) and we drop the Tax line.
     *
     * @return list<ExpressCheckoutLineItem>
     */
    private function buildLineItems(OrderInterface $cart): array
    {
        $subtotal = $cart->getItemsSubtotal();
        $tax = $cart->getTaxExcludedTotal();

        $lineItems = [new ExpressCheckoutLineItem(name: 'Subtotal', amount: $subtotal)];
        if ($tax > 0) {
            $lineItems[] = new ExpressCheckoutLineItem(name: 'Tax', amount: $tax);
        }

        return $lineItems;
    }
}
