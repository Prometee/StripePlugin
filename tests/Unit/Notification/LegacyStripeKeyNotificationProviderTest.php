<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Notification;

use FluxSE\SyliusStripePlugin\Notification\LegacyStripeKeyNotificationProvider;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

#[AllowMockObjectsWithoutExpectations]
final class LegacyStripeKeyNotificationProviderTest extends TestCase
{
    private LegacyStripePaymentMethodsProviderInterface&MockObject $legacyStripePaymentMethodsProvider;

    protected function setUp(): void
    {
        $this->legacyStripePaymentMethodsProvider = $this->createMock(LegacyStripePaymentMethodsProviderInterface::class);
    }

    public function test_it_always_supports_notifications(): void
    {
        self::assertTrue($this->createProvider()->supports());
    }

    public function test_it_returns_no_notifications_when_there_are_no_legacy_payment_methods(): void
    {
        $this->legacyStripePaymentMethodsProvider->method('provide')->willReturn([]);

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_returns_one_notification_per_legacy_payment_method(): void
    {
        $this->legacyStripePaymentMethodsProvider
            ->method('provide')
            ->willReturn([
                $this->paymentMethod(11, 'Stripe Checkout'),
                $this->paymentMethod(13, 'Stripe Web Elements'),
            ])
        ;

        self::assertSame([
            'legacy_stripe_secret_key.11' => [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => 'Stripe Checkout',
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => ['id' => 11, '_fragment' => 'stripe-gateway-configuration'],
            ],
            'legacy_stripe_secret_key.13' => [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => 'Stripe Web Elements',
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => ['id' => 13, '_fragment' => 'stripe-gateway-configuration'],
            ],
        ], $this->createProvider()->getNotifications());
    }

    private function createProvider(): LegacyStripeKeyNotificationProvider
    {
        return new LegacyStripeKeyNotificationProvider($this->legacyStripePaymentMethodsProvider);
    }

    private function paymentMethod(int $id, string $name): PaymentMethodInterface&MockObject
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getId')->willReturn($id);
        $paymentMethod->method('getName')->willReturn($name);

        return $paymentMethod;
    }
}
