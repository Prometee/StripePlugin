<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use FluxSE\SyliusStripePlugin\Provider\CustomerEmailMetadataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

#[AllowMockObjectsWithoutExpectations]
final class CustomerEmailMetadataProviderTest extends TestCase
{
    /** @var CustomerEmailMetadataProvider<PaymentIntent> */
    private CustomerEmailMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new CustomerEmailMetadataProvider();
    }

    public function test_it_sets_customer_email_from_order(): void
    {
        $paymentRequest = $this->wrapOrderCustomerEmail('oliver@doe.com');

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['customer_email' => 'oliver@doe.com'], $params);
    }

    public function test_it_omits_customer_email_when_customer_has_no_email(): void
    {
        $paymentRequest = $this->wrapOrderCustomerEmail(null);

        $params = ['untouched' => true];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    public function test_it_omits_customer_email_when_order_has_no_customer(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn(null);

        $paymentRequest = $this->wrapOrder($order);

        $params = ['untouched' => true];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    public function test_it_omits_customer_email_when_payment_has_no_order(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn(null);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $params = ['untouched' => true];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    private function wrapOrderCustomerEmail(?string $email): PaymentRequestInterface
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getEmail')->willReturn($email);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);

        return $this->wrapOrder($order);
    }

    private function wrapOrder(OrderInterface $order): PaymentRequestInterface
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        return $paymentRequest;
    }
}
