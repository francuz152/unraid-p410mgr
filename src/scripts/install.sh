#!/bin/bash
# p410mgr — installs hpssacli (runs on plugin install and on every boot)
PLG=/boot/config/plugins/p410mgr
EMH=/usr/local/emhttp/plugins/p410mgr
# The plugin manager downloads $DEB to $PLG and checks its SHA256 (see the
# <FILE> before this script in p410mgr.plg). Keep this name in step with build.py.
DEB=hpssacli-2.40-13.0_amd64.deb

if [ ! -s "$PLG/$DEB" ]; then
  echo "ERROR: $PLG/$DEB is missing. Reinstall the plugin so the plugin manager downloads and verifies it."
  exit 1
fi

rm -rf /tmp/p410mgr_x
if ! php "$EMH/scripts/extract_deb.php" "$PLG/$DEB" /tmp/p410mgr_x; then
  echo "Failed to extract $DEB – removing it, please reinstall the plugin."
  rm -f "$PLG/$DEB"; rm -rf /tmp/p410mgr_x; exit 1
fi
mkdir -p /opt/hp && rm -rf /opt/hp/hpssacli
cp -a /tmp/p410mgr_x/opt/hp/hpssacli /opt/hp/
rm -rf /tmp/p410mgr_x

chmod -R u+x /opt/hp/hpssacli
BIN=$(find /opt/hp/hpssacli -type f -name hpssacli | head -1)
if [ -z "$BIN" ]; then echo "ERROR: hpssacli binary not found after extraction."; exit 1; fi
ln -sf "$BIN" /usr/local/sbin/hpssacli
echo "hpssacli: $BIN"
/usr/local/sbin/hpssacli ctrl all show 2>&1 | grep -i "slot" || echo "Warning: hpssacli did not detect a controller."
[ -x /usr/local/sbin/update_cron ] && /usr/local/sbin/update_cron
echo "Smart Array P410 plugin installed – Settings → User Utilities → Smart Array P410 (+ Dashboard tile)"
