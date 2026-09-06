<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='درباره ما';
$pageDescription='آشنایی با کاداد کلاس و هدف آن برای آموزش آنلاین ساده و سریع.';
require __DIR__ . '/_header.php';
?>
<main class="page"><div class="container">
<div class="eyebrow"><span class="dot"></span> درباره کاداد کلاس</div>
<h1>آموزش آنلاین، <span class="gradient">ساده‌تر.</span></h1>
<p class="lead">کاداد کلاس برای این ساخته شده که برگزاری کلاس آنلاین تا جای ممکن ساده، سریع و قابل مدیریت باشد؛ بدون اینکه مدرس و دانش‌آموز درگیر منوها و تنظیمات اضافه شوند.</p>
<div class="grid">
<article class="card"><div class="icon">🎓</div><h2>هدف ما</h2><p>ساختن یک فضای متمرکز برای کلاس‌های آنلاین؛ از ورود با کد کلاس تا ارتباط زنده و مدیریت اعضا.</p></article>
<article class="card"><div class="icon">⚡</div><h2>سادگی</h2><p>رابط کاربری فارسی و مسیرهای کوتاه برای کارهای اصلی مدرس و دانش‌آموز طراحی شده است.</p></article>
<article class="card"><div class="icon">🔐</div><h2>امنیت</h2><p>احراز هویت، کنترل دسترسی کلاس، محافظت CSRF و تفکیک نقش‌های دانش‌آموز، مدرس و مدیر در نظر گرفته شده است.</p></article>
</div>
<div class="actions"><a class="btn primary" href="/home/features.php">مشاهده امکانات</a><a class="btn secondary" href="/home/contact.php">تماس با ما</a></div>
</div></main>
<?php require __DIR__ . '/_footer.php'; ?>
