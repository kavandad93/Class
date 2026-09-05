<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/presets.php';

header('Content-Type: application/json; charset=utf-8');
$user = require_auth();
$pdo = db();
$classId = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
$targetId = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

if ($classId < 1 || $targetId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'شناسه نامعتبر است'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = $pdo->prepare('SELECT id,title,teacher_id,realtime_meeting_id FROM classes WHERE id=? LIMIT 1');
$q->execute([$classId]);
$class = $q->fetch();
if (!$class) {
    http_response_code(404);
    echo json_encode(['error' => 'کلاس پیدا نشد'], JSON_UNESCAPED_UNICODE);
    exit;
}

$isManager = $user['role'] === 'admin' || (int)$class['teacher_id'] === (int)$user['id'];
if (!$isManager) {
    http_response_code(403);
    echo json_encode(['error' => 'فقط مدرس یا مدیر می‌تواند مجوزها را تغییر دهد'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = $pdo->prepare("SELECT cp.user_id,u.name,u.email,cp.role,cp.can_audio,cp.can_video,cp.can_screen_share FROM class_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.class_id=? AND cp.user_id=? LIMIT 1");
$q->execute([$classId, $targetId]);
$target = $q->fetch();
if (!$target) {
    http_response_code(404);
    echo json_encode(['error' => 'این کاربر عضو کلاس نیست'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($target['role'] !== 'student') {
    http_response_code(422);
    echo json_encode(['error' => 'مجوز رسانه‌ای برای مدرس یا مدیر قابل تغییر نیست'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'user' => ['id' => (int)$target['user_id'], 'name' => $target['name'], 'email' => $target['email']],
        'permissions' => [
            'audio' => (bool)$target['can_audio'],
            'video' => (bool)$target['can_video'],
            'screen_share' => (bool)$target['can_screen_share'],
            'chat' => true,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    verify_csrf();
    $permission = (string)($_POST['permission'] ?? '');
    if (!in_array($permission, ['audio', 'video', 'screen_share'], true)) {
        throw new InvalidArgumentException('مجوز نامعتبر است');
    }
    $value = !empty($_POST['value']) ? 1 : 0;
    $flags = [
        'audio' => (int)$target['can_audio'],
        'video' => (int)$target['can_video'],
        'screen_share' => (int)$target['can_screen_share'],
    ];
    $flags[$permission] = $value;

    $apiCfg = rtk_api_config();
    [$accountId, $appId, $apiToken] = $apiCfg;
    $api = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/realtime/kit/' . rawurlencode($appId);
    $preset = rtk_ensure_permission_preset($api, $apiToken, $flags['audio'], $flags['video'], $flags['screen_share']);

    if (!empty($class['realtime_meeting_id'])) {
        $participants = rtk_request('GET', $api . '/meetings/' . rawurlencode((string)$class['realtime_meeting_id']) . '/participants?per_page=100', $apiToken);
        foreach (($participants['data'] ?? []) as $p) {
            if (is_array($p) && ($p['custom_participant_id'] ?? '') === 'kadad-user-' . $targetId) {
                $rtkId = (string)($p['id'] ?? '');
                if ($rtkId !== '') {
                    rtk_request('PATCH', $api . '/meetings/' . rawurlencode((string)$class['realtime_meeting_id']) . '/participants/' . rawurlencode($rtkId), $apiToken, ['preset_name' => $preset]);
                }
                break;
            }
        }
    }

    $pdo->prepare('UPDATE class_participants SET can_audio=?,can_video=?,can_screen_share=?,updated_at=NOW() WHERE class_id=? AND user_id=?')
        ->execute([$flags['audio'], $flags['video'], $flags['screen_share'], $classId, $targetId]);

    echo json_encode([
        'ok' => true,
        'user_id' => $targetId,
        'permissions' => [
            'audio' => (bool)$flags['audio'],
            'video' => (bool)$flags['video'],
            'screen_share' => (bool)$flags['screen_share'],
            'chat' => true,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'تغییر مجوز انجام نشد', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
