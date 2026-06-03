<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout\ConfigurationAction;
use FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout\ConfirmAction;
use FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout\ShippingRatesAction;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ConfigurationAction::class)
        ->args([
            service('flux_se.sylius_stripe.shop.express_checkout.configuration_provider'),
        ])
        ->tag('controller.service_arguments');

    $services->set(ShippingRatesAction::class)
        ->args([
            service('flux_se.sylius_stripe.shop.express_checkout.shipping_options_calculator'),
            service('security.csrf.token_manager'),
        ])
        ->tag('controller.service_arguments');

    $services->set(ConfirmAction::class)
        ->args([
            service('flux_se.sylius_stripe.shop.express_checkout.order_completer'),
            service('security.csrf.token_manager'),
        ])
        ->tag('controller.service_arguments');
};
