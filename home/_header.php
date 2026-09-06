<?php
if (!isset($pageTitle)) $pageTitle = 'کاداد کلاس';
if (!isset($pageDescription)) $pageDescription = 'کاداد کلاس؛ فضای ساده و سریع برای برگزاری و شرکت در کلاس‌های آنلاین.';
if (!function_exists('h')) { function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); } }
$user = current_user();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?> | کاداد کلاس</title>
<meta name="description" content="<?= h($pageDescription) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#070b16;--panel:#0d1324;--line:#202a43;--text:#f7f8fc;--muted:#a4afc2;--brand:#766cff;--brand2:#5148e8}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;color:var(--text);background:radial-gradient(circle at 12% 5%,rgba(118,108,255,.28),transparent 30%),radial-gradient(circle at 88% 12%,rgba(56,189,248,.17),transparent 28%),linear-gradient(150deg,#070b16,#0b1120 55%,#080d19);min-height:100vh;overflow-x:hidden}.container{width:min(1160px,calc(100% - 34px));margin:auto}
header{position:sticky;top:0;z-index:30;background:rgba(7,11,22,.78);backdrop-filter:blur(18px);border-bottom:1px solid rgba(255,255,255,.06)}nav{height:72px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff;font-weight:900;font-size:20px}.logo{width:40px;height:40px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--brand),var(--brand2));box-shadow:0 12px 30px rgba(99,91,255,.28)}.nav-actions{display:flex;gap:7px;align-items:center}.nav-actions a{color:#d7deea;text-decoration:none;padding:9px 13px;border-radius:10px;font-size:12px;font-weight:800}.nav-actions a:hover{background:#111a2d}.nav-actions .active{background:rgba(118,108,255,.15);color:#c4bfff}.nav-actions .login{border:1px solid var(--line);background:#10182a}.page{padding:70px 0 90px}.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #27324d;background:#10182a;border-radius:999px;color:#b9b3ff;font-size:12px;font-weight:800}.dot{width:7px;height:7px;border-radius:50%;background:#34d399;box-shadow:0 0 0 6px rgba(52,211,153,.1)}h1{font-size:clamp(36px,5vw,58px);line-height:1.2;letter-spacing:-1.5px;margin:20px 0 14px;font-weight:900}.gradient{background:linear-gradient(100deg,#a59fff,#7c6cff 45%,#67e8f9);-webkit-background-clip:text;background-clip:text;color:transparent}.lead{color:var(--muted);font-size:17px;line-height:2;max-width:760px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-top:32px}.card{padding:24px;border:1px solid var(--line);background:rgba(13,19,36,.84);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.16)}.card .icon{font-size:26px;margin-bottom:14px}.card h2,.card h3{margin:0 0 8px;font-size:18px}.card p{margin:0;color:var(--muted);font-size:13px;line-height:2}.btn{min-height:48px;padding:0 20px;border-radius:13px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:900}.primary{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;box-shadow:0 15px 35px rgba(81,72,232,.25)}.secondary{color:#e5e7eb;background:#10182a;border:1px solid var(--line)}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px}footer{padding:0 0 28px;color:#738097;text-align:center;font-size:11px}.footer-links{display:flex;justify-content:center;gap:16px;margin-bottom:10px}.footer-links a{color:#8f9bb0;text-decoration:none}.footer-links a:hover{color:#fff}@media(max-width:850px){.grid{grid-template-columns:1fr 1fr}.nav-actions a:nth-child(1){display:none}}@media(max-width:560px){.page{padding-top:45px}.grid{grid-template-columns:1fr}.nav-actions a:nth-child(2){display:none}.brand{font-size:18px}}
</style>
</head>
<body>
<header><div class="container"><nav>
<a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a>
<div class="nav-actions">
<a href="/home">خانه</a><a href="/home/features.php">امکانات</a><a href="/home/about.php">درباره</a><a href="/home/contact.php">تماس</a><a href="/join">ورود به کلاس</a>
<?php if($user): ?><a class="login" href="/panel">پنل من</a><?php else: ?><a class="login" href="/login/signin.php">ورود</a><?php endif; ?>
</div></nav></div></header>
