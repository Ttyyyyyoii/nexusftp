<?php
define('API_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 3600);
define('CHUNK_SIZE', 1048576);

header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function sendJson($data, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
function sendError($message, $code = 400) {
    sendJson(['success' => false, 'message' => $message, 'timestamp' => date('c')], $code);
}
function sendSuccess($data = []) {
    sendJson(array_merge(['success' => true, 'timestamp' => date('c')], $data));
}
function getInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: $_POST;
}
function generateSessionId() { return bin2hex(random_bytes(16)); }
function getSessionDir() {
    $dir = __DIR__ . '/sessions/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}
function saveSession($sessionId, $data) {
    $file = getSessionDir() . $sessionId . '.json';
    $data['lastAccess'] = time();
    file_put_contents($file, json_encode($data));
}
function loadSession($sessionId) {
    $file = getSessionDir() . $sessionId . '.json';
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    if (!$data || (time() - ($data['lastAccess'] ?? 0)) > SESSION_TIMEOUT) { unlink($file); return null; }
    return $data;
}
function deleteSession($sessionId) {
    $file = getSessionDir() . $sessionId . '.json';
    if (file_exists($file)) unlink($file);
}
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) { $bytes /= 1024; $unitIndex++; }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
function encryptPassword($password) {
    $key = getenv('NEXUS_KEY') ?: 'nexusftp-default-key';
    return base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
}
function decryptPassword($encrypted) {
    $key = getenv('NEXUS_KEY') ?: 'nexusftp-default-key';
    return openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}
?>
