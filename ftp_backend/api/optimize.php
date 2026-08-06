<?php
require_once __DIR__ . '/../config.php';

$input = getInput();
$sessionId = $input['sessionId'] ?? '';
$remotePath = $input['remotePath'] ?? '';
$remoteName = $input['remoteName'] ?? '';

if (!$sessionId || !$remotePath || !$remoteName) {
    sendError('Missing required fields');
}

$session = loadSession($sessionId);
if (!$session) {
    sendError('Invalid or expired session', 401);
}

$ftp = ftp_connect($session['host'], $session['port']);
if (!$ftp) {
    sendError('Could not connect to FTP server', 500);
}

$password = decryptPassword($session['password']);
if (!ftp_login($ftp, $session['username'], $password)) {
    sendError('FTP authentication failed', 401);
}
if ($session['passive']) ftp_pasv($ftp, true);

$fullRemotePath = rtrim($remotePath, '/') . '/' . $remoteName;
$ext = strtolower(pathinfo($remoteName, PATHINFO_EXTENSION));

if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
    sendError('Seuls les fichiers JPG et PNG peuvent être optimisés in-place.');
}

// 1. Download to temp file
$tempFile = tempnam(sys_get_temp_dir(), 'nexus_opt_');
if (!ftp_get($ftp, $tempFile, $fullRemotePath, FTP_BINARY)) {
    sendError('Impossible de télécharger le fichier source pour optimisation.');
}

$originalSize = filesize($tempFile);

// 2. Process with GD
$image = null;
if ($ext === 'jpg' || $ext === 'jpeg') {
    $image = @imagecreatefromjpeg($tempFile);
} elseif ($ext === 'png') {
    $image = @imagecreatefrompng($tempFile);
    // Preserve transparency for PNG
    if ($image) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }
}

if (!$image) {
    unlink($tempFile);
    sendError('Impossible de lire l\'image avec GD. Fichier peut-être corrompu.');
}

// 3. Compress and overwrite temp file
$success = false;
if ($ext === 'jpg' || $ext === 'jpeg') {
    // Quality 70 is usually a good balance for web
    $success = imagejpeg($image, $tempFile, 70);
} elseif ($ext === 'png') {
    // Compression level 9 (max) for PNG
    $success = imagepng($image, $tempFile, 9);
}
imagedestroy($image);

if (!$success) {
    unlink($tempFile);
    sendError('Erreur lors de la compression de l\'image.');
}

clearstatcache();
$newSize = filesize($tempFile);

// 4. Upload back to FTP (Overwrite)
if (!ftp_put($ftp, $fullRemotePath, $tempFile, FTP_BINARY)) {
    unlink($tempFile);
    sendError('L\'image a été compressée mais l\'envoi vers le serveur FTP a échoué.');
}

unlink($tempFile);
ftp_close($ftp);

sendSuccess([
    'message' => 'Image optimisée avec succès',
    'originalSize' => $originalSize,
    'newSize' => $newSize,
    'savedBytes' => $originalSize - $newSize,
    'savedPercentage' => $originalSize > 0 ? round((($originalSize - $newSize) / $originalSize) * 100) : 0
]);
?>
