# Webhook events

> Webhooks are triggered by Stripe on their server to your server.
> If your server is into a private network, Stripe won't be allowed to reach your server.
> Use the Stripe CLI to catch those webhook events. [Install Stripe CLI](https://stripe.com/docs/stripe-cli)

This plugin will be able to listen to webhook events which are related to a Sylius payment only.
If you want to listen to other events, you will have to create your own route and controller.
However, you will be able to use services provided by this plugin to handle and verify the Stripe events.

## Required Stripe events per gateway and mode

The events your Stripe webhook endpoint must subscribe to depend on the gateway type, the `use authorize` flag, and on
whether Express Checkout (ECE) on the cart page is enabled.

| Gateway | `use authorize` | Express Checkout | Required events |
|---|:-:|:-:|---|
| `stripe_checkout` | OFF | OFF | `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_failed`, `checkout.session.async_payment_succeeded` |
| `stripe_checkout` | **ON** | OFF | the four `checkout.session.*` above **and** `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.processing` |
| `stripe_checkout` | OFF | **ON** | the four `checkout.session.*` above **and** `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.processing` |
| `stripe_checkout` | **ON** | **ON** | the four `checkout.session.*` above **and** `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.processing` |
| `stripe_web_elements` | any | any | `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.processing` |
| any using `setup` mode | — | — | add `setup_intent.succeeded`, `setup_intent.canceled` on top of the rows above |

Why the `payment_intent.*` events matter for `stripe_checkout`:

- **`use authorize` ON** — when a merchant captures or cancels the authorized PaymentIntent directly in the Stripe
  Dashboard (instead of via the Sylius admin), only a `payment_intent.*` event is emitted. Without subscribing to it,
  Sylius never learns about the Dashboard-side change and the order is left stuck in `Authorized` indefinitely.
- **`use authorize` ON** also lets the webhook act as a fallback for the Sylius admin "Complete" / "Cancel" actions —
  the admin action itself can fail mid-flight while the Stripe API call already succeeded; the matching
  `payment_intent.*` event rescues the order state in that case.
- **Express Checkout on the cart page** always creates a PaymentIntent (never a Checkout Session), regardless of the
  gateway type. See [Express Checkout](EXPRESS-CHECKOUT.md) for the full feature description.

> 💡 Subscribing to the three `payment_intent.*` events on a `stripe_checkout` endpoint is harmless even when neither
> flag is on — the plugin resolves each event against the Sylius `PaymentRequest` via the `token_hash` metadata and
> returns 2xx without state changes if there is nothing to transition.

## How are webhook events listened to using this plugin?
Here is how the Sylius `PaymentRequest` notify process is working:

1. Sylius provides a route named `sylius_payment_method_notify` which is used to listen to the Stripe events.
   This route needs a `code` parameter which is the payment method code. (ex:
   https://my-shop.tld/payment-methods/my_shop_stripe_checkout)
2. This route is handled by a controller `sylius.controller.payment_method_notify`
   this controller will:
   1. Try to find a related `Payment`. To do so, each `PaymentMethod` must implement a service with
      `\Sylius\Bundle\PaymentBundle\Provider\NotifyPaymentProviderInterface`.
   2. Create a new `PaymentRequest` object with:
      1. The related `Payment`.
      2. The current `PaymentMethod`.
      3. The `action` equal to `notify`.
      4. The `payload` equal to the current `Request` content (normalized as an array).
   3. A `Command` is dispatch to the `CommandBus` with this new `PaymentRequest` object.

This plugin is hooking into the 2.i part of this process
(see service: `FluxSE\SyliusStripePlugin\Provider\StripeNotifyPaymentProvider`) to:

1. Get the current `Request` data.
2. Validate the payload from Stripe (see service: `flux_se.sylius_stripe.stripe.resolver.event_resolver`).
3. Extract the `data.object.metadata.token_hash` to retrieve the related `Payment`.

It also hooks into the 2.ii.d part to add the Stripe `Event` as an array
to the `PaymentRequest` `payload` under the `event` key.

Finally, when the `NotifyPaymentRequest` command is handled, the `NotifyPaymentRequestHandler` will:

1. Retrieve the Stripe `Event` from the Stripe API (to get a fresh one along with the underlying data object).
2. Send the Stripe `Event` and the `PaymentRequest` to the `WebhookEventProcessor` service.
   1. This service is simply getting the inner Stripe `Event` data object.
   2. Set this object to the `Payment` `details`. 
3. Send the `PaymentRequest` to the `PaymentTransitionProcessor` service to transition the `Payment` state.

## How to listen to `Payment related` Stripe events?

When the event already contains a `data.object.metadata.token_hash` key and the value is an existing `PaymentRequest` hash.

> If it's not the case, go to the next chapter [to listen to `NON Payment related` Stripe events](#how-to-listen-to-non-payment-related-stripe-events).

Create a new class implementing the `FluxSE\SyliusStripePlugin\Processor\WebhookEventProcessorInterface` interface.
Then declare a tagged service with tag name(s) among those:
 - `flux_se.sylius_stripe.processor.webhook_event.checkout` for a "Stripe Checkout" related event.
 - `flux_se.sylius_stripe.processor.webhook_event.web_elements` for a "Stripe Web Elements" related event.

If needed, remember to update the `Payment` details with either a "Stripe Checkout Session" or a "Stripe Payment Intent" object
depending on the factory you are targeting.

## How to listen to `NON Payment related` Stripe events?

At this point, you are free to do everything you want.
You can create a new route and controller to listen to the NON `Payment` related Stripe events
and finally create your own Webhook event handler.

But you can also use the existing route and decorate the controller: `sylius.controller.payment_method_notify`
to handle the NON `Payment` related Stripe events on the same URL.

Using both cases, you will have to:

1. Create a controller.
2. Use `flux_se.sylius_stripe.stripe.resolver.event_resolver` to verify the Stripe sent payload contained in the `Request` content.
3. Send a `Command` to the `CommandBus` with at least the Stripe `Event` id and the `PaymentMethod` code.
