<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutAvailabilityChecker;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

#[AllowMockObjectsWithoutExpectations]
final class ExpressCheckoutAvailabilityCheckerTest extends TestCase
{
    public function test_it_is_available_when_the_resolver_finds_a_payment_method(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $checker = $this->createChecker($channel, $paymentMethod);

        self::assertTrue($checker->isAvailable());
    }

    public function test_it_is_not_available_when_the_resolver_finds_no_payment_method(): void
    {
        $channel = $this->createMock(ChannelInterface::class);

        $checker = $this->createChecker($channel, null);

        self::assertFalse($checker->isAvailable());
    }

    public function test_it_is_not_available_when_no_channel_is_found(): void
    {
        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        $resolver = $this->createMock(ExpressCheckoutPaymentMethodResolverInterface::class);
        $resolver->expects($this->never())->method('resolveForChannel');

        $checker = new ExpressCheckoutAvailabilityChecker($channelContext, $resolver);

        self::assertFalse($checker->isAvailable());
    }

    private function createChecker(
        ChannelInterface $channel,
        ?PaymentMethodInterface $resolved,
    ): ExpressCheckoutAvailabilityChecker {
        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($channel);

        $resolver = $this->createMock(ExpressCheckoutPaymentMethodResolverInterface::class);
        $resolver->method('resolveForChannel')->with($channel)->willReturn($resolved);

        return new ExpressCheckoutAvailabilityChecker($channelContext, $resolver);
    }
}
