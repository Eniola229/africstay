<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — AfricStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('dashboard/assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/africstay-theme.css') }}">
    <style>
        body { color: #1b2631; }
        .legal-nav { padding: 18px 0; border-bottom: 1px solid #eee; }
        .legal-content { max-width: 760px; margin: 0 auto; padding: 50px 20px 80px; }
        .legal-content h2 { font-size: 22px; font-weight: 800; margin-top: 40px; margin-bottom: 12px; }
        .legal-content h2:first-of-type { margin-top: 0; }
        .legal-content p, .legal-content li { color: #495057; font-size: 15px; line-height: 1.7; }
        .legal-content ul { padding-left: 20px; }
        .legal-footer { background: #14201a; color: rgba(255,255,255,.6); padding: 30px 0; font-size: 13px; }
    </style>
</head>
<body>

<nav class="legal-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="{{ asset('dashboard/assets/images/favicon.png') }}" style="height:28px;" alt="AfricStay">
            <strong style="color:#1b2631;">AfricStay</strong>
        </a>
        <a href="{{ route('home') }}" class="btn btn-outline-dark btn-sm">Back to Home</a>
    </div>
</nav>

<div class="legal-content">
    <p class="text-muted fs-13">Last updated: {{ now()->format('jS F Y') }}</p>
    <h1 class="fw-bold mb-3" style="font-size:32px;">Terms of Service</h1>
    <p>
        These Terms of Service ("Terms") govern access to and use of AfricStay (the "Service"), a
        hotel and guesthouse management platform operated by <strong>AFRICGEM INTERNATIONAL COMPANY
        LIMITED</strong> ("AfricStay", "we", "us", "our"). By registering for or using AfricStay, you
        ("you", "your hotel") agree to be bound by these Terms. If you do not agree, do not use the
        Service.
    </p>

    <h2>1. The Service</h2>
    <p>
        AfricStay provides hotel and guesthouse management tools including room management, bookings,
        check-in/check-out, guest payment collection via virtual accounts, an online booking page,
        staff role management, housekeeping and room service tracking, reporting, and related features
        ("Service"). Features available depend on your selected subscription tier.
    </p>

    <h2>2. Accounts and Registration</h2>
    <ul>
        <li>You must provide accurate information when registering your hotel, including a valid phone number used as your hotel's fallback contact.</li>
        <li>You are responsible for maintaining the confidentiality of your login credentials and for all activity that occurs under your account.</li>
        <li>The hotel owner is responsible for the conduct of all staff accounts they create or invite, including ensuring staff use the Service appropriately and within their assigned role.</li>
        <li>You must be legally authorized to operate the hotel or guesthouse you register on AfricStay.</li>
    </ul>

    <h2>3. Subscription Plans and Billing</h2>
    <ul>
        <li>AfricStay is offered on a subscription basis across Starter, Growth, Pro, and Enterprise tiers, each with different feature sets, room/staff/location limits, and transaction fee rates, as described on our pricing page.</li>
        <li>Subscriptions may be billed monthly or annually. Annual billing is offered at a 20% discount to the equivalent monthly cost.</li>
        <li>Payment is required to activate or renew a subscription. We use Flutterwave as our primary payment processor, with Paystack as an automatic fallback.</li>
        <li>A short grace period applies after a subscription's paid period ends, during which access continues with a renewal reminder displayed. After the grace period, access to operational features is suspended until the subscription is renewed.</li>
        <li>Fees are not refundable except where required by law or expressly stated otherwise.</li>
        <li>Enterprise pricing is custom and arranged directly with our sales team.</li>
    </ul>

    <h2>4. Transaction Fees</h2>
    <p>
        In addition to subscription fees, AfricStay deducts a percentage-based transaction fee from
        each confirmed guest payment processed through the platform, at the rate applicable to your
        subscription tier (currently 1.5% on Starter, 1.0% on Growth, 0.75% on Pro, and a negotiated
        rate on Enterprise). The net amount, after this fee, is credited to your hotel wallet.
    </p>

    <h2>5. Withdrawals</h2>
    <p>
        Hotel owners may request withdrawal of their wallet balance to a linked bank account, subject
        to a minimum withdrawal amount (currently ₦10,000) and successful processing by our payment
        partners. AfricStay is not responsible for delays caused by banking infrastructure or
        third-party payment processors outside our control.
    </p>

    <h2>6. Guest Data and Your Responsibilities</h2>
    <ul>
        <li>You are responsible for the accuracy of guest information entered into the Service and for obtaining any consents required under applicable law to collect and process that information.</li>
        <li>You must comply with all applicable laws regarding guest registration, identification, data protection, and hospitality regulation in your jurisdiction.</li>
        <li>AfricStay acts as a data processor on your behalf for guest information you input into the Service; you remain the data controller responsible for your guests' information.</li>
    </ul>

    <h2>7. Acceptable Use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Service for any unlawful purpose or in violation of any applicable law or regulation.</li>
        <li>Attempt to gain unauthorized access to any part of the Service, including other hotels' accounts or the platform admin panel.</li>
        <li>Interfere with or disrupt the integrity or performance of the Service.</li>
        <li>Use the Service to process payments for purposes unrelated to legitimate hotel/guesthouse operations.</li>
    </ul>

    <h2>8. Online Booking Page</h2>
    <p>
        Hotels using the online booking feature are responsible for the accuracy of room descriptions,
        pricing, and availability shown to guests, and for honoring confirmed bookings made and paid
        for through the platform, subject to their own cancellation and refund policies communicated to
        guests.
    </p>

    <h2>9. Third-Party Services</h2>
    <p>
        The Service relies on third-party providers, including Flutterwave and Paystack (payments),
        Termii (SMS), Brevo (email), and Cloudinary (media storage). AfricStay is not responsible for
        outages, errors, or losses caused by these third-party services, though we maintain automatic
        fallback between payment providers where possible to reduce disruption.
    </p>

    <h2>10. Suspension and Termination</h2>
    <p>
        We may suspend or terminate access to the Service for non-payment, violation of these Terms,
        fraudulent activity, or at our reasonable discretion to protect the security or integrity of
        the platform. You may stop using the Service at any time; subscription fees already paid are
        non-refundable except as required by law.
    </p>

    <h2>11. Limitation of Liability</h2>
    <p>
        To the maximum extent permitted by law, AfricStay and AFRICGEM INTERNATIONAL COMPANY LIMITED
        shall not be liable for indirect, incidental, special, or consequential damages arising from
        your use of the Service, including but not limited to loss of revenue, data, or business
        opportunities, even if advised of the possibility of such damages. Our aggregate liability for
        any claim relating to the Service shall not exceed the subscription fees paid by you in the
        three (3) months preceding the claim.
    </p>

    <h2>12. Changes to These Terms</h2>
    <p>
        We may update these Terms from time to time. Material changes will be reflected by updating
        the "Last updated" date above, and significant changes may be communicated by email. Continued
        use of the Service after changes take effect constitutes acceptance of the revised Terms.
    </p>

    <h2>13. Governing Law</h2>
    <p>
        These Terms are governed by the laws of the Federal Republic of Nigeria, without regard to
        conflict of law principles, without prejudice to any mandatory consumer protection laws that
        may apply in your jurisdiction.
    </p>

    <h2>14. Contact Us</h2>
    <p>
        Questions about these Terms can be sent to <a href="mailto:support@africstayhms.com">support@africstayhms.com</a>.
    </p>

    <p class="text-muted fs-13 mt-5">
        This document is a general template provided for convenience and does not constitute legal
        advice. We recommend having it reviewed by a qualified lawyer familiar with applicable law
        before relying on it for your business.
    </p>
</div>

<footer class="legal-footer text-center">
    <div class="container">
        &copy; {{ date('Y') }} AfricStay — A product of AFRICGEM INTERNATIONAL COMPANY LIMITED.
    </div>
</footer>
</body>
</html>