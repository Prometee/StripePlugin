<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Stripe\SecretKey;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;

#[AllowMockObjectsWithoutExpectations]
final class LegacyStripePaymentMethodsProviderTest extends TestCase
{
    private const STRIPE_FACTORY_NAMES = ['stripe_checkout', 'stripe_web_elements'];

    /** @var PaymentMethodRepositoryInterface<PaymentMethodInterface>&MockObject */
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;

    private GatewayFactoryNameProviderInterface&MockObject $gatewayFactoryNameProvider;

    private LegacyKeyDetectorInterface&MockObject $legacyKeyDetector;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->gatewayFactoryNameProvider = $this->createMock(GatewayFactoryNameProviderInterface::class);
        $this->legacyKeyDetector = $this->createMock(LegacyKeyDetectorInterface::class);
    }

    public function test_it_returns_nothing_when_no_payment_methods_exist(): void
    {
        $this->paymentMethodRepository
            ->method('findBy')
            ->with(['enabled' => true])
            ->willReturn([])
        ;

        self::assertSame([], $this->createProvider()->provide());
    }

    public function test_it_ignores_non_stripe_payment_methods(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig(['secret_key' => 'sk_live_abc']);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('offline');
        $this->legacyKeyDetector->expects(self::never())->method('isLegacy');

        self::assertSame([], $this->createProvider()->provide());
    }

    public function test_it_skips_payment_methods_without_gateway_config(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->expects(self::never())->method('provide');

        self::assertSame([], $this->createProvider()->provide());
    }

    public function test_it_skips_stripe_payment_methods_using_restricted_keys(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig(['secret_key' => 'rk_live_abc']);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('stripe_checkout');
        $this->legacyKeyDetector->method('isLegacy')->with('rk_live_abc')->willReturn(false);

        self::assertSame([], $this->createProvider()->provide());
    }

    public function test_it_skips_stripe_payment_methods_without_secret_key(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig([]);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('stripe_checkout');
        $this->legacyKeyDetector->method('isLegacy')->with(null)->willReturn(false);

        self::assertSame([], $this->createProvider()->provide());
    }

    public function test_it_returns_only_stripe_payment_methods_using_legacy_keys(): void
    {
        $legacyOne = $this->paymentMethodWithGatewayConfig(['secret_key' => 'sk_live_abc']);
        $modern = $this->paymentMethodWithGatewayConfig(['secret_key' => 'rk_live_abc']);
        $legacyTwo = $this->paymentMethodWithGatewayConfig(['secret_key' => 'sk_test_def']);

        $this->paymentMethodRepository
            ->method('findBy')
            ->willReturn([$legacyOne, $modern, $legacyTwo])
        ;
        $this->gatewayFactoryNameProvider
            ->method('provide')
            ->willReturnMap([
                [$legacyOne, 'stripe_checkout'],
                [$modern, 'stripe_checkout'],
                [$legacyTwo, 'stripe_web_elements'],
            ])
        ;
        $this->legacyKeyDetector
            ->method('isLegacy')
            ->willReturnMap([
                ['sk_live_abc', true],
                ['rk_live_abc', false],
                ['sk_test_def', true],
            ])
        ;

        self::assertSame([$legacyOne, $legacyTwo], $this->createProvider()->provide());
    }

    private function createProvider(): LegacyStripePaymentMethodsProvider
    {
        return new LegacyStripePaymentMethodsProvider(
            $this->paymentMethodRepository,
            $this->gatewayFactoryNameProvider,
            $this->legacyKeyDetector,
            self::STRIPE_FACTORY_NAMES,
        );
    }

    /** @param array<string, mixed> $config */
    private function paymentMethodWithGatewayConfig(array $config): PaymentMethodInterface&MockObject
    {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn($config);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        return $paymentMethod;
    }
}
