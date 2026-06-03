<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Processor;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\ChargeRefundedWebhookEventProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\Charge;
use Stripe\Event;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ChargeRefundedWebhookEventProcessorTest extends TestCase
{
    /** @var array<string, string[]> */
    private const SUPPORTED = ['stripe_web_elements' => [Event::CHARGE_REFUNDED]];

    /** @var RetrieveManagerInterface<PaymentIntent>&MockObject */
    private RetrieveManagerInterface&MockObject $retrieveManager;

    private LoggerInterface&MockObject $logger;

    private ChargeRefundedWebhookEventProcessor $processor;

    protected function setUp(): void
    {
        $this->retrieveManager = $this->createMock(RetrieveManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new ChargeRefundedWebhookEventProcessor(
            self::SUPPORTED,
            $this->retrieveManager,
            $this->logger,
        );
    }

    public function test_it_retrieves_the_payment_intent_and_stores_it_on_the_payment_details(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_test_1',
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'latest_charge' => [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
                'refunded' => true,
            ],
        ]);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setDetails')->with($paymentIntent->toArray());
        $paymentRequest = $this->createPaymentRequest($payment);

        // Retrieves by the charge's payment_intent id, NOT by the charge id.
        $this->retrieveManager
            ->expects(self::once())
            ->method('retrieve')
            ->with($paymentRequest, 'pi_test_1')
            ->willReturn($paymentIntent);

        $this->processor->process($paymentRequest, $this->chargeRefundedEvent(refunded: true));
    }

    public function test_it_returns_early_when_the_charge_has_no_payment_intent(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::never())->method('setDetails');
        $paymentRequest = $this->createPaymentRequest($payment);

        $this->retrieveManager->expects(self::never())->method('retrieve');

        $event = Event::constructFrom([
            'id' => 'evt_test_1',
            'object' => Event::OBJECT_NAME,
            'type' => Event::CHARGE_REFUNDED,
            'data' => ['object' => [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
            ]],
        ]);

        $this->processor->process($paymentRequest, $event);
    }

    public function test_it_logs_partial_refunds_but_still_stores_details(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_test_1',
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'latest_charge' => [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
                'refunded' => false,
            ],
        ]);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getId')->willReturn(7);
        $payment->expects(self::once())->method('setDetails')->with($paymentIntent->toArray());
        $paymentRequest = $this->createPaymentRequest($payment);

        $this->retrieveManager->method('retrieve')->willReturn($paymentIntent);

        $this->logger->expects(self::once())->method('info');

        $this->processor->process($paymentRequest, $this->chargeRefundedEvent(refunded: false, amountRefunded: 500));
    }

    public function test_supports_only_the_charge_refunded_event_on_a_supported_factory(): void
    {
        $paymentRequest = $this->createPaymentRequest($this->createMock(PaymentInterface::class));

        self::assertTrue($this->processor->supports($paymentRequest, $this->chargeRefundedEvent(refunded: true)));

        $otherEvent = Event::constructFrom([
            'id' => 'evt_test_2',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'data' => ['object' => ['id' => 'pi_test_1', 'object' => PaymentIntent::OBJECT_NAME]],
        ]);
        self::assertFalse($this->processor->supports($paymentRequest, $otherEvent));
    }

    public function test_it_does_not_support_an_unsupported_factory(): void
    {
        $paymentRequest = $this->createPaymentRequest(
            $this->createMock(PaymentInterface::class),
            factoryName: 'paypal',
        );

        self::assertFalse($this->processor->supports($paymentRequest, $this->chargeRefundedEvent(refunded: true)));
    }

    private function chargeRefundedEvent(bool $refunded, int $amountRefunded = 0): Event
    {
        return Event::constructFrom([
            'id' => 'evt_test_1',
            'object' => Event::OBJECT_NAME,
            'type' => Event::CHARGE_REFUNDED,
            'data' => ['object' => [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
                'payment_intent' => 'pi_test_1',
                'refunded' => $refunded,
                'amount_refunded' => $amountRefunded,
            ]],
        ]);
    }

    private function createPaymentRequest(
        PaymentInterface&MockObject $payment,
        string $factoryName = 'stripe_web_elements',
    ): PaymentRequestInterface&MockObject {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getFactoryName')->willReturn($factoryName);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);

        return $paymentRequest;
    }
}
