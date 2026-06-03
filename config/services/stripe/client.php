<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\HttpClient\PsrClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.stripe.client.chunk_size', 8192);

    $services = $container->services();

    $services->set(PsrClient::class)
        ->abstract()
        ->args([
            service(ClientInterface::class),
            service(RequestFactoryInterface::class),
            service(StreamFactoryInterface::class),
            param('flux_se.sylius_stripe.stripe.client.chunk_size'),
        ]);

    $services->set('flux_se.sylius_stripe.stripe.http_client')
        ->parent(PsrClient::class);

    $services->set('flux_se.sylius_stripe.stripe.streaming_http_client')
        ->parent(PsrClient::class);
};
