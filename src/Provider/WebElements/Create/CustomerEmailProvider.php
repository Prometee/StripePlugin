<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/** @implements InnerParamsProviderInterface<PaymentIntent> */
final readonly class CustomerEmailProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $email = $payment->getOrder()?->getCustomer()?->getEmail();

        if (null === $email) {
            return;
        }

        $params['receipt_email'] = $email;
    }
}
