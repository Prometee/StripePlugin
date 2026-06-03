<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo" src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

<h1 align="center">Stripe Plugin</h1>

<p align="center">
    <a href="https://packagist.org/packages/flux-se/sylius-stripe-plugin"><img src="https://img.shields.io/packagist/v/flux-se/sylius-stripe-plugin.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://packagist.org/packages/flux-se/sylius-stripe-plugin"><img src="https://img.shields.io/packagist/dt/flux-se/sylius-stripe-plugin.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License"></a>
    <a href="https://github.com/FLUX-SE/SyliusStripePlugin/actions?query=workflow%3A%22Build%22"><img src="https://github.com/FLUX-SE/SyliusStripePlugin/workflows/Build/badge.svg" alt="Build Status"></a>
</p>

<p align="center">
    <a href="https://sylius.com/plugins/" target="_blank">
        <img src="https://sylius.com/assets/badge-official-sylius-plugin.png" width="200" alt="Official Sylius Plugin">
    </a>
</p>

<p align="center">
    Official integration of 
    <a href="https://stripe.com/" target="_blank">Stripe</a> 
    with 
    <a href="https://sylius.com" target="_blank">Sylius</a>.
</p>

<p align="center">This plugin enables Stripe Checkout and Stripe Web Elements payment gateways with built-in support for Apple Pay, Google Pay, Link and other Stripe payment methods.</p>

---

## Installation

Two installation paths are supported:

- **Recipe-based** (recommended) — uses the Symfony Flex recipe published in `symfony/recipes-contrib`. Steps below.
- **Manual** — every step performed by hand. See [docs/INSTALLATION.md](docs/INSTALLATION.md).

### Recipe-based installation

> ℹ️ This path assumes you're using **Symfony Flex** with **yarn** and **Symfony Encore** correctly configured. If you're on a legacy setup without them, refer to the [manual installation](docs/INSTALLATION.md) instead.

1. Prepare your environment

    Before installing the plugin, ensure that your project:

    - Uses **Symfony Flex**
    - Runs **Sylius ^2.0**
    - Has **yarn** and **Symfony Encore** correctly configured

2. Allow contrib recipes (one-off, per project):
    ```shell
    composer config extra.symfony.allow-contrib true
    ```

    If prompted during plugin installation, accept the community recipe when asked.

3. Install the plugin via Composer:
    ```shell
    composer require flux-se/sylius-stripe-plugin
    ```

    This installs the plugin and applies the Flex recipe, which registers the bundle, drops in the `config/packages` import, and appends the asset entrypoint lines.

4. Install and build assets:
    ```shell
    bin/console assets:install public

    yarn install
    yarn encore dev   # or: yarn encore prod
    ```

5. Clear the cache:
    ```shell
    bin/console cache:clear   # add `-e prod` for production
    ```

## Configuration

 - Go to the admin area.
 - Log in.
 - Click on the left menu item "Configuration > Payment methods".
 - Create a new payment method type "Stripe (Checkout)" or "Stripe (Web Elements)":
 - The next chapter will explain how to fill the payment method creation form.
 
### Payment Method configuration

A form will be displayed, fill-in the required fields :

#### 1. The "code" field (ex: "my_shop_stripe_checkout").

> 💡 The code will be the `gateway name`, it will be necessary to build the right webhook URL later
> (see [Webhook key](#webhook-key) section for more info).

#### 2. Choose which channels this payment method will be affected to.

#### 3. Fill the gateway configuration ([need info from here](#api-keys)).

> _📖 NOTE: You can add as many webhook secret keys as you need here, however generic usage needs only one._

#### 4. Give to this payment method a display name (and a description) for each language you need.

Finally, click on the "Create" button to save your new payment method.

### API keys

**We recommend** installing the [Sylius Stripe App][link-sylius-stripe-app] — its Settings Page exposes both keys 
this plugin needs:

- the publishable key (`pk_test_…` / `pk_live_…`) for the "Publishable key" field,
- a Restricted API Key (`rk_test_…` / `rk_live_…`) for the "Restricted API key (recommended) or secret key" field.

The App ships with the minimum scopes the plugin needs, and the Restricted API Key will be the only supported option 
for the `secret_key` field in plugin 2.0.

Alternatively, you can pick both keys directly from the Stripe Dashboard:

https://dashboard.stripe.com/test/apikeys

In that case, paste a standard secret key (`sk_test_…` / `sk_live_…`) into the "Restricted API key (recommended) or secret key" field.

Restricted API keys are Stripe's officially recommended replacement for standard secret keys, see [Stripe's documentation on restricted API keys][link-stripe-restricted-keys] for the full rationale.

### Webhook key

Got to:

https://dashboard.stripe.com/test/webhooks

Then create a new endpoint with those events:

| Gateway | `stripe_checkout` | `stripe_web_elements` |
|-|-|-|
| Webhook events |  - `checkout.session.completed`<br> - `checkout.session.async_payment_failed`<br> - `checkout.session.async_payment_succeeded`<br> - `checkout.session.expired`<br> - `setup_intent.canceled` (⚠️ Only when using `setup` mode)<br> - `setup_intent.succeeded`  (⚠️ Only when using `setup` mode) |  - `payment_intent.canceled`<br> - `payment_intent.succeeded`<br> - `setup_intent.canceled` (⚠️ Only when using `setup` mode)<br> - `setup_intent.succeeded`  (⚠️ Only when using `setup` mode) |


The URL to fill is the route named `sylius_payment_method_notify` with the `{code}`
param equal to the `payment method code`, here is an example :

```
https://localhost/payment-methods/my_shop_stripe_checkout
```

> 📖 As you can see in this example the URL is dedicated to `localhost`, you will need to provide to
> Stripe a public host name to get the webhooks working.

> 📖 Use this command to know the exact structure of `sylius_payment_method_notify` route
>
> ```shell
> bin/console debug:router sylius_payment_method_notify
> ```

### Test or dev environment

Webhooks are triggered by Stripe on their server to your server.
If the server is into a private network, Stripe won't be allowed to reach your server.

Stripe provide an alternate way to catch those webhook events, you can use `Stripe CLI`: https://stripe.com/docs/stripe-cli
Follow the link and install `Stripe CLI`, then use those command line to get your webhook key:

First login to your Stripe account (needed every 90 days) :

```shell
stripe login
```

Then start to listen for the Stripe events (minimal ones are used here), forwarding request to your local server :

 1. Example with `my_shop_stripe_checkout` as payment method code:
    ```shell
    stripe listen \
       --events checkout.session.completed,checkout.session.async_payment_failed,checkout.session.async_payment_succeeded,checkout.session.expired \
       --forward-to https://localhost/payment-methods/my_shop_stripe_checkout
    ```

 2. Example with `my_shop_stripe_web_elements` as payment method code:
    ```shell
    stripe listen \
       --events payment_intent.canceled,payment_intent.succeeded \
       --forward-to https://localhost/payment-methods/my_shop_stripe_web_elements
    ```

> 💡 Replace --forward-to argument value with the right one you need.

When the command finishes, a webhook secret key is displayed, copy it to your Payment method configuration edit form in the Sylius admin.

> ⚠️ Using the command `stripe trigger checkout.session.completed` will always result in a `500 error`,
> because the test object will not embed any usable metadata.

## Advanced documentation

- [Manual installation](docs/INSTALLATION.md)
- [Webhook events](docs/WEBHOOK-EVENTS.md)

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Authors

This plugin was originally created by:

<a href="https://harman.com" target="_blank"><img src="docs/assets/harman-logo.svg" alt="Harman Professional, Inc." height="40"></a>
&nbsp;&nbsp;
<a href="https://flux.audio" target="_blank"><img src="docs/assets/flux-logo.svg" alt="Flux:: Sound and Picture Development" height="40"></a>

Kudos to [Prometee](https://github.com/Prometee) and [all contributors](../../contributors) 🙏

## License

This plugin is released under the [MIT License](LICENSE).

## Telemetry

This plugin enforces telemetry data collection when used with Sylius.
Details are described in [TELEMETRY_POLICY.md](./TELEMETRY_POLICY.md).

[ico-version]: https://img.shields.io/packagist/v/flux-se/sylius-stripe-plugin.svg?style=flat-square
[ico-total-downloads]: https://img.shields.io/packagist/dt/flux-se/sylius-stripe-plugin.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-github-actions]: https://github.com/FLUX-SE/SyliusStripePlugin/workflows/Build/badge.svg

[link-packagist]: https://packagist.org/packages/flux-se/sylius-stripe-plugin
[link-total-downloads]: https://packagist.org/packages/flux-se/sylius-stripe-plugin
[link-github-actions]: https://github.com/FLUX-SE/SyliusStripePlugin/actions?query=workflow%3A"Build"
[link-sylius-stripe-app]: https://marketplace.stripe.com/apps/install/link/com.sylius.stripe
[link-stripe-restricted-keys]: https://docs.stripe.com/keys/restricted-api-keys
