<?php
// github_repos.php
// Retourne la liste des dépôts de l'utilisateur connecté via son access_token GitHub

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true) ?: [];
$token = $input['token'] ?? '';

if (!$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token manquant']);
    exit;
}

// Récupérer les dépôts (jusqu'à 100, triés par date de mise à jour)
function githubGet($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        'Accept: application/vnd.github+json',
        'User-Agent: NexusFTP',
        'X-GitHub-Api-Version: 2022-11-28'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($body, true)];
}

// Récupérer les repos de l'utilisateur
$reposRes = githubGet('https://api.github.com/user/repos?per_page=100&sort=updated&affiliation=owner,collaborator', $token);

if ($reposRes['status'] !== 200) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
    exit;
}

// Récupérer le profil pour le nom d'utilisateur
$userRes = githubGet('https://api.github.com/user', $token);
$username = $userRes['data']['login'] ?? '';

$repos = array_map(function($r) {
    return [
        'fullName'        => $r['full_name'],
        'name'            => $r['name'],
        'owner'           => $r['owner']['login'],
        'private'         => $r['private'],
        'defaultBranch'   => $r['default_branch'],
        'description'     => $r['description'] ?? '',
        'updatedAt'       => $r['updated_at'],
    ];
}, $reposRes['data']);

echo json_encode(['success' => true, 'repos' => $repos, 'username' => $username]);
?>
