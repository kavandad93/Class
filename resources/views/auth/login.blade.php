<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود | کاداد کلاس</title>
    <style>body{font-family:Tahoma,sans-serif;background:#f4f6f8;margin:0}.box{max-width:420px;margin:80px auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 8px 30px #0001}input,select,button{width:100%;box-sizing:border-box;padding:12px;margin:8px 0;border:1px solid #ddd;border-radius:10px}button{background:#111;color:#fff;cursor:pointer}.error{color:#b42318}</style>
</head>
<body><main class="box"><h1>ورود به کاداد کلاس</h1>@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif<form method="post" action="{{ route('login.store') }}">@csrf<input type="email" name="email" placeholder="ایمیل" value="{{ old('email') }}" required><input type="password" name="password" placeholder="رمز عبور" required><label><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label><button type="submit">ورود</button></form><a href="{{ route('register') }}">ساخت حساب جدید</a></main></body>
</html>
