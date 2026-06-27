<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 — Too Many Requests | AfricStay</title>
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
            color: #0a3622;
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
            max-width: 380px;
            line-height: 1.7;
            margin: 0 auto 32px;
        }
        .btn-wait { 
            background: #0a3622;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 12px 28px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-wait:hover { 
            opacity: 0.85;
        }
        .btn-wait:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .countdown { 
            font-size: 13px;
            color: #6a8a6a;
            margin-top: 16px;
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
        <div class="err-code">429</div>
        <h1 class="err-title">Slow Down a Little</h1>
        <p class="err-sub">You've made too many requests in a short time. Please wait a moment before trying again.</p>
        <button class="btn-wait" onclick="location.reload()" id="retryBtn" disabled>
            ↻ Retry in <span id="countdown">30</span>s
        </button>
        <div class="divider"></div>
        <p class="reason">AfricStay — Hotel management, simplified</p>
    </div>
    <script>
        let t = 30;
        const btn = document.getElementById('retryBtn');
        const cd  = document.getElementById('countdown');
        const timer = setInterval(() => {
            t--;
            cd.textContent = t;
            if (t <= 0) {
                clearInterval(timer);
                btn.disabled = false;
                btn.innerHTML = '↻ Try Again';
            }
        }, 1000);
    </script>
</body>
</html>