<?php
// github_oauth_callback.php
// Reçoit le code OAuth de GitHub, l'échange contre un access_token,
// puis envoie le token à la fenêtre parente via postMessage et ferme le popup.

$clientId     = 'Ov23liyWoYP9G3BMK5q8';
$clientSecret = '79995788ab7aed53ed76807a831bbdb17a2abb6a';

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

function renderPopupMessage(string $type, array $data): void {
    $jsonData = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>NexusFTP — Connexion GitHub</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #0d1117; color: #c9d1d9; }
    .box { text-align: center; padding: 2rem; }
    .icon { font-size: 3rem; margin-bottom: 1rem; }
    p { margin: 0; font-size: 0.9rem; opacity: 0.7; }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">✅</div>
    <p>Authentification réussie. Fermeture en cours...</p>
  </div>
  <script>
    (function() {
      var data = $jsonData;
      if (window.opener) {
        window.opener.postMessage(data, window.location.origin);
      }
      setTimeout(function() { window.close(); }, 800);
    })();
  </script>
</body>
</html>
HTML;
    exit;
}

// En cas d'annulation ou d'erreur
if ($error || !$code) {
    renderPopupMessage('GITHUB_OAUTH_ERROR', [
        'type'  => 'GITHUB_OAUTH_ERROR',
        'error' => $error ?: 'Connexion annulée'
    ]);
}

// Échanger le code contre un access_token
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'code'          => $code,
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
curl_close($ch);

$data        = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;

if (!$accessToken) {
    renderPopupMessage('GITHUB_OAUTH_ERROR', [
        'type'  => 'GITHUB_OAUTH_ERROR',
        'error' => 'Échange du token échoué'
    ]);
}

// Succès — envoyer le token à NexusFTP
renderPopupMessage('GITHUB_OAUTH_SUCCESS', [
    'type'  => 'GITHUB_OAUTH_SUCCESS',
    'token' => $accessToken
]);
?>
