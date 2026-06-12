<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\RefundPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\RefundPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\Refund\PaymentIntentToRefundProviderInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\ErrorObject;
use Stripe\Exception\InvalidRequestException;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;

#[AllowMockObjectsWithoutExpectations]
final class RefundPaymentRequestHandlerTest extends TestCase
{
    /** @var PaymentRequestProviderInterface&MockObject */
    private PaymentRequestProviderInterface $paymentRequestProvider;

    /** @var RetrieveManagerInterface&MockObject */
    private RetrieveManagerInterface $retrieveCheckoutManager;

    /** @var PaymentIntentToRefundProviderInterface&MockObject */
    private PaymentIntentToRefundProviderInterface $refundPaymentProvider;

    /** @var PaymentIntentToRefundProviderInterface&MockObject */
    private PaymentIntentToRefundProviderInterface $refundSubscriptionInitProvider;

    /** @var CreateManagerInterface&MockObject */
    private CreateManagerInterface $createRefundManager;

    /** @var PaymentTransitionProcessorInterface&MockObject */
    private PaymentTransitionProcessorInterface $paymentTransitionProcessor;

    /** @var StateMachineInterface&MockObject */
    private StateMachineInterface $stateMachine;

    private RefundPaymentRequestHandler $handler;

    protected function setUp(): void
    {
        $this->paymentRequestProvider = $this->createMock(PaymentRequestProviderInterface::class);
        $this->retrieveCheckoutManager = $this->createMock(RetrieveManagerInterface::class);
        $this->refundPaymentProvider = $this->createMock(PaymentIntentToRefundProviderInterface::class);
        $this->refundSubscriptionInitProvider = $this->createMock(PaymentIntentToRefundProviderInterface::class);
        $this->createRefundManager = $this->createMock(CreateManagerInterface::class);
        $this->paymentTransitionProcessor = $this->createMock(PaymentTransitionProcessorInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);

        $this->handler = new RefundPaymentRequestHandler(
            $this->paymentRequestProvider,
            $this->retrieveCheckoutManager,
            $this->refundPaymentProvider,
            $this->refundSubscriptionInitProvider,
            $this->createRefundManager,
            $this->paymentTransitionProcessor,
            $this->stateMachine,
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function benignRefundRejections(): iterable
    {
        yield 'already refunded' => [
            'Charge ch_123 has already been refunded.',
            ErrorObject::CODE_CHARGE_ALREADY_REFUNDED,
        ];
        yield 'charged back' => [
            'Charge ch_123 has been charged back; cannot issue a refund.',
            ErrorObject::CODE_CHARGE_DISPUTED,
        ];
    }

    #[DataProvider('benignRefundRejections')]
    public function test_it_fails_the_payment_request_when_stripe_rejects_an_already_settled_refund(
        string $message,
        string $code,
    ): void {
        $command = new RefundPaymentRequest('hash', 1000);

        $paymentRequest = $this->createPaymentRequestExpectingRetrievableSession($command);

        $exception = new InvalidRequestException($message);
        $exception->setStripeCode($code);
        $this->createRefundManager->method('create')->willThrowException($exception);

        $paymentRequest->expects(self::once())
            ->method('setResponseData')
            ->with(['reason' => $message]);

        $this->stateMachine->expects(self::once())
            ->method('apply')
            ->with($paymentRequest, PaymentRequestTransitions::GRAPH, PaymentRequestTransitions::TRANSITION_FAIL);

        $this->paymentTransitionProcessor->expects(self::never())->method('process');

        $this->handler->__invoke($command);
    }

    public function test_it_rethrows_unexpected_stripe_invalid_request_errors(): void
    {
        $command = new RefundPaymentRequest('hash', 1000);

        $paymentRequest = $this->createPaymentRequestExpectingRetrievableSession($command);

        $exception = new InvalidRequestException('Refund amount is greater than charge amount.');
        $exception->setStripeCode(ErrorObject::CODE_AMOUNT_TOO_LARGE);
        $this->createRefundManager->method('create')->willThrowException($exception);

        $paymentRequest->expects(self::never())->method('setResponseData');
        $this->stateMachine->expects(self::never())->method('apply');
        $this->paymentTransitionProcessor->expects(self::never())->method('process');

        $this->expectExceptionObject($exception);

        $this->handler->__invoke($command);
    }

    /**
     * @return PaymentRequestInterface&MockObject
     */
    private function createPaymentRequestExpectingRetrievableSession(
        RefundPaymentRequest $command,
    ): PaymentRequestInterface {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getDetails')->willReturn(['id' => 'cs_123']);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($paymentRequest);

        $session = Session::constructFrom([
            'id' => 'cs_123',
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'amount_total' => 1000,
        ]);
        $this->retrieveCheckoutManager->method('retrieve')->willReturn($session);

        $this->refundPaymentProvider->method('provide')->with($paymentRequest)->willReturn('pi_123');

        return $paymentRequest;
    }
}
