# Masscan Web UI

A modern web interface for browsing, searching, and visualising [Masscan](https://github.com/robertdavidgraham/masscan) network scan results.

**Modernised from the original [Offensive Security](https://github.com/offensive-security/masscan-web-ui) project (archived 2022).**

![PHP 8.2](https://img.shields.io/badge/PHP-8.2+-8892BF?logo=php)
![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)

---

## Features

- **Search** scan results by IP, port, state, protocol, service, and banner/title
- **Dashboard** with host counts, top ports/services charts, and scan activity timeline
- **Scan launcher** — run Masscan directly from the browser (Docker mode)
- **Export** results as XML (Nmap-compatible) or CSV
- **Dark mode** toggle that persists across sessions
- **MySQL** (default) or **PostgreSQL** support
- Fully **Dockerised** — one command to get running

---

## Quick Start (Docker)

```bash
git clone https://github.com/lwild12/masscan-web-ui.git
cd masscan-web-ui
docker compose up --build -d
```

Open **http://localhost:8080** in your browser.

The database is initialised automatically on first start.

### Customise settings

Copy `.env.example` to `.env` and edit before running:

```bash
cp .env.example .env
# edit .env — change passwords, ports, etc.
docker compose up --build -d
```

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_DRIVER` | `mysql` | `mysql` or `pgsql` |
| `DB_HOST` | `db` (Docker) | Database host |
| `DB_USERNAME` | `masscan` | Database user |
| `DB_PASSWORD` | `changem3` | Database password — **change this!** |
| `DB_DATABASE` | `masscan` | Database name |
| `DB_ROOT_PASSWORD` | `rootpassword` | MySQL root password |
| `WEB_PORT` | `8080` | Host port for the web UI |
| `APP_DEBUG` | `false` | Show PHP errors (dev only) |

---

## Running Scans

### Option A — From the browser (Docker)

Click **Scan** in the navigation bar. Enter a target and ports, then click **Start Scan**.
Masscan runs inside the container and results are imported automatically.

> The container requires `CAP_NET_RAW` (included in `docker-compose.yml`) to run Masscan.

### Option B — Manual import

Run Masscan on the command line and export to XML:

```bash
masscan 10.0.0.0/24 -p 80,443,22,21 --banners -oX scan.xml
```

Then import into the database:

**Docker:**
```bash
# Copy the XML into the container's imports directory, then:
docker cp scan.xml masscan-web-ui-web-1:/var/www/html/imports/
docker exec -it masscan-web-ui-web-1 php /var/www/html/import.php /var/www/html/imports/scan.xml
```

**Bare-metal:**
```bash
php import.php /path/to/scan.xml
```

The import script will ask if you want to clear the database first.

---

## Bare-Metal Setup

Requirements: **PHP 8.2+**, **Apache 2** (or Nginx), **MySQL 8** or **PostgreSQL 14+**, `php-pdo`, `php-pdo-mysql` / `php-pdo-pgsql`, `php-xml`

```bash
# 1. Clone
git clone https://github.com/lwild12/masscan-web-ui.git /var/www/html/masscan

# 2. Create database (MySQL example)
mysql -u root -p <<'SQL'
CREATE DATABASE masscan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'masscan'@'localhost' IDENTIFIED BY 'changem3';
GRANT ALL PRIVILEGES ON masscan.* TO 'masscan'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql -u masscan -p masscan < db-structure-mysql.sql

# 3. Set environment variables in Apache (add to VirtualHost):
#   SetEnv DB_DRIVER   mysql
#   SetEnv DB_HOST     127.0.0.1
#   SetEnv DB_USERNAME masscan
#   SetEnv DB_PASSWORD changem3
#   SetEnv DB_DATABASE masscan
```

---

## Architecture

```
index.php               — Search page
dashboard.php           — Stats & charts
scan.php                — Scan launcher UI
ajax-scan.php           — Starts a masscan job (POST)
includes/
  scan_status.php       — Job status polling (GET → JSON)
  scan_import.php       — Auto-import after scan (CLI, called by wrapper)
  functions.php         — DB helpers: browse(), getStats(), getStartAndEndIps()
  data_validation.php   — Input sanitisation
  header.php / footer.php — Layout templates
  list.php              — Results table template
  res-wrapper.php       — Results card wrapper
assets/
  style.css             — Custom Bootstrap 5 theme
  scripts.js            — AJAX search, dark mode, modals
  dashboard.js          — Chart.js charts
docker/
  php/Dockerfile        — PHP 8.2 + Apache + Masscan
  mysql/init/           — DB auto-initialisation SQL
```

---

## Security Notes

- All database queries use **parameterised prepared statements** (PDO).
- Scan target and port inputs are **regex-validated** before being passed to `exec()`.
- All shell arguments use **`escapeshellarg()`**.
- One scan at a time is enforced via the `jobs` table.
- The scan launcher requires `CAP_NET_RAW` inside Docker — do not expose the UI to untrusted networks without authentication.

---

## Licence

Original work © Offensive Security — MIT Licence.
Modernisation additions are released under the same terms.
