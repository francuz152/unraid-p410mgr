<?php
/* Extracts a .deb package without 'ar' (not available on Unraid).
   Usage: php extract_deb.php file.deb destdir */
if ($argc < 3) { fwrite(STDERR, "usage: extract_deb.php file.deb destdir\n"); exit(2); }
[$_, $deb, $dest] = $argv;
$f = @fopen($deb, 'rb');
if (!$f || fread($f, 8) !== "!<arch>\n") { fwrite(STDERR, "Not a .deb (ar) archive: $deb\n"); exit(1); }
@mkdir($dest, 0755, true);
$found = false;
while (!feof($f)) {
  $hdr = fread($f, 60);
  if ($hdr === false || strlen($hdr) < 60) break;
  $name = rtrim(substr($hdr, 0, 16), " /");
  $size = (int)trim(substr($hdr, 48, 10));
  if (str_starts_with($name, 'data.tar')) {
    $tmp = rtrim($dest, '/') . '/' . $name;
    $o = fopen($tmp, 'wb');
    $left = $size;
    while ($left > 0) { $chunk = fread($f, min(1 << 20, $left)); if ($chunk === '' || $chunk === false) break; fwrite($o, $chunk); $left -= strlen($chunk); }
    fclose($o);
    $flag = str_ends_with($name, '.xz') ? 'J' : (str_ends_with($name, '.gz') ? 'z' : (str_ends_with($name, '.zst') ? '' : ''));
    $cmd = str_ends_with($name, '.zst')
      ? 'zstd -dc ' . escapeshellarg($tmp) . ' | tar -x --no-same-owner -C ' . escapeshellarg($dest)
      : 'tar -x' . $flag . 'f ' . escapeshellarg($tmp) . ' --no-same-owner -C ' . escapeshellarg($dest);
    passthru($cmd, $rc);
    unlink($tmp);
    if ($rc !== 0) { fwrite(STDERR, "tar failed ($rc)\n"); exit(1); }
    $found = true;
    break;
  }
  fseek($f, $size + ($size % 2), SEEK_CUR);
}
fclose($f);
if (!$found) { fwrite(STDERR, "data.tar.* not found in $deb\n"); exit(1); }
echo "OK\n";
