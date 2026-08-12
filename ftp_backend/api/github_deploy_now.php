<?php
// github_deploy_now.php
// Déclenche immédiatement un déploiement (télécharge le zip GitHub et l'envoie sur FTP)
// Appelé quand l'utilisateur clique "Déployer maintenant" dans NexusFTP

require_once __DIR__ . '/../config.php';

$input     = getInput();
$sessionId = $input['sessionId'] ?? '';
$token     = $input['token']     ?? ''; // webhook token = identifiant du déploiement

if (!$sessionId || !$token) {
    sendError('sessionId et token requis');
}

// Vérifier la session FTP
$session = loadSession($sessionId);
if (!$session) {
    sendError('Session FTP invalide ou expirée. Reconnectez-vous au serveur FTP.', 401);
}

// Charger la config du déploiement
$deploymentsDir = __DIR__ . '/../deployments/';
$configFile = $deploymentsDir . $token . '.json';

if (!file_exists($configFile)) {
    sendError('Déploiement introuvable. Vérifiez que la liaison GitHub est active.');
}

$config = json_decode(file_get_contents($configFile), true);

// Récupérer le token GitHub (pour les dépôts privés)
$githubToken = !empty($config['githubToken']) ? decryptPassword($config['githubToken']) : null;

// Télécharger le zip du dépôt
$zipUrl = "https://api.github.com/repos/{$config['githubRepo']}/zipball/{$config['githubBranch']}";

$ch = curl_init($zipUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'NexusFTP-DeployNow');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
if ($githubToken) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "Accept: application/vnd.github+json"
    ]);
}

$zipContent = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$zipContent) {
    sendError("Impossible de télécharger le dépôt GitHub (HTTP $httpCode). Vérifiez que le dépôt est public ou que le token est valide.");
}

// Sauvegarder et extraire le zip
$tempZip        = tempnam(sys_get_temp_dir(), 'nexus_deploy_');
$tempExtractDir = sys_get_temp_dir() . '/nexus_deploy_' . uniqid();

file_put_contents($tempZip, $zipContent);
mkdir($tempExtractDir);

$zip = new ZipArchive();
if ($zip->open($tempZip) !== true) {
    unlink($tempZip);
    sendError('Impossible d\'extraire le dépôt téléchargé.');
}
$zip->extractTo($tempExtractDir);
$zip->close();
unlink($tempZip);

// GitHub zip a un dossier racine du type "repo-branch-sha"
$entries = scandir($tempExtractDir);
$rootDir = '';
foreach ($entries as $entry) {
    if ($entry !== '.' && $entry !== '..' && is_dir($tempExtractDir . '/' . $entry)) {
        $rootDir = $tempExtractDir . '/' . $entry;
        break;
    }
}

if (!$rootDir) {
    sendError('Structure du zip GitHub inattendue.');
}

// Connexion FTP
$host     = $config['host'];
$port     = (int)($config['port'] ?? 21);
$username = $config['username'];
$password = decryptPassword($config['password']);
$passive  = $config['passive'] ?? true;
$remotePath = rtrim($config['remotePath'], '/') . '/';

$ftp = @ftp_connect($host, $port, 30);
if (!$ftp) {
    sendError("Impossible de se connecter au serveur FTP ($host:$port).");
}
if (!@ftp_login($ftp, $username, $password)) {
    ftp_close($ftp);
    sendError('Identifiants FTP incorrects.');
}
if ($passive) {
    ftp_pasv($ftp, true);
}

// Uploader tous les fichiers récursivement
$uploadedCount = 0;
$errors = [];

function uploadDirectory($ftp, $localDir, $remoteDir, &$uploadedCount, &$errors) {
    // Créer le dossier distant si besoin
    @ftp_mkdir($ftp, $remoteDir);

    $items = scandir($localDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $localPath  = $localDir  . '/' . $item;
        $remotePath = $remoteDir . '/' . $item;

        if (is_dir($localPath)) {
            uploadDirectory($ftp, $localPath, $remotePath, $uploadedCount, $errors);
        } else {
            if (@ftp_put($ftp, $remotePath, $localPath, FTP_BINARY)) {
                $uploadedCount++;
            } else {
                $errors[] = $item;
            }
        }
    }
}

uploadDirectory($ftp, $rootDir, $remotePath, $uploadedCount, $errors);
ftp_close($ftp);

// Nettoyer les fichiers temporaires
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        is_dir($p) ? rrmdir($p) : unlink($p);
    }
    rmdir($dir);
}
rrmdir($tempExtractDir);

// Mettre à jour la config avec la date du dernier déploiement
$config['lastDeployedAt'] = date('c');
$config['lastDeployedFiles'] = $uploadedCount;
file_put_contents($configFile, json_encode($config));

sendSuccess([
    'message'      => "Déploiement terminé ! $uploadedCount fichier(s) envoyé(s) vers $remotePath",
    'filesUploaded' => $uploadedCount,
    'remotePath'   => $remotePath,
    'repo'         => $config['githubRepo'],
    'branch'       => $config['githubBranch'],
    'errors'       => $errors
]);
?>
