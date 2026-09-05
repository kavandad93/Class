<?php

declare(strict_types=1);

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود | کاداد کلاس</title>
</head>
<body>
    <main>
        <h1>ورود به کاداد کلاس</h1>
        <form method="post" action="">
            <label for="email">ایمیل</label>
            <input id="email" name="email" type="email" autocomplete="email" required>
            <label for="password">رمز عبور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">ورود</button>
        </form>
        <p><a href="/login/signup.php">ساخت حساب</a></p>
    </main>
</body>
</html>
