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

1. Copy `app/`, `config/`, `database/migrations/`, `resources/views/`, and
   `routes/api.php` into your project (these are new files — nothing here
   overwrites Orderer-style files, since this is a separate `AfricStay` app).
2. **Composer packages this drop assumes but doesn't install** (no internet
   access to run composer in the environment this was built in):
   ```
   composer require laravel/sanctum
   composer require barryvdh/laravel-dompdf
   ```
   Sanctum powers the Pro-tier API tokens (Phase 5); DomPDF powers the PDF
   report exports (Phase 4). Everything else (CSV export, SMS/email, payments)
   only uses Laravel's built-in `Http` facade — no extra packages needed.
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

---

## Phase 3 — Online Booking

The public, unauthenticated booking page at `/hotel/{slug}` — no login, no
guard, this is what a guest sees when a hotel shares their link.

### How it reuses Phase 2 instead of duplicating it
Rather than build a parallel payment system, the online flow plugs into
exactly what Phase 2 already has:

- **Same overlap check.** `Booking::hasOverlap()` is the one and only
  double-booking guard, used identically by staff walk-in bookings and the
  public availability search — one rule, no drift between the two.
- **Booking-as-reservation.** An online booking is created with
  `status: pending` the moment the guest submits the form — that alone
  reserves the room, since `hasOverlap()` treats `pending` as active. No
  separate "soft lock" table was needed.
- **Same webhook, same reference prefix.** `OnlineBookingPaymentService`
  generates an `AFS-PAY-...` reference exactly like the check-in virtual
  account does, so the existing `FlutterwaveWebhookController`/
  `PaystackWebhookController` routing needed **zero changes** — it already
  forwards anything starting `AFS-PAY-` to `GuestPaymentConfirmationService`.
- **One service, two branches.** `GuestPaymentConfirmationService::confirm()`
  now checks whether the booking was still `pending` (online deposit — flips
  it to `confirmed` and sends a "booking confirmed" notification + notifies
  the hotel of the new booking) versus already `checked_in` (in-person
  payment — sends "payment received" instead). Same idempotency guarantee
  either way.

### What's actually on the public page
- Hero with hotel name/city, an availability widget (dates + guest count +
  room type → live AJAX search), room-type cards with photos and starting
  price, and a booking modal that shows the computed deposit amount before
  the guest commits.
- **At least one of phone or email is required** — enforced both with
  inline JS guidance and a server-side check, with the exact validation
  message from the spec.
- Picking a room → submitting → redirected straight to Flutterwave/Paystack
  hosted checkout for the deposit. Confirmation page (`/hotel/{slug}/booking/{reference}`)
  is screenshot-friendly, per spec.
- SEO: `meta_title`/`meta_description` are editable per hotel (Settings page),
  with sensible fallbacks if left blank.

### New: a Settings page
Nothing in Phase 1/2 actually let an owner reach `online_booking_enabled` or
`online_booking_deposit_percent`, so this phase adds a minimal **Hotel →
Settings** screen: hotel profile (name/phone/email/address/logo) and online
booking config (on/off toggle, deposit % with the spec's 50% default,
SEO fields), plus a copy-able link to the public page and a one-click
preview. Owner-only, like withdrawals.

### Abandoned-checkout cleanup
Because a `pending` online booking blocks its room immediately, a guest who
closes the payment tab without paying would otherwise hold that room
forever. `bookings:expire-stale-pending` (new scheduled command, runs every
30 minutes) cancels any online booking still `pending` after 2 hours,
freeing the room. Configurable via `--minutes=`.

### Still open for a future pass
- The hosted checkout flow charges the **deposit only** — collecting the
  remaining balance is still the in-person flow from Phase 2
  (check-in/check-out), not a second online charge. A "pay remaining
  balance online" link isn't built.
- No availability **calendar view** (month grid) — the widget is date-range
  search only, which covers the spec's required behavior but not a visual
  calendar.

---

## Phase 4 — Operations (Housekeeping, Room Service, Reports)

### Housekeeping — the closed loop
`checkout → dirty → task assigned → cleaned → verified → available`, exactly
as the spec requires (a room can't skip steps):

- `BookingController::checkOut()` now calls `createHousekeepingTask()`, which
  assigns the new task to whichever **active housekeeper currently has the
  fewest open (non-verified) tasks** — simple load balancing, no manual
  picking needed. SMS goes to that housekeeper's phone if they have one;
  if the hotel has no housekeepers yet, the task is created unassigned and
  a manager/owner gets an SMS instead, asking them to assign someone.
- Housekeepers see only their own assigned tasks (mobile-friendly card
  list with a tappable checklist); owners/managers see the full board and
  can reassign anything.
- `verify()` is owner/manager-only and is the **only** action that flips
  the room back to `available` — matches the spec's "room becomes available
  only after housekeeping marks it verified."
- Checklists default per room type (`HousekeepingTask::defaultChecklistFor()`)
  — suites/family rooms get an extra "clean living/sitting area" item.

### Room Service & Extras
- Owner/manager sets up a chargeable menu (`hotel.room-service.items`) —
  name, category, price.
- Any active staff member can add an extra to a **checked-in** booking
  from the booking detail page; it's added to `room_service_orders` AND
  immediately added to `booking.total_amount`/`balance` in the same
  transaction — exactly the "auto-added to the guest's running bill"
  behavior in the spec, so it shows up automatically at checkout with zero
  extra wiring needed there.
- Room-service staff get their own dashboard (`hotel.room-service.orders`)
  to move orders through pending → in_progress → delivered.

### Reports
All 11 reports from the spec (5 operational, 6 financial) are built, each
accepting `?from=&to=` (defaults to this month). **CSV export** has zero
dependencies — plain `fputcsv`, opens fine in Excel/Sheets/Numbers.
**PDF export** uses `barryvdh/laravel-dompdf`, which isn't auto-installed
in this drop (no internet access to run composer here) — add it with:

```
composer require barryvdh/laravel-dompdf
```

If you try a PDF export without that package installed, you'll get a clear
500 error telling you exactly what to run, rather than a silent failure.

---

## Phase 5 — Growth & Pro Features

### Multi-location dashboard (Pro tier, up to 3 locations)
**Important design note, read this one carefully:** a "location" here is
its own `hotels` row (own rooms, own bookings, own wallet — finances stay
separate per location), linked back to the primary hotel via
`parent_hotel_id`. `Hotel → Locations` lets a Pro-tier owner add up to
`Hotel::MAX_LOCATIONS_PRO` (3) child locations and shows an **aggregated,
read-only** dashboard (rooms, occupancy, this month's revenue per location).

What this does NOT do yet: actually switching into a child location to
manage its day-to-day rooms/bookings/check-ins. Every controller in this
codebase resolves "the current hotel" as `Auth::user()->hotel` — i.e. the
hotel the logged-in user's `hotel_id` points to. Building real location
*switching* would mean either (a) a session-based "acting as location X"
flag threaded through every single controller's `hotel()` method, or
(b) separate staff logins per location. That's a meaningful architecture
decision I didn't want to make silently on your behalf — happy to build
whichever direction you prefer next.

### Tier enforcement, actually enforced
- **Room limits** were already enforced (Phase 1) in onboarding + `RoomController`.
- **Staff limits** are now enforced too — `StaffController::invite()` checks
  `Hotel::canInviteMoreStaff()` before creating the invite, not just hiding
  a button in the UI.
- **80% upgrade prompt** (spec: "shown when limit is approached") — a badge
  in the dashboard header (`Hotel::isApproachingRoomLimit()` /
  `isApproachingStaffLimit()`), separate from the subscription-expiry banner.

### Branded booking pages (Pro tier+)
One field — `brand_primary_color` — settable from Settings (color picker,
gated to `pro`/`enterprise`), which overrides the public booking page's
`--bs-primary` CSS variable and button colors. Logo branding was already
there from Phase 1 (every hotel can upload a logo); this just adds the
accent-color part the spec calls out separately for Pro.

### API access (Pro tier+)
Read-only REST API via **Laravel Sanctum** personal access tokens:
- `composer require laravel/sanctum` (not auto-installed here) — the
  `personal_access_tokens` migration is already included.
- Settings → API (Pro/Enterprise only, owner-only) generates/revokes
  tokens. Each token is scoped to abilities `read:rooms`/`read:bookings`
  and automatically resolves to the token-owner's hotel — there's no
  hotel_id parameter to pass, so a token can never be used to pull another
  hotel's data.
- `GET /api/v1/rooms`, `GET /api/v1/bookings` — that's the whole surface for
  now; more endpoints are trivial to add following the same pattern.

### Platform Admin Panel (the big one)
A full internal panel at `/platform`, separate layout (`layouts.platform.app`),
role-gated sidebar that only shows what each role can actually do:

- **Hotel Management** — list/search/filter all hotels, view full detail
  (owner, subscription status, recent payments/withdrawals), activate/
  deactivate, change tier **with a required reason that's logged** to
  `platform_activity_logs` with before/after snapshots.
- **Read-only impersonation** — this needed a real mechanism, not just a
  flag: platform admins authenticate on the `platform` guard, which has
  zero access to hotel routes (those require `auth:web`). So impersonating
  means *also* logging into the `web` guard as the hotel's owner, in the
  same browser, both sessions active simultaneously — exactly what the spec
  describes ("a platform admin can be logged into both in the same browser
  with no conflict"). `ImpersonationReadOnly` middleware then blocks every
  non-GET request while that session flag is set, and the hotel dashboard
  layout renders a black "PLATFORM VIEW — READ ONLY" banner with an Exit
  button. I caught and fixed a real bug here while building this: every
  `Auth::user()` call in the platform controllers had to be made explicitly
  `Auth::guard('platform')->user()`, since calling the guard-less version
  after an impersonation login would silently resolve to the *hotel owner*
  instead of the platform admin doing the impersonating — which would have
  broken every activity-log entry's attribution.
- **Enterprise Inquiries** — the "Contact Us" CTA on both the plan picker
  and the onboarding wizard's Enterprise branch now actually creates a row
  here (previously a `mailto:` link), with status (new/contacted/converted/
  closed), assignment to a specific admin, and internal notes.
- **Platform Revenue Reports** — subscription revenue + transaction-fee
  revenue (computed live from each hotel's tier-based fee rate, since
  there's no separate fee ledger table — see `RevenueReportController`),
  top hotels by fees generated, breakdown by tier, MRR, and a churn list
  (hotels that went expired/cancelled in the last 90 days). Finance role
  also gets withdrawal oversight across every hotel.
- **Platform Settings** (super_admin only) — create/deactivate platform
  admins, change their roles, and a full activity log viewer showing every
  action any admin has taken, with IP and timestamp.

### Still open for a future pass
- Multi-location *operational* switching (see note above).
- The platform's enterprise-inquiry/hotel-management/admin panels are
  functional but intentionally plain Bootstrap, not the AfricStay green
  template — this is the internal tool, so I prioritized function over
  matching the guest-facing brand.
- No automated tests were added anywhere in this build — everything here
  has been hand-traced for route-name/controller-method/view-variable
  consistency (and a brace-balance + PHP-lint pass), but there's no
  substitute for `php artisan migrate` plus actually clicking through it.
