<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$data_dir = __DIR__ . '/../data';
$log_file = $data_dir . '/visitor_logs.json';

$logs = [];
if (file_exists($log_file)) {
    $json = file_get_contents($log_file);
    if ($json) {
        $logs = json_decode($json, true) ?: [];
    }
}

// Analytics calculations
$total_page_views = count($logs);
$unique_sessions = [];
$total_time_spent = 0;
$countries = [];
$browsers = [];
$devices = [];
$app_pages = [];
$visitors = []; // Aggregated by session/ip

$active_users_threshold = time() - 300; // 5 minutes actives

foreach ($logs as $log) {
    $session = $log['session_id'];
    $unique_sessions[$session] = true;
    $total_time_spent += $log['time_spent'];
    
    // Country
    if ($log['country'] && $log['country'] !== 'Unknown' && $log['country'] !== 'Localhost') {
        $c = $log['country'];
        if (!isset($countries[$c])) $countries[$c] = 0;
        $countries[$c]++;
    }

    // Browser
    $b = $log['browser'] ?? 'Unknown';
    if (!isset($browsers[$b])) $browsers[$b] = 0;
    $browsers[$b]++;

    // Device
    $d = $log['device_type'] ?? 'Desktop';
    if (!isset($devices[$d])) $devices[$d] = 0;
    $devices[$d]++;

    // App Pages
    $url = $log['page_url'];
    if (!isset($app_pages[$url])) {
        $app_pages[$url] = ['views' => 0, 'time_spent' => 0, 'active_users' => []];
    }
    $app_pages[$url]['views']++;
    $app_pages[$url]['time_spent'] += $log['time_spent'];
    
    // Si la vue date de moins de 5 minutes, on le compte comme "actif" sur cette page
    if (strtotime($log['created_at']) > $active_users_threshold) {
        $app_pages[$url]['active_users'][$session] = true;
    }

    // Agrégation par visiteur (par session_id)
    if (!isset($visitors[$session])) {
        $visitors[$session] = [
            'session_id' => $session,
            'ip_address' => $log['ip_address'],
            'country' => $log['country'],
            'city' => $log['city'],
            'device_type' => $log['device_type'],
            'os' => $log['os'],
            'browser' => $log['browser'],
            'language' => $log['language'],
            'screen_res' => $log['screen_res'],
            'referrer' => $log['referrer'],
            'pages_visited' => 0,
            'total_time_spent' => 0,
            'pages_path' => '',
            'first_visit' => $log['created_at']
        ];
    }
    $visitors[$session]['pages_visited']++;
    $visitors[$session]['total_time_spent'] += $log['time_spent'];
    
    $path_part = explode('?', $url)[0];
    if (strpos($visitors[$session]['pages_path'], $path_part) === false) {
        $visitors[$session]['pages_path'] .= ($visitors[$session]['pages_path'] ? ' → ' : '') . $path_part;
    }
}

$average_time = $total_page_views > 0 ? floor($total_time_spent / count($unique_sessions)) : 0;

// Format countries
$formatted_countries = [];
foreach ($countries as $c => $count) {
    $formatted_countries[] = ['country' => $c, 'count' => $count];
}
usort($formatted_countries, function($a, $b) { return $b['count'] <=> $a['count']; });

// Format browsers
$formatted_browsers = [];
foreach ($browsers as $b => $count) {
    $formatted_browsers[] = ['browser' => $b, 'count' => $count];
}
usort($formatted_browsers, function($a, $b) { return $b['count'] <=> $a['count']; });

// Format devices
$formatted_devices = [];
foreach ($devices as $d => $count) {
    $formatted_devices[] = ['device_type' => $d, 'count' => $count];
}
usort($formatted_devices, function($a, $b) { return $b['count'] <=> $a['count']; });

// Format app_pages
$formatted_app_pages = [];
$total_active_sessions = [];
foreach ($app_pages as $url => $data) {
    $formatted_app_pages[] = [
        'page_url' => $url,
        'views' => $data['views'],
        'time_spent' => $data['time_spent'],
        'unique_users' => count($data['active_users']),
        'avg_time' => $data['views'] > 0 ? floor($data['time_spent'] / $data['views']) : 0
    ];
    foreach ($data['active_users'] as $sid => $v) {
        $total_active_sessions[$sid] = true;
    }
}
usort($formatted_app_pages, function($a, $b) { return $b['views'] <=> $a['views']; });

$top_pages = array_slice($formatted_app_pages, 0, 5);

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_sessions' => count($unique_sessions),
        'total_page_views' => $total_page_views,
        'average_time_spent' => $average_time,
        'countries' => $formatted_countries,
        'browsers' => $formatted_browsers,
        'devices' => $formatted_devices,
        'top_pages' => $top_pages,
        'app_pages' => $formatted_app_pages,
        'app_sessions' => count($total_active_sessions),
        'visitors' => array_values($visitors)
    ]
]);
?>
