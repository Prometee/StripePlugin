<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\EventListener\Workflow\PaymentCompletedStateListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe_plugin.event_listener.workflow.payment_complete_state.cancel', PaymentCompletedStateListener::class)
        ->args([
            service('flux_se.sylius_stripe.state_machine.cancel'),
        ])
        ->tag('kernel.event_listener', ['event' => 'workflow.sylius_payment.completed.cancel']);

    $services->set('flux_se.sylius_stripe_plugin.event_listener.workflow.payment_complete_state.refund', PaymentCompletedStateListener::class)
        ->args([
            service('flux_se.sylius_stripe.state_machine.refund'),
        ])
        ->tag('kernel.event_listener', ['event' => 'workflow.sylius_payment.completed.refund']);

    $services->set('flux_se.sylius_stripe_plugin.event_listener.workflow.payment_complete_state.complete_authorized', PaymentCompletedStateListener::class)
        ->args([
            service('flux_se.sylius_stripe.state_machine.capture_authorized'),
        ])
        ->tag('kernel.event_listener', ['event' => 'workflow.sylius_payment.completed.complete']);
};
