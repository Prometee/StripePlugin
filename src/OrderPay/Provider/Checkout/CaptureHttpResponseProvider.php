<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\OrderPay\Provider\Checkout;

use Sylius\Bundle\PaymentBundle\Provider\HttpResponseProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class CaptureHttpResponseProvider implements HttpResponseProviderInterface
{
    // Sylius 2.2 passes a RequestConfiguration here, 2.3 a Request; neither is used.
    public function supports(
        mixed $request,
        PaymentRequestInterface $paymentRequest,
    ): bool {
        return $paymentRequest->getState() === PaymentRequestInterface::STATE_PROCESSING;
    }

    public function getResponse(
        mixed $request,
        PaymentRequestInterface $paymentRequest,
    ): Response {
        $data = $paymentRequest->getResponseData();

        /** @var string|null $url */
        $url = $data['url'] ?? null;
        if (null === $url) {
            throw new \LogicException('The Checkout Session "url" has not been provided.');
        }

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }
}
