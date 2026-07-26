<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\CheckoutSessionMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\EventMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\RefundMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeCheckoutMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectations;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeWebElementsMocker;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'cache' => [
            'pools' => [
                'cache.test_stripe_expectation_client' => [
                    'adapter' => 'cache.adapter.filesystem',
                ],
            ],
        ],
    ]);

    $services = $container->services();

    $services->set('tests.flux_se.sylius_stripe.stripe.http_client', StripeClientWithExpectations::class)
        ->decorate('flux_se.sylius_stripe.stripe.http_client')
        ->args([service('cache.test_stripe_expectation_client')]);

    $services->set(CheckoutSessionMocker::class)
        ->args([service('tests.flux_se.sylius_stripe.stripe.http_client')]);

    $services->set(PaymentIntentMocker::class)
        ->args([service('tests.flux_se.sylius_stripe.stripe.http_client')]);

    $services->set(RefundMocker::class)
        ->args([service('tests.flux_se.sylius_stripe.stripe.http_client')]);

    $services->set(EventMocker::class)
        ->args([service('tests.flux_se.sylius_stripe.stripe.http_client')]);

    $services->set(StripeCheckoutMocker::class)
        ->args([
            service(CheckoutSessionMocker::class),
            service(PaymentIntentMocker::class),
            service(RefundMocker::class),
            service(EventMocker::class),
        ]);

    $services->set(StripeWebElementsMocker::class)
        ->args([
            service(PaymentIntentMocker::class),
            service(RefundMocker::class),
            service(EventMocker::class),
        ]);
};
