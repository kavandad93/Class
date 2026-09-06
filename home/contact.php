<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='تماس با ما';
$pageDescription='راه‌های ارتباط با کاداد کلاس.';
require __DIR__ . '/_header.php';
?>
<main class="page"><div class="container">
<div class="eyebrow"><span class="dot"></span> ارتباط با کاداد کلاس</div>
<h1>با ما در <span class="gradient">ارتباط باش.</span></h1>
<p class="lead">اگر درباره کاداد کلاس، کلاس‌های آنلاین یا مشکلات استفاده از سامانه سوالی داری، می‌توانی از راه ارتباطی رسمی پروژه استفاده کنی.</p>
<div class="grid">
<article class="card"><div class="icon">🌐</div><h2>وب‌سایت</h2><p>برای شروع، امکانات را ببین یا مستقیماً وارد یک کلاس شو.</p><div class="actions"><a class="btn secondary" href="/home/features.php">مشاهده امکانات</a></div></article>
<article class="card"><div class="icon">🎓</div><h2>ورود به کلاس</h2><p>اگر کد کلاس را داری، می‌توانی از صفحه ورود کلاس وارد جلسه شوی.</p><div class="actions"><a class="btn primary" href="/join">ورود به کلاس</a></div></article>
<article class="card"><div class="icon">🔑</div><h2>حساب کاربری</h2><p>برای ساخت حساب یا ورود به پنل مدیریت کلاس از صفحه حساب کاربری استفاده کن.</p><div class="actions"><a class="btn secondary" href="/login/signin.php">ورود</a></div></article>
</div>
</div></main>
<?php require __DIR__ . '/_footer.php'; ?>
