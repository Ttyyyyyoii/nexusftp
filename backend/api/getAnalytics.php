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
$app_pages = [];
$visitors = []; // Aggregated by session/ip

foreach ($logs as $log) {
    $session = $log['session_id'];
    $unique_sessions[$session] = true;
    $total_time_spent += $log['time_spent'];
    
    if ($log['country'] && $log['country'] !== 'Unknown' && $log['country'] !== 'Localhost') {
        $countries[$log['country']] = true;
    }

    $url = $log['page_url'];
    if (!isset($app_pages[$url])) {
        $app_pages[$url] = ['views' => 0, 'time_spent' => 0, 'active_users' => []];
    }
    $app_pages[$url]['views']++;
    $app_pages[$url]['time_spent'] += $log['time_spent'];
    
    // Si la vue date de moins de 5 minutes, on le compte comme "actif" sur cette page
    if (strtotime($log['created_at']) > time() - 300) {
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
            'first_visit' => $log['created_at']
        ];
    }
    $visitors[$session]['pages_visited']++;
    $visitors[$session]['total_time_spent'] += $log['time_spent'];
}

$average_time = $total_page_views > 0 ? floor($total_time_spent / count($unique_sessions)) : 0;

// Format app_pages
$formatted_app_pages = [];
foreach ($app_pages as $url => $data) {
    $formatted_app_pages[] = [
        'url' => $url,
        'views' => $data['views'],
        'time_spent' => $data['time_spent'],
        'active_users' => count($data['active_users'])
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_sessions' => count($unique_sessions),
        'total_page_views' => $total_page_views,
        'average_time_spent' => $average_time,
        'countries' => array_keys($countries),
        'app_pages' => $formatted_app_pages,
        'visitors' => array_values($visitors)
    ]
]);
?>
