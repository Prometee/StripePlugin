# Adaptive Pricing

Stripe's [Adaptive Pricing](https://docs.stripe.com/payments/checkout/adaptive-pricing)
shows the buyer a price in their local currency on the Stripe Checkout page while
the merchant keeps settling in their own currency. Stripe handles the FX conversion
on the presentation layer; the Sylius `Order` is not modified.

**The plugin exposes this as an opt-in flag on the Stripe Checkout gateway only.**
The Web Elements gateway uses PaymentIntents + `stripe.elements()`, where Adaptive
Pricing is not supported by Stripe today.

## Requirements

- A Stripe account with Adaptive Pricing available (Dashboard → Settings → Checkout).
- A Sylius payment method configured with the **Stripe (Checkout)** gateway factory.

## How to enable

1. Open the admin area: **Configuration → Payment methods**.
2. Open (or create) a payment method using the **Stripe (Checkout)** factory.
3. Tick the **Enable Adaptive Pricing** checkbox in the gateway configuration section.
4. Save.

From that moment, every Stripe Checkout Session created for this payment method is
created with `adaptive_pricing: { enabled: true }` and Stripe will display the
buyer-local currency on the hosted Checkout page.

The field is **not** rendered for **Stripe (Web Elements)** payment methods.

## Interaction with the Stripe Dashboard toggle

| Plugin flag | Dashboard toggle | Effective behavior            |
|-------------|------------------|--------------------------------|
| on          | on               | Adaptive Pricing on            |
| on          | off              | Adaptive Pricing on (forced)   |
| off         | on               | Adaptive Pricing on (Dashboard)|
| off         | off              | Adaptive Pricing off           |

When the plugin flag is **off**, the parameter is omitted from the Checkout Session
payload, so the Dashboard fallback continues to work as before. The plugin
intentionally does not emit `adaptive_pricing: { enabled: false }`, so a gateway
without the flag does not override the merchant's Dashboard preference.

## Pricing considerations

Enabling Adaptive Pricing is **not free**. Stripe applies an FX spread on the
converted amount shown to the buyer, **starting at 2%**. Other Stripe fees
applicable to your account continue to apply on the same transaction.

Before enabling this flag, **review your full Stripe pricing profile for the
markets you sell into** and confirm the combined impact is acceptable for your
business model. Stripe's pricing page (`https://stripe.com/pricing`) lists the
headline rates; your account-specific contract may add or change some of them.

Refunds are processed in your **settlement currency**, not in the buyer's
displayed currency. The FX delta is not reversed by a refund. Communicate this
to your support team: a customer who paid in EUR but saw a USD total on the
Checkout page may expect a refund in USD, which Stripe will not provide.

## Limitations

- The plugin does not surface the buyer's displayed currency back to Sylius.
- The flag is per-payment-method, not per-channel or per-order.
