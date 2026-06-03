<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\ApiResource;
use Stripe\Event;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

/**
 * Handles "charge.refunded" events (refunds initiated from the Stripe Dashboard). The event object is
 * a Charge, so we retrieve its related PaymentIntent (with "latest_charge" expanded) and store it on the
 * payment details. The PaymentIntent transition processor then drives the "completed -> refunded" transition
 * for a full refund. A partial refund leaves "charge.refunded" false, so no transition is applied.
 */
final readonly class ChargeRefundedWebhookEventProcessor implements WebhookEventProcessorInterface
{
    /**
     * @param array<string, string[]> $supportedFactoriesAndEvents
     * @param RetrieveManagerInterface<ApiResource> $retrieveManager
     */
    public function __construct(
        private array $supportedFactoriesAndEvents,
        private RetrieveManagerInterface $retrieveManager,
        private LoggerInterface $logger,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Event $event): void
    {
        /** @var StripeObject|null $object */
        $object = $event->data->object;
        Assert::isInstanceOf(
            $object,
            StripeObject::class,
            'The Stripe event data object must be an instance of StripeObject.',
        );

        $paymentIntentId = $object->offsetGet('payment_intent');
        if (false === is_string($paymentIntentId) || '' === $paymentIntentId) {
            return;
        }

        $this->logPartialRefund($paymentRequest, $object);

        $stripeApiResource = $this->retrieveManager->retrieve($paymentRequest, $paymentIntentId);

        $paymentRequest->getPayment()->setDetails($stripeApiResource->toArray());
    }

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        $factoryName = $paymentRequest->getMethod()->getGatewayConfig()?->getFactoryName() ?? '';
        if (false === array_key_exists($factoryName, $this->supportedFactoriesAndEvents)) {
            return false;
        }

        return in_array($event->type, $this->supportedFactoriesAndEvents[$factoryName], true);
    }

    private function logPartialRefund(PaymentRequestInterface $paymentRequest, StripeObject $charge): void
    {
        if (true === $charge->offsetGet('refunded')) {
            return;
        }

        $amountRefunded = $charge->offsetGet('amount_refunded');
        if (false === is_int($amountRefunded) || $amountRefunded <= 0) {
            return;
        }

        $this->logger->info(sprintf(
            'Stripe partial refund (amount: %d) received for payment "%s". Sylius has no partial-refund state, so no payment transition was applied.',
            $amountRefunded,
            (string) $paymentRequest->getPayment()->getId(),
        ));
    }
}
