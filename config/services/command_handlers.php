<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Command\Checkout\NotifyPaymentRequest as CheckoutNotifyPaymentRequest;
use FluxSE\SyliusStripePlugin\Command\Checkout\StatusPaymentRequest as CheckoutStatusPaymentRequest;
use FluxSE\SyliusStripePlugin\Command\WebElements\NotifyPaymentRequest as WebElementsNotifyPaymentRequest;
use FluxSE\SyliusStripePlugin\Command\WebElements\StatusPaymentRequest as WebElementsStatusPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\CancelAuthorizedPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\CaptureEndPaymentRequestHandler as CheckoutCaptureEndPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\CapturePaymentRequestHandler as CheckoutCapturePaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\CompleteAuthorizedPaymentRequestHandler as CheckoutCompleteAuthorizedPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\ExpirePaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\Checkout\RefundPaymentRequestHandler as CheckoutRefundPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\NotifyPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\StatusPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\WebElements\CancelPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\WebElements\CaptureEndPaymentRequestHandler as WebElementsCaptureEndPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\WebElements\CapturePaymentRequestHandler as WebElementsCapturePaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\WebElements\CompleteAuthorizedPaymentRequestHandler as WebElementsCompleteAuthorizedPaymentRequestHandler;
use FluxSE\SyliusStripePlugin\CommandHandler\WebElements\RefundPaymentRequestHandler as WebElementsRefundPaymentRequestHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.capture', CheckoutCapturePaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.create'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.capture_end', CheckoutCaptureEndPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
            service('flux_se.sylius_stripe.manager.checkout.expire'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.authorize', CheckoutCompleteAuthorizedPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
            service('flux_se.sylius_stripe.manager.checkout.capture_authorized'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.status', StatusPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', [
            'bus' => 'sylius.payment_request.command_bus',
            'handles' => CheckoutStatusPaymentRequest::class,
        ]);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.notify', NotifyPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.event.retrieve'),
            service('flux_se.sylius_stripe.processor.webhook_event.checkout.composite'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout.composite'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', [
            'bus' => 'sylius.payment_request.command_bus',
            'handles' => CheckoutNotifyPaymentRequest::class,
        ]);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.expire', ExpirePaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
            service('flux_se.sylius_stripe.manager.checkout.expire'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.cancel_authorized', CancelAuthorizedPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
            service('flux_se.sylius_stripe.manager.checkout.cancel_authorized'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.checkout.refund', CheckoutRefundPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.checkout.retrieve'),
            service('flux_se.sylius_stripe.provider.refund.checkout_session_payment'),
            service('flux_se.sylius_stripe.provider.refund.checkout_session_subscription_init'),
            service('flux_se.sylius_stripe.manager.refund.create'),
            service('flux_se.sylius_stripe.processor.payment_transition.checkout'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.capture', WebElementsCapturePaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.web_elements.create'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.capture_end', WebElementsCaptureEndPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
            service('flux_se.sylius_stripe.manager.web_elements.cancel'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.authorize', WebElementsCompleteAuthorizedPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
            service('flux_se.sylius_stripe.manager.web_elements.capture'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.status', StatusPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', [
            'bus' => 'sylius.payment_request.command_bus',
            'handles' => WebElementsStatusPaymentRequest::class,
        ]);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.notify', NotifyPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.event.retrieve'),
            service('flux_se.sylius_stripe.processor.webhook_event.web_elements.composite'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', [
            'bus' => 'sylius.payment_request.command_bus',
            'handles' => WebElementsNotifyPaymentRequest::class,
        ]);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.cancel', CancelPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
            service('flux_se.sylius_stripe.manager.web_elements.cancel'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('flux_se.sylius_stripe.command_handlers.web_elements.refund', WebElementsRefundPaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
            service('flux_se.sylius_stripe.manager.refund.create'),
            service('flux_se.sylius_stripe.processor.payment_transition.web_elements'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);
};
