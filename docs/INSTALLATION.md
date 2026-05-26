# Manual installation

This guide walks through installing the plugin step by step. If you have Symfony Flex with contrib recipes enabled,
prefer the recipe-based path described in the [README](../README.md#recipe-based-installation) — it automates steps 2–5 below.

1. Install the plugin
    ```shell
    composer require flux-se/sylius-stripe-plugin --no-scripts
    ```

2. Enable this plugin :
    ```php
    <?php
    # config/bundles.php
    
    return [
        // ...
        FluxSE\SyliusStripePlugin\FluxSESyliusStripePlugin::class => ['all' => true],
    ];
    ```

3. Import configuration
    ```yaml
    # config/packages/sylius_stripe.yaml

    imports:
    - { resource: "@FluxSESyliusStripePlugin/config/config.yaml" }
    ```

4. Import shop routes (required for the Express Checkout cart button — Apple Pay, Google Pay, Link, etc.)
    ```yaml
    # config/routes/sylius_stripe.yaml

    sylius_stripe_shop_express_checkout:
        resource: "@FluxSESyliusStripePlugin/config/routes/shop_express_checkout.yaml"
    ```

5. Import plugin assets (admin form styling and the shop Express Checkout JS — skip the shop entry if you don't use the cart-page wallet button):
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

6. Install and build assets:
    ```shell
    bin/console assets:install public

    yarn install
    yarn encore dev   # or: yarn encore prod
    ```

7. Clear the cache
    ```shell
    bin/console cache:clear
    ```

Once the plugin is installed, configure a payment method as described in the [Configuration section of the README](../README.md#configuration).
