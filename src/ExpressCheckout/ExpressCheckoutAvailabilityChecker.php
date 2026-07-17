<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;

final readonly class ExpressCheckoutAvailabilityChecker implements ExpressCheckoutAvailabilityCheckerInterface
{
    public function __construct(
        private ChannelContextInterface $channelContext,
        private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver,
    ) {
    }

    public function isAvailable(): bool
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return false;
        }

        if (!$channel instanceof ChannelInterface) {
            return false;
        }

        return null !== $this->paymentMethodResolver->resolveForChannel($channel);
    }
}
