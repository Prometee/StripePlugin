<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\StateMachine;

use FluxSE\SyliusStripePlugin\StateMachine\StripeStateAppliedChecker;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class StripeStateAppliedCheckerTest extends TestCase
{
    private StripeStateAppliedChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new StripeStateAppliedChecker();
    }

    public function test_it_returns_false_when_details_are_empty(): void
    {
        $payment = $this->paymentWithDetails([]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_CANCEL));
        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    public function test_it_returns_false_for_unknown_object_shape(): void
    {
        $payment = $this->paymentWithDetails(['object' => 'something_else', 'status' => 'succeeded']);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
    }

    public function test_it_returns_false_for_unknown_action(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, 'unknown_action'));
    }

    public function test_payment_intent_authorize_converges_when_status_is_succeeded(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
    }

    public function test_payment_intent_authorize_does_not_converge_while_requires_capture(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
    }

    public function test_payment_intent_cancel_converges_when_status_is_canceled(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_CANCELED,
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_CANCEL));
    }

    public function test_payment_intent_refund_converges_when_latest_charge_is_refunded(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'latest_charge' => ['refunded' => true],
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    public function test_payment_intent_refund_does_not_converge_when_latest_charge_is_not_refunded(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'latest_charge' => ['refunded' => false],
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    public function test_payment_intent_refund_does_not_converge_when_latest_charge_is_not_expanded(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'latest_charge' => 'ch_abc',
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    public function test_checkout_session_authorize_converges_when_payment_status_is_paid(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
    }

    public function test_checkout_session_authorize_does_not_converge_while_unpaid(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'payment_status' => Session::PAYMENT_STATUS_UNPAID,
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_AUTHORIZE));
    }

    public function test_checkout_session_cancel_converges_when_session_is_expired(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'status' => Session::STATUS_EXPIRED,
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_CANCEL));
    }

    public function test_checkout_session_cancel_converges_when_nested_payment_intent_is_canceled(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'status' => Session::STATUS_OPEN,
            'payment_intent' => ['status' => PaymentIntent::STATUS_CANCELED],
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_CANCEL));
    }

    public function test_checkout_session_refund_converges_when_nested_charge_is_refunded(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'payment_intent' => [
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'latest_charge' => ['refunded' => true],
            ],
        ]);

        self::assertTrue($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    public function test_checkout_session_refund_does_not_converge_without_nested_payment_intent(): void
    {
        $payment = $this->paymentWithDetails([
            'object' => Session::OBJECT_NAME,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
        ]);

        self::assertFalse($this->checker->isAlreadyApplied($payment, PaymentRequestInterface::ACTION_REFUND));
    }

    /**
     * @param array<string, mixed> $details
     */
    private function paymentWithDetails(array $details): PaymentInterface
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getDetails')->willReturn($details);

        return $payment;
    }
}
