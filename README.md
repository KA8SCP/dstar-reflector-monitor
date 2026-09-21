# D-STAR Reflector Network Monitor — production v5

Production monitor for REF038, REF039, REF040, REF049, DCS016, XLX038, XLX049, XLX139, XLX351 and XLX978.

## v5 enhancements

The PHP collectors remain authoritative. The browser now polls `api.php` every 15 seconds, detects online/offline changes and new Last Heard activity, highlights active reflector cards, warns when data becomes stale, and can issue browser notifications after the user grants permission.

A local SQLite database (`data/monitor.sqlite`) records status observations and online/offline transitions. The dashboard displays 24-hour sampled availability; `history_api.php?hours=24` exposes history summaries. Retention defaults to 90 days.

## Service fields

- REF/DPLUS: Service, Uptime, DREFD Version
- XLX/XLXD: Service, Uptime, XLX Version, Dashboard Version
- DCS: Service, Uptime/Starttime, DCS Version

## Requirements and installation

Canonical path: `/var/www/xlxd/dstar-monitor`

Install required packages:

    sudo apt update
    sudo apt install apache2 php php-curl php-xml php-sqlite3 unzip

Back up an existing installation:

    sudo cp -a /var/www/xlxd/dstar-monitor /var/www/xlxd/dstar-monitor.backup-$(date +%Y%m%d-%H%M%S)

Extract v5 to a temporary directory, change into the extracted `dstar-reflector-monitor` directory, then deploy:

    sudo mkdir -p /var/www/xlxd/dstar-monitor
    sudo cp -a . /var/www/xlxd/dstar-monitor/
    sudo mkdir -p /var/www/xlxd/dstar-monitor/cache /var/www/xlxd/dstar-monitor/data
    sudo chown -R www-data:www-data /var/www/xlxd/dstar-monitor
    sudo find /var/www/xlxd/dstar-monitor -type d -exec chmod 755 {} \;
    sudo find /var/www/xlxd/dstar-monitor -type f -exec chmod 644 {} \;
    sudo chmod 775 /var/www/xlxd/dstar-monitor/cache /var/www/xlxd/dstar-monitor/data

Verify PHP modules:

    php -m | grep -E 'curl|dom|pdo_sqlite|sqlite3'

Syntax check:

    cd /var/www/xlxd/dstar-monitor
    php -l config.php
    php -l functions.php
    php -l history.php
    php -l api.php
    php -l history_api.php
    php -l index.php

Restart Apache after installing `php-sqlite3`:

    sudo systemctl restart apache2

Open the monitor:

    https://xlx839.dyndns.org/dstar-monitor/

API endpoints:

    https://xlx839.dyndns.org/dstar-monitor/api.php
    https://xlx839.dyndns.org/dstar-monitor/history_api.php?hours=24

## First-run checks

1. Refresh the dashboard twice, at least 15 seconds apart.
2. Confirm `data/monitor.sqlite` is created and owned by `www-data`.
3. Confirm the 24-hour Availability History panel begins showing samples.
4. Leave the page open for live polling. Browser notifications are optional and require permission in the browser.
5. If history says SQLite is unavailable, verify `php-sqlite3` is installed and `/var/www/xlxd/dstar-monitor/data` is writable by `www-data`.

## Apache note

The project path is `/var/www/xlxd/dstar-monitor`. Your Apache configuration must already expose `/var/www/xlxd` as the relevant DocumentRoot/Alias so `/dstar-monitor/` resolves correctly. Do not move the project back to `/var/www/html/dstar-monitor`.

## Operational notes

Remote dashboard fetches are cached for 15 seconds. SQLite availability is sample-based: it measures successful dashboard observations, not direct D-STAR protocol uptime. Public dashboard HTML can change, so absent values are reported as “Not published” rather than inferred.
