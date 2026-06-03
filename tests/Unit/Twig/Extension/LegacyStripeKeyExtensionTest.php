<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Twig\Extension\LegacyStripeKeyExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;

final class LegacyStripeKeyExtensionTest extends TestCase
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface>|null $repository
     * @param list<string> $stripeFactoryNames
     */
    private function buildExtension(
        ?LegacyKeyDetectorInterface $detector = null,
        ?PaymentMethodRepositoryInterface $repository = null,
        ?GatewayFactoryNameProviderInterface $factoryNameProvider = null,
        array $stripeFactoryNames = [],
    ): LegacyStripeKeyExtension {
        return new LegacyStripeKeyExtension(
            $detector ?? $this->createStub(LegacyKeyDetectorInterface::class),
            $repository ?? $this->createStub(PaymentMethodRepositoryInterface::class),
            $factoryNameProvider ?? $this->createStub(GatewayFactoryNameProviderInterface::class),
            $stripeFactoryNames,
        );
    }

    public function test_it_exposes_two_functions(): void
    {
        $functions = $this->buildExtension()->getFunctions();

        self::assertCount(2, $functions);
        self::assertSame('sylius_stripe_is_legacy_secret_key', $functions[0]->getName());
        self::assertSame('sylius_stripe_legacy_payment_methods', $functions[1]->getName());
    }

    public function test_the_is_legacy_function_delegates_to_the_detector(): void
    {
        $detector = $this->createMock(LegacyKeyDetectorInterface::class);
        $detector->expects(self::once())
            ->method('isLegacy')
            ->with('sk_test_abc')
            ->willReturn(true)
        ;

        $function = $this->buildExtension(detector: $detector)->getFunctions()[0];
        $callable = $function->getCallable();

        self::assertIsCallable($callable);
        self::assertTrue($callable('sk_test_abc'));
    }

    public function test_legacy_payment_methods_returns_only_stripe_methods_with_legacy_keys(): void
    {
        $detector = $this->createStub(LegacyKeyDetectorInterface::class);
        $detector->method('isLegacy')->willReturnCallback(
            static fn (?string $key): bool => $key !== null && str_starts_with($key, 'sk_'),
        );

        $gatewayConfig = $this->createStub(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn(['secret_key' => 'sk_test_abc']);

        $stripeMethod = $this->createStub(PaymentMethodInterface::class);
        $stripeMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $repository = $this->createStub(PaymentMethodRepositoryInterface::class);
        $repository->method('findBy')->willReturn([$stripeMethod]);

        $factoryNameProvider = $this->createStub(GatewayFactoryNameProviderInterface::class);
        $factoryNameProvider->method('provide')->willReturn('stripe_checkout');

        $extension = $this->buildExtension(
            detector: $detector,
            repository: $repository,
            factoryNameProvider: $factoryNameProvider,
            stripeFactoryNames: ['stripe_checkout', 'stripe_web_elements'],
        );

        $function = $extension->getFunctions()[1];
        $callable = $function->getCallable();

        self::assertIsCallable($callable);
        self::assertSame([$stripeMethod], $callable());
    }

    public function test_legacy_payment_methods_excludes_non_stripe_methods(): void
    {
        $detector = $this->createStub(LegacyKeyDetectorInterface::class);

        $gatewayConfig = $this->createStub(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn(['secret_key' => 'sk_test_abc']);

        $nonStripeMethod = $this->createStub(PaymentMethodInterface::class);
        $nonStripeMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $repository = $this->createStub(PaymentMethodRepositoryInterface::class);
        $repository->method('findBy')->willReturn([$nonStripeMethod]);

        $factoryNameProvider = $this->createStub(GatewayFactoryNameProviderInterface::class);
        $factoryNameProvider->method('provide')->willReturn('paypal');

        $extension = $this->buildExtension(
            detector: $detector,
            repository: $repository,
            factoryNameProvider: $factoryNameProvider,
            stripeFactoryNames: ['stripe_checkout', 'stripe_web_elements'],
        );

        $function = $extension->getFunctions()[1];
        $callable = $function->getCallable();

        self::assertIsCallable($callable);
        self::assertSame([], $callable());
    }
}
