<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use FluxSE\SyliusStripePlugin\Twig\Extension\LegacyStripeKeyExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

final class LegacyStripeKeyExtensionTest extends TestCase
{
    /**
     * @param list<PaymentMethodInterface> $legacyPaymentMethods
     */
    private function buildExtension(
        ?LegacyKeyDetectorInterface $detector = null,
        array $legacyPaymentMethods = [],
    ): LegacyStripeKeyExtension {
        $provider = $this->createStub(LegacyStripePaymentMethodsProviderInterface::class);
        $provider->method('provide')->willReturn($legacyPaymentMethods);

        return new LegacyStripeKeyExtension(
            $detector ?? $this->createStub(LegacyKeyDetectorInterface::class),
            $provider,
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

    public function test_the_legacy_payment_methods_function_delegates_to_the_provider(): void
    {
        $paymentMethod = $this->createStub(PaymentMethodInterface::class);

        $function = $this->buildExtension(legacyPaymentMethods: [$paymentMethod])->getFunctions()[1];
        $callable = $function->getCallable();

        self::assertIsCallable($callable);
        self::assertSame([$paymentMethod], $callable());
    }
}
