<?php
/* p410mgr — cykliczne sprawdzenie (cron) */
require_once '/usr/local/emhttp/plugins/p410mgr/include/monitor.php';
$lock = fopen('/tmp/p410mgr_check.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit(0);
$force = in_array('--force', $argv, true);
$r = p410_run_check($force);
echo ($r['problem'] ? 'PROBLEM: ' . implode('; ', $r['problems']) : 'OK') . "\n";
if ($r['mqtt'] !== null) echo "MQTT: {$r['mqtt']}\n";
