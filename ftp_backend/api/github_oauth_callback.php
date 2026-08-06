<?php
// github_oauth_callback.php
// Reçoit le code OAuth de GitHub, l'échange contre un access_token,
// puis ferme le popup en envoyant le token à la fenêtre parente via postMessage.

$clientId     = 'Ov23liyWoYP9G3BMK5q8';
$clientSecret = '79995788ab7aed53ed76807a831bbdb17a2abb6a';

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

// En cas d'annulation par l'utilisateur
if ($error || !$code) {
    echo "<script>
        window.opener && window.opener.postMessage({ type: 'GITHUB_OAUTH_ERROR', error: '" . htmlspecialchars($error ?: 'Cancelled') . "' }, '*');
        window.close();
    </script>";
    exit;
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

$response = curl_exec($ch);
curl_close($ch);

$data        = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;

if (!$accessToken) {
    echo "<script>
        window.opener && window.opener.postMessage({ type: 'GITHUB_OAUTH_ERROR', error: 'Token exchange failed' }, '*');
        window.close();
    </script>";
    exit;
}

// Renvoyer le token à la fenêtre parente (NexusFTP)
echo "<script>
    window.opener && window.opener.postMessage({ type: 'GITHUB_OAUTH_SUCCESS', token: '" . htmlspecialchars($accessToken) . "' }, '*');
    window.close();
</script>";
?>
