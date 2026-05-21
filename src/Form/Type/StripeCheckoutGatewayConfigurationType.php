<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class StripeCheckoutGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('enable_adaptive_pricing', CheckboxType::class, [
            'required' => false,
            'label' => 'flux_se_sylius_stripe_plugin.form.gateway_configuration.stripe.enable_adaptive_pricing',
        ]);
    }

    public function getParent(): string
    {
        return StripeGatewayConfigurationType::class;
    }
}
