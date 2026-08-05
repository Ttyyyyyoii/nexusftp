<?php
require_once __DIR__ . '/config.php';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '';
$basePath = dirname($scriptName);
$route = trim(str_replace($basePath, '', $path), '/');

if (empty($route)) {
    sendSuccess([
        'message' => 'NexusFTP API Server', 'version' => API_VERSION, 'status' => 'running', 'time' => date('c'),
        'endpoints' => [
            'POST /api/connect.php - Connect to server', 'POST /api/disconnect.php - Disconnect',
            'POST /api/list.php - List directory', 'POST /api/upload.php - Upload file',
            'POST /api/download.php - Download file', 'POST /api/mkdir.php - Create directory',
            'POST /api/delete.php - Delete file/directory', 'POST /api/rename.php - Rename file/directory'
        ]
    ]);
}

$apiFile = __DIR__ . '/api/' . $route;
if (file_exists($apiFile) && is_file($apiFile)) require_once $apiFile;
else sendError('Endpoint not found: ' . $route, 404);
?>
