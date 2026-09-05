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
    $headers = ['Authorization: Bearer '.$token, 'Content-Type: application/json', 'Accept: application/json'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_HTTPHEADER=>$headers, CURLOPT_TIMEOUT=>20, CURLOPT_CONNECTTIMEOUT=>10]);
    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) throw new RuntimeException('ساخت درخواست RealtimeKit ناموفق بود.');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }
    $raw = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($raw === false) throw new RuntimeException('ارتباط با Cloudflare برقرار نشد: '.$err);
    $data = json_decode($raw, true);
    if ($status < 200 || $status >= 300 || !is_array($data) || ($data['success'] ?? false) !== true) {
        $parts=[]; foreach (($data['errors']??[]) as $e) if(is_array($e)&&!empty($e['message'])) $parts[]=(isset($e['code'])?'['.$e['code'].'] ':'').(string)$e['message'];
        if(!$parts&&is_array($data)&&isset($data['message']))$parts[]=(string)$data['message'];
        $detail=$parts?implode(' | ',$parts):trim((string)$raw); if($detail==='')$detail='پاسخ خالی از Cloudflare دریافت شد.'; if(mb_strlen($detail)>1200)$detail=mb_substr($detail,0,1200).'…';
        $path=parse_url($url,PHP_URL_PATH)?:''; throw new RuntimeException('RealtimeKit HTTP '.$status.' — '.$method.' '.$path.' — '.$detail);
    }
    return $data;
}

function rtk_permission_preset_name(int $audio,int $video,int $screen): string { return 'kadad_student_'.($audio?'a1':'a0').'_'.($video?'v1':'v0').'_'.($screen?'s1':'s0'); }

function rtk_ensure_permission_preset(string $api,string $token,int $audio,int $video,int $screen): string
{
    $name=rtk_permission_preset_name($audio,$video,$screen);
    $list=rtk_request('GET',$api.'/presets?per_page=100',$token);
    foreach(($list['data']??[]) as $p) if(is_array($p)&&strcasecmp((string)($p['name']??''),$name)===0)return $name;

    $body=[
        'config'=>[
            'max_screenshare_count'=>1,
            'max_video_streams'=>['desktop'=>25,'mobile'=>9],
            'media'=>[
                'screenshare'=>['frame_rate'=>5,'quality'=>'hd'],
                'video'=>['frame_rate'=>30,'quality'=>'hd','simulcast'=>true],
                'audio'=>['enable_high_bitrate'=>true,'enable_stereo'=>true],
            ],
            'view_type'=>'GROUP_CALL',
        ],
        'name'=>$name,
        'permissions'=>[
            'chat'=>[
                'private'=>['can_receive'=>true,'can_send'=>false,'files'=>false,'text'=>false],
                'public'=>['can_send'=>true,'files'=>false,'text'=>true],
            ],
            'media'=>[
                'audio'=>['can_produce'=>$audio?'ALLOWED':'NOT_ALLOWED'],
                'video'=>['can_produce'=>$video?'ALLOWED':'NOT_ALLOWED'],
                'screenshare'=>['can_produce'=>$screen?'ALLOWED':'NOT_ALLOWED'],
            ],
            'show_participant_list'=>true,
            'waiting_room_type'=>'SKIP',
            'stage_access'=>'NOT_ALLOWED',
            'stage_enabled'=>false,
            'accept_waiting_requests'=>false,
            'can_accept_production_requests'=>false,
            'can_change_participant_permissions'=>false,
            'can_edit_display_name'=>false,
            'can_livestream'=>false,
            'can_record'=>false,
            'can_spotlight'=>false,
            'disable_participant_audio'=>false,
            'disable_participant_video'=>false,
            'disable_participant_screensharing'=>false,
            'hidden_participant'=>false,
            'kick_participant'=>false,
            'pin_participant'=>false,
            'plugins'=>['can_close'=>false,'can_edit_config'=>false,'can_start'=>false,'config'=>new stdClass()],
            'polls'=>['can_create'=>false,'can_view'=>false,'can_vote'=>false],
            'connected_meetings'=>['can_alter_connected_meetings'=>false,'can_switch_connected_meetings'=>false,'can_switch_to_parent_meeting'=>false],
            'recorder_type'=>'NONE',
        ],
        'ui'=>[
            'design_tokens'=>[
                'border_radius'=>'rounded',
                'border_width'=>'thin',
                'colors'=>[
                    'background'=>['600'=>'#111827','700'=>'#0f172a','800'=>'#0b1220','900'=>'#070d18','1000'=>'#030712'],
                    'brand'=>['300'=>'#a5b4fc','400'=>'#818cf8','500'=>'#6366f1','600'=>'#4f46e5','700'=>'#4338ca'],
                    'danger'=>'#ef4444','success'=>'#22c55e','text'=>'#f8fafc','text_on_brand'=>'#ffffff','video_bg'=>'#030712','warning'=>'#f59e0b'
                ],
                'spacing_base'=>4,
                'theme'=>'dark'
            ]
        ]
    ];
    rtk_request('POST',$api.'/presets',$token,$body);
    return $name;
}
