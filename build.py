#!/usr/bin/env python3
"""Builds src/ into a single p410mgr.plg (inline files in CDATA)."""
import pathlib

ROOT = pathlib.Path(__file__).parent
SRC = ROOT / "src"
VERSION = "2026.11.07f"
GITHUB = "francuz152/unraid-p410mgr"
BRANCH = "main"
EMH = "/usr/local/emhttp/plugins/p410mgr"

FILES = [
    ("P410mgr.page", f"{EMH}/P410mgr.page"),
    ("P410mgrDash.page", f"{EMH}/P410mgrDash.page"),
    ("include/i18n.php", f"{EMH}/include/i18n.php"),
    ("include/lib.php", f"{EMH}/include/lib.php"),
    ("include/exec.php", f"{EMH}/include/exec.php"),
    ("include/monitor.php", f"{EMH}/include/monitor.php"),
    ("scripts/check.php", f"{EMH}/scripts/check.php"),
    ("scripts/extract_deb.php", f"{EMH}/scripts/extract_deb.php"),
    ("scripts/install.sh", f"{EMH}/scripts/install.sh"),
]

CHANGES = f"""
###{VERSION}
- English UI (default) with Polish translation; language: auto / English / Polski
- Uninstall keeps settings, SMART baseline and the hpssacli package
- Options to ignore disabled controller cache / failed cache battery
- Periodic SMART + controller checks, Unraid notifications
- MQTT publishing with Home Assistant discovery
- Dashboard tile
- Status view, SMART, locate LED, create RAID0, delete LD, LD cache
"""

README = """**Smart Array P410**

Manage HP Smart Array controllers (P410 / P410i / P212 / P812 and related G6/G7 models) from the Unraid GUI:
controller and drive status, bay → logical drive → /dev → Unraid slot mapping, SMART via cciss,
locate LED, create RAID0 from a new drive, delete logical drives (blocked while in use), periodic
SMART checks with Unraid notifications and MQTT / Home Assistant discovery, and a Dashboard tile.
Uses HPE hpssacli 2.40, downloaded from the official HPE repository (HPE license applies).
Settings → User Utilities → Smart Array P410.
"""

def cdata(text: str) -> str:
    assert "]]>" not in text, "']]>' in file content"
    return f"<![CDATA[\n{text}]]>"

parts = [f"""<?xml version='1.0' standalone='yes'?>
<!DOCTYPE PLUGIN [
<!ENTITY name "p410mgr">
<!ENTITY author "francuz152">
<!ENTITY version "{VERSION}">
<!ENTITY github "{GITHUB}">
<!ENTITY pluginURL "https://raw.githubusercontent.com/&github;/{BRANCH}/&name;.plg">
<!ENTITY plugin "/boot/config/plugins/&name;">
<!ENTITY emhttp "/usr/local/emhttp/plugins/&name;">
]>
<PLUGIN name="&name;" author="&author;" version="&version;" pluginURL="&pluginURL;" support="https://github.com/&github;/issues" min="6.9.0" icon="hdd-o">

<CHANGES>{CHANGES}</CHANGES>

<FILE Name="&emhttp;/README.md">
<INLINE>{cdata(README)}</INLINE>
</FILE>
"""]

for src, dst in FILES:
    mode = ' Mode="0755"' if dst.endswith(".sh") else ""
    parts.append(f'<FILE Name="{dst}"{mode}>\n<INLINE>{cdata((SRC / src).read_text())}</INLINE>\n</FILE>\n')

parts.append(f"""<FILE Run="/bin/bash">
<INLINE>{cdata('bash ' + EMH + '/scripts/install.sh' + chr(10))}</INLINE>
</FILE>

<FILE Run="/bin/bash" Method="remove">
<INLINE>{cdata('''rm -f /usr/local/sbin/hpssacli /tmp/p410mgr_cciss.json /tmp/p410mgr_state.json /boot/config/plugins/p410mgr/p410mgr.cron
/usr/local/sbin/update_cron 2>/dev/null
rm -rf /opt/hp/hpssacli /usr/local/emhttp/plugins/p410mgr
# settings (p410mgr.cfg), SMART baseline and the hpssacli package stay on the flash drive
# full removal: rm -rf /boot/config/plugins/p410mgr
echo "Smart Array P410 plugin removed."
''')}</INLINE>
</FILE>
</PLUGIN>
""")

out = ROOT / "p410mgr.plg"
out.write_text("".join(parts))
print(f"Built {out} (version {VERSION})")
