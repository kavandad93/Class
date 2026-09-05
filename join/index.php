<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_auth();
$error = null;
$info = null;
$code = strtoupper(trim((string)($_POST['code'] ?? $_GET['code'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!preg_match('/^[A-Z0-9]{4,32}$/', $code)) throw new InvalidArgumentException('کد کلاس معتبر نیست.');
        $stmt = db()->prepare('SELECT c.*,u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.room_code=? LIMIT 1');
        $stmt->execute([$code]);
        $class = $stmt->fetch();
        if (!$class) throw new InvalidArgumentException('کلاسی با این کد پیدا نشد.');
        if ($class['expires_at'] !== null && strtotime((string)$class['expires_at']) <= time()) {
            db()->prepare("UPDATE classes SET status='expired' WHERE id=? AND status IN ('scheduled','active')")->execute([(int)$class['id']]);
            throw new InvalidArgumentException('زمان ورود به این کلاس تمام شده است.');
        }
        if (!in_array($class['status'], ['scheduled','active'], true)) throw new InvalidArgumentException('این کلاس دیگر قابل ورود نیست.');
        if ((int)$class['teacher_id'] === (int)$user['id'] || $user['role'] === 'admin') {
            db()->prepare("INSERT INTO class_participants (class_id,user_id,role,joined_at,left_at,created_at,updated_at) VALUES (?,?,?,NOW(),NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE role=VALUES(role),joined_at=NOW(),left_at=NULL,updated_at=NOW()")->execute([(int)$class['id'],(int)$user['id'],$user['role']==='admin'?'teacher':'teacher']);
            redirect('/room/?code=' . rawurlencode($code));
        }
        $stmt = db()->prepare('SELECT status FROM class_join_requests WHERE class_id=? AND user_id=? LIMIT 1');
        $stmt->execute([(int)$class['id'],(int)$user['id']]);
        $request = $stmt->fetchColumn();
        if ($request === 'approved') redirect('/room/?code=' . rawurlencode($code));
        if ($request === 'pending') { $info = 'درخواست ورودت قبلاً ارسال شده و منتظر تأیید مدرس یا مدیر است.'; }
        else {
            db()->prepare("INSERT INTO class_join_requests (class_id,user_id,status,requested_at) VALUES (?,?,'pending',NOW()) ON DUPLICATE KEY UPDATE status='pending',requested_at=NOW(),decided_at=NULL,decided_by=NULL,reject_reason=NULL")->execute([(int)$class['id'],(int)$user['id']]);
            $info = 'درخواست ورود ارسال شد. بعد از تأیید مدرس یا مدیر می‌توانی وارد کلاس شوی.';
        }
    } catch (Throwable $e) {
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'ارسال درخواست ورود انجام نشد. ابتدا جدول درخواست‌ها را در دیتابیس بسازید.';
    }
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ورود به کلاس | کاداد کلاس</title><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style>:root{--text:#101828;--muted:#667085;--primary:#635bff;--border:#e4e7ec}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Vazirmatn,Tahoma,sans-serif;color:var(--text);background:linear-gradient(145deg,#f8f9ff,#eef2ff);display:grid;place-items:center;padding:24px}.wrap{width:min(520px,100%)}.brand{display:flex;justify-content:center;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:900;font-size:22px;margin-bottom:22px}.logo{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8)}.card{background:#fff;border:1px solid var(--border);border-radius:26px;padding:36px;box-shadow:0 24px 70px rgba(16,24,40,.1)}h1{margin:0 0 8px;font-size:30px}.intro{margin:0 0 22px;color:var(--muted);line-height:1.9}.error,.info{padding:12px 14px;border-radius:13px;margin-bottom:16px;font-size:13px;font-weight:700}.error{background:#fff1f2;color:#be123c}.info{background:#eff6ff;color:#1d4ed8}label{display:block;font-size:14px;font-weight:700;margin-bottom:8px}input{width:100%;height:54px;border:1px solid var(--border);border-radius:15px;padding:0 16px;font:inherit;text-transform:uppercase;letter-spacing:2px;margin-bottom:18px}button{width:100%;height:55px;border:0;border-radius:15px;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;font:800 16px Vazirmatn;cursor:pointer}.back{display:block;text-align:center;margin-top:18px;color:var(--muted);text-decoration:none;font-size:13px}</style></head><body><div class="wrap"><a class="brand" href="/home"><span class="logo">ک</span><span>کاداد کلاس</span></a><main class="card"><h1>ورود به کلاس 🎓</h1><p class="intro">کد کلاس را وارد کن. دانش‌آموزها قبل از ورود باید تأیید مدرس یا مدیر را بگیرند.</p><?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?><?php if($info): ?><div class="info">✓ <?=h($info)?></div><?php endif; ?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><label for="code">کد کلاس</label><input id="code" name="code" value="<?=h($code)?>" placeholder="مثلاً A1B2C3D4" minlength="4" maxlength="32" required><button>ارسال درخواست ورود</button></form></main><a class="back" href="/panel">← بازگشت به پنل</a></div></body></html>
