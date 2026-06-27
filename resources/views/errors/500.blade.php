<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server Error | AfricStay</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            background: #f0f5f0; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            color: #2d2d2d; 
            text-align: center; 
            padding: 40px 20px; 
        }
        .wrapper {
            max-width: 600px;
            background: #ffffff;
            border-radius: 12px;
            padding: 60px 48px 48px;
            box-shadow: 0 2px 16px rgba(10,54,34,0.08);
        }
        .err-code { 
            font-family: 'Playfair Display', serif;
            font-size: clamp(80px, 16vw, 140px);
            font-weight: 700;
            line-height: 1;
            color: #8a1a1a;
            letter-spacing: -4px;
            margin-bottom: 4px;
            opacity: 0.08;
        }
        .err-title { 
            font-family: 'Playfair Display', serif;
            font-size: clamp(24px, 4vw, 32px);
            margin: -20px 0 12px;
            color: #0a3622;
        }
        .err-sub { 
            color: #6a8a6a;
            font-size: 15px;
            max-width: 400px;
            line-height: 1.7;
            margin: 0 auto 36px;
        }
        .err-actions { 
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-retry { 
            background: #0a3622;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 12px 28px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-retry:hover { 
            opacity: 0.85;
            color: #ffffff;
        }
        .btn-home { 
            background: #f0f5f0;
            color: #2d2d2d;
            border: 1.5px solid #e8efe8;
            border-radius: 6px;
            padding: 12px 28px;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-home:hover { 
            background: #e8efe8;
        }
        .divider { 
            width: 40px;
            height: 1px;
            background: #e8efe8;
            margin: 28px auto 16px;
        }
        .logo { 
            font-size: 14px;
            font-weight: 600;
            color: #0a3622;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .support-note { 
            font-size: 12px;
            color: #6a8a6a;
            margin: 0;
        }
        .support-note a { 
            color: #0a3622;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo">AfricStay</div>
        <div class="err-code">500</div>
        <h1 class="err-title">Something Went Wrong</h1>
        <p class="err-sub">Our servers hit an unexpected snag. Our team has been notified. Please try again in a moment.</p>
        <div class="err-actions">
            <a href="javascript:location.reload()" class="btn-retry">↻ Try Again</a>
            <a href="{{ url('/') }}" class="btn-home">← Home</a>
        </div>
        <div class="divider"></div>
        <p class="support-note">If the problem persists, contact <a href="mailto:support@africstayhms.com">support@africstayhms.com</a></p>
    </div>
</body>
</html>