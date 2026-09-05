<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_auth();
$error = null;

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function role_label(string $role): string { return ['admin'=>'مدیر','teacher'=>'مدرس','student'=>'دانش‌آموز'][$role] ?? $role; }
function format_date(?string $value): string {
    if (!$value) return 'تعیین نشده';
    $ts = strtotime($value);
    return $ts ? date('Y/m/d - H:i', $ts) : $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'create_class') {
            require_teacher();
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $startsAt = trim((string)($_POST['starts_at'] ?? ''));
            if ($title === '' || mb_strlen($title) > 200) throw new InvalidArgumentException('عنوان کلاس را وارد کنید.');
            $startTs = strtotime($startsAt);
            if (!$startTs) throw new InvalidArgumentException('زمان کلاس را درست وارد کنید.');
            if ($startTs < time() - 60) throw new InvalidArgumentException('زمان کلاس نمی‌تواند در گذشته باشد.');
            do {
                $code = strtoupper(bin2hex(random_bytes(4)));
                $check = db()->prepare('SELECT id FROM classes WHERE room_code = ? LIMIT 1');
                $check->execute([$code]);
            } while ($check->fetch());
            $expiresAt = date('Y-m-d H:i:s', $startTs + 3600);
            $stmt = db()->prepare("INSERT INTO classes (teacher_id,title,description,room_code,status,starts_at,expires_at) VALUES (?,?,?,?, 'scheduled', ?, ?)");
            $stmt->execute([$user['id'], $title, $description ?: null, $code, date('Y-m-d H:i:s', $startTs), $expiresAt]);
            redirect('/panel');
        }

        if ($action === 'create_student') {
            require_teacher();
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) throw new InvalidArgumentException('نام دانش‌آموز معتبر نیست.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('ایمیل معتبر نیست.');
            if (strlen($password) < 8) throw new InvalidArgumentException('رمز عبور باید حداقل ۸ کاراکتر باشد.');
            $check = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) throw new InvalidArgumentException('این ایمیل قبلاً ثبت شده است.');
            $stmt = db()->prepare("INSERT INTO users (name,email,password,role,created_at,updated_at) VALUES (?,?,?,'student',NOW(),NOW())");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            redirect('/panel?student_created=1');
        }

        if ($action === 'delete_class') {
            require_teacher();
            $id = (int)($_POST['class_id'] ?? 0);
            if ($user['role'] === 'admin') {
                $stmt = db()->prepare('DELETE FROM classes WHERE id=?');
                $stmt->execute([$id]);
            } else {
                $stmt = db()->prepare('DELETE FROM classes WHERE id=? AND teacher_id=?');
                $stmt->execute([$id, $user['id']]);
            }
            redirect('/panel');
        }
    } catch (Throwable $e) {
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'عملیات انجام نشد.';
    }
}

$classes = [];
if (in_array($user['role'], ['teacher','admin'], true)) {
    $stmt = $user['role'] === 'admin'
        ? db()->query('SELECT c.*,u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id ORDER BY COALESCE(c.starts_at,c.created_at) DESC')
        : db()->prepare('SELECT * FROM classes WHERE teacher_id = ? ORDER BY COALESCE(starts_at,created_at) DESC');
    if ($stmt instanceof PDOStatement && $user['role'] !== 'admin') $stmt->execute([$user['id']]);
    $classes = $stmt->fetchAll();
} else {
    $stmt = db()->prepare('SELECT c.*, cp.joined_at, u.name AS teacher_name FROM class_participants cp JOIN classes c ON c.id=cp.class_id JOIN users u ON u.id=c.teacher_id WHERE cp.user_id=? ORDER BY COALESCE(c.starts_at,c.created_at) DESC');
    $stmt->execute([$user['id']]);
    $classes = $stmt->fetchAll();
}
?><!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#635bff"><title>پنل | کاداد کلاس</title><link rel="manifest" href="/assets/manifest.json"><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--text:#101828;--muted:#667085;--primary:#635bff;--bg:#f6f7fb;--card:#fff;--border:#eaecf0}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Vazirmatn,Tahoma,sans-serif}.top{height:76px;background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 max(22px,calc((100% - 1180px)/2))}.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:900}.logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#766cff,#5148e8)}.user{display:flex;align-items:center;gap:12px}.user span{color:var(--muted);font-size:14px}.logout{border:0;background:#f2f4f7;border-radius:10px;padding:9px 13px;font:700 13px Vazirmatn;cursor:pointer}.container{width:min(1180px,calc(100% - 40px));margin:34px auto}.hero{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:28px}.hero h1{margin:0 0 7px;font-size:30px}.hero p{margin:0;color:var(--muted)}.primary{display:inline-flex;text-decoration:none;border:0;background:linear-gradient(135deg,#6d63ff,#5148e8);color:#fff;border-radius:13px;padding:13px 18px;font:800 14px Vazirmatn;cursor:pointer}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}.card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:22px;box-shadow:0 8px 28px rgba(16,24,40,.05)}.form{margin-bottom:28px}.form h2{margin:0 0 18px;font-size:20px}.fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field.full{grid-column:1/-1}label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}input,textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:11px 13px;font:inherit;outline:none}textarea{min-height:90px;resize:vertical}.error{background:#fff1f2;color:#be123c;border:1px solid #fecdd3;padding:10px 12px;border-radius:12px;margin-bottom:15px}.success{background:#ecfdf3;color:#027a48;border:1px solid #abefc6;padding:10px 12px;border-radius:12px;margin-bottom:15px}.class-title{font-size:18px;font-weight:900;margin:0 0 8px}.desc{color:var(--muted);font-size:13px;min-height:22px}.code{display:inline-block;background:#f2f4ff;color:#5148e8;padding:6px 9px;border-radius:8px;font-weight:900;letter-spacing:2px;margin:12px 0}.meta{font-size:12px;color:var(--muted);margin-bottom:16px;line-height:1.9}.actions{display:flex;gap:8px;align-items:center}.join{display:inline-block;text-decoration:none;background:#101828;color:#fff;border-radius:10px;padding:9px 12px;font-size:12px;font-weight:800}.danger{border:0;background:#fff1f2;color:#be123c;border-radius:10px;padding:9px 12px;font:700 12px Vazirmatn;cursor:pointer}.empty{grid-column:1/-1;text-align:center;padding:50px 20px;color:var(--muted)}.section-title{font-size:20px;margin:0 0 15px}.role{font-size:12px;color:#5148e8;font-weight:800}.student-form{margin-top:28px}@media(max-width:650px){.hero{align-items:flex-start;flex-direction:column}.fields{grid-template-columns:1fr}.field.full{grid-column:auto}.user span{display:none}}
</style></head><body><header class="top"><a class="brand" href="/panel"><span class="logo">ک</span><span>کاداد کلاس</span></a><div class="user"><span><?=h($user['name'])?> · <?=h(role_label($user['role']))?></span><form method="post" action="/login/logout.php"><button class="logout" type="submit">خروج</button></form></div></header><main class="container"><section class="hero"><div><h1>سلام <?=h($user['name'])?> 👋</h1><p><?php if($user['role']==='student'): ?>کلاس‌ها و زمان برگزاری آن‌ها را اینجا می‌بینی.<?php else: ?>کلاس‌ها و دانش‌آموزها را از اینجا مدیریت کن.<?php endif; ?></p></div><?php if(in_array($user['role'],['teacher','admin'],true)): ?><a class="primary" href="#create">+ ساخت کلاس</a><?php endif; ?></section><?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?><?php if(isset($_GET['student_created'])): ?><div class="success">دانش‌آموز با موفقیت ساخته شد.</div><?php endif; ?>
<?php if(in_array($user['role'],['teacher','admin'],true)): ?><section id="create" class="card form"><h2>ساخت کلاس جدید 🎓</h2><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="create_class"><div class="fields"><div><label for="title">عنوان کلاس</label><input id="title" name="title" placeholder="مثلاً ریاضی دهم" maxlength="200" required></div><div><label for="starts_at">زمان شروع کلاس</label><input id="starts_at" type="datetime-local" name="starts_at" required></div><div class="field full"><label for="description">توضیح کوتاه</label><textarea id="description" name="description" placeholder="موضوع یا توضیح کلاس"></textarea></div></div><button class="primary" type="submit" style="margin-top:15px">ساخت کلاس</button></form></section>
<section class="card student-form"><h2>ساخت دانش‌آموز 👨‍🎓</h2><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="create_student"><div class="fields"><div><label for="student_name">نام و نام خانوادگی</label><input id="student_name" name="name" required></div><div><label for="student_email">ایمیل</label><input id="student_email" type="email" name="email" required></div><div><label for="student_password">رمز عبور موقت</label><input id="student_password" type="password" name="password" minlength="8" required></div></div><button class="primary" type="submit" style="margin-top:15px">ساخت حساب دانش‌آموز</button></form></section><?php endif; ?>
<section><h2 class="section-title"><?php if($user['role']==='student'): ?>کلاس‌های من<?php else: ?>کلاس‌ها<?php endif; ?></h2><div class="grid"><?php if(!$classes): ?><div class="card empty">هنوز کلاسی اینجا نیست.</div><?php else: foreach($classes as $class): ?><article class="card"><h3 class="class-title"><?=h($class['title'])?></h3><div class="role"><?php if($user['role']==='student'): ?>مدرس: <?=h((string)$class['teacher_name'])?><?php elseif($user['role']==='admin'): ?>مدرس: <?=h((string)$class['teacher_name'])?><?php endif; ?></div><div class="desc"><?=h((string)($class['description'] ?? ''))?></div><div class="code"><?=h($class['room_code'])?></div><div class="meta">🕐 زمان کلاس: <?=h(format_date($class['starts_at'] ?? null))?><br>وضعیت: <?=h($class['status'])?> · پایان ورود: <?=h(format_date($class['expires_at'] ?? null))?></div><div class="actions"><a class="join" href="/join/?code=<?=rawurlencode($class['room_code'])?>">ورود به کلاس</a><?php if(in_array($user['role'],['teacher','admin'],true)): ?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="delete_class"><input type="hidden" name="class_id" value="<?=h((string)$class['id'])?>"><button class="danger" type="submit">حذف</button></form><?php endif; ?></div></article><?php endforeach; endif; ?></div></section></main><script src="/assets/app.js" defer></script></body></html>
