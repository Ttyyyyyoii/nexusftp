<?php
// github_status.php
// Renvoie les infos du dernier déploiement GitHub pour auto-actualisation
require_once __DIR__ . '/../config.php';

$deploymentsDir = __DIR__ . '/../deployments/';
$statusFile = $deploymentsDir . 'last_deploy.json';

if (!file_exists($statusFile)) {
    sendSuccess(['lastDeploy' => null]);
}

$statusData = json_decode(file_get_contents($statusFile), true);
sendSuccess(['lastDeploy' => $statusData]);
?>
