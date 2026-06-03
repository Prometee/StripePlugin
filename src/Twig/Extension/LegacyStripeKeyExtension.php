<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LegacyStripeKeyExtension extends AbstractExtension
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param list<string> $stripeFactoryNames
     */
    public function __construct(
        private readonly LegacyKeyDetectorInterface $legacyKeyDetector,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        private readonly array $stripeFactoryNames,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sylius_stripe_is_legacy_secret_key',
                $this->legacyKeyDetector->isLegacy(...),
            ),
            new TwigFunction(
                'sylius_stripe_legacy_payment_methods',
                $this->getLegacyPaymentMethods(...),
            ),
        ];
    }

    /** @return list<PaymentMethodInterface> */
    private function getLegacyPaymentMethods(): array
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
