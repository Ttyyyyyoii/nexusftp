<?php
// Script appelé par GitHub lors d'un Push
// Endpoint: POST /api/github_webhook.php?token=...

require_once __DIR__ . '/../config.php';

// Les webhooks GitHub envoient du JSON brut dans le body
$jsonPayload = file_get_contents('php://input');
$payload = json_decode($jsonPayload, true);

$token = $_GET['token'] ?? '';

// Debug log function
function logWebhook($msg) {
    $logFile = __DIR__ . '/../deployments/webhook_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}
logWebhook("Received webhook for token: " . $token);

if (!$token) {
    logWebhook("Error: Missing token");
    http_response_code(400);
    die('Missing token');
}

// Vérifier si c'est un ping GitHub
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($event === 'ping') {
    http_response_code(200);
    echo "Ping reçu avec succès !";
    exit;
}

if ($event !== 'push') {
    http_response_code(200);
    echo "L'événement n'est pas un push. Ignoré.";
    exit;
}

// Charger la configuration du déploiement
$deploymentsDir = __DIR__ . '/../deployments/';
$configFile = $deploymentsDir . $token . '.json';

if (!file_exists($configFile)) {
    logWebhook("Error: Invalid deployment token (file not found)");
    http_response_code(404);
    die('Invalid deployment token');
}

$config = json_decode(file_get_contents($configFile), true);
logWebhook("Loaded config for repo: " . $config['githubRepo']);

// Vérifier la branche
$ref = $payload['ref'] ?? ''; // ex: refs/heads/main
$pushedBranch = str_replace('refs/heads/', '', $ref);

if ($pushedBranch !== $config['githubBranch']) {
    http_response_code(200);
    echo "Push ignoré car la branche ($pushedBranch) ne correspond pas à la branche cible ({$config['githubBranch']}).";
    exit;
}

// L'URL pour télécharger le Zipball
// Pour les dépôts privés, on peut devoir passer le PAT dans l'URL ou les headers
$githubToken = $config['githubToken'] ? decryptPassword($config['githubToken']) : null;

// Ex: https://api.github.com/repos/Ttyyyyyoii/nexusftp/zipball/main
$zipUrl = "https://api.github.com/repos/{$config['githubRepo']}/zipball/{$config['githubBranch']}";

$ch = curl_init($zipUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'NexusFTP-Webhook');
if ($githubToken) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken"
    ]);
}

$zipContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$zipContent) {
    logWebhook("Error downloading zip: HTTP $httpCode");
    http_response_code(500);
    die("Échec du téléchargement du dépôt (Code: $httpCode). Vérifiez que le dépôt est public ou que le token PAT est correct.");
}
logWebhook("Zip downloaded successfully (" . strlen($zipContent) . " bytes)");

// Sauvegarder le Zip localement
$tempZip = tempnam(sys_get_temp_dir(), 'nexus_github_');
file_put_contents($tempZip, $zipContent);

// Extraire le Zip
$tempExtractDir = sys_get_temp_dir() . '/nexus_extract_' . uniqid();
mkdir($tempExtractDir);

$zip = new ZipArchive();
if ($zip->open($tempZip) === TRUE) {
    $zip->extractTo($tempExtractDir);
    $zip->close();
} else {
    unlink($tempZip);
    http_response_code(500);
    die('Échec de l\'extraction du Zip.');
}
unlink($tempZip);

// GitHub met les fichiers dans un sous-dossier (ex: user-repo-commitHash)
$extractedDirs = glob($tempExtractDir . '/*', GLOB_ONLYDIR);
if (empty($extractedDirs)) {
    http_response_code(500);
    die('Le Zip extrait est vide.');
}
$sourceDir = $extractedDirs[0]; // C'est ici que sont les vrais fichiers

// Se connecter au FTP
$ftp = ftp_connect($config['host'], $config['port']);
if (!$ftp) {
    logWebhook("FTP connection failed to " . $config['host']);
    http_response_code(500);
    die('Impossible de se connecter au serveur FTP.');
}
logWebhook("FTP connected. Attempting login...");

$password = decryptPassword($config['password']);
if (!ftp_login($ftp, $config['username'], $password)) {
    logWebhook("FTP login failed for user " . $config['username']);
    http_response_code(401);
    die('Échec de l\'authentification FTP.');
}
logWebhook("FTP login success.");

if ($config['passive']) ftp_pasv($ftp, true);

// Fonction récursive pour uploader un dossier entier
function ftpUploadDirectory($ftp, $localDir, $remoteDir) {
    $files = scandir($localDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $localFile = $localDir . '/' . $file;
        $remoteFile = $remoteDir . '/' . $file;
        
        if (is_dir($localFile)) {
            // Créer le dossier s'il n'existe pas
            @ftp_mkdir($ftp, $remoteFile);
            ftpUploadDirectory($ftp, $localFile, $remoteFile);
        } else {
            ftp_put($ftp, $remoteFile, $localFile, FTP_BINARY);
        }
    }
}

// Créer le dossier distant s'il n'existe pas
@ftp_mkdir($ftp, $config['remotePath']);

// Lancer l'upload
ftpUploadDirectory($ftp, $sourceDir, $config['remotePath']);

ftp_close($ftp);

// Nettoyer les fichiers temporaires locaux
function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
deleteDir($tempExtractDir);

// Enregistrer le succès du déploiement pour le frontend
$statusFile = $deploymentsDir . 'last_deploy.json';
$statusData = [
    'timestamp'  => time(),
    'remotePath' => $config['remotePath'],
    'repo'       => $config['githubRepo']
];
file_put_contents($statusFile, json_encode($statusData));

logWebhook("Deployment finished successfully for " . $config['githubRepo']);
http_response_code(200);
echo "Déploiement réussi !";
?>
