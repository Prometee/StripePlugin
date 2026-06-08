<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\CompositeMetadataParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\CustomerEmailMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\FirstOrderFlagMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\OrderMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\PaymentIntentCaptureMethodManualProvider;
use FluxSE\SyliusStripePlugin\Provider\ProductCategoriesMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\TokenHashMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\WebElements\Create\AmountProvider;
use FluxSE\SyliusStripePlugin\Provider\WebElements\Create\CurrencyProvider;
use FluxSE\SyliusStripePlugin\Provider\WebElements\Create\CustomerEmailProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.web_elements.create.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.amount', AmountProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.inner_params', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.currency', CurrencyProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.inner_params', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.receipt_email', CustomerEmailProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.inner_params', ['priority' => -250]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.capture_method.manual', PaymentIntentCaptureMethodManualProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.inner_params', ['priority' => -400]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata', CompositeMetadataParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.web_elements.create.metadata'),
        ])
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.inner_params', ['priority' => -500]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata.token_hash', TokenHashMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.metadata', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata.order', OrderMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.metadata', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata.product_categories', ProductCategoriesMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.metadata', ['priority' => -250]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata.first_order_flag', FirstOrderFlagMetadataProvider::class)
        ->args([
            service('sylius.repository.order'),
        ])
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.metadata', ['priority' => -300]);

    $services->set('flux_se.sylius_stripe.provider.web_elements.create.metadata.customer_email', CustomerEmailMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.web_elements.create.metadata', ['priority' => -350]);
};
