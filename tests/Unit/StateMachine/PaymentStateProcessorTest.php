<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\StateMachine;

use FluxSE\SyliusStripePlugin\StateMachine\PaymentStateProcessor;
use FluxSE\SyliusStripePlugin\StateMachine\StripeStateAppliedCheckerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PaymentBundle\Announcer\PaymentRequestAnnouncerInterface;
use Sylius\Bundle\PaymentBundle\Checker\FinalizedPaymentRequestCheckerInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;

#[AllowMockObjectsWithoutExpectations]
final class PaymentStateProcessorTest extends TestCase
{
    private GatewayFactoryNameProviderInterface&MockObject $gatewayFactoryNameProvider;

    private FinalizedPaymentRequestCheckerInterface&MockObject $finalizedPaymentRequestChecker;

    /** @var PaymentRequestFactoryInterface<PaymentRequestInterface>&MockObject */
    private PaymentRequestFactoryInterface&MockObject $paymentRequestFactory;

    /** @var PaymentRequestRepositoryInterface<PaymentRequestInterface>&MockObject */
    private PaymentRequestRepositoryInterface&MockObject $paymentRequestRepository;

    private PaymentRequestAnnouncerInterface&MockObject $paymentRequestAnnouncer;

    private StripeStateAppliedCheckerInterface&MockObject $stripeStateAppliedChecker;

    protected function setUp(): void
    {
        $this->gatewayFactoryNameProvider = $this->createMock(GatewayFactoryNameProviderInterface::class);
        $this->finalizedPaymentRequestChecker = $this->createMock(FinalizedPaymentRequestCheckerInterface::class);
        $this->paymentRequestFactory = $this->createMock(PaymentRequestFactoryInterface::class);
        $this->paymentRequestRepository = $this->createMock(PaymentRequestRepositoryInterface::class);
        $this->paymentRequestAnnouncer = $this->createMock(PaymentRequestAnnouncerInterface::class);
        $this->stripeStateAppliedChecker = $this->createMock(StripeStateAppliedCheckerInterface::class);
    }

    public function test_it_skips_dispatch_when_stripe_state_already_converged(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn($paymentMethod);
        $payment->method('getState')->willReturn(PaymentInterface::STATE_COMPLETED);

        $this->gatewayFactoryNameProvider
            ->method('provide')
            ->with($paymentMethod)
            ->willReturn('stripe_web_elements');

        $this->stripeStateAppliedChecker
            ->expects(self::once())
            ->method('isAlreadyApplied')
            ->with($payment, PaymentRequestInterface::ACTION_AUTHORIZE)
            ->willReturn(true);

        $this->paymentRequestRepository->expects(self::never())->method('findOneByActionPaymentAndMethod');
        $this->paymentRequestFactory->expects(self::never())->method('create');
        $this->paymentRequestRepository->expects(self::never())->method('add');
        $this->paymentRequestAnnouncer->expects(self::never())->method('dispatchPaymentRequestCommand');

        $processor = $this->buildProcessor();
        $processor(payment: $payment, fromState: PaymentInterface::STATE_AUTHORIZED);
    }

    public function test_it_dispatches_when_stripe_state_has_not_converged(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn($paymentMethod);
        $payment->method('getState')->willReturn(PaymentInterface::STATE_COMPLETED);

        $this->gatewayFactoryNameProvider
            ->method('provide')
            ->with($paymentMethod)
            ->willReturn('stripe_web_elements');

        $this->stripeStateAppliedChecker
            ->expects(self::once())
            ->method('isAlreadyApplied')
            ->willReturn(false);

        $newPaymentRequest = $this->createMock(PaymentRequestInterface::class);
        $newPaymentRequest->expects(self::once())->method('setAction')->with(PaymentRequestInterface::ACTION_AUTHORIZE);

        $this->paymentRequestRepository
            ->expects(self::once())
            ->method('findOneByActionPaymentAndMethod')
            ->willReturn(null);
        $this->paymentRequestFactory
            ->expects(self::once())
            ->method('create')
            ->with($payment, $paymentMethod)
            ->willReturn($newPaymentRequest);
        $this->paymentRequestRepository->expects(self::once())->method('add')->with($newPaymentRequest);
        $this->paymentRequestAnnouncer
            ->expects(self::once())
            ->method('dispatchPaymentRequestCommand')
            ->with($newPaymentRequest);

        $processor = $this->buildProcessor();
        $processor(payment: $payment, fromState: PaymentInterface::STATE_AUTHORIZED);
    }

    public function test_it_returns_early_when_from_state_is_not_allowed(): void
    {
        $payment = $this->createMock(PaymentInterface::class);

        $this->stripeStateAppliedChecker->expects(self::never())->method('isAlreadyApplied');
        $this->paymentRequestAnnouncer->expects(self::never())->method('dispatchPaymentRequestCommand');

        $processor = $this->buildProcessor();
        $processor(payment: $payment, fromState: PaymentInterface::STATE_NEW);
    }

    private function buildProcessor(): PaymentStateProcessor
    {
        return new PaymentStateProcessor(
            $this->gatewayFactoryNameProvider,
            $this->finalizedPaymentRequestChecker,
            $this->paymentRequestFactory,
            $this->paymentRequestRepository,
            $this->paymentRequestAnnouncer,
            $this->stripeStateAppliedChecker,
            ['stripe_checkout', 'stripe_web_elements'],
            [PaymentInterface::STATE_AUTHORIZED],
            PaymentInterface::STATE_COMPLETED,
            PaymentRequestInterface::ACTION_AUTHORIZE,
        );
    }
}
