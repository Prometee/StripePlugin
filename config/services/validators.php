<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Validator\Constraints\CheckoutSessionCreatePayloadRequirementValidator;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.validator.payload_requirements.supported_factory_names', [
            'stripe_checkout',
        ])
        ->set('flux_se.sylius_stripe.validator.payload_requirements.supported_actions', [
            null,
            PaymentRequestInterface::ACTION_AUTHORIZE,
            PaymentRequestInterface::ACTION_CAPTURE,
        ]);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.validator.checkout_session_create_payload_requirement', CheckoutSessionCreatePayloadRequirementValidator::class)
        ->args([
            service('sylius.repository.payment_method'),
            service('sylius.provider.payment_request.gateway_factory_name'),
            param('flux_se.sylius_stripe.validator.payload_requirements.supported_factory_names'),
            param('flux_se.sylius_stripe.validator.payload_requirements.supported_actions'),
        ])
        ->tag('validator.constraint_validator', [
            'alias' => 'flux_se_sylius_stripe_checkout_session_create_payload_requirement',
        ]);
};
