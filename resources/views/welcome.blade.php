<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AfricStay — Hotel & Guesthouse Management System for Africa. Bookings, payments, staff and reports, all in one place.">
    <title>AfricStay — Hotel & Guesthouse Management System</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('dashboard/assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/africstay-theme.css') }}">
    <style>
        * { scroll-behavior: smooth; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            color: #0f172a;
            background: #ffffff;
            line-height: 1.6;
        }

        a { text-decoration: none; }

        /* ===== NAVIGATION ===== */
        .navbar-pro {
            padding: 12px 0;
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 999;
            transition: all 0.3s ease;
        }

        .navbar-pro .brand {
            font-weight: 700;
            font-size: 20px;
            color: #0f172a;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-pro .brand img {
            height: 28px;
            width: auto;
        }

        .navbar-pro .brand span {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-pro .nav-item {
            color: #4b5563;
            font-weight: 500;
            font-size: 14px;
            margin: 0 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .navbar-pro .nav-item:hover {
            color: #10b981;
        }

        .btn-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
            color: #fff;
        }

        .btn-white {
            background: transparent;
            border: 1.5px solid #e5e7eb;
            color: #0f172a;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-white:hover {
            border-color: #10b981;
            color: #10b981;
        }

        /* ===== HERO ===== */
        .hero-section {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #ffffff 100%);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 24px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .hero-title {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 24px;
            color: #0f172a;
            letter-spacing: -1.5px;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 20px;
            color: #475569;
            line-height: 1.7;
            max-width: 600px;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 60px;
            flex-wrap: wrap;
        }

        .hero-buttons .btn-green,
        .hero-buttons .btn-white {
            padding: 14px 32px;
            font-size: 15px;
            border-radius: 10px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            max-width: 600px;
        }

        .stat-item h3 {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .stat-item p {
            font-size: 13px;
            color: #7c8fa3;
            font-weight: 500;
        }

        .hero-image {
            position: relative;
        }

        .hero-image-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* ===== TRUST ===== */
        .trust-section {
            padding: 40px 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .trust-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .trust-avatars {
            display: flex;
            align-items: center;
        }

        .trust-avatars .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            margin-left: -14px;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .trust-avatars .avatar:first-child {
            margin-left: 0;
        }

        .trust-text {
            color: #1f2937;
            font-weight: 600;
        }

        .trust-text span {
            color: #9ca3af;
        }

        /* ===== PROBLEMS ===== */
        .problems-section {
            padding: 100px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-label {
            display: inline-block;
            color: #10b981;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 42px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            letter-spacing: -0.8px;
        }

        .section-subtitle {
            font-size: 18px;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .problems-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .problem-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .problem-card:hover {
            border-color: #10b981;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.1);
            transform: translateY(-8px);
        }

        .problem-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .problem-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .problem-card p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* ===== FEATURES ===== */
        .features-section {
            padding: 100px 0;
            background: #f9fafb;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.08);
            border-color: #10b981;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #0369a1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .feature-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* ===== HOW IT WORKS ===== */
        .how-section {
            padding: 100px 0;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 40px;
        }

        .step-card {
            display: flex;
            flex-direction: column;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .step-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .step-card p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* ===== PRICING ===== */
        .pricing-section {
            padding: 100px 0;
            background: #f9fafb;
        }

        .pricing-toggle {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 60px;
            background: white;
            border-radius: 12px;
            padding: 6px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid #e5e7eb;
        }

        .pricing-toggle button {
            background: transparent;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pricing-toggle button.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 16px;
            border: 2px solid #e5e7eb;
            padding: 40px;
            transition: all 0.3s ease;
            position: relative;
        }

        .pricing-card.popular {
            border-color: #10b981;
            transform: scale(1.05);
            box-shadow: 0 30px 60px rgba(16, 185, 129, 0.15);
        }

        .pricing-badge {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .pricing-card h3 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .pricing-card p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 24px;
            min-height: 40px;
        }

        .pricing-price {
            font-size: 48px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .pricing-price span {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 32px 0;
        }

        .pricing-features li {
            padding: 12px 0;
            color: #4b5563;
            font-size: 15px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features li i {
            color: #10b981;
            font-size: 18px;
        }

        .pricing-card button {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .pricing-card button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .pricing-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
        }

        /* ===== FAQ ===== */
        .faq-section {
            padding: 100px 0;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .accordion-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .accordion-button {
            padding: 20px 24px;
            border: none;
            background: white;
            color: #0f172a;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .accordion-button:hover {
            color: #10b981;
        }

        .accordion-button.active {
            color: #10b981;
            background: #f0fdf4;
        }

        .accordion-body {
            padding: 24px;
            color: #6b7280;
            line-height: 1.7;
            font-size: 15px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        /* ===== CTA ===== */
        .cta-section {
            padding: 100px 0;
        }

        .cta-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 20px;
            padding: 80px 60px;
            text-align: center;
            color: white;
        }

        .cta-box h2 {
            font-size: 44px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.8px;
        }

        .cta-box p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== FOOTER ===== */
        .footer-pro {
            background: #0f172a;
            color: rgba(255, 255, 255, 0.6);
            padding: 60px 0 24px;
        }

        .footer-pro h5 {
            color: white;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .footer-pro a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            display: block;
            margin-bottom: 12px;
            transition: color 0.3s ease;
        }

        .footer-pro a:hover {
            color: #10b981;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 24px;
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 40px; }
            .hero-section { padding: 80px 0 50px; }
            .section-title { font-size: 32px; }
            .problems-section,
            .features-section,
            .how-section,
            .pricing-section,
            .faq-section { padding: 60px 0; }
            .pricing-card.popular { transform: scale(1); }
            .cta-box { padding: 50px 30px; }
            .cta-box h2 { font-size: 32px; }
            .hero-buttons { flex-direction: column; }
            .hero-buttons .btn-green,
            .hero-buttons .btn-white { width: 100%; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-pro d-flex align-items-center justify-content-between px-4">
    <div class="d-flex align-items-center flex-grow-1">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('dashboard/assets/images/favicon.png') }}" alt="AfricStay">
            <span>AfricStay</span>
        </a>
    </div>

    <div class="d-none d-lg-flex align-items-center flex-grow-1 justify-content-center">
        <a href="#features" class="nav-item">Features</a>
        <a href="#pricing" class="nav-item">Pricing</a>
        <a href="#how" class="nav-item">How It Works</a>
        <a href="#faq" class="nav-item">FAQ</a>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('login') }}" class="btn-white d-none d-md-block">Sign In</a>
        <a href="{{ route('register') }}" class="btn-green">Register Free</a>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="feather-map-pin"></i> Built for African Hotels
                    </span>

                    <h1 class="hero-title">
                        Run your hotel.<br>Stop losing money<br>to <span class="gradient-text">guesswork.</span>
                    </h1>

                    <p class="hero-subtitle">
                        AfricStay is the all-in-one booking, payments and management system for small and mid-sized hotels and guesthouses. No more cash leakage, double bookings, or wondering what your hotel actually made last week.
                    </p>

                    <div class="hero-image">
                        <div class="hero-image-box">
                            <img src="{{ asset('dashboard/assets/images/dashboard.png') }}" alt="AfricStay Dashboard" style="width: 100%; height: auto; border-radius: 12px;">
                        </div>
                    </div>

                    <div class="hero-stats">
                        <div class="stat-item">
                            <h3>₦20k</h3>
                            <p>Starting/month</p>
                        </div>
                        <div class="stat-item">
                            <h3>15 min</h3>
                            <p>To go live</p>
                        </div>
                        <div class="stat-item">
                            <h3>0.75%</h3>
                            <p>Pro fee</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-image">
                    <div class="hero-image-box">
                        <div style="aspect-ratio: 16/10; background: linear-gradient(135deg, #e5e7eb, #d1d5db); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            Dashboard Preview
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST SECTION -->
<div class="trust-section">
    <div class="container">
        <div class="trust-content">
            <div class="trust-avatars">
                <div class="avatar">GL</div>
                <div class="avatar">RE</div>
                <div class="avatar">OK</div>
                <div class="avatar">+</div>
            </div>
            <div class="trust-text">
                Trusted by <strong>100+ hotels</strong> <span>across Africa</span>
            </div>
        </div>
    </div>
</div>

<!-- PROBLEMS SECTION -->
<section class="problems-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">The Challenge</span>
            <h2 class="section-title">Does this sound like your hotel?</h2>
        </div>

        <div class="problems-grid">
            <div class="problem-card">
                <div class="problem-icon" style="background: #fee2e2; color: #dc2626;">
                    <i class="feather-alert-circle"></i>
                </div>
                <h4>Cash Leakage</h4>
                <p>Payments collected in cash with zero accountability. Money just... disappears.</p>
            </div>

            <div class="problem-card">
                <div class="problem-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="feather-calendar"></i>
                </div>
                <h4>Double Bookings</h4>
                <p>Two guests. One room. Same night. Your staff caught in the middle.</p>
            </div>

            <div class="problem-card">
                <div class="problem-icon" style="background: #dbeafe; color: #0284c7;">
                    <i class="feather-eye-off"></i>
                </div>
                <h4>No Visibility</h4>
                <p>You find out how the month went when it's already over—or never.</p>
            </div>

            <div class="problem-card">
                <div class="problem-icon" style="background: #e0f2fe; color: #0369a1;">
                    <i class="feather-wifi-off"></i>
                </div>
                <h4>Offline to Bookings</h4>
                <p>Guests can't find you or book online. It's walk-ins only or nothing.</p>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Capabilities</span>
            <h2 class="section-title">Everything your hotel needs</h2>
            <p class="section-subtitle">One integrated platform. Front desk to finances. No more spreadsheets or juggling apps.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-calendar"></i>
                </div>
                <h4>Smart Bookings</h4>
                <p>Walk-in or online. Auto double-booking prevention. Guests get a unique payment account at check-in.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-credit-card"></i>
                </div>
                <h4>Payments & Wallet</h4>
                <p>Every guest payment lands in your hotel wallet instantly. Withdraw to bank anytime. 0.75% fee on Pro.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-home"></i>
                </div>
                <h4>Room Management</h4>
                <p>Visual status board. Photos & videos per room. Maintenance blocking. All from one screen.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-users"></i>
                </div>
                <h4>Staff Roles</h4>
                <p>Owner, manager, receptionist, cashier, housekeeper, accountant. Everyone sees only what they need.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-clipboard"></i>
                </div>
                <h4>Housekeeping</h4>
                <p>Auto-assigned tasks on checkout. Clean → inspect → available. Zero room falls through the cracks.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-bar-chart-2"></i>
                </div>
                <h4>Real Reports</h4>
                <p>Daily revenue. Occupancy. Outstanding balances. Full P&L. Exportable PDF. No guessing.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-globe"></i>
                </div>
                <h4>Booking Page</h4>
                <p>Your own public page. Guests search, check availability, pay deposit. No external website needed.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-bell"></i>
                </div>
                <h4>SMS & Email</h4>
                <p>Every booking, payment, check-in auto-sent. Fallback system means nothing slips through.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="feather-coffee"></i>
                </div>
                <h4>Room Service Menu</h4>
                <p>Chargeable items. Orders auto-add to guest bill. Simple commission tracking.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section" id="how">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Onboarding</span>
            <h2 class="section-title">Live in 15 minutes</h2>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h4>Register Your Hotel</h4>
                <p>Name, hotel name, phone. No card required to start exploring.</p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h4>Add Your Rooms</h4>
                <p>Room types, prices, photos. Or skip and come back later.</p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h4>Choose a Plan</h4>
                <p>Starter, Growth, or Pro. Monthly or yearly (save 20%).</p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <h4>Start Taking Bookings</h4>
                <p>Walk-ins from day one. Turn on online booking whenever you're ready.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING SECTION -->
<section class="pricing-section" id="pricing">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Plans</span>
            <h2 class="section-title">Transparent pricing, no surprises</h2>
            <p class="section-subtitle">All plans include online booking page + virtual account payments. Pick monthly or yearly.</p>
        </div>

        <div class="pricing-toggle">
            <button class="active" data-toggle="monthly">Monthly</button>
            <button data-toggle="yearly">Yearly <span style="font-size:11px; margin-left:4px; opacity:0.8;">(Save 20%)</span></button>
        </div>

        <div class="pricing-grid">
            <!-- STARTER -->
            <div class="pricing-card">
                <h3>Starter</h3>
                <p>Perfect for guesthouses getting organized.</p>
                <div class="pricing-price">₦20,000<span>/month</span></div>

                <button class="btn-green">Get Started</button>

                <ul class="pricing-features">
                    <li><i class="feather-check"></i> Up to 15 rooms</li>
                    <li><i class="feather-check"></i> 1 location</li>
                    <li><i class="feather-check"></i> 2 staff logins</li>
                    <li><i class="feather-check"></i> Room management</li>
                    <li><i class="feather-check"></i> Online bookings</li>
                    <li><i class="feather-check"></i> Daily reports</li>
                    <li><i class="feather-check"></i> Email support</li>
                </ul>
            </div>

            <!-- GROWTH -->
            <div class="pricing-card popular">
                <span class="pricing-badge">Most Popular</span>
                <h3>Growth</h3>
                <p>For hotels ready to scale operations.</p>
                <div class="pricing-price">₦40,000<span>/month</span></div>

                <button class="btn-green">Get Started</button>

                <ul class="pricing-features">
                    <li><i class="feather-check"></i> Up to 50 rooms</li>
                    <li><i class="feather-check"></i> 1 location</li>
                    <li><i class="feather-check"></i> 10 staff logins</li>
                    <li><i class="feather-check"></i> Everything in Starter</li>
                    <li><i class="feather-check"></i> Housekeeping tasks</li>
                    <li><i class="feather-check"></i> Room service menu</li>
                    <li><i class="feather-check"></i> Full financial reports</li>
                    <li><i class="feather-check"></i> SMS notifications</li>
                    <li><i class="feather-check"></i> Priority support</li>
                </ul>
            </div>

            <!-- PRO -->
            <div class="pricing-card">
                <h3>Pro</h3>
                <p>For multi-location hotels at scale.</p>
                <div class="pricing-price">₦80,000<span>/month</span></div>

                <button class="btn-green">Contact Sales</button>

                <ul class="pricing-features">
                    <li><i class="feather-check"></i> Unlimited rooms</li>
                    <li><i class="feather-check"></i> 3 locations</li>
                    <li><i class="feather-check"></i> Unlimited staff</li>
                    <li><i class="feather-check"></i> Everything in Growth</li>
                    <li><i class="feather-check"></i> Multi-location dashboard</li>
                    <li><i class="feather-check"></i> Advanced analytics</li>
                    <li><i class="feather-check"></i> Branded booking page</li>
                    <li><i class="feather-check"></i> API access</li>
                    <li><i class="feather-check"></i> Account manager</li>
                    <li><i class="feather-check"></i> Phone support</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" id="faq">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Questions</span>
            <h2 class="section-title">Commonly asked</h2>
        </div>

        <div class="faq-container">
            <div class="accordion-item">
                <button class="accordion-button active">
                    Do I need a bank account or POS to start?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="accordion-body" style="display: block;">
                    No. Every guest gets a unique virtual account at check-in. Payments land in your AfricStay wallet, which you can withdraw to any bank account anytime.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-button">
                    What if a guest has no phone or email?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="accordion-body">
                    AfricStay falls back to your hotel's phone and email. Your receptionist can relay details manually, and receipts are always printed as a physical backup.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-button">
                    Can I upgrade or downgrade plans?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="accordion-body">
                    Absolutely. Change anytime from Settings. We'll give you a heads-up if you approach your room or staff limits.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-button">
                    Is there a free trial?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="accordion-body">
                    Register and explore free. A paid subscription unlocks bookings, payments, and the full dashboard. No surprises.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-button">
                    Which payment providers do you use?
                    <i class="feather-chevron-down"></i>
                </button>
                <div class="accordion-body">
                    Flutterwave is primary, with Paystack as an automatic fallback. If Flutterwave is ever down, payments still work.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Ready to take control of your hotel?</h2>
            <p>Set up in 15 minutes. No card needed to register. Start today.</p>
            <a href="{{ route('register') }}" class="btn-green">Register Your Hotel Free</a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer-pro">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <h5>Product</h5>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#how">How It Works</a>
                <a href="#faq">FAQ</a>
            </div>

            <div class="col-md-3">
                <h5>Company</h5>
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms of Service</a>
                <a href="mailto:support@africstayhms.com">Contact</a>
            </div>

            <div class="col-md-3">
                <h5>Account</h5>
                <a href="{{ route('register') }}">Register</a>
                <a href="{{ route('login') }}">Sign In</a>
                <a href="{{ route('platform.login') }}">Admin Portal</a>
            </div>

            <div class="col-md-3">
                <h5>Support</h5>
                <a href="mailto:support@africstayhms.com">Email Support</a>
                <a href="https://wa.me/2348000000000">WhatsApp</a>
                <a href="#">Status Page</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; 2024 AfricStay. All rights reserved.</span>
            <span>A product of AFRICGEM INTERNATIONAL COMPANY LIMITED</span>
        </div>
    </div>
</footer>

<script src="{{ asset('dashboard/assets/vendors/js/vendors.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accordion
    const buttons = document.querySelectorAll('.accordion-button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            document.querySelectorAll('.accordion-body').forEach(body => body.style.display = 'none');
            document.querySelectorAll('.accordion-button').forEach(btn => btn.classList.remove('active'));
            
            if (!isActive) {
                this.classList.add('active');
                this.nextElementSibling.style.display = 'block';
            }
        });
    });

    // Pricing toggle
    document.querySelectorAll('.pricing-toggle button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pricing-toggle button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
</body>
</html>