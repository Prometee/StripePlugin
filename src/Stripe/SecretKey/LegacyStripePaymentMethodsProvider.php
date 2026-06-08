<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\SecretKey;

use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;

final readonly class LegacyStripePaymentMethodsProvider implements LegacyStripePaymentMethodsProviderInterface
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param list<string> $stripeFactoryNames
     */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        private LegacyKeyDetectorInterface $legacyKeyDetector,
        private array $stripeFactoryNames,
    ) {
    }

    public function provide(): array
    {
        $legacyPaymentMethods = [];

        /** @var list<PaymentMethodInterface> $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findBy(['enabled' => true]);

        foreach ($paymentMethods as $paymentMethod) {
            $gatewayConfig = $paymentMethod->getGatewayConfig();
            if ($gatewayConfig === null) {
                continue;
            }

            if (!in_array($this->gatewayFactoryNameProvider->provide($paymentMethod), $this->stripeFactoryNames, true)) {
                continue;
            }

            /** @var mixed $secretKey */
            $secretKey = $gatewayConfig->getConfig()['secret_key'] ?? null;
            if (!is_string($secretKey) && $secretKey !== null) {
                continue;
            }

            if (!$this->legacyKeyDetector->isLegacy($secretKey)) {
                continue;
            }

            $legacyPaymentMethods[] = $paymentMethod;
        }

        return $legacyPaymentMethods;
    }
}
