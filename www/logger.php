<?php
function log_event(string $type, string $message): void {
    $file = '/home/shaykins/Projects/siRNA/logs/' . $type . '_audit.log';
    if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    @file_put_contents($file, "[$ts][$ip] $message\n", FILE_APPEND);
}
?>
