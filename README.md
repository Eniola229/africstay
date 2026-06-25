# AfricStay — Phase 1 Scaffold

This is Phase 1 (Auth + Onboarding) plus the subscription-billing pieces you
asked for: UUID primary keys everywhere, room photos/videos, pay-before-access
subscriptions with a 20%-off yearly option, a middleware that enforces it, and
a daily scheduled job that warns hotels before expiry.

## How the subscription flow actually works

You asked: *"do they pay before it registers them, or how does it work?"*
Here's the design, and why:

1. **Registration is free and instant.** The owner fills the form, a `Hotel`
   row + their `User` (role `owner`) are created immediately, and they're
   logged straight in. No payment friction at the door — this matches the
   spec's "self-registers" language and gets them into the product fast.
2. **Onboarding Step 1** (hotel address/phone/logo) is free — no paywall.
3. **Onboarding Step 2** (choose tier + billing cycle) is where payment is
   required. Picking Starter/Growth/Pro sends them to
   `GET /subscription/checkout?tier=...&billing_cycle=...`, which calls
   Flutterwave first, then **automatically falls back to Paystack** if
   Flutterwave's API call fails — exactly like the payment-fallback rule in
   the spec for booking payments. They're redirected to the provider's hosted
   checkout page to actually pay.
4. **Only the webhook activates anything.** `SubscriptionBillingService::confirmPayment()`
   is the single place a subscription ever becomes `active` — never the
   redirect-back page, which is just UX. This makes it idempotent: Flutterwave
   or Paystack can retry the webhook and nothing gets double-activated
   (checked via the unique `payment_reference` on `subscription_payments`).
5. Once paid, **Steps 3 (rooms) and 4 (staff invite)** unlock, and so does the
   rest of the dashboard — enforced by `EnsureSubscriptionActive` middleware
   on every `hotel.*` route except the subscription/onboarding routes
   themselves (had to exclude those or it'd be circular).
6. **Yearly billing = 20% off.** `Subscription::amountFor($tier, 'yearly')`
   computes `monthly_fee * 12 * 0.8`. This is one config constant
   (`Hotel::YEARLY_DISCOUNT_PERCENT`), so changing the discount later is a
   one-line edit, not a hunt through controllers.
7. **Enterprise has no self-serve checkout** — picking it just shows a
   "we'll contact you" message, per the spec ("no self-serve signup for this
   tier"). Converting them is a platform-admin action in the Phase 5 module.
8. **Renewals reuse the exact same checkout** — `hotel.subscription.plans` is
   reachable any time from the dashboard header, not just during onboarding.

### What happens when a subscription lapses

- `ends_at` passes → still within `Hotel::GRACE_PERIOD_DAYS` (3 days) →
  status becomes `past_due`. The hotel **keeps access** but sees a renew-now
  warning banner (dashboard header + a banner on the dashboard page itself).
- Past the grace period → status becomes `expired` → `EnsureSubscriptionActive`
  hard-locks every route except the plan picker.

This grace period exists so a slow bank transfer or a missed webhook doesn't
instantly lock someone out — you may want to shorten/lengthen it; it's the one
constant in `Hotel::GRACE_PERIOD_DAYS`.

## The expiry scheduler (the cron job you asked about)

`app/Console/Commands/CheckSubscriptionExpiry.php`, registered in
`routes/console.php` to run **daily at 08:00**:

- Sends a reminder (SMS to the hotel's phone + email to the owner, if they
  have one) at **7, 3, and 1 day(s)** before `ends_at` — each threshold fires
  exactly once per subscription (tracked via the `renewal_reminder_*_sent`
  flags), so nobody gets spammed.
- Sweeps subscriptions whose `ends_at` has already passed and flips them to
  `past_due` or `expired` as described above, also notifying the hotel when
  it actually goes `expired`.

**For this to actually run**, your server's real system cron needs the one
standard Laravel entry (this doesn't ship with a fresh project, you add it
once per server):

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## UUIDs

Every entity table (`platform_admins`, `hotels`, `users`, `subscriptions`,
`subscription_payments`, `rooms`, `room_media`, `activity_logs`,
`platform_activity_logs`) uses `$table->uuid('id')->primary()`, and every
model uses Laravel's built-in `HasUuids` trait, so `Model::create()` just
works — no manual `Str::uuid()` calls needed anywhere. Foreign keys use
`foreignUuid()` to match. The only tables that intentionally kept a normal
primary key are `password_reset_tokens` / `platform_password_reset_tokens`
(Laravel's password-broker convention — keyed by email) and `phone_otps`
(internal, never referenced by anything else) — let me know if you want those
switched too.

## Rooms with photos and videos

- `rooms` + `room_media` tables. A room has many `room_media` rows, each
  either `type: image` or `type: video`, with the **Cloudinary** `secure_url`
  and `public_id` stored.
- Uploads happen **client-side** via Cloudinary's unsigned upload widget (the
  browser talks to Cloudinary directly — nothing routes through your server
  except the final URL). You only need to create one *unsigned* upload preset
  in your Cloudinary dashboard and drop its name in `.env`
  (`CLOUDINARY_UPLOAD_PRESET`).
- Deleting a photo/video calls Cloudinary's Admin API server-side
  (`CloudinaryService::destroy()`) so the asset is actually removed from your
  Cloudinary storage, not just unlinked in the DB.
- You can add rooms (with media) two places: the **onboarding wizard step 3**
  (quick multi-room add) and the standalone **Rooms → Add Room** screen for
  later use, which also supports adding/removing media on existing rooms.

## Wiring this into your actual project

This was built as a clean, self-contained scaffold rather than edited in
place, since no existing `routes/web.php` / `config/auth.php` / models were
shared. To merge it in:

1. Copy `app/`, `config/`, `database/migrations/`, `resources/views/` into
   your project (these are new files — nothing here overwrites Orderer-style
   files, since this is a separate `AfricStay` app).
2. **`bootstrap/app.php`** — if you already have one, don't overwrite it;
   copy the `->alias([...])` block and the `validateCsrfTokens(except: [...])`
   line into your existing `->withMiddleware()` closure.
3. **`routes/web.php`** and **`routes/console.php`** — same idea, merge rather
   than overwrite if you already have routes defined.
4. Copy `.env.example` values into your real `.env` and fill in your actual
   Flutterwave/Paystack/Termii/Brevo/Cloudinary credentials.
5. `php artisan migrate`
6. Make sure the server cron line above is in place for the scheduler.

## What's intentionally not in this drop

Full Room Management UI polish (visual drag-drop status board), Housekeeping,
Room Service, Staff permissions UI, and Reports are all later phases per the
original build order — say which one you want next and I'll build it the
same way: real migrations, real controllers, views in this exact template
style.

---

## Phase 2 — Payments (added on top of Phase 1)

Phase 2 needs Bookings to exist (a virtual account has to attach to a
booking), so this drop also finishes the rest of Phase 1's "Core" scope at
the same time: **Guests, Bookings, Check-in, Check-out** — then layers
**virtual accounts, the hotel wallet, and withdrawals** on top.

### New tables
`guests`, `bookings`, `payments`, `withdrawals`, `notifications_log` — all
UUID-keyed like everything else.

### Booking flow
- `GET /guests/search` — autocomplete by name/phone for the new-booking form.
- `GET /bookings/available-rooms` — given a room type + dates, returns only
  rooms with **no overlapping active booking** (`Booking::hasOverlap()`),
  checked again server-side on submit since the list could go stale between
  page-load and form-submit.
- `POST /bookings` — creates the guest (if new) + the booking in one
  transaction, generates the `AFS-{HOTEL}-{YYYYMMDD}-{RANDOM4}` reference,
  and sends the booking-confirmed notification through the fallback resolver.
- Restricted to `owner`, `manager`, `receptionist` — matches the spec's
  staff permission table.

### The notification fallback rule, centralized
Every guest-facing notification (booking confirmed, check-in, payment
received, check-out) goes through **one** method:
`NotificationFallbackService::notify()`. It implements the spec's
`resolveGuestNotificationChannels()` pseudocode exactly — SMS if the guest
has a phone, email if they have one, and if they have **neither**, it falls
back to the hotel's own phone (always present) and email (if set), flagged
`was_fallback = true` in `notifications_log` so you can see at a glance which
notifications need a receptionist to relay information manually. Putting
this in one service (rather than repeating the if/else in every controller)
means that rule can't accidentally get skipped somewhere.

### Check-in → virtual account
`POST /bookings/{id}/check-in` flips the room to `occupied` and calls
`VirtualAccountService::generate()`, which:
1. Tries **Flutterwave** (`POST /v3/virtual-account-numbers`).
2. Falls back to **Paystack** (creates a customer, then
   `POST /dedicated_account`) if Flutterwave's call fails for any reason.
3. Stores the result on a `payments` row (`status: pending`) so the
   receptionist can read/print the account details immediately — the
   on-screen/printed display is always shown regardless of whether the SMS/
   email also went out, per the spec's "physical fallback" requirement.

### Webhook → wallet credit
Both `FlutterwaveWebhookController` and `PaystackWebhookController` now
route by **reference prefix** before doing anything:
- `AFS-SUB-...` → `SubscriptionBillingService` (subscription billing, Phase 1)
- `AFS-PAY-...` → `GuestPaymentConfirmationService` (guest booking payment)

`GuestPaymentConfirmationService::confirm()` is idempotent on
`payment_reference` (a retried webhook can't double-credit), and on success:
updates `booking.amount_paid`/`balance`, credits `hotel.wallet_balance`
**after deducting AfricStay's tier-based transaction fee**
(`Hotel::TIER_TRANSACTION_FEE_PERCENT` — 1.5%/1.0%/0.75%/0.5% by tier), and
sends the payment-received notification.

### Check-out
Validates the balance is settled (or requires an `owner`/`manager` override
with a logged reason), flips the room to `dirty` (the housekeeping
auto-task-creation hook is commented in place, ready for the Housekeeping
module), and always generates the on-screen/printable receipt regardless of
whether the guest has any contact info.

### Withdrawals
`WithdrawalService::initiate()` — owner-only, ₦10,000 minimum
(`Hotel::MIN_WITHDRAWAL_KOBO`), deducts the wallet up front, then tries
**Flutterwave Transfer API** first, **Paystack Transfer API** as fallback;
reverts the wallet deduction if both providers reject the transfer outright.
A transfer that's accepted but fails later on the provider's side would
normally be reconciled via their separate transfer-status webhook — that
reconciliation endpoint isn't wired up yet (flagged in code as Phase 2.1),
since neither provider's webhook payload for transfer status was specified.

### Still open for a future pass
- Tier-based **staff login limits** aren't enforced yet on invite (room
  limits are, via `Hotel::TIER_ROOM_LIMITS`).
- The Flutterwave/Paystack transfer-status webhook (to flip `processing` →
  `completed`/`failed` automatically) — right now `WithdrawalService::markCompleted()`
  exists but nothing calls it yet.
- Cash/manual payment recording (the spec lists `cash`/`transfer` as
  `payment_method` options on top of virtual accounts) isn't wired into the
  booking UI yet — only virtual-account checkout/confirmation is built.
