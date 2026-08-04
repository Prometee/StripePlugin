<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext as SyliusManagingPaymentMethodsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Api\Admin\ManagingPaymentMethodsContext as ApiAdminManagingPaymentMethodsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Api\Shop\StripeCheckoutContext as ApiShopStripeCheckoutContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Api\Shop\StripeWebElementsContext as ApiShopStripeWebElementsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Hook\StripeClientWithExpectationsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup\ManagingStripeCheckoutOrdersContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup\ManagingStripeWebElementsOrdersContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup\StripeContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext as UiAdminManagingPaymentMethodsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Shop\StripeCheckoutContext as UiShopStripeCheckoutContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Shop\StripeWebElementsContext as UiShopStripeWebElementsContext;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeCheckoutMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeWebElementsMocker;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->defaults()
        ->public();

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.hook.stripe_client_with_expectations', StripeClientWithExpectationsContext::class)
        ->args([service('tests.flux_se.sylius_stripe.stripe.http_client')]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.ui.admin.managing_payment_methods.stripe', SyliusManagingPaymentMethodsContext::class)
        ->args([
            service('sylius.behat.page.admin.payment_method.create'),
            service('sylius.behat.page.admin.payment_method.index'),
            service('sylius.behat.page.admin.payment_method.update'),
            service('sylius.behat.current_page_resolver'),
            ['stripe_checkout' => 'Stripe Checkout', 'stripe_web_elements' => 'Stripe Web Elements'],
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.setup.stripe', StripeContext::class)
        ->args([
            service('sylius.behat.shared_storage'),
            service('sylius.repository.payment_method'),
            service('sylius.fixture.example_factory.payment_method'),
            service('sylius.manager.payment_method'),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.api.admin.managing_payment_methods', ApiAdminManagingPaymentMethodsContext::class)
        ->args([service('sylius.behat.api_platform_client.admin')]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.ui.admin.managing_payment_methods', UiAdminManagingPaymentMethodsContext::class)
        ->args([service('tests.flux_se.sylius_stripe_plugin.behat.page.admin.payment_method.create')]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.setup.managing_orders.stripe_checkout', ManagingStripeCheckoutOrdersContext::class)
        ->args([
            service('sylius_abstraction.state_machine'),
            service('sylius.manager.order'),
            service(StripeCheckoutMocker::class),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.setup.managing_orders.stripe_web_elements', ManagingStripeWebElementsOrdersContext::class)
        ->args([
            service('sylius_abstraction.state_machine'),
            service('sylius.manager.order'),
            service(StripeWebElementsMocker::class),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.ui.shop.stripe_checkout', UiShopStripeCheckoutContext::class)
        ->args([
            service(StripeCheckoutMocker::class),
            service('sylius.behat.page.shop.checkout.complete'),
            service('sylius.behat.page.shop.order.show'),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_checkout_session'),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.ui.shop.stripe_web_elements', UiShopStripeWebElementsContext::class)
        ->args([
            service(StripeWebElementsMocker::class),
            service('sylius.behat.page.shop.checkout.complete'),
            service('sylius.behat.page.shop.order.show'),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_web_elements'),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.api.shop.stripe_checkout', ApiShopStripeCheckoutContext::class)
        ->args([
            service('sylius.behat.shared_storage'),
            service('sylius.behat.context.api.shop.checkout'),
            service('sylius.behat.context.api.shop.payment_request'),
            service(StripeCheckoutMocker::class),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_checkout_session'),
            service('sylius.behat.api_platform_client.shop'),
            service(ResponseCheckerInterface::class),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.context.api.shop.stripe_web_elements', ApiShopStripeWebElementsContext::class)
        ->args([
            service('sylius.behat.shared_storage'),
            service('sylius.behat.context.api.shop.checkout'),
            service('sylius.behat.context.api.shop.payment_request'),
            service(StripeWebElementsMocker::class),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_web_elements'),
            service('sylius.behat.api_platform_client.shop'),
        ]);
};
