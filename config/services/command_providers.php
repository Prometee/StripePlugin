<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CancelPaymentRequestCommandProvider as CheckoutCancelPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CapturePaymentRequestCommandProvider as CheckoutCapturePaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CheckoutOrPaymentIntentCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\NotifyPaymentRequestCommandProvider as CheckoutNotifyPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\RefundPaymentRequestCommandProvider as CheckoutRefundPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\StatusPaymentRequestCommandProvider as CheckoutStatusPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\WebElements\CancelPaymentRequestCommandProvider as WebElementsCancelPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\WebElements\CapturePaymentRequestCommandProvider as WebElementsCapturePaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\WebElements\NotifyPaymentRequestCommandProvider as WebElementsNotifyPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\WebElements\RefundPaymentRequestCommandProvider as WebElementsRefundPaymentRequestCommandProvider;
use FluxSE\SyliusStripePlugin\CommandProvider\WebElements\StatusPaymentRequestCommandProvider as WebElementsStatusPaymentRequestCommandProvider;
use Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Per-action ActionsCommandProvider for the Checkout namespace; not tagged for the
    // Sylius announcer — wrapped by the composite below which forwards Express Checkout
    // payments (PaymentIntent in payment.details) to the Web Elements provider.
    $services->set('flux_se.sylius_stripe.command_provider.checkout.actions', ActionsCommandProvider::class)
        ->args([
            tagged_locator('flux_se.sylius_stripe.command_provider.checkout', 'action'),
        ]);

    $services->set('flux_se.sylius_stripe.command_provider.checkout', CheckoutOrPaymentIntentCommandProvider::class)
        ->args([
            service('flux_se.sylius_stripe.command_provider.checkout.actions'),
            service('flux_se.sylius_stripe.command_provider.web_elements'),
        ])
        ->tag('sylius.payment_request.command_provider', ['gateway_factory' => 'stripe_checkout']);

    $services->set('flux_se.sylius_stripe.command_provider.checkout.capture', CheckoutCapturePaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_CAPTURE])
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_AUTHORIZE]);

    $services->set('flux_se.sylius_stripe.command_provider.checkout.status', CheckoutStatusPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_STATUS]);

    $services->set('flux_se.sylius_stripe.command_provider.checkout.notify', CheckoutNotifyPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_NOTIFY]);

    $services->set('flux_se.sylius_stripe.command_provider.checkout.cancel', CheckoutCancelPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_CANCEL]);

    $services->set('flux_se.sylius_stripe.command_provider.checkout.refund', CheckoutRefundPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.checkout', ['action' => PaymentRequestInterface::ACTION_REFUND]);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements', ActionsCommandProvider::class)
        ->args([
            tagged_locator('flux_se.sylius_stripe.command_provider.web_elements', 'action'),
        ])
        ->tag('sylius.payment_request.command_provider', ['gateway_factory' => 'stripe_web_elements']);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements.capture', WebElementsCapturePaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_CAPTURE])
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_AUTHORIZE]);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements.status', WebElementsStatusPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_STATUS]);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements.notify', WebElementsNotifyPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_NOTIFY]);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements.cancel', WebElementsCancelPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_CANCEL]);

    $services->set('flux_se.sylius_stripe.command_provider.web_elements.refund', WebElementsRefundPaymentRequestCommandProvider::class)
        ->tag('flux_se.sylius_stripe.command_provider.web_elements', ['action' => PaymentRequestInterface::ACTION_REFUND]);
};
