<?php

declare(strict_types=1);

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کاداد کلاس | کلاس آنلاین، ساده و سریع</title>
    <meta name="description" content="کاداد کلاس؛ برگزاری و شرکت در کلاس‌های آنلاین، ساده و سریع.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f8fc;
            --text: #101828;
            --muted: #667085;
            --primary: #635bff;
            --primary-dark: #5148e8;
            --card: rgba(255,255,255,.72);
            --border: rgba(255,255,255,.75);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Vazirmatn, Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 15%, rgba(99,91,255,.18), transparent 28%),
                radial-gradient(circle at 85% 25%, rgba(56,189,248,.16), transparent 26%),
                linear-gradient(145deg, #f8f9ff 0%, #f5f7fb 48%, #eef2ff 100%);
            overflow-x: hidden;
        }
        body::before, body::after {
            content: "";
            position: fixed;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .35;
            pointer-events: none;
            z-index: -1;
        }
        body::before { background: #8b5cf6; top: -120px; right: -80px; }
        body::after { background: #38bdf8; bottom: -150px; left: -80px; }
        .container { width: min(1160px, calc(100% - 40px)); margin: auto; }
        header { padding: 24px 0; }
        nav { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 11px; text-decoration: none; color: var(--text); font-weight: 900; font-size: 21px; }
        .logo { width: 42px; height: 42px; border-radius: 14px; display: grid; place-items: center; color: white; background: linear-gradient(135deg, #766cff, #5148e8); box-shadow: 0 10px 25px rgba(99,91,255,.28); }
        .nav-links { display: flex; align-items: center; gap: 10px; }
        .nav-links a { text-decoration: none; color: #475467; padding: 10px 15px; border-radius: 12px; font-weight: 600; transition: .2s; }
        .nav-links a:hover { background: rgba(255,255,255,.65); color: var(--text); }
        .login { border: 1px solid #e4e7ec; background: rgba(255,255,255,.6); }
        .hero { min-height: calc(100vh - 90px); display: grid; place-items: center; text-align: center; padding: 55px 0 90px; }
        .hero-content { max-width: 850px; }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border: 1px solid var(--border); background: rgba(255,255,255,.62); backdrop-filter: blur(12px); border-radius: 999px; color: #5148e8; font-size: 14px; font-weight: 700; box-shadow: 0 8px 30px rgba(16,24,40,.05); }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 5px rgba(34,197,94,.12); }
        h1 { margin: 25px 0 18px; font-size: clamp(42px, 7vw, 78px); line-height: 1.15; letter-spacing: -2.5px; font-weight: 900; }
        .gradient { background: linear-gradient(100deg, #5148e8, #7c3aed 45%, #0891b2); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .subtitle { max-width: 650px; margin: auto; color: var(--muted); font-size: clamp(17px, 2.1vw, 21px); line-height: 2; font-weight: 500; }
        .actions { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-top: 34px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 56px; padding: 0 26px; border-radius: 16px; text-decoration: none; font-weight: 800; font-size: 16px; transition: transform .2s, box-shadow .2s, background .2s; }
        .btn:hover { transform: translateY(-2px); }
        .primary { color: white; background: linear-gradient(135deg, #6d63ff, #5148e8); box-shadow: 0 14px 32px rgba(81,72,232,.28); }
        .primary:hover { box-shadow: 0 18px 38px rgba(81,72,232,.36); }
        .secondary { color: #344054; background: rgba(255,255,255,.72); border: 1px solid rgba(228,231,236,.9); backdrop-filter: blur(12px); }
        .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 70px; text-align: right; }
        .feature { padding: 24px; border: 1px solid var(--border); border-radius: 22px; background: var(--card); backdrop-filter: blur(16px); box-shadow: 0 18px 50px rgba(16,24,40,.06); }
        .icon { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: #eeedff; color: #5148e8; font-size: 20px; margin-bottom: 17px; }
        .feature h3 { margin: 0 0 7px; font-size: 17px; }
        .feature p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.9; }
        footer { text-align: center; color: #98a2b3; font-size: 13px; padding: 0 0 28px; }
        @media (max-width: 720px) {
            .container { width: min(100% - 28px, 1160px); }
            .nav-links a:not(.login) { display: none; }
            .hero { padding-top: 30px; }
            h1 { letter-spacing: -1.5px; }
            .features { grid-template-columns: 1fr; margin-top: 48px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <a class="brand" href="/home">
                <span class="logo">ک</span>
                <span>کاداد کلاس</span>
            </a>
            <div class="nav-links">
                <a href="/join">ورود به کلاس</a>
                <a class="login" href="/login/signin.php">ورود</a>
            </div>
        </nav>
    </div>
</header>

<main class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="badge"><span class="dot"></span> کلاس آنلاین، ساده و سریع</div>
            <h1>کلاس خودت را<br><span class="gradient">همین حالا شروع کن</span></h1>
            <p class="subtitle">یک فضای ساده و حرفه‌ای برای برگزاری کلاس‌های آنلاین؛ بدون پیچیدگی، با تمرکز روی چیزی که واقعاً مهم است: یادگیری.</p>
            <div class="actions">
                <a class="btn primary" href="/login/signup.php">همین حالا شروع کنید</a>
                <a class="btn secondary" href="/join">ورود به یک کلاس</a>
            </div>

            <section class="features" aria-label="ویژگی‌ها">
                <article class="feature">
                    <div class="icon">◉</div>
                    <h3>کلاس آنلاین</h3>
                    <p>کلاس را بساز، لینک را به اشتراک بگذار و با دانش‌آموزها شروع کن.</p>
                </article>
                <article class="feature">
                    <div class="icon">⚡</div>
                    <h3>سریع و ساده</h3>
                    <p>رابط کاربری تمیز و بدون مراحل اضافی برای ورود سریع به کلاس.</p>
                </article>
                <article class="feature">
                    <div class="icon">✦</div>
                    <h3>ساخته‌شده برای کاداد</h3>
                    <p>زیرساخت اختصاصی کلاس آنلاین کاداد، آماده برای امکانات بیشتر.</p>
                </article>
            </section>
        </div>
    </div>
</main>

<footer>© کاداد کلاس</footer>
</body>
</html>
