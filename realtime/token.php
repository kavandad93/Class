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
    return 'HTTP '.$status.'.'.($parts ? ' '.implode(' | ', $parts) : '');
}

function cloudflare_get(string $url, string $apiToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiToken, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('ارتباط با Cloudflare برقرار نشد: '.$curlErr);
    return [$status, $raw];
}

function diagnostics(array $class, array $user, bool $allowed, string $accountId, string $appId, string $apiToken, string $configuredPreset, string $meetingId, array $presets = []): array
{
    return [
        'account_id' => $accountId !== '' ? $accountId : 'MISSING',
        'account_id_length' => strlen($accountId),
        'app_id' => $appId !== '' ? $appId : 'MISSING',
        'app_id_length' => strlen($appId),
        'api_token' => $apiToken !== '' ? 'PRESENT (hidden)' : 'MISSING',
        'api_token_length' => strlen($apiToken),
        'preset_configured' => $configuredPreset !== '' ? $configuredPreset : 'AUTO',
        'preset_count' => count($presets),
        'presets' => $presets,
        'class_id' => (int)$class['id'],
        'room_code' => (string)$class['room_code'],
        'class_status' => (string)$class['status'],
        'realtime_meeting_id' => $meetingId !== '' ? $meetingId : 'NOT CREATED',
        'user_id' => (int)$user['id'],
        'user_name' => (string)$user['name'],
        'user_role' => (string)$user['role'],
        'teacher_name' => (string)$class['teacher_name'],
        'access' => $allowed ? 'ALLOWED' : 'DENIED',
    ];
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
    if (!is_file($cfgFile) || !is_readable($cfgFile)) throw new RuntimeException('فایل /home2/kadad/data.php برای PHP قابل خواندن نیست.');
    $cfg = (array)require $cfgFile;

    $accountId = trim((string)($cfg['cloudflare_account_id'] ?? getenv('CLOUDFLARE_ACCOUNT_ID') ?? ''));
    $appId = trim((string)($cfg['realtimekit_app_id'] ?? getenv('REALTIMEKIT_APP_ID') ?? ''));
    $apiToken = trim((string)($cfg['cloudflare_api_token'] ?? $cfg['realtimekit_api_token'] ?? getenv('CLOUDFLARE_API_TOKEN') ?? ''));
    $configuredPreset = trim((string)($cfg['realtimekit_preset'] ?? getenv('REALTIMEKIT_PRESET') ?? ''));
    $meetingId = trim((string)($class['realtime_meeting_id'] ?? ''));
    $safe = diagnostics($class, $user, $allowed, $accountId, $appId, $apiToken, $configuredPreset, $meetingId);

    if ($accountId === '') throw new RuntimeException('cloudflare_account_id در data.php خالی است.');
    if ($appId === '') throw new RuntimeException('realtimekit_app_id در data.php خالی است.');
    if ($apiToken === '') throw new RuntimeException('cloudflare_api_token در data.php خالی است.');

    $api = 'https://api.cloudflare.com/client/v4/accounts/'.rawurlencode($accountId).'/realtime/kit/'.rawurlencode($appId);

    // Cloudflare's official endpoint returns the actual presets belonging to this App.
    [$presetStatus, $presetRaw] = cloudflare_get($api.'/presets?per_page=100', $apiToken);
    if ($presetStatus < 200 || $presetStatus >= 300) {
        $safe['preset_api_status'] = $presetStatus;
        throw new RuntimeException('دریافت Presetهای واقعی App ناموفق بود: '.cloudflare_error_message($presetRaw, $presetStatus));
    }
    $presetData = json_decode($presetRaw, true);
    $rows = is_array($presetData['data'] ?? null) ? $presetData['data'] : [];
    $presets = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $name = trim((string)($row['name'] ?? ''));
        $id = trim((string)($row['id'] ?? ''));
        if ($name !== '' || $id !== '') $presets[] = ['name' => $name, 'id' => $id];
    }
    $safe['presets'] = $presets;
    $safe['preset_count'] = count($presets);
    $safe['preset_api_status'] = $presetStatus;

    $presetName = $configuredPreset;
    if ($presetName === '') {
        $preferred = $isTeacher ? ['host','teacher','moderator','admin'] : ['participant','student','attendee','guest'];
        foreach ($preferred as $candidate) {
            foreach ($presets as $p) {
                if (strcasecmp($p['name'], $candidate) === 0) {
                    $presetName = $p['name'];
                    break 2;
                }
            }
        }
    }
    if ($presetName === '' && count($presets) === 1) $presetName = $presets[0]['name'];
    if ($presetName === '') {
        $names = array_values(array_filter(array_map(static fn($p) => $p['name'], $presets)));
        throw new RuntimeException('هیچ Preset مناسبی انتخاب نشد. Presetهای واقعی App: '.($names ? implode('، ', $names) : 'هیچ‌کدام'));
    }

    $selectedPreset = null;
    foreach ($presets as $p) {
        if (strcasecmp($p['name'], $presetName) === 0) { $selectedPreset = $p; break; }
    }
    if (!$selectedPreset) {
        $names = array_values(array_filter(array_map(static fn($p) => $p['name'], $presets)));
        throw new RuntimeException('Preset «'.$presetName.'» در App وجود ندارد. Presetهای واقعی: '.($names ? implode('، ', $names) : 'هیچ‌کدام'));
    }
    $safe['preset_selected'] = $selectedPreset['name'];
    $safe['preset_id'] = $selectedPreset['id'];

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
        if ($status < 200 || $status >= 300) throw new RuntimeException('ساخت Meeting در RealtimeKit ناموفق بود: '.cloudflare_error_message($raw, $status));
        $data = json_decode($raw, true);
        $meetingId = (string)($data['data']['id'] ?? '');
        if ($meetingId === '') throw new RuntimeException('شناسه Meeting از Cloudflare دریافت نشد.');
        $pdo->prepare('UPDATE classes SET realtime_meeting_id=?,updated_at=NOW() WHERE id=? AND (realtime_meeting_id IS NULL OR realtime_meeting_id=?)')->execute([$meetingId,$classId,'']);
        $safe['realtime_meeting_id'] = $meetingId;
        $safe['create_meeting'] = 'OK';
    } else {
        $safe['create_meeting'] = 'EXISTING';
    }

    $body = ['custom_participant_id'=>'kadad-user-'.$user['id'],'preset_name'=>$presetName,'name'=>(string)$user['name']];
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
    if ($status < 200 || $status >= 300) throw new RuntimeException('صدور دسترسی RealtimeKit ناموفق بود: '.cloudflare_error_message($raw, $status));
    $data = json_decode($raw, true);
    $token = (string)($data['data']['token'] ?? '');
    if ($token === '') throw new RuntimeException('توکن RealtimeKit دریافت نشد.');

    $safe['participant_token'] = 'ISSUED (hidden)';
    echo json_encode(['ok'=>true,'token'=>$token,'meeting_id'=>$meetingId,'diagnostics'=>$safe], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    $diag = isset($safe) && is_array($safe) ? $safe : [];
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'diagnostics'=>$diag], JSON_UNESCAPED_UNICODE);
}
