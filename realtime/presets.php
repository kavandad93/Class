<?php
declare(strict_types=1);

function rtk_api_config(): array
{
    $cfgFile = '/home2/kadad/data.php';
    if (!is_file($cfgFile) || !is_readable($cfgFile)) throw new RuntimeException('فایل data.php قابل خواندن نیست.');
    $cfg = (array)require $cfgFile;
    $accountId = trim((string)($cfg['cloudflare_account_id'] ?? ''));
    $appId = trim((string)($cfg['realtimekit_app_id'] ?? ''));
    $apiToken = trim((string)($cfg['cloudflare_api_token'] ?? $cfg['realtimekit_api_token'] ?? ''));
    if ($accountId === '' || $appId === '' || $apiToken === '') throw new RuntimeException('تنظیمات Cloudflare RealtimeKit در data.php کامل نیست.');
    return [$accountId, $appId, $apiToken];
}

function rtk_request(string $method, string $url, string $token, ?array $body = null): array
{
    $ch = curl_init($url);
    $headers = ['Authorization: Bearer '.$token, 'Content-Type: application/json'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_HTTPHEADER=>$headers, CURLOPT_TIMEOUT=>20]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    $raw = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($raw === false) throw new RuntimeException('ارتباط با Cloudflare برقرار نشد: '.$err);
    $data = json_decode($raw, true);
    if ($status < 200 || $status >= 300 || !is_array($data) || ($data['success'] ?? false) !== true) {
        $msg = '';
        foreach (($data['errors'] ?? []) as $e) if (is_array($e) && !empty($e['message'])) $msg .= ($msg ? ' | ' : '').$e['message'];
        throw new RuntimeException('RealtimeKit HTTP '.$status.($msg ? ': '.$msg : ''));
    }
    return $data;
}

function rtk_permission_preset_name(int $audio, int $video, int $screen): string
{
    return 'kadad_student_'.($audio ? 'a1' : 'a0').'_'.($video ? 'v1' : 'v0').'_'.($screen ? 's1' : 's0');
}

function rtk_ensure_permission_preset(string $api, string $token, int $audio, int $video, int $screen): string
{
    $name = rtk_permission_preset_name($audio, $video, $screen);
    $list = rtk_request('GET', $api.'/presets?per_page=100', $token);
    foreach (($list['data'] ?? []) as $p) if (is_array($p) && strcasecmp((string)($p['name'] ?? ''), $name) === 0) return $name;

    $body = [
        'config' => [
            'max_screenshare_count' => 1,
            'max_video_streams' => ['desktop'=>25,'mobile'=>9],
            'media' => [
                'screenshare' => ['frame_rate'=>5,'quality'=>'hd'],
                'video' => ['frame_rate'=>30,'quality'=>'hd','simulcast'=>true],
                'audio' => ['enable_high_bitrate'=>true,'enable_stereo'=>true],
            ],
            'view_type' => 'GROUP_CALL',
        ],
        'name' => $name,
        'permissions' => [
            'chat' => [
                'private' => ['can_receive'=>true,'can_send'=>false,'files'=>false,'text'=>false],
                'public' => ['can_send'=>true,'files'=>false,'text'=>true],
            ],
            'media' => [
                'audio' => ['can_produce'=>$audio ? 'ALLOWED' : 'NOT_ALLOWED'],
                'video' => ['can_produce'=>$video ? 'ALLOWED' : 'NOT_ALLOWED'],
                'screenshare' => ['can_produce'=>$screen ? 'ALLOWED' : 'NOT_ALLOWED'],
            ],
            'show_participant_list' => true,
            'waiting_room_type' => 'SKIP',
            'stage_access' => 'NOT_ALLOWED',
            'stage_enabled' => false,
            'accept_waiting_requests' => false,
            'can_accept_production_requests' => false,
            'can_change_participant_permissions' => false,
            'can_edit_display_name' => false,
            'can_livestream' => false,
            'can_record' => false,
            'can_spotlight' => false,
            'disable_participant_audio' => false,
            'disable_participant_video' => false,
            'disable_participant_screensharing' => false,
            'hidden_participant' => false,
            'kick_participant' => false,
            'pin_participant' => false,
            'plugins' => ['can_close'=>false,'can_edit_config'=>false,'can_start'=>false,'config'=>[]],
            'polls' => ['can_create'=>false,'can_view'=>false,'can_vote'=>false],
            'connected_meetings' => ['can_alter_connected_meetings'=>false,'can_switch_connected_meetings'=>false,'can_switch_to_parent_meeting'=>false],
            'recorder_type' => 'NONE',
        ],
    ];
    rtk_request('POST', $api.'/presets', $token, $body);
    return $name;
}
