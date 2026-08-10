<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$data_dir = __DIR__ . '/../data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}
$log_file = $data_dir . '/visitor_logs.json';

// Lire les logs existants
$logs = [];
if (file_exists($log_file)) {
    $json = file_get_contents($log_file);
    if ($json) {
        $logs = json_decode($json, true) ?: [];
    }
}

// Map timezone -> country (light version of the Nuvio one)
function timezoneToCountry($tz) {
    $map = [
        'Africa/Abidjan' => 'Ivory Coast',
        'Africa/Dakar' => 'Senegal',
        'Europe/Paris' => 'France',
        'Europe/London' => 'United Kingdom',
        'America/New_York' => 'United States'
    ];
    return $map[$tz] ?? null;
}

function getUserIpAddr() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

if ($action === 'enter') {
    $session_id  = $data['session_id'] ?? '';
    $page_url    = $data['page_url'] ?? '';
    $device_type = $data['device_type'] ?? 'Desktop';
    $os          = $data['os'] ?? 'Unknown';
    $browser     = $data['browser'] ?? 'Unknown';
    $language    = $data['language'] ?? 'Unknown';
    $screen_res  = $data['screen_res'] ?? 'Unknown';
    $referrer    = $data['referrer'] ?? 'Direct';
    $timezone    = $data['timezone'] ?? '';

    $ip_address = getUserIpAddr();
    
    // Détecter les robots
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $is_bot = (str_contains($user_agent, 'bot') || str_contains($user_agent, 'crawl'));

    if ($is_bot) {
        $country = '🤖 Bot / Crawler';
        $city = 'Unknown Bot';
    } else {
        $country = timezoneToCountry($timezone) ?: 'Unknown';
        $city = $timezone ?: 'Unknown';
        // En vrai, il faudrait utiliser une API IP, on simplifie pour le FTP
    }

    $log_id = uniqid('log_', true);
    
    $new_log = [
        'id' => $log_id,
        'session_id' => $session_id,
        'ip_address' => $ip_address,
        'country' => $country,
        'city' => $city,
        'device_type' => $device_type,
        'os' => $os,
        'browser' => $browser,
        'page_url' => $page_url,
        'language' => $language,
        'screen_res' => $screen_res,
        'referrer' => $referrer,
        'time_spent' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $logs[] = $new_log;
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));

    echo json_encode(["status" => "success", "log_id" => $log_id]);

} elseif ($action === 'leave') {
    $log_id     = $data['log_id'] ?? null;
    $time_spent = $data['time_spent'] ?? 0;

    if ($log_id && $time_spent > 0) {
        $found = false;
        foreach ($logs as &$log) {
            if ($log['id'] === $log_id) {
                $log['time_spent'] += $time_spent;
                $found = true;
                break;
            }
        }
        if ($found) {
            file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
        }
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Données invalides"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Action non reconnue"]);
}
?>
