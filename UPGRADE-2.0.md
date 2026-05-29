# Upgrade from 1.0 to 2.0

## Stripe PHP SDK upgrade

`stripe/stripe-php` is bumped from `^16.1` to `^20.0`. Crossing those majors brings breaking changes — both at this 
plugin's public surface (DI parameters, service decorators, providers) and transitively through the Stripe SDK / Stripe 
API itself.

The subsections below list only the changes that can affect **end applications** embedding this plugin.

### Stripe PHP SDK requirement

`composer.json` now requires `stripe/stripe-php: ^20.0` (was `^16.1`). Applications depending on the SDK directly inherit
the bump and should review the upstream changelog for BC breaks introduced across v17–v20:

https://github.com/stripe/stripe-php/blob/master/CHANGELOG.md

### Stripe API version (default)

The plugin does not call `Stripe::setApiVersion()`, so it inherits the default pinned by the SDK. Bumping the SDK across
four majors moves the default forward to a newer Basil API. As a result:

- Live webhook payloads will use the new schema. Custom processors tagged
  `flux_se.sylius_stripe.processor.webhook_event.{checkout,web_elements}` should be reviewed.
- Existing webhook endpoints in your Stripe Dashboard keep delivering events on the API version they were created with —
  only newly created (or explicitly upgraded) endpoints will match the new SDK default. Verify both sides agree before
  going live.

To lock to a specific version, call `Stripe::setApiVersion()` from your application bootstrap.

### `Invoice.payment_intent` moved under `Invoice.payments`

Stripe removed the direct `Invoice.payment_intent` field. The PaymentIntent attached to a subscription invoice now lives
under `Invoice.payments.data[].payment.payment_intent`, gated by `payment.type === 'payment_intent'`.

You must migrate if your application:

- decorates / extends `SubscriptionModeTransitionProvider` or `RefundSubscriptionInitProvider`,
- reads `payment.details['invoice']['payment_intent']` directly anywhere (listeners, controllers, fixtures, reports).

#### Before

```php
$paymentIntentId = $invoice['payment_intent']; // string|array
```

#### After

```php
$paymentIntentId = null;
foreach ($invoice['payments']['data'] ?? [] as $invoicePayment) {
    $payment = $invoicePayment['payment'] ?? null;
    if (null === $payment || 'payment_intent' !== ($payment['type'] ?? null)) {
        continue;
    }

    $pi = $payment['payment_intent'] ?? null;
    $paymentIntentId = is_array($pi) ? ($pi['id'] ?? null) : $pi;
    break;
}
```

See `src/Provider/Transition/Checkout/SubscriptionModeTransitionProvider.php` for a typed-SDK reference.

### `flux_se.sylius_stripe.checkout.retrieve.expand_fields` parameter changed

Defined in `config/services/providers/checkout/retrieve_params_providers.yaml`.

- **Removed:** `invoice.charge`, `invoice.payment_intent`, `invoice.payment_intent.latest_charge`,
  `invoice.payment_intent.payment_method`
- **Added:** `invoice.payments`

If you override this parameter, rebuild your list around `invoice.payments`. The deeper
`invoice.payments.data.payment.payment_intent.*` chain is intentionally **not** in `expand_fields` — Stripe caps
`expand[]` at 4 levels, and the PaymentIntent is fetched separately by the new manager (see below).

### `flux_se.sylius_stripe.web_elements.retrieve.expand_fields` parameter changed

`payment_method` was added (alongside the existing `latest_charge`). If you override this parameter, add
`payment_method` back — it is required for subscription-mode PaymentIntent enrichment to work end-to-end.

## Assets

### Make sure the plugin asset entrypoints are imported

The plugin ships its asset entrypoints under `assets/admin/` and `assets/shop/`. In 2.0 the **shop** entrypoint now 
also bundles the Express Checkout client code (`assets/shop/js/express-checkout/`), so importing it is no longer 
cosmetic: without the shop entrypoint imported and rebuilt, the Express Checkout button never renders on the cart page 
even when the feature is enabled and the routes are imported. If you have not added these imports yet, do it now.

Import them from your application's Encore entrypoints:

```js
// assets/admin/entrypoint.js

// ...
import '../../vendor/flux-se/sylius-stripe-plugin/assets/admin/entrypoint';
```
```js
// assets/shop/entrypoint.js

// ...
import '../../vendor/flux-se/sylius-stripe-plugin/assets/shop/entrypoint';
```

Then (re)build assets:

```shell
bin/console assets:install public

yarn install
yarn encore dev   # or: yarn encore prod
```

Fresh installs using the Symfony Flex contrib recipe get these imports appended automatically — this step is only
required for projects upgrading in place. See [docs/INSTALLATION.md](docs/INSTALLATION.md) for the full manual setup.

## Express Checkout (cart page)

2.0 introduces Express Checkout (ECE) on the cart page — a single button rendering Apple Pay, Google Pay, Link
and whatever other wallets your Stripe Dashboard exposes. The feature is opt-in per PaymentMethod (the
`enable_express_checkout` toggle in the gateway configuration), but the steps below apply even to applications
that do **not** plan to enable it, because the new routes and the rewired command provider land regardless.

### Shop routes must be imported

The plugin now ships an additional shop routes file that the bundle does **not** auto-load. Add it to your
application's route configuration:

```yaml
# config/routes/sylius_stripe.yaml

sylius_stripe_express_checkout_shop:
    resource: "@FluxSESyliusStripePlugin/config/routes/shop_express_checkout.yaml"
```

The file `config/routes/shop_express_checkout.yaml` registers three endpoints under `/express-checkout/`:

| Route name | Method | Path |
|---|---|---|
| `sylius_stripe_express_checkout_configuration` | `GET` | `/express-checkout/configuration` |
| `sylius_stripe_express_checkout_shipping_rates` | `POST` | `/express-checkout/shipping-rates` |
| `sylius_stripe_express_checkout_confirm` | `POST` | `/express-checkout/confirm` |

Without the import, `GET /express-checkout/configuration` returns 404, the cart-page JavaScript silently
hides itself, and the wallet button never appears — there is no visible error.

If your application uses a non-default firewall pattern (e.g. `^/(en|fr)/`), make sure these paths fall inside
the shop firewall — they rely on the cart session like the rest of the shop area.

### New required Stripe webhook events when Express Checkout is enabled

Express Checkout always creates a Stripe `PaymentIntent`, regardless of the gateway type backing the
PaymentMethod. This means a `stripe_checkout` PaymentMethod with `enable_express_checkout` turned on must
subscribe to PaymentIntent events **in addition** to the existing `checkout.session.*` ones.

Add the following events to the Stripe webhook endpoint of every `stripe_checkout` PaymentMethod that has the
toggle on:

- `payment_intent.succeeded`
- `payment_intent.canceled`
- `payment_intent.processing`

For `stripe_web_elements` PaymentMethods the same three events are already required by the regular flow, so
the toggle does not change that list.

Without these events the ECE payment never receives a completion signal — the Sylius `Payment` stays in the
`processing` state and never transitions to `completed`. The full per-gateway matrix is in
`docs/EXPRESS-CHECKOUT.md` (section *Webhook events*).

### `flux_se.sylius_stripe.command_provider.checkout` now wraps the old class

The service ID `flux_se.sylius_stripe.command_provider.checkout` points to a **different class** in 2.0. The
old class is still wired, but under a new ID.

#### Before (1.x)

```yaml
flux_se.sylius_stripe.command_provider.checkout:
    class: Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider
```

#### After (2.0)

```yaml
flux_se.sylius_stripe.command_provider.checkout.actions:
    class: Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider
    # ...

flux_se.sylius_stripe.command_provider.checkout:
    class: FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CheckoutOrPaymentIntentCommandProvider
    arguments:
        - '@flux_se.sylius_stripe.command_provider.checkout.actions'
        - '@flux_se.sylius_stripe.command_provider.web_elements'
```

The new wrapper inspects the Stripe object stored on the `PaymentRequest` (Checkout `Session` vs
`PaymentIntent`) and delegates to the Web Elements command provider whenever it sees a PaymentIntent. This is
what lets a `payment_intent.*` webhook arriving on a `stripe_checkout` PaymentMethod URL (the ECE case above)
reach the correct command pipeline.

This is a **real BC change** for anyone decorating that service:

- If you decorated `flux_se.sylius_stripe.command_provider.checkout` to extend `ActionsCommandProvider`
  (e.g. to register an extra `action`), move the decoration to
  `flux_se.sylius_stripe.command_provider.checkout.actions`.
- If you decorated it to intercept the dispatch itself, keep the same ID but note that `'$.inner'` is now
  `CheckoutOrPaymentIntentCommandProvider`, not `ActionsCommandProvider`. The wrapper's constructor signature
  is `(PaymentRequestCommandProviderInterface $checkoutCommandProvider, PaymentRequestCommandProviderInterface $webElementsCommandProvider)`.

## `payment_method_types` gateway configuration removed

The `payment_method_types` field in the Stripe gateway configuration (deprecated in 1.1 — see `UPGRADE-1.1.md`) is fully 
removed in 2.0. Stripe's Payment Element / Checkout Session now relies on the automatic payment methods configured 
in your Stripe Dashboard for every customer.

**Removed surface:**

- Form field `payment_method_types` in `FluxSE\SyliusStripePlugin\Form\Type\StripeGatewayConfigurationType`
- Class `FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProvider`
- Services `flux_se.sylius_stripe.provider.checkout.create.payment_method_types` and
  `flux_se.sylius_stripe.provider.web_elements.create.payment_method_types`
- Admin templates under `@FluxSESyliusStripePlugin/admin/payment_method/form/payment_method_types*` and the
  associated twig hooks
- Translation keys `flux_se_sylius_stripe_plugin.form.gateway_configuration.stripe.payment_method_types`,
  `...info.payment_method_types_deprecated`, `...action.manage_payment_methods`

**Migration:**

1. Before upgrading, follow the migration steps in `UPGRADE-1.1.md` — open your Stripe Dashboard → Settings →
   Payment methods, enable the methods you want to offer, and clear the "Payment method types" field in each
   Sylius Stripe payment method.
2. After upgrading, the field disappears from the admin form. Any remaining `payment_method_types: [...]` value
   still serialized inside `payment_method.gateway_config.config` is silently ignored — no data migration is provided.
   If you want a clean payload, clear it manually (e.g. via a one-off update query) before or after the upgrade;
   it has no functional impact.

**For developers extending the plugin:** if your custom code references the removed class, services,
templates or twig hooks, remove those references before upgrading.

## Restricted API Key required for the `secret_key` field

The `Restricted API key` field of the Stripe gateway configuration now only accepts a Restricted API Key (`rk_test_…` / `rk_live_…`) 
generated by the [Sylius Stripe App][link-sylius-stripe-app]. Standard Stripe secret keys (`sk_test_…` / `sk_live_…`), 
which 1.1 accepted with a runtime deprecation notice (`UPGRADE-1.1.md`), are rejected by the form validator in 2.0.

**Removed surface:**

- The `sk_` branch of `SECRET_KEY_PATTERN` in `FluxSE\SyliusStripePlugin\Form\Type\StripeGatewayConfigurationType` 
  (regex is now `/^rk_(test|live)_/`).
- Admin info-box "legacy" copy and the secondary CTA to the Stripe Dashboard.
- Translation keys `info.secret_key_recommended_title`, `info.secret_key_recommended_body`, `info.secret_key_legacy_body`, 
  `action.secret_key`.

**Backwards-compatibility kept on purpose:**

- `Stripe/Factory/ClientFactory::createFromPaymentMethod` still builds a working `StripeClient` from a `sk_*` value 
  persisted before the upgrade. A `trigger_deprecation` notice is emitted on every build so the issue surfaces in logs. 
  The class is **not** the right place to fail-fast: webhook delivery and refund flows would otherwise break
  for anyone who upgrades before migrating their keys.
- The admin form keeps a dynamic `alert-warning` rendered under the field whenever the saved `secret_key` starts 
  with `sk_`, telling the admin that saving will fail until the key is replaced. Migrate, then the warning disappears.

The internal gateway_config key is still `secret_key` (no data migration is needed for existing payment methods that 
already store an `rk_*` value there).

**Migration:**

1. Install the [Sylius Stripe App][link-sylius-stripe-app] on your Stripe account.
2. Open the App's Settings Page and copy the generated Restricted API Key (`rk_test_…` / `rk_live_…`).
3. In Sylius admin, edit every Stripe payment method whose secret key still starts with `sk_` and paste the `rk_*` key 
   into the `Restricted API key` field. Save.

Payment methods that already held an `rk_*` value before upgrading need no further action.

**For developers extending the plugin:** if you imported the `SECRET_KEY_PATTERN` constant directly, the value changed 
(the name did not). Any custom validator allowing `sk_*` will start drifting from the plugin's behaviour after upgrading.

## Admin — gateway configuration form hooks restructured

The admin payment-method form hooks for both `stripe_checkout` and `stripe_web_elements` were reordered and consolidated.
The three separate hooks rendering the API keys block (`publishable_key`, `secret_key`, `secret_key_info`) are merged
into one field hook plus its info-box; the field order now leads with API keys and webhook secret keys (the two
mandatory blocks) before the optional toggles; and all priorities follow a single step-50 scheme (100 between adjacent
fields, 100 between adjacent info-boxes).

### Hooks renamed / removed

For both `…stripe_checkout.form.*` and `…stripe_web_elements.form.*` anchors (create and update):

- **Removed:** `publishable_key`
- **Removed:** `secret_key` (deprecated empty stub since 1.0 — `UPGRADE-1.0.md`)
- **Removed:** `secret_key_info`
- **Added:** `api_keys` — renders both the publishable and secret key fields, plus the legacy `sk_*` warning
- **Added:** `api_keys_info` — info-box pointing to the [Sylius Stripe App][link-sylius-stripe-app]

If you registered custom templates under any of the three removed hooks, re-register them under `api_keys` and/or
`api_keys_info` instead. Custom templates targeting `secret_key` were already deprecated in 1.0 and are not rendered
anymore.

### Template files renamed / removed

- **Renamed:** `@FluxSESyliusStripePlugin/admin/payment_method/form/publishable_key.html.twig` →
  `@FluxSESyliusStripePlugin/admin/payment_method/form/api_keys.html.twig`
- **Renamed:** `@FluxSESyliusStripePlugin/admin/payment_method/form/secret_key_info.html.twig` →
  `@FluxSESyliusStripePlugin/admin/payment_method/form/api_keys_info.html.twig`
- **Removed:** `@FluxSESyliusStripePlugin/admin/payment_method/form/secret_key.html.twig`

Sylius test attributes referenced inside these templates (`config-publishable-key`, `config-secret-key`,
`secret-key-info`, `secret-key-legacy-warning`) are unchanged so existing Behat scenarios keep working.

### New hook priorities

| Hook                            | Old priority | New priority |
|---------------------------------|--------------|--------------|
| `api_keys` (was `publishable_key`) | 400       | **500**      |
| `api_keys_info` (was `secret_key_info`) | 350  | **450**      |
| `webhook_secret_keys`           | 100          | **400**      |
| `webhook_secret_key_info`       | 50           | **350**      |
| `use_authorize`                 | 200          | **300**      |
| `use_authorize_info`            | 150          | **250**      |
| `enable_express_checkout`       | 130          | **200**      |
| `enable_express_checkout_info`  | 120          | **150**      |
| `enable_adaptive_pricing`*      | 115          | **100**      |
| `enable_adaptive_pricing_info`* | 110          | **50**       |

\* Registered only for `stripe_checkout` (Adaptive Pricing does not apply to Web Elements).

If you registered hooks at any of the old priorities to slot a custom field between existing ones, recompute against
the new scheme (step 50).

## `payment_intent.*` webhook events required for `stripe_checkout` with `use authorize`

In 2.0 the Checkout Session creation request propagates the Sylius `PaymentRequest` `token_hash` into 
`payment_intent_data.metadata` (and therefore onto the resulting `PaymentIntent` and `Charge`). Before this change, 
`payment_intent.*` events emitted by a `stripe_checkout` PaymentIntent were unresolvable by the plugin (the resolver 
looks the `PaymentRequest` up by `metadata.token_hash`) and returned HTTP 500.

As a consequence, **Stripe Dashboard-side capture and cancel of an authorized PaymentIntent now synchronize back to 
Sylius via webhooks** — which means the Stripe webhook endpoint of every `stripe_checkout` PaymentMethod with 
`use authorize` ON must subscribe to:

- `payment_intent.succeeded`
- `payment_intent.canceled`
- `payment_intent.processing`

These are the same three events the Express Checkout migration already requires (see the section above) — if you applied 
that migration on a PaymentMethod with `use authorize` ON, you are already done; the events are needed regardless of 
whether the ECE toggle is on.

Without these events the following stops working:

- Capturing the authorized PaymentIntent in the Stripe Dashboard leaves the order stuck in `Authorized` while the money 
  is already taken (Sylius / Stripe state mismatch).
- Cancelling the authorized PaymentIntent in the Stripe Dashboard does not propagate to Sylius (Payment stays
  `Authorized`).
- The webhook can no longer act as a fallback when a Sylius admin "Complete" / "Cancel" action on an Authorized order 
  fails mid-flight (the Stripe API call may have already succeeded by then).

**Migration:** for every existing `stripe_checkout` PaymentMethod with `use authorize` ON, open the matching webhook 
endpoint in the Stripe Dashboard and add the three events above. The [`docs/WEBHOOK-EVENTS.md`](docs/WEBHOOK-EVENTS.md) 
file has the full per-gateway / per-mode matrix.

## `PaymentStateProcessor` constructor signature changed

`FluxSE\SyliusStripePlugin\StateMachine\PaymentStateProcessor` gained a new constructor argument
`StripeStateAppliedCheckerInterface $stripeStateAppliedChecker`, inserted **before** the existing
`array $supportedFactories` argument.

The new service is wired automatically through the abstract `flux_se.sylius_stripe.state_machine.payment_state`
definition (`config/services/state_machine.yaml`).

**You must migrate if** you instantiate `PaymentStateProcessor` directly in PHP or via a manual service
definition that does **not** `parent:` the bundled abstract. Add `@flux_se.sylius_stripe.state_machine.stripe_state_applied_checker`
(or any other `StripeStateAppliedCheckerInterface` implementation) as the sixth constructor argument.

[link-sylius-stripe-app]: https://marketplace.stripe.com/apps/install/link/com.sylius.stripe
