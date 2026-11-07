<?php
require_once __DIR__ . '/i18n.php';
/* p410mgr — library: running hpssacli and parsing its output */

const P410_BIN   = '/usr/local/sbin/hpssacli';
const P410_CACHE = '/tmp/p410mgr_cciss.json';

function p410_run(string $args, ?int &$rc = null): string {
  $out = [];
  exec(P410_BIN . ' ' . $args . ' 2>&1', $out, $rc);
  return implode("\n", $out);
}

function p410_valid_slot($s): bool { return is_string($s) && preg_match('/^\d{1,2}$/', $s) === 1; }
function p410_valid_pd($s): bool   { return is_string($s) && preg_match('/^\d{1,2}[IEC]:\d{1,2}:\d{1,3}$/', $s) === 1; }
function p410_valid_ld($s): bool   { return is_string($s) && preg_match('/^\d{1,3}$/', $s) === 1; }

/* "Smart Array P410 in Slot 3 (sn: XXX)" -> [ ['slot'=>'3','model'=>'Smart Array P410'] ] */
function p410_parse_controllers(string $txt): array {
  $res = [];
  if (preg_match_all('/^\s*(.+?) in Slot (\d+)/m', $txt, $m, PREG_SET_ORDER)) {
    foreach ($m as $x) $res[] = ['slot' => $x[2], 'model' => trim($x[1])];
  }
  return $res;
}

/* Simple first-level "Key: Value" pairs (ctrl show detail) */
function p410_parse_kv(string $txt): array {
  $kv = [];
  foreach (explode("\n", $txt) as $line) {
    if (preg_match('/^\s*([^:]{2,60}?):\s+(.*\S)\s*$/', $line, $m)) {
      $k = trim($m[1]);
      if (!isset($kv[$k])) $kv[$k] = trim($m[2]);
    }
  }
  return $kv;
}

/* ctrl slot=X show config -> drives with array/LD assignment + unassigned */
function p410_parse_config(string $txt): array {
  $arrays = []; $pds = []; $lds = [];
  $curArray = null; $curLd = null; $unassigned = false;
  foreach (explode("\n", $txt) as $line) {
    $t = trim($line);
    if ($t === '') continue;
    if (preg_match('/^array (\S+)\s*\((.*)\)/', $t, $m)) {
      $curArray = $m[1]; $curLd = null; $unassigned = false;
      $arrays[$curArray] = ['name' => $curArray, 'info' => $m[2], 'lds' => []];
      continue;
    }
    if ($t === 'unassigned') { $unassigned = true; $curArray = null; $curLd = null; continue; }
    if (preg_match('/^logicaldrive (\d+)\s*\(([^,]+),\s*([^,]+),\s*([^)]+)\)/', $t, $m)) {
      $curLd = $m[1];
      $lds[$curLd] = ['ld' => $curLd, 'size' => trim($m[2]), 'raid' => trim($m[3]),
                      'status' => trim($m[4]), 'array' => $curArray, 'pds' => []];
      if ($curArray !== null) $arrays[$curArray]['lds'][] = $curLd;
      continue;
    }
    if (preg_match('/^physicaldrive (\S+)\s*\((.*)\)/', $t, $m)) {
      $pd = $m[1];
      $parts = array_map('trim', explode(',', $m[2]));
      $bay = null;
      if (preg_match('/bay (\d+)/', $m[2], $b)) $bay = (int)$b[1];
      $n = count($parts);
      $pds[$pd] = [
        'pd'         => $pd,
        'bay'        => $bay,
        'type'       => $n >= 3 ? $parts[$n - 3] : '',
        'size'       => $n >= 2 ? $parts[$n - 2] : '',
        'status'     => $n >= 1 ? $parts[$n - 1] : '',
        'array'      => $unassigned ? null : $curArray,
        'unassigned' => $unassigned,
        'lds'        => [],
      ];
      if (!$unassigned && $curArray !== null) {
        foreach ($arrays[$curArray]['lds'] as $l) { $lds[$l]['pds'][] = $pd; $pds[$pd]['lds'][] = $l; }
      }
      continue;
    }
  }
  return ['arrays' => $arrays, 'lds' => $lds, 'pds' => $pds];
}

/* Bloki "Logical Drive: N" z ld all show detail */
function p410_parse_ld_detail(string $txt): array {
  $res = []; $cur = null;
  foreach (explode("\n", $txt) as $line) {
    if (preg_match('/^\s*Logical Drive:\s*(\d+)/', $line, $m)) { $cur = $m[1]; $res[$cur] = []; continue; }
    if ($cur !== null && preg_match('/^\s*([^:]{2,60}?):\s+(.*\S)\s*$/', $line, $m)) {
      $k = trim($m[1]); if (!isset($res[$cur][$k])) $res[$cur][$k] = trim($m[2]);
    }
  }
  return $res;
}

/* Bloki "physicaldrive X" z pd all show detail */
function p410_parse_pd_detail(string $txt): array {
  $res = []; $cur = null;
  foreach (explode("\n", $txt) as $line) {
    if (preg_match('/^\s*physicaldrive (\S+)\s*$/', $line, $m)) { $cur = $m[1]; $res[$cur] = []; continue; }
    if ($cur !== null && preg_match('/^\s*([^:]{2,60}?):\s+(.*\S)\s*$/', $line, $m)) {
      $k = trim($m[1]); if (!isset($res[$cur][$k])) $res[$cur][$k] = trim($m[2]);
    }
  }
  return $res;
}

/* Unraid assignments: device (sdX) -> slot name (disk1, parity, cache...) */
function p410_unraid_assignments(): array {
  $map = [];
  $ini = '/var/local/emhttp/disks.ini';
  if (is_readable($ini)) {
    $disks = @parse_ini_file($ini, true) ?: [];
    foreach ($disks as $name => $d) {
      $dev = $d['device'] ?? '';
      if ($dev !== '' && ($d['type'] ?? '') !== 'Flash') $map[$dev] = $d['name'] ?? $name;
    }
  }
  return $map;
}

function p410_dev_in_use(string $dev): ?string {
  $dev = basename($dev);
  if ($dev === '' || !preg_match('/^sd[a-z]+$/', $dev)) return null;
  $assign = p410_unraid_assignments();
  if (isset($assign[$dev])) return p410_t('in_unraid', $assign[$dev]);
  $mounts = @file_get_contents('/proc/mounts') ?: '';
  if (preg_match('#^/dev/' . preg_quote($dev, '#') . '\d*\s#m', $mounts)) return p410_t('mounted');
  $holders = glob("/sys/block/$dev/holders/*") ?: [];
  if ($holders) return p410_t('used_by', implode(', ', array_map('basename', $holders)));
  return null;
}

/* Full state of all controllers */
function p410_status(): array {
  if (!is_executable(P410_BIN)) return ['error' => p410_t('no_bin', P410_BIN)];
  $ctrls = p410_parse_controllers(p410_run('ctrl all show'));
  if (!$ctrls) return ['error' => p410_t('no_ctrl')];
  $assign = p410_unraid_assignments();
  foreach ($ctrls as &$c) {
    $s = $c['slot'];
    $c['detail'] = p410_parse_kv(p410_run("ctrl slot=$s show detail"));
    $cfg  = p410_parse_config(p410_run("ctrl slot=$s show config"));
    $ldd  = p410_parse_ld_detail(p410_run("ctrl slot=$s ld all show detail"));
    $pdd  = p410_parse_pd_detail(p410_run("ctrl slot=$s pd all show detail"));
    foreach ($cfg['lds'] as $n => &$ld) {
      $d = $ldd[$n] ?? [];
      $dev = $d['Disk Name'] ?? '';
      $ld['dev']     = $dev;
      $ld['caching'] = $d['Caching'] ?? '';
      $ld['unraid']  = $dev !== '' ? ($assign[basename($dev)] ?? '') : '';
      $ld['present'] = $dev !== '' && file_exists($dev);
    }
    unset($ld);
    foreach ($cfg['pds'] as $p => &$pd) {
      $d = $pdd[$p] ?? [];
      $pd['model']  = preg_replace('/\s+/', ' ', $d['Model'] ?? '');
      $pd['serial'] = $d['Serial Number'] ?? '';
      $pd['temp']   = $d['Current Temperature (C)'] ?? '';
      $pd['fw']     = $d['Firmware Revision'] ?? '';
      $pd['rpm']    = $d['Rotational Speed'] ?? '';
      $pd['link']   = $d['PHY Transfer Rate'] ?? '';
      if (!empty($d['Status'])) $pd['status'] = $d['Status'];
    }
    unset($pd);
    $c['lds'] = array_values($cfg['lds']);
    $pds = array_values($cfg['pds']);
    usort($pds, fn($a, $b) => ($a['bay'] ?? 999) <=> ($b['bay'] ?? 999));
    $c['pds'] = $pds;
  }
  unset($c);
  return ['controllers' => $ctrls];
}

/* Find the cciss,N index for a serial number (cached in /tmp) */
function p410_cciss_index(string $slot, string $serial): ?array {
  $cache = @json_decode(@file_get_contents(P410_CACHE) ?: '[]', true) ?: [];
  $ldd = p410_parse_ld_detail(p410_run("ctrl slot=$slot ld all show detail"));
  $dev = null;
  foreach ($ldd as $d) { $n = $d['Disk Name'] ?? ''; if ($n !== '' && file_exists($n)) { $dev = $n; break; } }
  if ($dev === null) return null;
  if (isset($cache[$slot][$serial])) return ['dev' => $dev, 'n' => $cache[$slot][$serial]];
  for ($i = 0; $i < 32; $i++) {
    $o = [];
    exec('smartctl -i -d cciss,' . $i . ' ' . escapeshellarg($dev) . ' 2>/dev/null', $o);
    foreach ($o as $line) {
      if (preg_match('/^Serial number:\s*(\S+)/i', $line, $m)) {
        $cache[$slot][$m[1]] = $i;
      }
    }
  }
  @file_put_contents(P410_CACHE, json_encode($cache));
  return isset($cache[$slot][$serial]) ? ['dev' => $dev, 'n' => $cache[$slot][$serial]] : null;
}

function p410_rescan_hpsa(): void {
  foreach (glob('/sys/class/scsi_host/host*') ?: [] as $h) {
    if (trim(@file_get_contents("$h/proc_name") ?: '') === 'hpsa') @file_put_contents("$h/scan", "- - -");
  }
}
