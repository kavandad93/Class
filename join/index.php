<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_auth();
$error = null;
$code = strtoupper(trim((string)($_POST['code'] ?? $_GET['code'] ?? '')));
$class = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!preg_match('/^[A-Z0-9]{4,32}$/', $code)) {
            throw new InvalidArgumentException('کد کلاس معتبر نیست.');
        }

        $stmt = db()->prepare('SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.room_code=? LIMIT 1');
        $stmt->execute([$code]);
        $class = $stmt->fetch() ?: null;
        if (!$class) throw new InvalidArgumentException('کلاسی با این کد پیدا نشد.');

        if ($class['expires_at'] !== null && strtotime((string)$class['expires_at']) <= time()) {
            db()->prepare("UPDATE classes SET status='expired' WHERE id=? AND status IN ('scheduled','active')")->execute([(int)$class['id']]);
            throw new InvalidArgumentException('زمان ورود به این کلاس تمام شده است.');
        }
        if (!in_array($class['status'], ['scheduled','active'], true)) {
            throw new InvalidArgumentException('این کلاس دیگر قابل ورود نیست.');
        }

        $role = ((int)$class['teacher_id'] === (int)$user['id']) ? 'teacher' : 'student';
        $stmt = db()->prepare("INSERT INTO class_participants (class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES (?,?,?,NOW(),NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role=VALUES(role),joined_at=NOW(),left_at=NULL,updated_at=NOW()");
        $stmt->execute([(int)$class['id'], (int)$user['id'], $role]);
        redirect('/room/?code=' . rawurlencode($code));
    } catch (Throwable $e) {
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'ورود به کلاس انجام نشد.';
    }
}

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#635bff"><title>ورود به کلاس | کاداد کلاس</title><link rel="manifest" href="/assets/manifest.json"><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--text:#101828;--muted:#667085;--primary:#635bff;--border:#e4e7ec}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Vazirmatn,Tahoma,sans-serif;color:var(--text);background:radial-gradient(circle at 20% 20%,rgba(99,91,255,.2),transparent 30%),radial-gradient(circle at 85% 75%,rgba(8,145,178,.17),transparent 28%),linear-gradient(145deg,#f8f9ff,#eef2ff);display:grid;place-items:center;padding:24px}.wrap{width:min(520px,100%)}.brand{display:flex;justify-content:center;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:900;font-size:22px;margin-bottom:22px}.logo{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8);box-shadow:0 12px 28px rgba(81,72,232,.25)}.card{background:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.9);backdrop-filter:blur(20px);border-radius:26px;padding:36px;box-shadow:0 24px 70px rgba(16,24,40,.1)}h1{margin:0 0 8px;font-size:30px;font-weight:900}.intro{margin:0 0 27px;color:var(--muted);line-height:1.9}.hint{margin:0 0 18px;padding:12px 14px;border-radius:14px;background:#f5f4ff;color:#5148e8;font-size:13px;font-weight:600}.error{margin-bottom:16px;padding:12px 14px;border-radius:13px;background:#fff1f2;border:1px solid #fecdd3;color:#be123c;font-size:13px;font-weight:700}label{display:block;font-size:14px;font-weight:700;margin-bottom:8px}input{width:100%;height:54px;border:1px solid var(--border);border-radius:15px;background:#fff;padding:0 16px;font:inherit;outline:none;transition:.2s;margin-bottom:18px;text-transform:uppercase;letter-spacing:2px}input:focus{border-color:#8b85ff;box-shadow:0 0 0 4px rgba(99,91,255,.1)}button{width:100%;height:55px;border:0;border-radius:15px;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;font:800 16px Vazirmatn;cursor:pointer;box-shadow:0 13px 28px rgba(81,72,232,.25)}.back{display:block;text-align:center;margin-top:18px;color:#667085;text-decoration:none;font-size:13px}@media(max-width:480px){.card{padding:27px 21px}}
</style></head><body><div class="wrap"><a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a><main class="card"><h1>ورود به کلاس 🎓</h1><p class="intro">کد کلاس را وارد کن تا اطلاعات کلاس و صفحه جلسه برایت باز شود.</p><?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?><div class="hint">✦ تصویر و صدا فعلاً غیرفعال‌اند؛ اطلاعات کلاس، افراد و چت را می‌سازیم.</div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><label for="code">کد کلاس</label><input id="code" name="code" value="<?=h($code)?>" placeholder="مثلاً A1B2C3D4" autocomplete="off" minlength="4" maxlength="32" required><button type="submit">ورود به کلاس</button></form></main><a class="back" href="/panel">← بازگشت به پنل</a></div></body></html>
