<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use ArrayAccess;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

final readonly class RefundEventTokenHashResolver implements RefundEventTokenHashResolverInterface
{
    public function __construct(
        private ClientFactoryInterface $clientFactory,
    ) {
    }

    public function resolve(StripeObject $object, PaymentMethodInterface $paymentMethod): ?string
    {
        $paymentIntentId = $object->offsetGet('payment_intent');
        if (false === is_string($paymentIntentId) || '' === $paymentIntentId) {
            return null;
        }

        $stripeClient = $this->clientFactory->createFromPaymentMethod($paymentMethod);
        $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId);

        /** @var ArrayAccess<string, string>|null $metadata */
        $metadata = $paymentIntent->metadata;
        if (false === $metadata instanceof ArrayAccess) {
            return null;
        }

        $hash = $metadata->offsetGet(MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME);

        return is_string($hash) ? $hash : null;
    }
}
