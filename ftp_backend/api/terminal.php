<?php
require_once __DIR__ . '/../config.php';

$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$command = $data['command'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');
if (empty($command)) sendError('Command is required');

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

if ($session['type'] !== 'sftp') {
    sendError('Terminal commands are only supported on SFTP (SSH) connections', 403);
}

if (!function_exists('ssh2_connect')) {
    sendError('PHP ssh2 extension is not installed on this server', 500);
}

$password = decryptPassword($session['password']);

$connection = @ssh2_connect($session['host'], $session['port']);
if (!$connection) sendError("Could not connect to SSH server at {$session['host']}:{$session['port']}", 500);

if (!@ssh2_auth_password($connection, $session['username'], $password)) {
    sendError('SSH authentication failed', 401);
}

// Security: Prevent some highly interactive commands or very dangerous ones if needed
// But since this is a dev tool, we allow standard commands.
// Note: interactive commands like 'top', 'nano', 'vim' will block or break the stream.
$blockedCommands = ['nano', 'vim', 'vi', 'top', 'htop', 'less', 'more'];
$cmdParts = explode(' ', trim($command));
$baseCmd = strtolower($cmdParts[0]);

if (in_array($baseCmd, $blockedCommands)) {
    sendError("Interactive command '$baseCmd' is not supported in the web terminal.", 400);
}

// Exec
$stream = @ssh2_exec($connection, $command);
if (!$stream) {
    sendError('Failed to execute command on server', 500);
}

stream_set_blocking($stream, true);
$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
$stream_err = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

$output = stream_get_contents($stream_out);
$error = stream_get_contents($stream_err);

fclose($stream);

if (!empty($error) && empty($output)) {
    // Some commands write to stderr even if successful, but if stdout is empty and stderr has content, return as error if needed.
    // However, in terminal, stderr is just output. Let's return it as output unless it failed to execute.
    $output = $error;
} else if (!empty($error)) {
    $output .= "\n" . $error;
}

sendSuccess(['output' => trim($output)]);
?>
