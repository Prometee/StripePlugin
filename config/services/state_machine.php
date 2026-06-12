<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\StateMachine\PaymentStateProcessor;
use FluxSE\SyliusStripePlugin\StateMachine\StripeStateAppliedChecker;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.state_machine.stripe_state_applied_checker', StripeStateAppliedChecker::class);

    $services->set('flux_se.sylius_stripe.state_machine.payment_state', PaymentStateProcessor::class)
        ->abstract()
        ->args([
            service('sylius.provider.payment_request.gateway_factory_name'),
            service('sylius.checker.finalized_payment_request'),
            service('sylius.factory.payment_request'),
            service('sylius.repository.payment_request'),
            service('sylius.announcer.payment_request'),
            service('flux_se.sylius_stripe.state_machine.stripe_state_applied_checker'),
            param('flux_se.sylius_stripe.factories'),
        ]);

    $services->set('flux_se.sylius_stripe.state_machine.refund')
        ->parent('flux_se.sylius_stripe.state_machine.payment_state')
        ->public()
        ->args([
            [],
            PaymentInterface::STATE_REFUNDED,
            PaymentRequestInterface::ACTION_REFUND,
        ]);

    $services->set('flux_se.sylius_stripe.state_machine.cancel')
        ->parent('flux_se.sylius_stripe.state_machine.payment_state')
        ->public()
        ->args([
            [
                PaymentInterface::STATE_NEW,
                PaymentInterface::STATE_AUTHORIZED,
            ],
            PaymentInterface::STATE_CANCELLED,
            PaymentRequestInterface::ACTION_CANCEL,
        ]);

    $services->set('flux_se.sylius_stripe.state_machine.capture_authorized')
        ->parent('flux_se.sylius_stripe.state_machine.payment_state')
        ->public()
        ->args([
            [
                PaymentInterface::STATE_AUTHORIZED,
            ],
            PaymentInterface::STATE_COMPLETED,
            PaymentRequestInterface::ACTION_AUTHORIZE,
        ]);
};
