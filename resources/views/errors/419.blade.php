<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired | AfricStay</title>
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
        .icon { 
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #f0f5f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            border: 2px solid #e8efe8;
        }
        .icon i { 
            font-size: 28px;
            color: #0a3622;
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
            max-width: 380px;
            line-height: 1.7;
            margin: 0 auto 32px;
        }
        .btn-refresh { 
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
        .btn-refresh:hover { 
            opacity: 0.85;
            color: #ffffff;
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
        .reason { 
            font-size: 12px;
            color: #6a8a6a;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo">AfricStay</div>
        <div class="icon">
            <i>⏰</i>
        </div>
        <h1 class="err-title">Session Expired</h1>
        <p class="err-sub">Your session timed out for security. Please refresh the page and try your action again — your work is safe.</p>
        <a href="javascript:location.reload()" class="btn-refresh">↻ Reload Page</a>
        <div class="divider"></div>
        <p class="reason">AfricStay — Hotel management, simplified</p>
    </div>
</body>
</html>