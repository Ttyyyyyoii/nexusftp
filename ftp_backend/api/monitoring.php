<?php
require_once __DIR__ . '/../config.php';

$data      = getInput();
$sessionId = $data['sessionId'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$type     = $session['type'] ?? 'ftp';
$password = decryptPassword($session['password']);

if ($type !== 'sftp') {
    sendSuccess([
        'available' => false,
        'reason' => 'Monitoring requires an SFTP (SSH) connection.',
        'metrics' => null
    ]);
}

if (!function_exists('ssh2_connect')) sendError('ssh2 extension not available');

$conn = @ssh2_connect($session['host'], $session['port']);
if (!$conn) sendError('SSH connection failed');
if (!@ssh2_auth_password($conn, $session['username'], $password)) sendError('SSH authentication failed');

function sshExec($conn, $cmd) {
    $stream = @ssh2_exec($conn, $cmd . ' 2>/dev/null');
    if (!$stream) return '';
    stream_set_blocking($stream, true);
    $out = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
    fclose($stream);
    return trim($out);
}

$metrics = [];

// ── Uptime + Load ──────────────────────────────────────────
$uptime = sshExec($conn, 'uptime');
$metrics['uptime_raw'] = $uptime;
// e.g. " 10:15:34 up 3 days,  2:12,  1 user,  load average: 0.08, 0.06, 0.05"
if (preg_match('/up\s+(.+?),\s+\d+\s+user/', $uptime, $m)) {
    $metrics['uptime'] = trim($m[1]);
} elseif (preg_match('/up\s+(.+?),\s+load/', $uptime, $m)) {
    $metrics['uptime'] = trim($m[1]);
} else {
    $metrics['uptime'] = null;
}
if (preg_match('/load average:\s+([\d.]+),\s*([\d.]+),\s*([\d.]+)/', $uptime, $m)) {
    $metrics['load1']  = (float)$m[1];
    $metrics['load5']  = (float)$m[2];
    $metrics['load15'] = (float)$m[3];
}

// ── CPU ────────────────────────────────────────────────────
$cpuLine = sshExec($conn, "top -bn1 | grep 'Cpu\\|%Cpu'");
if (preg_match('/(\d+\.?\d*)\s*[%]?\s*id/', $cpuLine, $m)) {
    $metrics['cpu_percent'] = round(100 - floatval($m[1]), 1);
} elseif (preg_match('/(\d+\.?\d*)\s*us.*?(\d+\.?\d*)\s*sy/', $cpuLine, $m)) {
    $metrics['cpu_percent'] = round(floatval($m[1]) + floatval($m[2]), 1);
} else {
    // fallback: /proc/stat
    $stat1 = sshExec($conn, 'cat /proc/stat | grep "^cpu "');
    usleep(200000);
    $stat2 = sshExec($conn, 'cat /proc/stat | grep "^cpu "');
    $p1 = array_map('intval', array_slice(preg_split('/\s+/', trim($stat1)), 1));
    $p2 = array_map('intval', array_slice(preg_split('/\s+/', trim($stat2)), 1));
    $total1 = array_sum($p1); $idle1 = $p1[3] ?? 0;
    $total2 = array_sum($p2); $idle2 = $p2[3] ?? 0;
    $totalDiff = $total2 - $total1;
    $idleDiff  = $idle2 - $idle1;
    $metrics['cpu_percent'] = $totalDiff > 0 ? round((1 - $idleDiff / $totalDiff) * 100, 1) : null;
}

// ── RAM ────────────────────────────────────────────────────
$ramOut = sshExec($conn, 'free -m');
foreach (explode("\n", $ramOut) as $line) {
    if (preg_match('/^Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
        $metrics['ram_total_mb'] = (int)$m[1];
        $metrics['ram_used_mb']  = (int)$m[2];
        $metrics['ram_free_mb']  = (int)$m[3];
        $metrics['ram_percent']  = $m[1] > 0 ? round($m[2] / $m[1] * 100, 1) : 0;
        break;
    }
}

// ── Disk ───────────────────────────────────────────────────
$dfOut = sshExec($conn, 'df -k / | tail -1');
$parts = preg_split('/\s+/', $dfOut);
if (count($parts) >= 5) {
    $metrics['disk_total_kb'] = (int)$parts[1];
    $metrics['disk_used_kb']  = (int)$parts[2];
    $metrics['disk_free_kb']  = (int)$parts[3];
    $useStr = $parts[4] ?? '0%';
    $metrics['disk_percent']  = (int)str_replace('%', '', $useStr);
}

// ── Top processes ──────────────────────────────────────────
$psOut = sshExec($conn, 'ps aux --sort=-%cpu | head -6');
$procs = [];
foreach (array_slice(explode("\n", $psOut), 1) as $line) {
    $cols = preg_split('/\s+/', trim($line), 11);
    if (count($cols) >= 11) {
        $procs[] = [
            'user'    => $cols[0],
            'pid'     => $cols[1],
            'cpu'     => $cols[2],
            'mem'     => $cols[3],
            'command' => mb_substr($cols[10], 0, 40),
        ];
    }
}
$metrics['top_processes'] = $procs;

// ── Network ────────────────────────────────────────────────
$netConn = sshExec($conn, 'ss -s 2>/dev/null || netstat -an 2>/dev/null | grep ESTABLISHED | wc -l');
if (preg_match('/estab\s+(\d+)/i', $netConn, $m)) {
    $metrics['connections_established'] = (int)$m[1];
} elseif (is_numeric(trim($netConn))) {
    $metrics['connections_established'] = (int)trim($netConn);
} else {
    $metrics['connections_established'] = null;
}

// ── OS info (cached — slow cmd) ────────────────────────────
$metrics['os'] = sshExec($conn, 'uname -sr');
$metrics['hostname'] = sshExec($conn, 'hostname');

sendSuccess(['available' => true, 'metrics' => $metrics]);
?>
