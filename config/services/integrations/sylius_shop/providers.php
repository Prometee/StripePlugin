<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\SyliusShopAfterUrlProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.shop.provider.after_url', SyliusShopAfterUrlProvider::class)
        ->args([
            service('sylius_shop.provider.order_pay.payment_request_pay_url'),
        ]);

    $services->set('flux_se.sylius_stripe.order_pay.provider.web_elements.after_url')
        ->parent('flux_se.sylius_stripe.shop.provider.after_url');

    $services->set('flux_se.sylius_stripe.shop.provider.checkout.after_url')
        ->parent('flux_se.sylius_stripe.shop.provider.after_url')
        ->decorate('flux_se.sylius_stripe.provider.checkout.after_url.default');
};
