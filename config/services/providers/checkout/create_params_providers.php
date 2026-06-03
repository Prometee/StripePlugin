<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\AdaptivePricingProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\AfterUrlProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\CheckoutSessionModeDataProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\CustomerEmailProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\OrderItemLineItemProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\OrderItemPriceDataProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\CurrencyProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\OrderItemProductDataProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData\ProductImagesProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData\ProductNameProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData\ShipmentNameProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ShipmentProductDataProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\UnitAmountProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\QuantityProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\ShipmentLineItemProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\ShipmentPriceDataProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItemsProvider;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ModePaymentProvider;
use FluxSE\SyliusStripePlugin\Provider\CompositeMetadataParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\DefaultAfterUrlProvider;
use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use FluxSE\SyliusStripePlugin\Provider\FirstOrderFlagMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\OrderMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\PaymentIntentCaptureMethodManualProvider;
use FluxSE\SyliusStripePlugin\Provider\ProductCategoriesMetadataProvider;
use FluxSE\SyliusStripePlugin\Provider\TokenHashMetadataProvider;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.checkout.after_urls', [
            AfterUrlProviderInterface::CANCEL_URL => null,
            AfterUrlProviderInterface::SUCCESS_URL => null,
        ]);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.checkout.after_url.default', DefaultAfterUrlProvider::class)
        ->args([
            param('flux_se.sylius_stripe.checkout.after_urls'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.expand', ExpandProvider::class)
        ->args([
            param('flux_se.sylius_stripe.checkout.create.expand_fields'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.after_url', AfterUrlProvider::class)
        ->args([
            service('flux_se.sylius_stripe.provider.checkout.after_url.default'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.adaptive_pricing', AdaptivePricingProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -250]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.mode.payment', ModePaymentProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -300]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.customer_email', CustomerEmailProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -400]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data', CheckoutSessionModeDataProvider::class)
        ->args([
            Session::MODE_PAYMENT,
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -600]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.capture_method.manual', PaymentIntentCaptureMethodManualProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata', CompositeMetadataParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata.order', OrderMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata.product_categories', ProductCategoriesMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata', ['priority' => -150]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata.first_order_flag', FirstOrderFlagMetadataProvider::class)
        ->args([
            service('sylius.repository.order'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata.token_hash', TokenHashMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.payment_intent_data.metadata', ['priority' => -250]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_items', LineItemsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item'),
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -600]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item', OrderItemLineItemProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.inner'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.quantity', QuantityProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.inner', ['priority' => -100])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.inner', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data', OrderItemPriceDataProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.inner', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data', OrderItemProductDataProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.unit_amount', UnitAmountProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.currency', CurrencyProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data', ['priority' => -300]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data.images', ProductImagesProvider::class)
        ->args([
            service('liip_imagine.cache.manager'),
            param('flux_se.sylius_stripe.line_item_image.imagine_filter'),
            param('flux_se.sylius_stripe.line_item_image.fallback_image'),
            param('flux_se.sylius_stripe.line_item_image.localhost_pattern'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data.name', ProductNameProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.order_item.price_data.product_data', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment', ShipmentLineItemProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.inner'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data', ShipmentPriceDataProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.inner', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.product_data', ShipmentProductDataProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.product_data'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.uni_amount', UnitAmountProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.currency', CurrencyProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data', ['priority' => -300]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.product_data.name', ShipmentNameProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.line_item.shipment.price_data.product_data', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.metadata', CompositeMetadataParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.checkout.create.metadata'),
        ])
        ->tag('flux_se.sylius_stripe.provider.checkout.create.inner_params', ['priority' => -700]);

    $services->set('flux_se.sylius_stripe.provider.checkout.create.metadata.token_hash', TokenHashMetadataProvider::class)
        ->tag('flux_se.sylius_stripe.provider.checkout.create.metadata', ['priority' => -100]);
};
