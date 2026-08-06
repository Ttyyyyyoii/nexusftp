<?php
// github_manage.php
// Liste et supprime les déploiements GitHub actifs pour la connexion FTP courante.

require_once __DIR__ . '/../config.php';

$input     = getInput();
$action    = $input['action']    ?? 'list';  // list | delete
$sessionId = $input['sessionId'] ?? '';
$token     = $input['token']     ?? ''; // Webhook token (pour delete)
$oauthToken= $input['oauthToken']?? ''; // GitHub OAuth token (pour delete le hook côté GitHub)

if (!$sessionId) {
    sendError('sessionId manquant');
}

$session = loadSession($sessionId);
if (!$session) {
    sendError('Session invalide', 401);
}

$deploymentsDir = __DIR__ . '/../deployments/';
if (!is_dir($deploymentsDir)) {
    sendSuccess(['deployments' => []]);
}

// ─── ACTION : LIST ───────────────────────────────────────────────────────────
if ($action === 'list') {
    $files = glob($deploymentsDir . '*.json');
    $result = [];

    foreach ($files as $file) {
        if (basename($file) === 'last_deploy.json') continue;
        
        $config = json_decode(file_get_contents($file), true);
        if (!$config) continue;

        // Filtrer par connexion FTP courante (host + username)
        if ($config['host'] !== $session['host'] || $config['username'] !== $session['username']) {
            continue;
        }

        $result[] = [
            'token'       => basename($file, '.json'),
            'githubRepo'  => $config['githubRepo']   ?? '—',
            'githubBranch'=> $config['githubBranch'] ?? 'main',
            'remotePath'  => $config['remotePath']   ?? '—',
            'hookId'      => $config['hookId']        ?? null,
            'createdAt'   => $config['createdAt']     ?? null,
        ];
    }

    // Tri par date de création (plus récent en premier)
    usort($result, fn($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));

    sendSuccess(['deployments' => $result]);
}

// ─── ACTION : DELETE ─────────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$token) sendError('Token du déploiement manquant');

    $configFile = $deploymentsDir . basename($token) . '.json';
    if (!file_exists($configFile)) {
        sendError('Déploiement introuvable');
    }

    $config = json_decode(file_get_contents($configFile), true);

    // Vérification de sécurité : ce déploiement appartient bien à la connexion courante
    if ($config['host'] !== $session['host'] || $config['username'] !== $session['username']) {
        sendError('Accès refusé', 403);
    }

    $githubError = null;

    // Supprimer le webhook côté GitHub (si on a le token OAuth et le hookId)
    if ($oauthToken && !empty($config['hookId'])) {
        $repo   = $config['githubRepo'];
        $hookId = $config['hookId'];

        $ch = curl_init("https://api.github.com/repos/$repo/hooks/$hookId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $oauthToken",
            'Accept: application/vnd.github+json',
            'User-Agent: NexusFTP',
            'X-GitHub-Api-Version: 2022-11-28'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 204 = supprimé, 404 = déjà supprimé — les deux sont OK
        if ($status !== 204 && $status !== 404) {
            $githubError = "Webhook GitHub non supprimé (code $status). Vous devrez le supprimer manuellement.";
        }
    }

    // Supprimer le fichier de config local
    unlink($configFile);

    sendSuccess([
        'message'     => 'Déploiement supprimé avec succès',
        'githubError' => $githubError
    ]);
}

sendError('Action inconnue');
?>
