#!/bin/bash
# p410mgr — installs hpssacli (runs on plugin install and on every boot)
PLG=/boot/config/plugins/p410mgr
EMH=/usr/local/emhttp/plugins/p410mgr
DEB=hpssacli-2.40-13.0_amd64.deb
URL=https://downloads.linux.hpe.com/SDR/repo/mcp/pool/non-free/$DEB

mkdir -p "$PLG"

if [ ! -s "$PLG/$DEB" ]; then
  echo "Downloading $DEB from the HPE repository..."
  if curl -fsSL --retry 6 --retry-delay 5 --connect-timeout 10 -o "$PLG/$DEB.tmp" "$URL"; then
    mv "$PLG/$DEB.tmp" "$PLG/$DEB"
  else
    rm -f "$PLG/$DEB.tmp"
    echo "Could not download $DEB."
  fi
fi

if [ -s "$PLG/$DEB" ]; then
  rm -rf /tmp/p410mgr_x
  if ! php "$EMH/scripts/extract_deb.php" "$PLG/$DEB" /tmp/p410mgr_x; then
    echo "Failed to extract $DEB – removing it, please reinstall the plugin."
    rm -f "$PLG/$DEB"; rm -rf /tmp/p410mgr_x; exit 1
  fi
  mkdir -p /opt/hp && rm -rf /opt/hp/hpssacli
  cp -a /tmp/p410mgr_x/opt/hp/hpssacli /opt/hp/
  rm -rf /tmp/p410mgr_x
elif [ -d /boot/extra-tools/hpssacli/opt/hp/hpssacli ]; then
  echo "Using previously extracted copy from /boot/extra-tools/hpssacli"
  mkdir -p /opt/hp && rm -rf /opt/hp/hpssacli
  cp -r /boot/extra-tools/hpssacli/opt/hp/hpssacli /opt/hp/
else
  echo "ERROR: hpssacli not available. Copy $DEB manually to $PLG and reinstall the plugin."
  exit 1
fi

chmod -R u+x /opt/hp/hpssacli
BIN=$(find /opt/hp/hpssacli -type f -name hpssacli | head -1)
if [ -z "$BIN" ]; then echo "ERROR: hpssacli binary not found after extraction."; exit 1; fi
ln -sf "$BIN" /usr/local/sbin/hpssacli
echo "hpssacli: $BIN"
/usr/local/sbin/hpssacli ctrl all show 2>&1 | grep -i "slot" || echo "Warning: hpssacli did not detect a controller."
[ -x /usr/local/sbin/update_cron ] && /usr/local/sbin/update_cron
echo "Smart Array P410 plugin installed – Settings → User Utilities → Smart Array P410 (+ Dashboard tile)"
