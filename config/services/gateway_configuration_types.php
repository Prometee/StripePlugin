<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Form\Type\StripeCheckoutGatewayConfigurationType;
use FluxSE\SyliusStripePlugin\Form\Type\StripeGatewayConfigurationType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.form.type.gateway_configuration.stripe_checkout', StripeCheckoutGatewayConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', [
            'type' => 'stripe_checkout',
            'label' => 'flux_se_sylius_stripe_plugin.gateway_factory.stripe_checkout',
        ])
        ->tag('form.type');

    $services->set('flux_se.sylius_stripe.form.type.gateway_configuration.stripe_web_elements', StripeGatewayConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', [
            'type' => 'stripe_web_elements',
            'label' => 'flux_se_sylius_stripe_plugin.gateway_factory.stripe_web_elements',
        ])
        ->tag('form.type');
};
