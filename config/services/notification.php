<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Notification\LegacyStripeKeyNotificationProvider;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.notification.legacy_stripe_key', LegacyStripeKeyNotificationProvider::class)
        ->args([
            service(LegacyStripePaymentMethodsProviderInterface::class),
        ])
        ->tag('sylius_admin.notification');
};
