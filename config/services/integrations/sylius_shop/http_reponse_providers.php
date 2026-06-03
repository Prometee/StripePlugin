<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Appearance\AppearanceBuilder;
use FluxSE\SyliusStripePlugin\OrderPay\Provider\Checkout\CaptureHttpResponseProvider as CheckoutCaptureHttpResponseProvider;
use FluxSE\SyliusStripePlugin\OrderPay\Provider\WebElements\CaptureHttpResponseProvider as WebElementsCaptureHttpResponseProvider;
use Sylius\Bundle\PaymentBundle\Provider\ActionsHttpResponseProvider;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.http_response.checkout', ActionsHttpResponseProvider::class)
        ->args([
            tagged_locator('flux_se.sylius_stripe.provider.http_response.checkout', 'action'),
        ])
        ->tag('sylius.payment_request.provider.http_response', ['gateway_factory' => 'stripe_checkout']);

    $services->set('flux_se.sylius_stripe.provider.http_response.checkout.capture', CheckoutCaptureHttpResponseProvider::class)
        ->tag('flux_se.sylius_stripe.provider.http_response.checkout', ['action' => PaymentRequestInterface::ACTION_CAPTURE])
        ->tag('flux_se.sylius_stripe.provider.http_response.checkout', ['action' => PaymentRequestInterface::ACTION_AUTHORIZE]);

    $services->set('flux_se.sylius_stripe.provider.http_response.web_elements', ActionsHttpResponseProvider::class)
        ->args([
            tagged_locator('flux_se.sylius_stripe.provider.http_response.web_elements', 'action'),
        ])
        ->tag('sylius.payment_request.provider.http_response', ['gateway_factory' => 'stripe_web_elements']);

    $services->set('flux_se.sylius_stripe.provider.http_response.web_elements.capture', WebElementsCaptureHttpResponseProvider::class)
        ->args([
            service('flux_se.sylius_stripe.order_pay.provider.web_elements.after_url'),
            service('twig'),
            service(AppearanceBuilder::class),
        ])
        ->tag('flux_se.sylius_stripe.provider.http_response.web_elements', ['action' => PaymentRequestInterface::ACTION_CAPTURE])
        ->tag('flux_se.sylius_stripe.provider.http_response.web_elements', ['action' => PaymentRequestInterface::ACTION_AUTHORIZE]);
};
