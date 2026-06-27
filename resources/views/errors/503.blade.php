<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Back Soon — AfricStay</title>
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
        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid #e8efe8;
            border-top-color: #0a3622;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .err-title { 
            font-family: 'Playfair Display', serif;
            font-size: clamp(24px, 4vw, 32px);
            margin: 0 0 12px;
            color: #0a3622;
        }
        .err-sub { 
            color: #6a8a6a;
            font-size: 15px;
            max-width: 420px;
            line-height: 1.8;
            margin: 0 auto 32px;
        }
        .status-pill {
            display: inline-block;
            background: #f0f5f0;
            border: 1px solid #e8efe8;
            border-radius: 40px;
            padding: 6px 18px;
            font-size: 13px;
            color: #6a8a6a;
            margin-bottom: 16px;
        }
        .status-pill span {
            color: #0a3622;
            font-weight: 600;
        }
        .divider { 
            width: 40px;
            height: 1px;
            background: #e8efe8;
            margin: 28px auto 16px;
        }
        .logo { 
            font-size: 18px;
            font-weight: 700;
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
        <div class="spinner"></div>
        <div class="status-pill">Status: <span>Under Maintenance</span></div>
        <h1 class="err-title">We'll Be Right Back</h1>
        <p class="err-sub">AfricStay is currently undergoing a scheduled maintenance upgrade. We'll be back online shortly — usually within a few minutes.</p>
        <div class="divider"></div>
        <p class="support-note">Urgent? Email us at <a href="mailto:support@africstayhms.com">support@africstayhms.com</a></p>
    </div>
</body>
</html>