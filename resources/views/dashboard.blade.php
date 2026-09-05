<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>داشبورد | کاداد کلاس</title><style>body{font-family:Tahoma,sans-serif;background:#f4f6f8;margin:0}.box{max-width:900px;margin:50px auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 8px 30px #0001}button{padding:10px 16px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer}.muted{color:#667085}</style></head>
<body><main class="box"><h1>داشبورد کاداد کلاس</h1><p>سلام {{ auth()->user()->name }} 👋</p><p class="muted">نقش: {{ auth()->user()->role === 'teacher' ? 'معلم' : 'دانش‌آموز' }}</p><hr><h2>فاز ۱</h2><p>احراز هویت و زیرساخت اولیه فعال است. مدیریت کلاس‌ها در فاز بعد اضافه می‌شود.</p><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">خروج</button></form></main></body>
</html>
