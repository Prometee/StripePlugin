<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Twig\Extension\LegacyStripeKeyExtension;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.twig.extension.legacy_stripe_key', LegacyStripeKeyExtension::class)
        ->args([
            service(LegacyKeyDetectorInterface::class),
            service('sylius.repository.payment_method'),
            service(GatewayFactoryNameProviderInterface::class),
            param('flux_se.sylius_stripe.factories'),
        ])
        ->tag('twig.extension');
};
