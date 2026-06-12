<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\Refund\RefundPaymentProvider;
use FluxSE\SyliusStripePlugin\Provider\Refund\RefundSubscriptionInitProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.refund.checkout_session_payment', RefundPaymentProvider::class);

    $services->set('flux_se.sylius_stripe.provider.refund.checkout_session_subscription_init', RefundSubscriptionInitProvider::class);
};
