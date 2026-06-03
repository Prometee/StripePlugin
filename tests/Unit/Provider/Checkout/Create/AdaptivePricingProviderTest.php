<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\AdaptivePricingProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class AdaptivePricingProviderTest extends TestCase
{
    public function test_it_enables_adaptive_pricing_when_gateway_flag_is_true(): void
    {
        $provider = new AdaptivePricingProvider();

        $params = [];
        $provider->provide($this->wrap(['enable_adaptive_pricing' => true]), $params);

        self::assertSame(['adaptive_pricing' => ['enabled' => true]], $params);
    }

    public function test_it_omits_key_when_gateway_flag_is_false(): void
    {
        $provider = new AdaptivePricingProvider();

        $params = ['untouched' => true];
        $provider->provide($this->wrap(['enable_adaptive_pricing' => false]), $params);

        self::assertSame(['untouched' => true], $params);
    }

    public function test_it_omits_key_when_gateway_flag_is_missing(): void
    {
        $provider = new AdaptivePricingProvider();

        $params = [];
        $provider->provide($this->wrap([]), $params);

        self::assertSame([], $params);
    }

    public function test_it_omits_key_when_gateway_config_is_null(): void
    {
        $provider = new AdaptivePricingProvider();

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);

        $params = [];
        $provider->provide($paymentRequest, $params);

        self::assertSame([], $params);
    }

    /** @param array<string, mixed> $config */
    private function wrap(array $config): PaymentRequestInterface
    {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn($config);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);

        return $paymentRequest;
    }
}
