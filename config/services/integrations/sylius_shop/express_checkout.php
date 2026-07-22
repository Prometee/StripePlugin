<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\ExpressCheckout\ConfigurationProvider;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ConfigurationProviderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutAvailabilityChecker;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutAvailabilityCheckerInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\OrderCompleter;
use FluxSE\SyliusStripePlugin\ExpressCheckout\OrderCompleterInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReader;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payment\CapturePaymentRequestDispatcher;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payment\CapturePaymentRequestDispatcherInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssembler;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssemblerInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ShippingOptionsCalculator;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ShippingOptionsCalculatorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.shop.express_checkout.payload_reader', ExpressCheckoutPayloadReader::class);
    $services->alias(ExpressCheckoutPayloadReaderInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.payload_reader');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.shipping_rate_assembler', ShippingRateAssembler::class)
        ->args([
            service('sylius.calculator.shipping'),
        ]);
    $services->alias(ShippingRateAssemblerInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.shipping_rate_assembler');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.capture_payment_request_dispatcher', CapturePaymentRequestDispatcher::class)
        ->args([
            service('sylius.factory.payment_request'),
            service('sylius.payment_request.command_bus'),
            service('sylius.repository.payment_request'),
        ]);
    $services->alias(CapturePaymentRequestDispatcherInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.capture_payment_request_dispatcher');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.configuration_provider', ConfigurationProvider::class)
        ->args([
            service('sylius.context.cart'),
            service('sylius.context.channel'),
            service('flux_se.sylius_stripe.resolver.express_checkout_payment_method'),
        ]);
    $services->alias(ConfigurationProviderInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.configuration_provider');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.availability_checker', ExpressCheckoutAvailabilityChecker::class)
        ->args([
            service('sylius.context.channel'),
            service('flux_se.sylius_stripe.resolver.express_checkout_payment_method'),
        ]);
    $services->alias(ExpressCheckoutAvailabilityCheckerInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.availability_checker');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.shipping_options_calculator', ShippingOptionsCalculator::class)
        ->args([
            service('sylius.context.cart'),
            service('sylius.order_processing.order_processor'),
            service('sylius.resolver.shipping_methods'),
            service('sylius.repository.shipping_method'),
            service('flux_se.sylius_stripe.normalizer.express_checkout_address'),
            service('flux_se.sylius_stripe.shop.express_checkout.payload_reader'),
            service('flux_se.sylius_stripe.shop.express_checkout.shipping_rate_assembler'),
        ]);
    $services->alias(ShippingOptionsCalculatorInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.shipping_options_calculator');

    $services->set('flux_se.sylius_stripe.shop.express_checkout.order_completer', OrderCompleter::class)
        ->args([
            service('sylius.context.cart'),
            service('sylius.context.channel'),
            service('flux_se.sylius_stripe.resolver.express_checkout_payment_method'),
            service('flux_se.sylius_stripe.normalizer.express_checkout_address'),
            service('flux_se.sylius_stripe.shop.express_checkout.payload_reader'),
            service('sylius.resolver.customer'),
            service('sylius_abstraction.state_machine'),
            service('sylius.factory.payment'),
            service('sylius.repository.shipping_method'),
            service('flux_se.sylius_stripe.shop.express_checkout.capture_payment_request_dispatcher'),
            service('flux_se.sylius_stripe.shop.provider.after_url'),
            service('request_stack'),
        ]);
    $services->alias(OrderCompleterInterface::class, 'flux_se.sylius_stripe.shop.express_checkout.order_completer');
};
