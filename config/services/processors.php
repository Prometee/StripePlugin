<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Processor\ChargeRefundedWebhookEventProcessor;
use FluxSE\SyliusStripePlugin\Processor\Checkout\CheckoutOrPaymentIntentTransitionProcessor;
use FluxSE\SyliusStripePlugin\Processor\Checkout\SessionTransitionProcessor;
use FluxSE\SyliusStripePlugin\Processor\CompositeWebhookEventProcessor;
use FluxSE\SyliusStripePlugin\Processor\NotifyPayloadProcessor;
use FluxSE\SyliusStripePlugin\Processor\WebElements\PaymentIntentTransitionProcessor;
use FluxSE\SyliusStripePlugin\Processor\WebhookEventProcessor;
use Stripe\Event;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.processor.webhook_event.checkout_session', [
            'stripe_checkout' => [
                Event::CHECKOUT_SESSION_COMPLETED,
                Event::CHECKOUT_SESSION_EXPIRED,
                Event::CHECKOUT_SESSION_ASYNC_PAYMENT_FAILED,
                Event::CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED,
            ],
        ])
        ->set('flux_se.sylius_stripe.processor.webhook_event.payment_intent', [
            'stripe_web_elements' => [
                Event::PAYMENT_INTENT_SUCCEEDED,
                Event::PAYMENT_INTENT_CANCELED,
                Event::PAYMENT_INTENT_PROCESSING,
            ],
            'stripe_checkout' => [
                Event::PAYMENT_INTENT_SUCCEEDED,
                Event::PAYMENT_INTENT_CANCELED,
                Event::PAYMENT_INTENT_PROCESSING,
            ],
        ])
        ->set('flux_se.sylius_stripe.processor.webhook_event.charge_refunded', [
            'stripe_web_elements' => [
                Event::CHARGE_REFUNDED,
            ],
            'stripe_checkout' => [
                Event::CHARGE_REFUNDED,
            ],
        ]);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.processor.payment_transition.checkout', SessionTransitionProcessor::class)
        ->args([
            service('flux_se.sylius_stripe.processor.transition.checkout.session'),
            service('sylius_abstraction.state_machine'),
        ]);

    $services->set('flux_se.sylius_stripe.processor.payment_transition.web_elements', PaymentIntentTransitionProcessor::class)
        ->args([
            service('flux_se.sylius_stripe.processor.transition.web_elements.payment_intent'),
            service('sylius_abstraction.state_machine'),
        ]);

    $services->set(NotifyPayloadProcessor::class)
        ->decorate('sylius.processor.payment_request.notify_payload')
        ->args([
            service('.inner'),
            param('flux_se.sylius_stripe.factories'),
        ]);

    $services->set('flux_se.sylius_stripe.processor.webhook_event.checkout.composite', CompositeWebhookEventProcessor::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.processor.webhook_event.checkout'),
        ]);

    $services->set('flux_se.sylius_stripe.processor.webhook_event.web_elements.composite', CompositeWebhookEventProcessor::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.processor.webhook_event.web_elements'),
        ]);

    $services->set('flux_se.sylius_stripe.processor.webhook_event.checkout.checkout_session', WebhookEventProcessor::class)
        ->args([
            param('flux_se.sylius_stripe.processor.webhook_event.checkout_session'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
        ])
        ->tag('flux_se.sylius_stripe.processor.webhook_event.checkout');

    $services->set('flux_se.sylius_stripe.processor.webhook_event.web_elements.payment_intent', WebhookEventProcessor::class)
        ->args([
            param('flux_se.sylius_stripe.processor.webhook_event.payment_intent'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
        ])
        ->tag('flux_se.sylius_stripe.processor.webhook_event.web_elements')
        // Express Checkout on cart always creates a PaymentIntent. When the merchant enables
        // ECE on a stripe_checkout PaymentMethod, payment_intent.* webhooks land on that
        // method's URL too — reuse the same processor here.
        ->tag('flux_se.sylius_stripe.processor.webhook_event.checkout');

    $services->set('flux_se.sylius_stripe.processor.webhook_event.charge_refunded', ChargeRefundedWebhookEventProcessor::class)
        ->args([
            param('flux_se.sylius_stripe.processor.webhook_event.charge_refunded'),
            // A "charge.refunded" event only references a PaymentIntent id, so we retrieve the
            // PaymentIntent (with "latest_charge" expanded) regardless of the gateway factory.
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
            service('logger'),
        ])
        ->tag('flux_se.sylius_stripe.processor.webhook_event.web_elements')
        ->tag('flux_se.sylius_stripe.processor.webhook_event.checkout');

    $services->set('flux_se.sylius_stripe.processor.payment_transition.checkout.composite', CheckoutOrPaymentIntentTransitionProcessor::class)
        ->args([
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
        ]);
};
