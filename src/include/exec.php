<?php
/* p410mgr — AJAX backend (POST; CSRF token is checked by Unraid) */
require_once __DIR__ . '/monitor.php';
header('Content-Type: application/json; charset=utf-8');

function p410_out(array $a): void { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function p410_fail(string $msg): void { p410_out(['ok' => false, 'msg' => $msg]); }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') p410_fail(p410_t('only_post'));
if (!is_executable(P410_BIN)) p410_fail(p410_t('no_bin', P410_BIN));

$action = $_POST['action'] ?? '';
$slot   = $_POST['slot'] ?? '';
$noSlot = ['status', 'summary', 'getcfg', 'savecfg', 'testmqtt', 'runcheck', 'resetbaseline', 'laststate'];
if (!in_array($action, $noSlot, true) && !p410_valid_slot($slot)) p410_fail(p410_t('bad_slot'));

switch ($action) {

  case 'status':
    p410_out(['ok' => true] + p410_status());

  case 'summary':
    $st = p410_status();
    if (isset($st['error'])) p410_out(['ok' => false, 'msg' => $st['error']]);
    $mcfg = p410_cfg_load();
    $sum = [];
    foreach ($st['controllers'] as $c) {
      $d = $c['detail'];
      $bad = []; $temps = []; $unassigned = 0;
      foreach ($c['pds'] as $p) {
        if (strcasecmp($p['status'], 'OK') !== 0) $bad[] = p410_t('bay_state', $p['bay'], $p['status']);
        if ($p['unassigned']) $unassigned++;
        if (is_numeric($p['temp'])) $temps[] = (int)$p['temp'];
      }
      foreach ($c['lds'] as $l) if (strcasecmp($l['status'], 'OK') !== 0) $bad[] = "LD {$l['ld']}: {$l['status']}";
      $sum[] = [
        'slot' => $c['slot'], 'model' => $c['model'],
        'ctrl' => $d['Controller Status'] ?? '?', 'cache' => $d['Cache Status'] ?? '?',
        'battery' => $d['Battery/Capacitor Status'] ?? '—', 'fw' => $d['Firmware Version'] ?? '',
        'pds' => count($c['pds']), 'lds' => count($c['lds']), 'unassigned' => $unassigned,
        'ign_cache' => $mcfg['IGNORE_CACHE'] === 'yes', 'ign_battery' => $mcfg['IGNORE_BATTERY'] === 'yes',
        'tmax' => $temps ? max($temps) : null, 'bad' => $bad,
      ];
    }
    p410_out(['ok' => true, 'controllers' => $sum]);

  case 'led':
    $pd = $_POST['pd'] ?? ''; $state = $_POST['state'] ?? '';
    if (!p410_valid_pd($pd) || !in_array($state, ['on', 'off'], true)) p410_fail(p410_t('bad_params'));
    $o = p410_run("ctrl slot=$slot pd $pd modify led=$state", $rc);
    p410_out(['ok' => $rc === 0, 'msg' => $rc === 0 ? p410_t('led_done', $pd, $state) : $o]);

  case 'smart':
    $pd = $_POST['pd'] ?? '';
    if (!p410_valid_pd($pd)) p410_fail(p410_t('bad_drive'));
    $pdd = p410_parse_pd_detail(p410_run("ctrl slot=$slot pd $pd show detail"));
    $serial = $pdd[$pd]['Serial Number'] ?? '';
    if ($serial === '') p410_fail(p410_t('no_serial'));
    $idx = p410_cciss_index($slot, $serial);
    if ($idx === null) p410_fail(p410_t('no_cciss'));
    $o = [];
    exec('smartctl -a -d cciss,' . (int)$idx['n'] . ' ' . escapeshellarg($idx['dev']) . ' 2>&1', $o);
    p410_out(['ok' => true, 'msg' => "cciss,{$idx['n']} @ {$idx['dev']}", 'text' => implode("\n", $o)]);

  case 'create':
    $pd = $_POST['pd'] ?? '';
    if (!p410_valid_pd($pd)) p410_fail(p410_t('bad_drive'));
    $cfg = p410_parse_config(p410_run("ctrl slot=$slot show config"));
    if (empty($cfg['pds'][$pd]['unassigned'])) p410_fail(p410_t('not_unassigned', $pd));
    $before = array_keys($cfg['lds']);
    $o = p410_run("ctrl slot=$slot create type=ld drives=$pd raid=0", $rc);
    if ($rc !== 0) p410_fail(p410_t('create_err', $o));
    sleep(2);
    $ldd = p410_parse_ld_detail(p410_run("ctrl slot=$slot ld all show detail"));
    $new = array_values(array_diff(array_keys($ldd), $before));
    $ld  = $new[0] ?? null;
    $dev = $ld !== null ? ($ldd[$ld]['Disk Name'] ?? '') : '';
    if ($dev === '' || !file_exists($dev)) {
      p410_rescan_hpsa(); sleep(2);
      $ldd = p410_parse_ld_detail(p410_run("ctrl slot=$slot ld all show detail"));
      $dev = $ld !== null ? ($ldd[$ld]['Disk Name'] ?? '') : '';
    }
    p410_out(['ok' => true, 'msg' => p410_t('created', $ld, $pd) . ($dev ? " → $dev" : p410_t('created_nodev'))]);

  case 'delete':
    $ld = $_POST['ld'] ?? ''; $confirm = $_POST['confirm'] ?? '';
    if (!p410_valid_ld($ld)) p410_fail(p410_t('bad_ld'));
    if ($confirm !== $ld) p410_fail(p410_t('confirm_bad'));
    $ldd = p410_parse_ld_detail(p410_run("ctrl slot=$slot ld all show detail"));
    if (!isset($ldd[$ld])) p410_fail(p410_t('ld_missing', $ld));
    $dev = $ldd[$ld]['Disk Name'] ?? '';
    if ($dev !== '') {
      $use = p410_dev_in_use($dev);
      if ($use !== null) p410_fail(p410_t('refused', $dev, $use));
    }
    $o = p410_run("ctrl slot=$slot ld $ld delete forced", $rc);
    if ($rc !== 0) p410_fail(p410_t('delete_err', $o));
    @unlink(P410_CACHE);
    p410_out(['ok' => true, 'msg' => p410_t('deleted', $ld) . ($dev ? " ($dev)" : '')]);

  case 'cache':
    $ld = $_POST['ld'] ?? ''; $state = $_POST['state'] ?? '';
    if (!p410_valid_ld($ld) || !in_array($state, ['enable', 'disable'], true)) p410_fail(p410_t('bad_params'));
    $o = p410_run("ctrl slot=$slot ld $ld modify arrayaccelerator=$state", $rc);
    p410_out(['ok' => $rc === 0, 'msg' => $rc === 0 ? p410_t('cache_done', $ld, $state) : $o]);


  case 'getcfg':
    $cfg = p410_cfg_load(); $cfg['MQTT_PASS'] = $cfg['MQTT_PASS'] !== '' ? '********' : '';
    p410_out(['ok' => true, 'cfg' => $cfg]);

  case 'savecfg':
    $in = $_POST['cfg'] ?? [];
    if (!is_array($in)) p410_fail(p410_t('no_data'));
    if (($in['MQTT_PASS'] ?? '') === '********') unset($in['MQTT_PASS']);
    p410_cfg_save($in);
    p410_out(['ok' => true, 'msg' => p410_t('saved')]);

  case 'testmqtt':
    $cfg = p410_cfg_load();
    if ($cfg['MQTT_HOST'] === '') p410_fail(p410_t('set_broker'));
    $err = null;
    $ok = p410_mqtt_publish($cfg, [[$cfg['MQTT_TOPIC'] . '/test', 'p410mgr test ' . date('c'), false]], $err);
    p410_out(['ok' => $ok, 'msg' => $ok ? p410_t('mqtt_test_ok', $cfg['MQTT_HOST'], $cfg['MQTT_TOPIC']) : $err]);

  case 'runcheck':
    $r = p410_run_check(true);
    p410_out(['ok' => true, 'state' => $r]);

  case 'laststate':
    $r = @json_decode(@file_get_contents(P410_STATE) ?: 'null', true);
    p410_out(['ok' => true, 'state' => $r]);

  case 'resetbaseline':
    p410_baseline_reset();
    p410_out(['ok' => true, 'msg' => p410_t('baseline_reset')]);

  default:
    p410_fail(p410_t('unknown_action'));
}
