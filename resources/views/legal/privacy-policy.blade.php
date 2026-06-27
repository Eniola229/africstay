<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — AfricStay</title>
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
    <h1 class="fw-bold mb-3" style="font-size:32px;">Privacy Policy</h1>
    <p>
        This Privacy Policy explains how AfricStay ("AfricStay", "we", "us", "our"), a product of
        <strong>AFRICGEM INTERNATIONAL COMPANY LIMITED</strong>, collects, uses, shares and protects
        information when hotels, their staff, and their guests use the AfricStay platform
        ("Service"). By using AfricStay, you agree to the collection and use of information as
        described in this policy.
    </p>

    <h2>1. Information We Collect</h2>
    <p>We collect the following categories of information:</p>
    <ul>
        <li><strong>Hotel account information:</strong> hotel name, address, phone number, email, logo, subscription tier, and billing history.</li>
        <li><strong>Staff information:</strong> name, email and/or phone number, role, and login activity for owners, managers, receptionists, cashiers, housekeepers, room service staff and accountants.</li>
        <li><strong>Guest information:</strong> name, phone number and/or email (where provided), government ID type and number at check-in, and booking/stay history. Guests are not required to create an AfricStay account themselves — their information is entered by hotel staff or submitted directly through a hotel's public booking page.</li>
        <li><strong>Payment information:</strong> transaction references, amounts, and payment status. AfricStay does not directly store full card numbers or bank account credentials — these are processed by our payment partners, Flutterwave and Paystack, under their own security standards.</li>
        <li><strong>Usage data:</strong> IP address, browser/device information, pages visited, and actions taken within the platform, used for security, audit logging and improving the Service.</li>
    </ul>

    <h2>2. How We Use Information</h2>
    <ul>
        <li>To provide core functionality: bookings, check-in/check-out, payments, housekeeping, room service, reporting, and staff management.</li>
        <li>To send transactional notifications (booking confirmations, payment receipts, check-in details, staff invites) via SMS and email.</li>
        <li>To process subscription billing and guest payments through our payment partners.</li>
        <li>To maintain security and audit logs of actions taken within a hotel's account and within the AfricStay platform admin panel.</li>
        <li>To respond to support requests sent to support@africstayhms.com.</li>
        <li>To improve, maintain, and develop new features for the Service.</li>
    </ul>

    <h2>3. Who We Share Information With</h2>
    <p>We share information only as needed to operate the Service, with the following categories of third parties:</p>
    <ul>
        <li><strong>Payment processors:</strong> Flutterwave and Paystack, to process subscription payments, guest payments, and withdrawals.</li>
        <li><strong>SMS provider:</strong> Termii, to deliver booking, payment, and account notifications by text message.</li>
        <li><strong>Email provider:</strong> Brevo, to deliver transactional emails.</li>
        <li><strong>Media storage:</strong> Cloudinary, to host hotel logos and room photos/videos uploaded by hotel staff.</li>
        <li><strong>Legal and safety reasons:</strong> where required by law, to protect the rights, property, or safety of AfricStay, our users, or the public.</li>
    </ul>
    <p>We do not sell personal information to third parties.</p>

    <h2>4. Data Retention</h2>
    <p>
        We retain hotel, staff, and guest information for as long as a hotel's account remains active,
        and for a reasonable period after closure to comply with financial record-keeping obligations,
        resolve disputes, and enforce our agreements. Activity logs and financial records (bookings,
        payments, withdrawals) are retained rather than hard-deleted, consistent with standard
        hospitality and financial record-keeping practice.
    </p>

    <h2>5. Data Security</h2>
    <p>
        We apply reasonable technical and organizational measures to protect information, including
        encrypted password storage, role-based access controls, rate-limited login attempts, and
        signature verification on payment webhooks. No method of electronic storage or transmission is
        completely secure, and we cannot guarantee absolute security.
    </p>

    <h2>6. Your Rights</h2>
    <p>
        Hotel owners and staff can review and update their information from within their AfricStay
        account settings. Guests who wish to have their information corrected or removed from a
        hotel's records should contact that hotel directly, or reach AfricStay at support@africstayhms.com and
        we will assist in directing the request appropriately. Depending on your location, you may have
        additional rights under applicable data protection law (such as Nigeria's Data Protection Act),
        including the right to request access to, correction of, or deletion of your personal data.
    </p>

    <h2>7. Cookies</h2>
    <p>
        AfricStay uses session cookies necessary to keep you logged in and to protect against
        cross-site request forgery. We do not currently use third-party advertising or tracking
        cookies.
    </p>

    <h2>8. Children's Privacy</h2>
    <p>
        AfricStay is intended for use by hotel businesses and adult guests. We do not knowingly collect
        information from children, except where a guest's accompanying minor's name is recorded as part
        of standard hotel registration practice at the hotel's discretion.
    </p>

    <h2>9. Changes to This Policy</h2>
    <p>
        We may update this Privacy Policy from time to time. Material changes will be reflected by
        updating the "Last updated" date above. Continued use of the Service after changes take effect
        constitutes acceptance of the revised policy.
    </p>

    <h2>10. Contact Us</h2>
    <p>
        If you have questions about this Privacy Policy or how your information is handled, contact us
        at <a href="mailto:support@africstayhms.com">support@africstayhms.com</a>.
    </p>

    <p class="text-muted fs-13 mt-5">
        This document is a general template provided for convenience and does not constitute legal
        advice. We recommend having it reviewed by a qualified lawyer familiar with applicable data
        protection law before relying on it for your business.
    </p>
</div>

<footer class="legal-footer text-center">
    <div class="container">
        &copy; {{ date('Y') }} AfricStay — A product of AFRICGEM INTERNATIONAL COMPANY LIMITED.
    </div>
</footer>
</body>
</html>