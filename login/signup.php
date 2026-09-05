<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmation = (string)($_POST['password_confirmation'] ?? '');
        $role = in_array($_POST['role'] ?? 'student', ['student','teacher'], true) ? $_POST['role'] : 'student';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) throw new InvalidArgumentException('نام باید بین ۲ تا ۱۰۰ کاراکتر باشد.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('ایمیل معتبر نیست.');
        if (strlen($password) < 8) throw new InvalidArgumentException('رمز عبور باید حداقل ۸ کاراکتر باشد.');
        if ($password !== $confirmation) throw new InvalidArgumentException('تکرار رمز عبور یکسان نیست.');

        $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) throw new InvalidArgumentException('این ایمیل قبلاً ثبت شده است.');

        $stmt = db()->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        redirect('/panel');
    } catch (Throwable $e) {
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'ثبت‌نام انجام نشد. تنظیمات دیتابیس را بررسی کنید.';
    }
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ثبت‌نام | کاداد کلاس</title><link rel="manifest" href="/assets/manifest.json"><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--text:#101828;--muted:#667085;--border:#e4e7ec}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Vazirmatn,Tahoma,sans-serif;color:var(--text);background:radial-gradient(circle at 15% 15%,rgba(99,91,255,.2),transparent 30%),radial-gradient(circle at 85% 80%,rgba(8,145,178,.16),transparent 28%),linear-gradient(145deg,#f8f9ff,#eef2ff);display:grid;place-items:center;padding:24px}.wrap{width:min(440px,100%)}.brand{display:flex;justify-content:center;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:900;font-size:22px;margin-bottom:22px}.logo{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8)}.card{background:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.9);backdrop-filter:blur(20px);border-radius:26px;padding:34px;box-shadow:0 24px 70px rgba(16,24,40,.1)}h1{margin:0 0 8px;font-size:28px;font-weight:900}.intro{margin:0 0 22px;color:var(--muted);line-height:1.9}.error{background:#fff1f2;color:#be123c;border:1px solid #fecdd3;border-radius:13px;padding:11px 13px;margin:-4px 0 18px;font-size:13px}label{display:block;font-size:14px;font-weight:700;margin:0 0 8px}input,select{display:block;width:100%;height:52px;border:1px solid var(--border);border-radius:14px;background:#fff;padding:0 15px;font:inherit;outline:none;margin-bottom:16px}button{width:100%;height:54px;border:0;border-radius:15px;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;font:800 16px Vazirmatn;cursor:pointer;box-shadow:0 13px 28px rgba(81,72,232,.25)}.bottom{text-align:center;margin:22px 0 0;color:var(--muted);font-size:14px}.bottom a{color:#5148e8;font-weight:800;text-decoration:none}.back{display:block;text-align:center;margin-top:18px;color:#667085;text-decoration:none;font-size:13px}@media(max-width:480px){.card{padding:27px 21px}}
</style></head><body><div class="wrap"><a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a><main class="card"><h1>حساب خودت را بساز ✨</h1><p class="intro">در چند ثانیه عضو کاداد کلاس شو و کلاس آنلاین خودت را شروع کن.</p><?php if($error): ?><div class="error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif; ?><form method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8')?>"><label for="name">نام</label><input id="name" name="name" type="text" autocomplete="name" placeholder="نام شما" required><label for="email">ایمیل</label><input id="email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required><label for="password">رمز عبور</label><input id="password" name="password" type="password" autocomplete="new-password" placeholder="حداقل ۸ کاراکتر" minlength="8" required><label for="password_confirmation">تکرار رمز عبور</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="رمز را دوباره وارد کنید" required><label for="role">نوع حساب</label><select id="role" name="role"><option value="student">دانش‌آموز</option><option value="teacher">مدرس</option></select><button type="submit">ساخت حساب</button></form><p class="bottom">قبلاً حساب داری؟ <a href="/login/signin.php">ورود به حساب</a></p></main><a class="back" href="/home">← بازگشت به صفحه اصلی</a></div><script src="/assets/app.js" defer></script></body></html>
