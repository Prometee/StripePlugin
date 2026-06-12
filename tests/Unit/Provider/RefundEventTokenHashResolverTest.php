<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use FluxSE\SyliusStripePlugin\Provider\RefundEventTokenHashResolver;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

#[AllowMockObjectsWithoutExpectations]
final class RefundEventTokenHashResolverTest extends TestCase
{
    private ClientFactoryInterface&MockObject $clientFactory;

    private StripeClient&MockObject $stripeClient;

    private PaymentIntentService&MockObject $paymentIntentService;

    private PaymentMethodInterface&MockObject $paymentMethod;

    private RefundEventTokenHashResolver $resolver;

    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(ClientFactoryInterface::class);
        $this->stripeClient = $this->createMock(StripeClient::class);
        $this->paymentIntentService = $this->createMock(PaymentIntentService::class);
        $this->paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->stripeClient->method('__get')->with('paymentIntents')->willReturn($this->paymentIntentService);

        $this->resolver = new RefundEventTokenHashResolver($this->clientFactory);
    }

    public function test_it_resolves_token_hash_from_the_related_payment_intent(): void
    {
        $charge = Charge::constructFrom([
            'id' => 'ch_test_1',
            'object' => Charge::OBJECT_NAME,
            'payment_intent' => 'pi_test_1',
        ]);

        $this->clientFactory
            ->method('createFromPaymentMethod')
            ->with($this->paymentMethod)
            ->willReturn($this->stripeClient);

        $this->paymentIntentService
            ->expects(self::once())
            ->method('retrieve')
            ->with('pi_test_1')
            ->willReturn(PaymentIntent::constructFrom([
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'metadata' => ['token_hash' => 'the-hash'],
            ]));

        self::assertSame('the-hash', $this->resolver->resolve($charge, $this->paymentMethod));
    }

    public function test_it_returns_null_when_object_has_no_payment_intent(): void
    {
        $charge = Charge::constructFrom([
            'id' => 'ch_test_1',
            'object' => Charge::OBJECT_NAME,
        ]);

        $this->clientFactory->expects(self::never())->method('createFromPaymentMethod');

        self::assertNull($this->resolver->resolve($charge, $this->paymentMethod));
    }

    public function test_it_returns_null_when_the_payment_intent_has_no_token_hash(): void
    {
        $charge = Charge::constructFrom([
            'id' => 'ch_test_1',
            'object' => Charge::OBJECT_NAME,
            'payment_intent' => 'pi_test_1',
        ]);

        $this->clientFactory
            ->method('createFromPaymentMethod')
            ->willReturn($this->stripeClient);

        $this->paymentIntentService
            ->method('retrieve')
            ->with('pi_test_1')
            ->willReturn(PaymentIntent::constructFrom([
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'metadata' => [],
            ]));

        self::assertNull($this->resolver->resolve($charge, $this->paymentMethod));
    }
}
