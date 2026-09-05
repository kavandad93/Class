<?php

declare(strict_types=1);

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ثبت‌نام | کاداد کلاس</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--text:#101828;--muted:#667085;--primary:#635bff;--border:#e4e7ec}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Vazirmatn,Tahoma,sans-serif;color:var(--text);background:radial-gradient(circle at 15% 15%,rgba(99,91,255,.2),transparent 30%),radial-gradient(circle at 85% 80%,rgba(8,145,178,.16),transparent 28%),linear-gradient(145deg,#f8f9ff,#eef2ff);display:grid;place-items:center;padding:24px}.wrap{width:min(440px,100%)}.brand{display:flex;justify-content:center;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:900;font-size:22px;margin-bottom:22px}.logo{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8);box-shadow:0 12px 28px rgba(81,72,232,.25)}.card{background:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.9);backdrop-filter:blur(20px);border-radius:26px;padding:34px;box-shadow:0 24px 70px rgba(16,24,40,.1)}h1{margin:0 0 8px;font-size:28px;font-weight:900}.intro{margin:0 0 28px;color:var(--muted);line-height:1.9}label{display:block;font-size:14px;font-weight:700;margin:0 0 8px}input{display:block;width:100%;height:52px;border:1px solid var(--border);border-radius:14px;background:#fff;padding:0 15px;font:inherit;outline:none;margin-bottom:16px;transition:.2s}input:focus{border-color:#8b85ff;box-shadow:0 0 0 4px rgba(99,91,255,.1)}button{width:100%;height:54px;border:0;border-radius:15px;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;font:800 16px Vazirmatn;cursor:pointer;box-shadow:0 13px 28px rgba(81,72,232,.25);transition:.2s}button:hover{transform:translateY(-1px);box-shadow:0 17px 34px rgba(81,72,232,.32)}.bottom{text-align:center;margin:22px 0 0;color:var(--muted);font-size:14px}.bottom a{color:#5148e8;font-weight:800;text-decoration:none}.back{display:block;text-align:center;margin-top:18px;color:#667085;text-decoration:none;font-size:13px}@media(max-width:480px){.card{padding:27px 21px}}
    </style>
</head>
<body><div class="wrap"><a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a><main class="card"><h1>حساب خودت را بساز ✨</h1><p class="intro">در چند ثانیه عضو کاداد کلاس شو و کلاس آنلاین خودت را شروع کن.</p><form method="post" action=""><label for="name">نام</label><input id="name" name="name" type="text" autocomplete="name" placeholder="نام شما" required><label for="email">ایمیل</label><input id="email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required><label for="password">رمز عبور</label><input id="password" name="password" type="password" autocomplete="new-password" placeholder="حداقل یک رمز امن" required><label for="password_confirmation">تکرار رمز عبور</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="رمز را دوباره وارد کنید" required><button type="submit">ساخت حساب</button></form><p class="bottom">قبلاً حساب داری؟ <a href="/login/signin.php">ورود به حساب</a></p></main><a class="back" href="/home">← بازگشت به صفحه اصلی</a></div></body>
</html>
