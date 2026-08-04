<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\FluxSE\SyliusStripePlugin\Behat\Page\Admin\PaymentMethod\CreatePage;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\External\StripePage;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\NotifyPage;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.page.admin.payment_method.create', CreatePage::class)
        ->parent('sylius.behat.page.admin.payment_method.create');

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.page.notify_page', NotifyPage::class)
        ->share(false)
        ->parent('sylius.behat.symfony_page')
        ->args(['sylius_payment_method_notify']);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_checkout_session', StripePage::class)
        ->parent('sylius.behat.page')
        ->args([
            service('sylius.repository.payment_request'),
            service('test.client'),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.notify_page'),
            service('router'),
            service('sylius_shop.provider.order_pay.payment_request_pay_url'),
        ]);

    $services->set('tests.flux_se.sylius_stripe_plugin.behat.page.external.stripe_web_elements', StripePage::class)
        ->parent('sylius.behat.page')
        ->args([
            service('sylius.repository.payment_request'),
            service('test.client'),
            service('tests.flux_se.sylius_stripe_plugin.behat.page.notify_page'),
            service('router'),
            service('sylius_shop.provider.order_pay.payment_request_pay_url'),
        ]);
};
