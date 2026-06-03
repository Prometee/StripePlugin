<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizer;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolver;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.resolver.express_checkout_payment_method', ExpressCheckoutPaymentMethodResolver::class)
        ->args([
            service('sylius.repository.payment_method'),
            param('flux_se.sylius_stripe.factories'),
        ]);
    $services->alias(ExpressCheckoutPaymentMethodResolverInterface::class, 'flux_se.sylius_stripe.resolver.express_checkout_payment_method');

    $services->set('flux_se.sylius_stripe.normalizer.express_checkout_address', ExpressCheckoutAddressNormalizer::class)
        ->args([
            service('sylius.factory.address'),
        ]);
    $services->alias(ExpressCheckoutAddressNormalizerInterface::class, 'flux_se.sylius_stripe.normalizer.express_checkout_address');
};
