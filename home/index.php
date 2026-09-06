<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='خانه';
$pageDescription='کاداد کلاس؛ فضای ساده و سریع برای برگزاری و شرکت در کلاس‌های آنلاین.';
require __DIR__ . '/_header.php';
?>
<main>
<section class="page" style="padding-bottom:45px"><div class="container">
<div class="eyebrow"><span class="dot"></span> فضای کلاس آنلاین کاداد</div>
<h1>کلاس آنلاین،<br><span class="gradient">بدون دردسر.</span></h1>
<p class="lead">کلاس را بساز، دانش‌آموزها را مدیریت کن و آموزش را در یک فضای ساده و سریع شروع کن؛ از ورود و چت تا مدیریت اعضا و مجوزهای کلاس.</p>
<div class="actions">
<a class="btn primary" href="<?= $user ? '/panel' : '/login/signup.php' ?>"><?= $user ? 'رفتن به پنل' : 'شروع کنید' ?></a>
<a class="btn secondary" href="/join">🚪 ورود با کد کلاس</a>
<a class="btn secondary" href="/home/features.php">✨ مشاهده امکانات</a>
<a class="btn secondary" href="/home/about.php">ℹ️ درباره کاداد کلاس</a>
<a class="btn secondary" href="/home/contact.php">📞 تماس با ما</a>
</div>
</div></section>
<section class="section"><div class="container"><h2>کاداد کلاس چه امکاناتی دارد؟</h2><p class="section-intro">همه‌چیز برای شروع و مدیریت یک کلاس آنلاین، در یک محیط ساده.</p><div class="grid">
<article class="card"><div class="icon">🎥</div><h3>کلاس زنده</h3><p>تصویر، صدا و اشتراک صفحه برای برگزاری جلسه آنلاین.</p></article>
<article class="card"><div class="icon">👥</div><h3>مدیریت اعضا</h3><p>مدیریت شرکت‌کنندگان، درخواست‌های ورود و مجوزهای رسانه‌ای.</p></article>
<article class="card"><div class="icon">💬</div><h3>چت کلاس</h3><p>گفت‌وگوی سریع مدرس و دانش‌آموزها در کنار کلاس.</p></article>
</div></div></section>
<section class="container"><div class="card" style="text-align:center;padding:38px;margin-bottom:20px"><h2 style="margin:0 0 10px">بیشتر با کاداد کلاس آشنا شو</h2><p class="section-intro" style="margin-bottom:20px">جزئیات امکانات و اطلاعات پروژه را در صفحات زیر ببین.</p><div class="actions" style="justify-content:center"><a class="btn primary" href="/home/features.php">مشاهده همه امکانات</a><a class="btn secondary" href="/home/about.php">درباره ما</a><a class="btn secondary" href="/home/contact.php">تماس با ما</a></div></div></section>
</main>
<?php require __DIR__ . '/_footer.php'; ?>
