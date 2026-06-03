<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Notification\LegacyStripeKeyNotificationProvider;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.notification.legacy_stripe_key', LegacyStripeKeyNotificationProvider::class)
        ->args([
            service('sylius.repository.payment_method'),
            service(GatewayFactoryNameProviderInterface::class),
            service(LegacyKeyDetectorInterface::class),
            param('flux_se.sylius_stripe.factories'),
        ])
        ->tag('sylius_admin.notification');
};
