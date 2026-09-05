<?php

declare(strict_types=1);

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ثبت‌نام | کاداد کلاس</title>
</head>
<body>
    <main>
        <h1>ساخت حساب کاداد کلاس</h1>
        <form method="post" action="">
            <label for="name">نام</label>
            <input id="name" name="name" type="text" autocomplete="name" required>
            <label for="email">ایمیل</label>
            <input id="email" name="email" type="email" autocomplete="email" required>
            <label for="password">رمز عبور</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            <label for="password_confirmation">تکرار رمز عبور</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            <button type="submit">ثبت‌نام</button>
        </form>
        <p><a href="/login/signin.php">قبلاً حساب داری؟ ورود</a></p>
    </main>
</body>
</html>
