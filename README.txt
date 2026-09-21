# D-STAR Reflector Network Monitor — production package

Monitors these public dashboards:

REF038  ref038.dstargateway.org
REF039  ref039.dstargateway.org
REF040  ref040.dstargateway.org
REF049  ref049.dstargateway.org
XLX038  xlx038.dyndns.org
XLX049  xlx049.dyndns.org
XLX139  xlx139.dyndns.org
XLX351  xlx351.dyndns.org
XLX978  xlx978.dyndns.org

## Requirements

Debian/Ubuntu Apache/PHP example:

    sudo apt update
    sudo apt install apache2 php php-curl php-xml

Verify:

    php -m | grep -E 'curl|dom'

## Install

    sudo mkdir -p /var/www/xlxd/dstar-monitor
    sudo cp -a . /var/www/xlxd/dstar-monitor/

Set ownership:

    sudo chown -R www-data:www-data /var/www/xlxd/dstar-monitor

Set permissions:

    sudo find /var/www/xlxd/dstar-monitor -type f -exec chmod 644 {} \;
    sudo chmod 755 /var/www/xlxd/dstar-monitor/cache

Syntax check:

    cd /var/www/xlxd/dstar-monitor
    php -l config.php
    php -l functions.php
    php -l api.php
    php -l index.php

Open:

    https://YOUR-SERVER/dstar-monitor/

JSON API:

    https://YOUR-SERVER/dstar-monitor/api.php

## Design notes

* Polling is cached for 15 seconds per reflector to avoid repeatedly hitting remote dashboards.
* The browser refreshes the API every 30 seconds without a full page reload.
* HTTPS is preferred; HTTP is a fallback if the remote dashboard does not answer HTTPS.
* The parser intentionally treats data as "not published" when a remote dashboard does not expose it.
* No remote credentials are required.
* The public dashboard HTML can change. If an operator changes the dashboard layout, the parser may need a site-specific adjustment.

## What is displayed

* Online/offline
* HTTP response time
* XLXD service uptime when published
* XLXD version when published
* Active/defined modules
* Reported users
* Last Heard data when published
* XLX peers
* Direct dashboard link
* Network-wide Last Heard table
* Callsign/reflector/type filtering

## Important

The status is a web-dashboard/application status check, not a direct D-STAR protocol health check. A reflector may be reachable by HTTP while one of its D-STAR services is impaired, or it may publish limited information.

For a true protocol-level monitor, add separate TCP/UDP checks for the relevant D-STAR services and/or run a monitor close to the reflector infrastructure.


DEPLOYMENT PATH
---------------
The canonical installation path for this project is:

    /var/www/xlxd/dstar-monitor

Do not install this project under /var/www/html/dstar-monitor. If Apache is configured with /var/www/xlxd as the relevant document root or Alias target, the public URL remains /dstar-monitor/ as configured by the server.
