<?php
require_once __DIR__ . '/../config.php';

$data = getInput();
$sessionId = $data['sessionId'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$type = $session['type'] ?? 'ftp';
$stats = [
    'type'         => $type,
    'host'         => $session['host'],
    'port'         => $session['port'],
    'username'     => $session['username'],
    'passive'      => $session['passive'] ?? true,
    'diskTotal'    => null,
    'diskFree'     => null,
    'diskUsed'     => null,
    'serverSoftware' => null,
];

if ($type === 'sftp') {
    if (!function_exists('ssh2_connect')) {
        sendSuccess(['stats' => $stats, 'note' => 'ssh2 extension not available']);
    }
    $conn = @ssh2_connect($session['host'], $session['port']);
    if ($conn && @ssh2_auth_password($conn, $session['username'], $password)) {
        // Try to get disk info via df
        $stream = @ssh2_exec($conn, 'df -k / 2>/dev/null | tail -1');
        if ($stream) {
            stream_set_blocking($stream, true);
            $out = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            fclose($stream);
            // df output: Filesystem  1K-blocks  Used  Available  Use%  Mount
            $parts = preg_split('/\s+/', trim($out));
            if (count($parts) >= 4) {
                $stats['diskTotal'] = intval($parts[1]) * 1024;
                $stats['diskUsed']  = intval($parts[2]) * 1024;
                $stats['diskFree']  = intval($parts[3]) * 1024;
            }
        }
        // server uname
        $stream2 = @ssh2_exec($conn, 'uname -sr 2>/dev/null');
        if ($stream2) {
            stream_set_blocking($stream2, true);
            $stats['serverSoftware'] = trim(stream_get_contents(ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO)));
            fclose($stream2);
        }
    }
} else {
    // FTP / FTPS
    $timeout = 30;
    if ($type === 'ftps' || $type === 'ftpse') {
        $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = @ftp_connect($session['host'], $session['port'], $timeout);
    }
    if ($conn && @ftp_login($conn, $session['username'], $password)) {
        ftp_pasv($conn, $session['passive'] ?? true);
        // Server software
        $stats['serverSoftware'] = @ftp_systype($conn) ?: null;
        // Try SIZE command-based quota via SITE QUOTA (not widely supported, but we try)
        $quota = @ftp_raw($conn, 'SITE QUOTA');
        if ($quota && is_array($quota)) {
            $stats['quotaRaw'] = implode(' ', $quota);
        }
        // Some FTP servers support SITE DISKUSAGE or similar
        $diskCmd = @ftp_raw($conn, 'SITE DISKUSAGE');
        if ($diskCmd && is_array($diskCmd)) {
            $stats['diskUsageRaw'] = implode(' ', $diskCmd);
            // Try to extract number in bytes
            foreach ($diskCmd as $line) {
                if (preg_match('/(\d+)\s*(byte|kbyte|KB|MB|GB)?/i', $line, $m)) {
                    $val = intval($m[1]);
                    $unit = strtolower($m[2] ?? '');
                    if (strpos($unit, 'k') !== false) $val *= 1024;
                    elseif (strpos($unit, 'm') !== false) $val *= 1024 * 1024;
                    elseif (strpos($unit, 'g') !== false) $val *= 1024 * 1024 * 1024;
                    $stats['diskUsed'] = $val;
                    break;
                }
            }
        }
        // Try FEAT to get server features
        $feat = @ftp_raw($conn, 'FEAT');
        if ($feat && is_array($feat)) {
            $stats['features'] = implode(', ', array_map('trim', array_filter($feat, fn($l) => !preg_match('/^(211|Features:|--)/', $l))));
        }
        ftp_close($conn);
    }
}

sendSuccess(['stats' => $stats]);
?>
