<?php
require_once __DIR__ . '/../config.php';

$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
if (empty($sessionId)) sendError('Session ID is required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$files = [];
$password = decryptPassword($session['password']);

// --- Logging propre : on écrase le log a chaque appel, avec un timestamp ---
// Ca permet de verifier immediatement si le fichier est bien regenere a chaque test
// (sinon ca veut dire que ce endpoint n'est meme pas atteint).
$debugLog = [];
$debugLog['timestamp'] = date('Y-m-d H:i:s');
$debugLog['session_type'] = $session['type'] ?? 'undefined';

// Capture TOUS les warnings PHP (meme ceux normalement avales par @)
// pour voir le vrai message d'erreur ftp_* au lieu d'un simple false.
$capturedWarnings = [];
set_error_handler(function ($errno, $errstr) use (&$capturedWarnings) {
    $capturedWarnings[] = $errstr;
    return true;
});

if ($session['type'] === 'sftp') {

    $connection = @ssh2_connect($session['host'], $session['port']);
    $debugLog['ssh2_connect'] = (bool) $connection;

    if (!$connection) {
        $debugLog['fatal'] = 'ssh2_connect a echoue - host/port injoignable';
    } else {
        $authOk = @ssh2_auth_password($connection, $session['username'], $password);
        $debugLog['auth_ok'] = $authOk;

        if (!$authOk) {
            $debugLog['fatal'] = 'Authentification SFTP refusee (mauvais login/mdp ?)';
        } else {
            $sftp = @ssh2_sftp($connection);
            $debugLog['sftp_subsystem'] = (bool) $sftp;

            if ($sftp) {
                $sftpPath = "ssh2.sftp://" . intval($sftp) . $path;
                $dir = @opendir($sftpPath);
                $debugLog['opendir_ok'] = (bool) $dir;

                if ($dir) {
                    while (($file = readdir($dir)) !== false) {
                        if ($file === '.' || $file === '..') continue;
                        $fullPath = rtrim($path, '/') . '/' . $file;
                        $stat = @ssh2_sftp_stat($sftp, $fullPath);
                        if ($stat) {
                            $isDir = ($stat['mode'] & 0040000) === 0040000;
                            $files[] = [
                                'name' => $file,
                                'size' => $stat['size'],
                                'sizeFormatted' => formatFileSize($stat['size']),
                                'isDirectory' => $isDir,
                                'permissions' => formatPerms($stat['mode']),
                                'modified' => date('Y-m-d H:i:s', $stat['mtime']),
                                'type' => $isDir ? 'directory' : getFileType($file),
                            ];
                        }
                    }
                    closedir($dir);
                } else {
                    $debugLog['fatal'] = 'opendir a echoue sur ' . $sftpPath;
                }
            } else {
                $debugLog['fatal'] = 'ssh2_sftp (sous-systeme SFTP) indisponible';
            }
        }
    }

} else {

    $timeout = 30;

    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
        $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = @ftp_connect($session['host'], $session['port'], $timeout);
    }
    $debugLog['connect_ok'] = (bool) $conn;

    if (!$conn) {
        $debugLog['fatal'] = 'Impossible de se connecter a ' . $session['host'] . ':' . $session['port'];
    } else {

        $loginOk = @ftp_login($conn, $session['username'], $password);
        $debugLog['login_ok'] = $loginOk;

        if (!$loginOk) {
            $debugLog['fatal'] = 'Login FTP refuse pour ' . $session['username'];
        } else {

            // Commandes control-only : si elles echouent, la connexion de controle
            // elle-meme est cassee (pas un probleme de canal data / passif).
            $debugLog['systype'] = @ftp_systype($conn);
            $debugLog['pwd_before'] = @ftp_pwd($conn);

            $pasvWanted = $session['passive'] ?? true;

            // On envoie la commande PASV brute AVANT d'utiliser le wrapper ftp_pasv(),
            // pour voir exactement ce que le serveur repond (notamment l'IP annoncee
            // pour le canal de donnees - souvent la cause reelle du probleme si c'est
            // une IP privee/locale que le script PHP distant ne peut pas joindre).
            $debugLog['raw_pasv_response'] = @ftp_raw($conn, 'PASV');

            $pasvResult = @ftp_pasv($conn, $pasvWanted);
            $debugLog['pasv_wanted'] = $pasvWanted;
            $debugLog['pasv_result'] = $pasvResult;

            $chdirResult = @ftp_chdir($conn, $path);
            $debugLog['chdir_result'] = $chdirResult;
            $debugLog['pwd_after'] = @ftp_pwd($conn);

            // On tente plusieurs variantes de listing, dans l'ordre du plus complet
            // au plus basique, en gardant une trace de CHAQUE tentative.
            $list = @ftp_rawlist($conn, '-al');
            $debugLog['list_al_count'] = $list ? count($list) : 0;
            $debugLog['list_al_raw'] = $list;

            if (empty($list)) {
                $list = @ftp_rawlist($conn, $path);
                $debugLog['list_path_count'] = $list ? count($list) : 0;
                $debugLog['list_path_raw'] = $list;
            }

            if (empty($list)) {
                $list = @ftp_nlist($conn, $path);
                $debugLog['list_nlist_count'] = $list ? count($list) : 0;
                $debugLog['list_nlist_raw'] = $list;
            }

            // Si le mode passif choisi n'a rien donne, on essaie explicitement l'autre mode.
            if (empty($list)) {
                $altMode = !$pasvWanted;
                @ftp_pasv($conn, $altMode);
                $debugLog['tried_alt_pasv_mode'] = $altMode;
                $list = @ftp_rawlist($conn, '-al');
                $debugLog['list_alt_mode_count'] = $list ? count($list) : 0;
            }

            // ULTIMATE FALLBACK: cURL (Pour contourner le Pare-feu Windows sans droits Admin)
            if (empty($list) && function_exists('curl_init')) {
                $maxRetries = 3;
                $curlOutput = false;
                $curlError = '';
                
                for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                    $ch = curl_init();
                    $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                    $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($path, '/');
                    if (substr($url, -1) !== '/') $url .= '/';
                    
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6); // 6 secondes max par tentative
                    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
                    curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
                    curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
                    
                    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    }
                    
                    $curlOutput = curl_exec($ch);
                    $curlError = curl_error($ch);
                    $errno = curl_errno($ch);
                    curl_close($ch);
                    
                    if ($errno === 0) {
                        break; // Success!
                    }
                    // Attendre un peu avant de reessayer
                    usleep(500000); // 0.5 sec
                }
                
                $debugLog['curl_fallback_used'] = true;
                $debugLog['curl_error'] = $curlError;
                
                if ($curlOutput !== false) {
                    $list = explode("\n", trim(str_replace("\r", "", $curlOutput)));
                } else if ($curlError) {
                    $files[] = [
                        'name' => 'ERREUR_CURL_PARE_FEU: ' . $curlError,
                        'size' => 0, 'sizeFormatted' => '0 B',
                        'isDirectory' => false, 'permissions' => '---',
                        'modified' => date('Y-m-d H:i:s'), 'type' => 'file'
                    ];
                }
            }

            if ($list) {
                foreach ($list as $line) {
                    $parsed = parseFtpLine($line);
                    if ($parsed && $parsed['name'] !== '.' && $parsed['name'] !== '..') {
                        $files[] = $parsed;
                    }
                }
                $debugLog['files_parsed'] = count($files);
                if (count($files) === 0 && count($list) > 0) {
                    $debugLog['parsing_warning'] = 'Le serveur a renvoye des lignes mais aucune n a matche parseFtpLine() - format non reconnu';
                }
            } else {
                $debugLog['fatal'] = 'Toutes les tentatives de listing (rawlist/nlist, passif/actif) ont echoue';
            }

            ftp_close($conn);
        }
    }
}

restore_error_handler();
$debugLog['captured_php_warnings'] = $capturedWarnings;

file_put_contents(__DIR__ . '/debug.log', print_r($debugLog, true));

sendSuccess(['files' => $files, 'path' => $path, 'count' => count($files)]);

function parseFtpLine($line) {
    if (empty($line) || preg_match('/^total/', $line)) return null;

    // UNIX style: drwxr-xr-x   2 user  group    4096 Jan  1 12:00 folder
    $unixPattern = '/^([\-dlcbsp])([rwxst\-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w{3}\s+\d+\s+[\d:]+)\s+(.+)$/';
    if (preg_match($unixPattern, $line, $matches)) {
        $isDir = $matches[1] === 'd';
        return ['name' => $matches[8], 'size' => intval($matches[6]), 'sizeFormatted' => formatFileSize(intval($matches[6])),
            'isDirectory' => $isDir, 'permissions' => $matches[2], 'modified' => $matches[7], 'type' => $isDir ? 'directory' : getFileType($matches[8])];
    }

    // DOS style: 11-05-21  12:20PM       <DIR>          Folder Name
    // DOS style: 11-05-21  12:20PM               1024 file name.txt
    $dosPattern = '/^(\d{2}-\d{2}-\d{2,4}\s+\d{2}:\d{2}\s*[AaPp][Mm])\s+(<DIR>|\d+)\s+(.+)$/';
    if (preg_match($dosPattern, $line, $matches)) {
        $isDir = strtoupper($matches[2]) === '<DIR>';
        $size = $isDir ? 0 : intval($matches[2]);
        return ['name' => trim($matches[3]), 'size' => $size, 'sizeFormatted' => formatFileSize($size),
            'isDirectory' => $isDir, 'permissions' => $isDir ? 'drwxr-xr-x' : '-rw-r--r--', 'modified' => $matches[1], 'type' => $isDir ? 'directory' : getFileType($matches[3])];
    }

    return null;
}

function getFileType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = ['jpg'=>'image','jpeg'=>'image','png'=>'image','gif'=>'image','pdf'=>'document','txt'=>'text','js'=>'code','html'=>'code','css'=>'code','zip'=>'archive','mp3'=>'audio','mp4'=>'video','csv'=>'spreadsheet','doc'=>'document'];
    return $types[$ext] ?? 'file';
}

function formatPerms($mode) {
    $perms = '';
    for ($i = 8; $i >= 0; $i--) $perms .= ($mode & (1 << $i)) ? (($i % 3 == 2) ? 'r' : (($i % 3 == 1) ? 'w' : 'x')) : '-';
    return $perms;
}