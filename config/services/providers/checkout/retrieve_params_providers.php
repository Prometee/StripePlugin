<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $sessionExpands = [
        'customer',
        'line_items',
        'payment_intent',
        'payment_intent.latest_charge',
        'payment_intent.payment_method',
        'invoice',
        'invoice.payments', // subscription mode (PaymentIntent enrichment via SubscriptionAwareRetrieveManager)
        'invoice.default_payment_method',
        'invoice.discounts',
        'setup_intent',
        'setup_intent.payment_method',
        'subscription',
        'subscription.default_payment_method',
        'subscription.latest_invoice',
        'subscription.default_source',
        'subscription.discounts',
    ];

    $container->parameters()
        ->set('flux_se.sylius_stripe.checkout.retrieve.expand_fields', $sessionExpands)
        ->set('flux_se.sylius_stripe.checkout.create.expand_fields', $sessionExpands);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.checkout.retrieve.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.retrieve.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.checkout.retrieve.expand', ExpandProvider::class)
        ->args([
            param('flux_se.sylius_stripe.checkout.retrieve.expand_fields'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.retrieve.inner_params', ['priority' => -100]);
};
