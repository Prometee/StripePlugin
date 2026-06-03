<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Appearance\AppearanceBuilder;
use FluxSE\SyliusStripePlugin\Provider\RefundEventTokenHashResolver;
use FluxSE\SyliusStripePlugin\Provider\RefundEventTokenHashResolverInterface;
use FluxSE\SyliusStripePlugin\Provider\StripeNotifyPaymentProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\CompositeSessionModeTransitionProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\PaymentModeTransitionProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionModeTransitionProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionTransitionProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionTransitionProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SubscriptionModeTransitionProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\WebElements\PaymentIntentTransitionProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $container->import('providers/**/*.php');

    $container->parameters()
        ->set('flux_se.sylius_stripe.factories', [
            'stripe_checkout',
            'stripe_web_elements',
        ]);

    $services = $container->services();

    $services->set(AppearanceBuilder::class);

    $services->set(StripeNotifyPaymentProvider::class)
        ->args([
            param('flux_se.sylius_stripe.factories'),
            service('sylius.repository.payment_request'),
            service('flux_se.sylius_stripe.stripe.resolver.event_resolver'),
            service('flux_se.sylius_stripe.provider.refund_event_token_hash_resolver'),
        ])
        ->tag('sylius.payment_request.payment_notify_provider');

    $services->set('flux_se.sylius_stripe.provider.refund_event_token_hash_resolver', RefundEventTokenHashResolver::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
        ]);
    $services->alias(RefundEventTokenHashResolverInterface::class, 'flux_se.sylius_stripe.provider.refund_event_token_hash_resolver');

    $services->set('flux_se.sylius_stripe.processor.transition.checkout.session', SessionTransitionProvider::class)
        ->args([
            service('flux_se.sylius_stripe.processor.transition.checkout.mode'),
        ]);
    $services->alias(SessionTransitionProviderInterface::class, 'flux_se.sylius_stripe.processor.transition.checkout.session');

    $services->set('flux_se.sylius_stripe.processor.transition.checkout.mode', CompositeSessionModeTransitionProvider::class)
        ->args([
            tagged_locator('flux_se.sylius_stripe.processor.transition.checkout.mode', null, 'getSupportedMode'),
        ]);
    $services->alias(SessionModeTransitionProviderInterface::class, 'flux_se.sylius_stripe.processor.transition.checkout.mode');

    $services->set('flux_se.sylius_stripe.processor.transition.checkout.mode.payment', PaymentModeTransitionProvider::class)
        ->args([
            service('flux_se.sylius_stripe.processor.transition.web_elements.payment_intent'),
        ])
        ->tag('flux_se.sylius_stripe.processor.transition.checkout.mode', ['mode' => 'payment']);

    $services->set('flux_se.sylius_stripe.processor.transition.checkout.mode.subscription', SubscriptionModeTransitionProvider::class)
        ->args([
            service('flux_se.sylius_stripe.processor.transition.web_elements.payment_intent'),
        ])
        ->tag('flux_se.sylius_stripe.processor.transition.checkout.mode', ['mode' => 'subscription']);

    $services->set('flux_se.sylius_stripe.processor.transition.web_elements.payment_intent', PaymentIntentTransitionProvider::class);
    $services->alias(PaymentIntentTransitionProvider::class, 'flux_se.sylius_stripe.processor.transition.web_elements.payment_intent');
};
