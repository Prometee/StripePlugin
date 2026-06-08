<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetector;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProvider;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.stripe.secret_key.legacy_key_detector', LegacyKeyDetector::class);

    $services->alias(LegacyKeyDetectorInterface::class, 'flux_se.sylius_stripe.stripe.secret_key.legacy_key_detector');

    $services->set('flux_se.sylius_stripe.stripe.secret_key.legacy_stripe_payment_methods', LegacyStripePaymentMethodsProvider::class)
        ->args([
            service('sylius.repository.payment_method'),
            service(GatewayFactoryNameProviderInterface::class),
            service(LegacyKeyDetectorInterface::class),
            param('flux_se.sylius_stripe.factories'),
        ]);

    $services->alias(LegacyStripePaymentMethodsProviderInterface::class, 'flux_se.sylius_stripe.stripe.secret_key.legacy_stripe_payment_methods');
};
