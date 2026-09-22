D-STAR Reflector Monitor v6.2.1

Maintenance release: fixes browser-side JavaScript syntax errors that could leave the 24-hour Availability History at 'Loading history…'. It also adds defensive handling for missing users/modules/peers arrays and ensures history initialization runs even if initial card rendering fails.

# D-STAR Reflector Monitor — Production v6.2.1

Production web monitor for D-STAR reflector dashboards.

## Version

**v6.2.1 — September 2026**

v6 consolidates the production monitor and corrects four dashboard parsing/display issues:

1. **XLX country/flag and callsign parsing**
   - Country/flag and callsign are parsed as separate fields.
   - Callsign suffix information remains separate where the source dashboard provides it.
   - Via/Peer, Last Heard, and Listening On/module data remain associated with the correct user row.

2. **REF/DREFD Remote Users**
   - REF038, REF039, REF040, and REF049 explicitly parse and display the DREFD **Remote Users** table.
   - Remote Users are kept separate from Last Heard activity.

3. **REF module count/list**
   - REF cards no longer report an XLX-style `Peers: 0`.
   - Available REF modules are shown as modules (for example `A B C D E`) and the module count is derived from the detected module letters.

4. **Alphabetical XLX modules**
   - XLX module rows are normalized and sorted alphabetically by module letter before display.
   - Module metadata stays attached to the correct module.

v6 retains the v5 client-side live monitoring and SQLite history features.


## v6.2.1 SQLite concurrency and history reliability

v6.2.1 preserves the v6.1 reflector corrections and fixes history locking/read-only behavior. It uses WAL + synchronous=NORMAL, a 5-second SQLite busy timeout, a non-blocking writer lock, one history sample per 60 seconds, non-fatal history failures, and a 5-second browser history timeout. Existing `data/monitor.sqlite` history should be preserved.

### Step-by-step upgrade from v6.1

1. Back up the installation and database:
```bash
cd /var/www/xlxd
sudo cp -a dstar-monitor dstar-monitor.backup-$(date +%Y%m%d-%H%M%S)
sudo cp -a dstar-monitor/data/monitor.sqlite /root/monitor.sqlite.backup-$(date +%Y%m%d-%H%M%S)
```

2. Extract v6.2.1 in a temporary directory. Do not replace your existing `data/monitor.sqlite`; the ZIP intentionally contains no SQLite database.

3. Copy v6.2.1 application files:
```bash
cd /path/to/extracted/dstar-reflector-monitor-production-v6.2.1
sudo cp -a config.php functions.php history.php history_api.php api.php index.php .htaccess README.md README.txt /var/www/xlxd/dstar-monitor/
```

4. Correct permissions:
```bash
sudo mkdir -p /var/www/xlxd/dstar-monitor/cache /var/www/xlxd/dstar-monitor/data
sudo chown -R www-data:www-data /var/www/xlxd/dstar-monitor/cache /var/www/xlxd/dstar-monitor/data
sudo chmod 775 /var/www/xlxd/dstar-monitor/cache /var/www/xlxd/dstar-monitor/data
sudo chmod 664 /var/www/xlxd/dstar-monitor/data/monitor.sqlite
sudo find /var/www/xlxd/dstar-monitor/data -type f -name 'monitor.sqlite-*' -exec chown www-data:www-data {} \; -exec chmod 664 {} \;
```

5. Verify writes:
```bash
sudo -u www-data test -w /var/www/xlxd/dstar-monitor/data && echo "DATA DIR WRITE OK"
sudo -u www-data test -w /var/www/xlxd/dstar-monitor/data/monitor.sqlite && echo "DATABASE WRITE OK"
```

6. Confirm PHP SQLite modules:
```bash
php -m | grep -Ei 'sqlite|pdo'
```

7. Syntax check:
```bash
cd /var/www/xlxd/dstar-monitor
php -l config.php
php -l functions.php
php -l history.php
php -l history_api.php
php -l api.php
php -l index.php
```

8. Restart Apache:
```bash
sudo systemctl restart apache2
```

9. Test history and live API:
```bash
curl -sS --max-time 10 "http://127.0.0.1/dstar-monitor/history_api.php?hours=24"
curl -sS --max-time 30 "http://127.0.0.1/dstar-monitor/api.php" | head -40
```

10. Verify SQLite mode:
```bash
sudo -u www-data php -r '$db=new PDO("sqlite:/var/www/xlxd/dstar-monitor/data/monitor.sqlite"); echo $db->query("PRAGMA journal_mode")->fetchColumn(),PHP_EOL; echo $db->query("PRAGMA busy_timeout")->fetchColumn(),PHP_EOL;'
```
Expected: `wal` and `5000`.

11. Open the dashboard. If history fails, it now says `History temporarily unavailable. Live reflector monitoring is still operating.` and the live dashboard continues.

12. After several minutes check for new errors:
```bash
sudo tail -n 100 /var/log/apache2/error.log | grep -E 'DSTAR history|database is locked|readonly database'
```
No new lock/read-only messages should appear.

## Monitored reflectors

The configured production set contains 10 reflectors:

- REF038 — `ref038.dstargateway.org`
- REF039 — `ref039.dstargateway.org`
- REF040 — `ref040.dstargateway.org`
- REF049 — `ref049.dstargateway.org`
- XLX038 — `xlx038.dyndns.org`
- XLX049 — `xlx049.dyndns.org`
- XLX139 — `xlx139.dyndns.org`
- XLX351 — `xlx351.dyndns.org`
- XLX978 — `xlx978.dyndns.org`
- DCS016 — `http://dcs016.xreflector.net/dcs/`

## Reflector-family Service fields

### REF / DPLUS / DREFD
Displays:
- Service state
- Uptime
- DREFD Version
- Available modules
- Remote Users
- Last Heard/activity

### XLX
Displays:
- Service state
- Uptime
- XLX Version
- Dashboard Version
- Alphabetically sorted modules
- Country/flag and callsign as separate user fields
- Peers/links
- Last Heard/activity

### DCS
DCS016 uses its own parser and displays the status/version/module/activity information published by its dashboard.

## Live client monitoring

The browser periodically refreshes `api.php` without requiring a full page reload. The client supports:

- reflector status-change detection
- new-activity highlighting
- stale-data detection
- optional browser notifications
- 24-hour availability-history presentation

The browser does **not** directly poll every reflector. Public reflector dashboards are collected server-side and exposed through the local API.

## SQLite history

v6 retains the SQLite history subsystem introduced in v5. Historical observations are stored under:

```text
/var/www/xlxd/dstar-monitor/data/
```

The database is used for sampled availability/history. This measures whether the monitor successfully observed the reflector dashboard and should not be interpreted as a protocol-level guarantee that every D-STAR service was operational.

## Production installation path

The canonical installation directory is:

```text
/var/www/xlxd/dstar-monitor
```

Do **not** install this project under `/var/www/html/dstar-monitor`.

## Requirements

Debian/Ubuntu example:

```bash
sudo apt update
sudo apt install apache2 php php-curl php-xml php-sqlite3 unzip
```

Confirm required PHP modules:

```bash
php -m | grep -E 'curl|dom|pdo_sqlite|sqlite3'
```

## Upgrade from v5 or an earlier release

Back up the existing installation first:

```bash
sudo cp -a /var/www/xlxd/dstar-monitor \
  /var/www/xlxd/dstar-monitor.backup-$(date +%Y%m%d-%H%M%S)
```

Extract the v6 package into a temporary directory, then copy the project files into the production directory.

Preserve the existing SQLite history database if you want to retain collected history. Do not delete the existing `data/monitor.sqlite` during an upgrade.

Example:

```bash
cd /path/to/extracted/v6

sudo mkdir -p /var/www/xlxd/dstar-monitor
sudo cp -a . /var/www/xlxd/dstar-monitor/

sudo mkdir -p \
  /var/www/xlxd/dstar-monitor/cache \
  /var/www/xlxd/dstar-monitor/data

sudo chown -R www-data:www-data /var/www/xlxd/dstar-monitor
sudo find /var/www/xlxd/dstar-monitor -type d -exec chmod 755 {} \;
sudo find /var/www/xlxd/dstar-monitor -type f -exec chmod 644 {} \;
sudo chmod 775 \
  /var/www/xlxd/dstar-monitor/cache \
  /var/www/xlxd/dstar-monitor/data
```

If you are upgrading an installation with an existing `data/monitor.sqlite`, verify that the database remains present after the copy.

## PHP validation

Run:

```bash
cd /var/www/xlxd/dstar-monitor

php -l config.php
php -l functions.php
php -l history.php
php -l api.php
php -l history_api.php
php -l index.php
```

Then restart Apache:

```bash
sudo systemctl restart apache2
```

## URLs

Main dashboard:

```text
https://xlx839.dyndns.org/dstar-monitor/
```

Current-state API:

```text
https://xlx839.dyndns.org/dstar-monitor/api.php
```

24-hour history example:

```text
https://xlx839.dyndns.org/dstar-monitor/history_api.php?hours=24
```

## Post-upgrade checks for v6

After installing v6, verify these items in the live dashboard:

- XLX user rows show the correct **country/flag** separately from the **callsign**.
- XLX modules appear in alphabetical order.
- REF038/039/040/049 show **Remote Users** when their source dashboards report connected remote users.
- REF cards show the detected **module count and module letters**, not `Peers: 0`.
- REF Service information uses **DREFD Version**.
- XLX Service information uses **XLX Version** and **Dashboard Version**.
- DCS016 remains visible and is handled as a DCS reflector.
- `history_api.php?hours=24` returns history data.
- Existing SQLite history is retained after an upgrade.

## Important monitoring note

This application monitors information exposed by public reflector dashboards. Dashboard reachability and parsed status are not identical to protocol-level D-STAR health. A reflector may require additional TCP/UDP or host-local checks if protocol-level availability monitoring is required.
