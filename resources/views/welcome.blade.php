<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AfricStay — Hotel Management System</title>
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
</head>
<body class="d-flex align-items-center justify-content-center" style="height:100vh;background:#0f1f17;color:#fff;">
    <div class="text-center">
        <h1 class="fw-bold mb-3">AfricStay</h1>
        <p class="mb-4 text-white-50">Hotel &amp; Guesthouse Management System</p>
        <a href="{{ route('login') }}" class="btn btn-light me-2">Hotel Login</a>
        <a href="{{ route('register') }}" class="btn btn-outline-light">Register Your Hotel</a>
    </div>
</body>
</html>
