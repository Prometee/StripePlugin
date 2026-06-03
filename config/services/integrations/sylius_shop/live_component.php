<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Twig\Component\WebElements\SummaryPaymentComponent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(SummaryPaymentComponent::class)
        ->args([
            service('request_stack'),
            service('sylius.repository.payment_request'),
        ])
        ->tag('twig.component', [
            'key' => 'sylius_shop:order_pay:web_elements:content',
            'template' => '@FluxSESyliusStripePlugin/shop/order_pay/web_elements/capture/content.html.twig',
        ]);
};
