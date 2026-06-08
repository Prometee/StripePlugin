<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Manager\Checkout\CreateManager as CheckoutCreateManager;
use FluxSE\SyliusStripePlugin\Manager\Checkout\CreateManagerInterface as CheckoutCreateManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Checkout\ExpireManager;
use FluxSE\SyliusStripePlugin\Manager\Checkout\ExpireManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManager as CheckoutRetrieveManager;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface as CheckoutRetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Checkout\SubscriptionAwareRetrieveManager;
use FluxSE\SyliusStripePlugin\Manager\Event\RetrieveManager as EventRetrieveManager;
use FluxSE\SyliusStripePlugin\Manager\Event\RetrieveManagerInterface as EventRetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Invoice\AllManager;
use FluxSE\SyliusStripePlugin\Manager\Invoice\AllManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManager as RefundCreateManager;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManagerInterface as RefundCreateManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CancelManager;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CancelManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CaptureManager;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CaptureManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CreateManager as WebElementsCreateManager;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CreateManagerInterface as WebElementsCreateManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManager as WebElementsRetrieveManager;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface as WebElementsRetrieveManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.manager.checkout.create', CheckoutCreateManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.checkout.create.params'),
        ]);
    $services->alias(CheckoutCreateManagerInterface::class, 'flux_se.sylius_stripe.manager.checkout.create');

    $services->set('flux_se.sylius_stripe.manager.checkout.retrieve', CheckoutRetrieveManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.checkout.retrieve.params'),
        ]);
    $services->alias(CheckoutRetrieveManagerInterface::class, 'flux_se.sylius_stripe.manager.checkout.retrieve');

    $services->set('flux_se.sylius_stripe.manager.checkout.retrieve.subscription_aware', SubscriptionAwareRetrieveManager::class)
        ->decorate('flux_se.sylius_stripe.manager.checkout.retrieve')
        ->args([
            service('.inner'),
            service('flux_se.sylius_stripe.manager.web_elements.retrieve'),
        ]);

    $services->set('flux_se.sylius_stripe.manager.checkout.expire', ExpireManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
        ]);
    $services->alias(ExpireManagerInterface::class, 'flux_se.sylius_stripe.manager.checkout.expire');

    $services->set('flux_se.sylius_stripe.manager.checkout.capture_authorized', CaptureManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.payment_intent.capture.params'),
        ]);

    $services->set('flux_se.sylius_stripe.manager.checkout.cancel_authorized', CancelManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.payment_intent.cancel.params'),
        ]);

    $services->set('flux_se.sylius_stripe.manager.web_elements.create', WebElementsCreateManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.web_elements.create.params'),
        ]);
    $services->alias(WebElementsCreateManagerInterface::class, 'flux_se.sylius_stripe.manager.web_elements.create');

    $services->set('flux_se.sylius_stripe.manager.web_elements.retrieve', WebElementsRetrieveManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.web_elements.retrieve.params'),
        ]);
    $services->alias(WebElementsRetrieveManagerInterface::class, 'flux_se.sylius_stripe.manager.web_elements.retrieve');

    $services->set('flux_se.sylius_stripe.manager.web_elements.cancel', CancelManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.payment_intent.cancel.params'),
        ]);
    $services->alias(CancelManagerInterface::class, 'flux_se.sylius_stripe.manager.web_elements.cancel');

    $services->set('flux_se.sylius_stripe.manager.web_elements.capture', CaptureManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.payment_intent.capture.params'),
        ]);
    $services->alias(CaptureManagerInterface::class, 'flux_se.sylius_stripe.manager.web_elements.capture');

    $services->set('flux_se.sylius_stripe.manager.event.retrieve', EventRetrieveManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
        ]);
    $services->alias(EventRetrieveManagerInterface::class, 'flux_se.sylius_stripe.manager.event.retrieve');

    $services->set('flux_se.sylius_stripe.manager.refund.create', RefundCreateManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.refund.create.params'),
        ]);
    $services->alias(RefundCreateManagerInterface::class, 'flux_se.sylius_stripe.manager.refund.create');

    $services->set('flux_se.sylius_stripe.manager.invoice.all', AllManager::class)
        ->args([
            service('flux_se.sylius_stripe.stripe.factory.client'),
            service('flux_se.sylius_stripe.provider.invoice.all.params'),
        ]);
    $services->alias(AllManagerInterface::class, 'flux_se.sylius_stripe.manager.invoice.all');
};
