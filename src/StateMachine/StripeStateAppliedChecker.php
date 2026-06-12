<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\StateMachine;

use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class StripeStateAppliedChecker implements StripeStateAppliedCheckerInterface
{
    public function isAlreadyApplied(PaymentInterface $payment, string $paymentRequestAction): bool
    {
        $details = $payment->getDetails();
        $object = $details['object'] ?? null;

        if (PaymentIntent::OBJECT_NAME === $object) {
            return $this->isPaymentIntentApplied($details, $paymentRequestAction);
        }

        if (Session::OBJECT_NAME === $object) {
            return $this->isCheckoutSessionApplied($details, $paymentRequestAction);
        }

        return false;
    }

    /** @param array<string, mixed> $paymentIntent */
    private function isPaymentIntentApplied(array $paymentIntent, string $paymentRequestAction): bool
    {
        $status = $paymentIntent['status'] ?? null;

        return match ($paymentRequestAction) {
            PaymentRequestInterface::ACTION_AUTHORIZE => PaymentIntent::STATUS_SUCCEEDED === $status,
            PaymentRequestInterface::ACTION_CANCEL => PaymentIntent::STATUS_CANCELED === $status,
            PaymentRequestInterface::ACTION_REFUND => $this->isLatestChargeRefunded($paymentIntent),
            default => false,
        };
    }

    /** @param array<string, mixed> $session */
    private function isCheckoutSessionApplied(array $session, string $paymentRequestAction): bool
    {
        return match ($paymentRequestAction) {
            PaymentRequestInterface::ACTION_AUTHORIZE => Session::PAYMENT_STATUS_PAID === ($session['payment_status'] ?? null),
            PaymentRequestInterface::ACTION_CANCEL => $this->isCheckoutSessionCanceled($session),
            PaymentRequestInterface::ACTION_REFUND => $this->isCheckoutSessionRefunded($session),
            default => false,
        };
    }

    /** @param array<string, mixed> $session */
    private function isCheckoutSessionCanceled(array $session): bool
    {
        if (Session::STATUS_EXPIRED === ($session['status'] ?? null)) {
            return true;
        }

        $paymentIntent = $session['payment_intent'] ?? null;
        if (is_array($paymentIntent)) {
            return PaymentIntent::STATUS_CANCELED === ($paymentIntent['status'] ?? null);
        }

        return false;
    }

    /** @param array<string, mixed> $session */
    private function isCheckoutSessionRefunded(array $session): bool
    {
        $paymentIntent = $session['payment_intent'] ?? null;
        if (!is_array($paymentIntent)) {
            return false;
        }

        return $this->isLatestChargeRefunded($paymentIntent);
    }

    /** @param array<string, mixed> $paymentIntent */
    private function isLatestChargeRefunded(array $paymentIntent): bool
    {
        $latestCharge = $paymentIntent['latest_charge'] ?? null;
        if (!is_array($latestCharge)) {
            return false;
        }

        return true === ($latestCharge['refunded'] ?? false);
    }
}
