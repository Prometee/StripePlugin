<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Notification;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use Sylius\Bundle\AdminBundle\Notification\NotificationProviderInterface;

final readonly class LegacyStripeKeyNotificationProvider implements NotificationProviderInterface
{
    public function __construct(
        private LegacyStripePaymentMethodsProviderInterface $legacyStripePaymentMethodsProvider,
    ) {
    }

    public function getNotifications(array $context = []): array
    {
        $notifications = [];

        foreach ($this->legacyStripePaymentMethodsProvider->provide() as $paymentMethod) {
            $notifications[sprintf('legacy_stripe_secret_key.%s', (string) $paymentMethod->getId())] = [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => (string) $paymentMethod->getName(),
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => [
                    'id' => $paymentMethod->getId(),
                    '_fragment' => 'stripe-gateway-configuration',
                ],
            ];
        }

        return $notifications;
    }

    public function supports(array $context = []): bool
    {
        return true;
    }
}
