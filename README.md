# D-STAR Reflector Network Monitor — production v4

Monitors 10 public D-STAR reflector dashboards:

- REF038 — ref038.dstargateway.org (DPLUS / DREFD)
- REF039 — ref039.dstargateway.org (DPLUS / DREFD)
- REF040 — ref040.dstargateway.org (DPLUS / DREFD)
- REF049 — ref049.dstargateway.org (DPLUS / DREFD)
- DCS016 — http://dcs016.xreflector.net/dcs/ (DCS / XReflector)
- XLX038 — xlx038.dyndns.org (XLXD)
- XLX049 — xlx049.dyndns.org (XLXD)
- XLX139 — xlx139.dyndns.org (XLXD)
- XLX351 — xlx351.dyndns.org (XLXD)
- XLX978 — xlx978.dyndns.org (XLXD)

## Service fields by reflector family

REF/DPLUS cards display Service, Uptime (when published), and DREFD Version parsed from the reflector dashboard.
XLX/XLXD cards display Service, Uptime, XLX Version, and Dashboard Version.
DCS cards display Service, Uptime/Starttime when available, and DCS Version when published by XReflector.

## Requirements

Debian/Ubuntu Apache/PHP:

    sudo apt update
    sudo apt install apache2 php php-curl php-xml

Verify:

    php -m | grep -E 'curl|dom'

## Canonical installation path

This project MUST be installed at:

    /var/www/xlxd/dstar-monitor

Do not use /var/www/html/dstar-monitor.

## Install / upgrade

Extract the package in a temporary directory, then:

    sudo mkdir -p /var/www/xlxd/dstar-monitor
    sudo cp -a . /var/www/xlxd/dstar-monitor/
    sudo mkdir -p /var/www/xlxd/dstar-monitor/cache
    sudo chown -R www-data:www-data /var/www/xlxd/dstar-monitor
    sudo find /var/www/xlxd/dstar-monitor -type f -exec chmod 644 {} \;
    sudo chmod 755 /var/www/xlxd/dstar-monitor/cache

Syntax check:

    cd /var/www/xlxd/dstar-monitor
    php -l config.php
    php -l functions.php
    php -l api.php
    php -l index.php

If Apache maps /var/www/xlxd as its document root or Alias target, open:

    https://YOUR-SERVER/dstar-monitor/

JSON API:

    https://YOUR-SERVER/dstar-monitor/api.php

## What is displayed

- Online/offline web-dashboard status
- HTTP response time
- Reflector-family-specific software/version information
- Service uptime/start time when the remote dashboard publishes it
- Active/defined modules
- Reported users/activity
- Last Heard when published
- XLX peers and DCS repeater/interlink information when published
- Direct dashboard link
- Network-wide Last Heard
- Callsign/reflector/type filtering

## Operational notes

Polling is cached for 15 seconds per reflector. The browser refreshes the API every 30 seconds. HTTPS is preferred where configured; DCS016 uses its published HTTP dashboard. No remote credentials are required.

Public dashboard HTML varies by reflector family and can change. The parser deliberately reports “Not published” instead of inventing values when a field is absent.

This is a web-dashboard/application monitor, not a direct D-STAR protocol health test. HTTP availability does not prove every D-STAR service is healthy.
