# Kadad Class

پلتفرم کلاس آنلاین کاداد (`class.kadad.ir`) برای برگزاری کلاس‌های آنلاین با احراز هویت، مدیریت اعضا، چت و ویدیو/صدا با Cloudflare RealtimeKit.

## وضعیت

**Final release — V.1.0.1**

این نسخه پس از یک بازبینی امنیتی داخلی منتشر می‌شود و شامل سخت‌گیری‌های امنیتی و بهبودهای نسخه نهایی است.

## امکانات

- ثبت‌نام و ورود کاربران
- نقش‌های دانش‌آموز، مدرس و مدیر
- ساخت و مدیریت کلاس
- ورود با کد کلاس
- کنترل دسترسی اعضای کلاس
- درخواست ورود و تأیید توسط مدرس/مدیر
- چت کلاس
- ویدیو، صدا و Screen Share با Cloudflare RealtimeKit
- مدیریت مجوز میکروفون، دوربین و Screen Share برای دانش‌آموزان
- رابط کاربری فارسی و RTL
- استقرار با cPanel Git Version Control

## امنیت

- کوئری‌های دیتابیس با PDO prepared statements
- ذخیره رمز عبور با `password_hash`
- بازتولید Session ID هنگام ورود
- محافظت CSRF برای عملیات تغییر‌دهنده
- کنترل دسترسی در سطح کلاس و کاربر
- Escape کردن خروجی‌های HTML برای کاهش ریسک XSS
- اطلاعات محرمانه Cloudflare خارج از `public_html` نگهداری می‌شود
- endpoint چت دارای CSRF protection است

> این پروژه هنوز باید در محیط واقعی، جدا از بررسی سورس، از نظر تنظیمات وب‌سرور، TLS، هدرهای امنیتی و پیکربندی هاست نیز بررسی شود.

## ساختار

- `app/` — منطق Laravel
- `routes/` — routeهای Laravel
- `database/` — migrations و SQLهای دیتابیس
- `login/` — ورود، ثبت‌نام و خروج
- `panel/` — پنل کاربر و مدیریت کلاس
- `join/` — ورود به کلاس و رابط جلسه
- `room/` — رابط room و API چت
- `realtime/` — صدور token و مدیریت Cloudflare RealtimeKit
- `includes/` — bootstrap و توابع مشترک
- `assets/` — assetهای عمومی

## محیط استقرار

- Domain: `class.kadad.ir`
- PHP: 8.1
- Database: MySQL 5.7
- Hosting: cPanel
- Deployment: cPanel Git Version Control
- Repository: GitHub

## پیکربندی

اطلاعات حساس مانند رمز دیتابیس و Cloudflare API Token نباید داخل Git commit شوند.

در محیط فعلی، تنظیمات حساس در فایل خارج از document root قرار می‌گیرند:

```text
/home2/kadad/data.php
```

## مجوز

این پروژه تحت مجوز **MIT License** منتشر شده است. متن کامل مجوز در فایل `LICENSE` قرار دارد.

## نسخه

`V.1.0.1` — Final security release
