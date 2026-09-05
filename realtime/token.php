<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function cloudflare_error_message(string $raw, int $status): string
{
    $data = json_decode($raw, true);
    $parts = [];
    if (is_array($data) && isset($data['errors']) && is_array($data['errors'])) {
        foreach ($data['errors'] as $err) {
            if (is_array($err)) {
                $code = isset($err['code']) ? (string)$err['code'] : '';
                $message = isset($err['message']) ? (string)$err['message'] : '';
                if ($message !== '') $parts[] = ($code !== '' ? '['.$code.'] ' : '').$message;
            }
        }
    }
    if (!$parts && is_array($data) && isset($data['message'])) $parts[] = (string)$data['message'];
    $detail = $parts ? ' '.implode(' | ', $parts) : '';
    return 'HTTP '.$status.'.'.$detail;
}

try {
    $user = require_auth();
    $pdo = db();
    $classId = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
    if ($classId < 1) throw new RuntimeException('کلاس نامعتبر است.');

    $q = $pdo->prepare("SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.id=? LIMIT 1");
    $q->execute([$classId]);
    $class = $q->fetch();
    if (!$class) throw new RuntimeException('کلاس پیدا نشد.');
    if ($class['expires_at'] !== null && strtotime((string)$class['expires_at']) <= time()) throw new RuntimeException('زمان کلاس تمام شده است.');
    if (!in_array($class['status'], ['scheduled','active'], true)) throw new RuntimeException('این کلاس قابل ورود نیست.');

    $isTeacher = (int)$class['teacher_id'] === (int)$user['id'] || $user['role'] === 'admin';
    $allowed = false;
    if ($isTeacher) {
        $allowed = true;
    } else {
        $q = $pdo->prepare('SELECT 1 FROM class_participants WHERE class_id=? AND user_id=? AND left_at IS NULL LIMIT 1');
        $q->execute([$classId, $user['id']]);
        $allowed = (bool)$q->fetchColumn();
        if (!$allowed && (bool)$pdo->query("SHOW TABLES LIKE 'class_allowed_users'")->fetch()) {
            $q = $pdo->prepare('SELECT 1 FROM class_allowed_users WHERE class_id=? AND user_id=? LIMIT 1');
            $q->execute([$classId, $user['id']]);
            $allowed = (bool)$q->fetchColumn();
        }
    }
    if (!$allowed) throw new RuntimeException('دسترسی شما به این کلاس تأیید نشده است.');

    $cfgFile = '/home2/kadad/data.php';
    if (!is_file($cfgFile) || !is_readable($cfgFile)) {
        throw new RuntimeException('فایل /home2/kadad/data.php برای PHP قابل خواندن نیست.');
    }
    $cfg = (array)require $cfgFile;

    $accountId = trim((string)($cfg['cloudflare_account_id'] ?? getenv('CLOUDFLARE_ACCOUNT_ID') ?? ''));
    $appId = trim((string)($cfg['realtimekit_app_id'] ?? getenv('REALTIMEKIT_APP_ID') ?? ''));
    $apiToken = trim((string)(
        $cfg['cloudflare_api_token']
        ?? $cfg['realtimekit_api_token']
        ?? getenv('CLOUDFLARE_API_TOKEN')
        ?? ''
    ));
    $preset = trim((string)($cfg['realtimekit_preset'] ?? getenv('REALTIMEKIT_PRESET') ?? ($isTeacher ? 'host' : 'participant')));

    $safe = [
        'account_id' => $accountId !== '' ? $accountId : 'MISSING',
        'account_id_length' => strlen($accountId),
        'app_id' => $appId !== '' ? $appId : 'MISSING',
        'app_id_length' => strlen($appId),
        'api_token' => $apiToken !== '' ? 'PRESENT (hidden)' : 'MISSING',
        'api_token_length' => strlen($apiToken),
        'preset' => $preset !== '' ? $preset : 'MISSING',
        'class_id' => $classId,
        'room_code' => (string)$class['room_code'],
        'class_status' => (string)$class['status'],
        'realtime_meeting_id' => trim((string)($class['realtime_meeting_id'] ?? '')) ?: 'NOT CREATED',
        'user_id' => (int)$user['id'],
        'user_role' => (string)$user['role'],
        'access' => $allowed ? 'ALLOWED' : 'DENIED',
    ];

    $missing = [];
    if ($accountId === '') $missing[] = 'cloudflare_account_id';
    if ($appId === '') $missing[] = 'realtimekit_app_id';
    if ($apiToken === '') $missing[] = 'cloudflare_api_token';
    if ($missing) {
        throw new RuntimeException('این موارد در data.php خالی یا نامعتبر هستند: '.implode('، ', $missing));
    }

    $meetingId = trim((string)($class['realtime_meeting_id'] ?? ''));
    $api = 'https://api.cloudflare.com/client/v4/accounts/'.rawurlencode($accountId).'/realtime/kit/'.rawurlencode($appId);

    if ($meetingId === '') {
        $ch = curl_init($api.'/meetings');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiToken, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['title' => $class['title']], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('ارتباط با Cloudflare برقرار نشد: '.$curlErr);
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('ساخت Meeting در RealtimeKit ناموفق بود: '.cloudflare_error_message($raw, $status));
        }
        $data = json_decode($raw, true);
        $meetingId = (string)($data['data']['id'] ?? '');
        if ($meetingId === '') throw new RuntimeException('شناسه Meeting از Cloudflare دریافت نشد.');
        $pdo->prepare('UPDATE classes SET realtime_meeting_id=?,updated_at=NOW() WHERE id=? AND (realtime_meeting_id IS NULL OR realtime_meeting_id=?)')->execute([$meetingId,$classId,'']);
        $safe['realtime_meeting_id'] = $meetingId;
    }

    $body = [
        'custom_participant_id' => 'kadad-user-'.$user['id'],
        'preset_name' => $preset,
        'name' => (string)$user['name'],
    ];
    $ch = curl_init($api.'/meetings/'.rawurlencode($meetingId).'/participants');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiToken, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('ارتباط با Cloudflare برقرار نشد: '.$curlErr);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('صدور دسترسی RealtimeKit ناموفق بود: '.cloudflare_error_message($raw, $status));
    }
    $data = json_decode($raw, true);
    $token = (string)($data['data']['token'] ?? '');
    if ($token === '') throw new RuntimeException('توکن RealtimeKit دریافت نشد.');

    $safe['create_meeting'] = 'OK';
    $safe['participant_token'] = 'ISSUED (hidden)';
    echo json_encode(['ok'=>true,'token'=>$token,'meeting_id'=>$meetingId,'diagnostics'=>$safe], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    $diag = isset($safe) && is_array($safe) ? $safe : [];
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'diagnostics'=>$diag], JSON_UNESCAPED_UNICODE);
}
