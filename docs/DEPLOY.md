# Guida al Deploy - Ainstein SEO Toolkit

> Ultimo aggiornamento: 2026-03-10 (Migrazione da SiteGround a Hetzner VPS)

---

## Ambienti

| Ambiente | URL | Server | Tipo |
|----------|-----|--------|------|
| **Produzione** | https://ainstein.it | Hetzner VPS `91.99.20.247` | CPX22 Ubuntu 24.04 |
| **Locale** | http://localhost/seo-toolkit | Windows/Laragon | PHP 8.3 |
| ~~Produzione legacy~~ | ~~ssh.ainstein.it~~ | ~~SiteGround~~ | ~~Dismesso~~ |

---

## Specifiche Server Produzione (Hetzner VPS)

| Componente | Dettaglio |
|-----------|-----------|
| **Piano** | CPX22 Regular Performance (AMD) |
| **CPU** | 2 vCPU |
| **RAM** | 4 GB |
| **Disco** | 80 GB SSD |
| **OS** | Ubuntu 24.04 LTS |
| **Location** | Nuremberg, Germania (eu-central) |
| **IP** | `91.99.20.247` |
| **Costo** | ~6.49 EUR/mese (server + IPv4) |

### Stack installato

| Software | Versione | Config |
|----------|---------|--------|
| PHP | 8.3 + FPM | `memory_limit=512M`, `max_execution_time=300`, `timezone=Europe/Rome` |
| MySQL | 8.0 | `innodb_buffer_pool_size=1G`, `max_connections=150`, `utf8mb4` |
| Apache | 2.4 + mod_rewrite, mod_ssl, proxy_fcgi | VirtualHost su `/var/www/ainstein.it/public_html/public` |
| Composer | latest | Dipendenze in `vendor/` |
| Certbot | latest | Let's Encrypt auto-renew |
| Fail2ban | attivo | Anti brute-force SSH |
| UFW | attivo | Porte: 22, 80, 443 |

### Estensioni PHP attive

```
pdo_mysql, curl, mbstring, xml, gd, zip, intl, bcmath, opcache,
openssl, json, readline, dom, SimpleXML, fileinfo, exif, sockets
```

---

## Connessione SSH

### Produzione (Hetzner VPS)

```bash
# Connessione standard
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247

# Chiave SSH locale: ~/.ssh/ainstein_hetzner (ed25519)
# Utente: ainstein (sudo senza password)
# Root login: DISABILITATO
# Password auth: DISABILITATO
```

### Credenziali Database Produzione

```bash
mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo
```

| Parametro | Valore |
|-----------|--------|
| Host | `localhost` |
| Database | `ainstein_seo` |
| User | `ainstein` |
| Password | `Ainstein_DB_2026!Secure` |

### ~~SiteGround (legacy — dismesso)~~

```bash
# NON PIU' IN USO — mantenuto come reference
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
# DB: mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1
```

---

## Struttura Directory (Produzione)

```
/var/www/ainstein.it/
└── public_html/              ← git repo root
    ├── public/               ← Apache DocumentRoot
    │   ├── index.php         ← Entry point
    │   ├── .htaccess         ← Rewrite rules
    │   └── assets/           ← CSS, JS, immagini statiche
    ├── config/               ← app.php, database.php, environment.php
    ├── core/                 ← Framework core
    ├── modules/              ← Tutti i moduli
    ├── services/             ← Servizi condivisi
    ├── storage/              ← Logs, cache, uploads (775, www-data)
    │   ├── logs/
    │   ├── cache/
    │   ├── images/
    │   └── reports/
    ├── vendor/               ← Composer dependencies
    ├── .env                  ← Config ambiente (640, ainstein:www-data)
    └── CLAUDE.md
```

---

## File .env Produzione

```env
APP_NAME="Ainstein SEO Toolkit"
APP_URL=https://ainstein.it
APP_DEBUG=false

DB_HOST=localhost
DB_NAME=ainstein_seo
DB_USER=ainstein
DB_PASS=Ainstein_DB_2026!Secure
```

> **NOTA**: Le API key (Claude, SerpAPI, Google, Stripe) e le credenziali SMTP sono salvate nel database (tabella `settings`), NON nel `.env` — vedi Golden Rule #5.

---

## Deploy Aggiornamenti

### Procedura standard (git pull)

```bash
# 1. Connettiti al VPS
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247

# 2. Pull aggiornamenti
cd /var/www/ainstein.it/public_html
git pull origin main

# 3. Aggiorna dipendenze (solo se composer.lock e' cambiato)
composer install --no-dev --optimize-autoloader

# 4. Fix permessi (se necessario)
sudo chown -R ainstein:www-data /var/www/ainstein.it/public_html
chmod -R 755 /var/www/ainstein.it/public_html
chmod -R 775 /var/www/ainstein.it/public_html/storage
```

### Migrazioni Database

```bash
# Se ci sono nuove migrazioni
mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < database/migrations/XXX.sql

# Per migrazioni specifiche di modulo
mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < modules/{modulo}/database/migrations/XXX.sql
```

### Deploy rapido (da locale)

```bash
# Push locale
git push origin main

# Pull su VPS (one-liner)
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && git pull origin main"
```

---

## Cron Jobs

Tutti i cron sono nel crontab dell'utente `ainstein`. I log vanno in `/var/log/ainstein/cron.log`.

```bash
# Visualizza crontab attivo
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "crontab -l"

# Modifica crontab
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "crontab -e"
```

### Lista completa cron job

| Schedule | Script | Modulo |
|----------|--------|--------|
| `0 0 * * *` | `cron/cleanup-data.php` | Core |
| `0 3 * * *` | `cron/cleanup-api-logs.php` | Core |
| `0 4 * * *` | `cron/cleanup-ai-logs.php` | Core |
| `0 8 * * 1` | `cron/admin-report.php` | Core (lunedi) |
| `* * * * *` | `modules/ai-content/cron/dispatcher.php` | AI Content |
| `*/5 * * * *` | `modules/ads-analyzer/cron/auto-evaluate.php` | Ads Analyzer |
| `*/5 * * * *` | `modules/seo-audit/cron/crawl-dispatcher.php` | SEO Audit |
| `*/5 * * * *` | `modules/seo-tracking/cron/rank-dispatcher.php` | SEO Tracking |
| `0 * * * *` | `modules/seo-tracking/cron/gsc-sync-dispatcher.php` | SEO Tracking |
| `0 * * * *` | `modules/seo-tracking/cron/ai-report-dispatcher.php` | SEO Tracking |
| `*/5 * * * *` | `modules/crawl-budget/cron/crawl-dispatcher.php` | Crawl Budget |
| `0 2 * * *` | `/home/ainstein/backup-db.sh` | Backup DB |

### Differenze rispetto a SiteGround

| Aspetto | SiteGround (prima) | Hetzner VPS (ora) |
|---------|--------------------|--------------------|
| Gestione | Pannello web (no redirect `>>`) | `crontab -e` (pieno controllo) |
| Path PHP | `/usr/bin/php` | `/usr/bin/php` |
| Log output | Solo interno (storage/logs) | `>> /var/log/ainstein/cron.log` + interno |
| Log rotation | Manuale | Automatico (logrotate, 30 giorni) |

---

## Backup

### Automatico (giornaliero)

Lo script `/home/ainstein/backup-db.sh` gira ogni notte alle 2:00:
- Dump completo MySQL → `/home/ainstein/backups/ainstein_YYYYMMDD_HHMM.sql.gz`
- Retention: 7 giorni (vecchi eliminati automaticamente)

```bash
# Verifica backup esistenti
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "ls -lh /home/ainstein/backups/"

# Backup manuale
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "/home/ainstein/backup-db.sh"
```

### Restore da backup

```bash
gunzip < /home/ainstein/backups/ainstein_XXXXXXXX_XXXX.sql.gz | mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo
```

### Backup Hetzner (snapshot)

Dalla [Hetzner Console](https://console.hetzner.com):
- Server > ainstein-vps > Snapshots > Create Snapshot
- Costo: ~0.01 EUR/GB/mese
- Include TUTTO (OS, codice, DB, config)

---

## SSL / HTTPS

### Installazione (da fare dopo DNS pointing)

```bash
sudo certbot --apache -d ainstein.it -d www.ainstein.it
```

### Rinnovo

Certbot configura automaticamente il rinnovo. Per verificare:

```bash
sudo certbot renew --dry-run
```

### Verifica

```bash
curl -I https://ainstein.it
# Deve restituire HTTP/2 200 con header SSL
```

---

## DNS

Per completare la migrazione, puntare i record DNS di `ainstein.it`:

| Tipo | Nome | Valore | TTL |
|------|------|--------|-----|
| A | `@` | `91.99.20.247` | 300 (poi 3600) |
| A | `www` | `91.99.20.247` | 300 (poi 3600) |

> **IMPORTANTE**: Abbassare il TTL a 300 almeno 24h prima del cutover. Dopo la verifica, rialzare a 3600.

---

## Configurazione Apache

### VirtualHost (`/etc/apache2/sites-available/ainstein.conf`)

```apache
<VirtualHost *:80>
    ServerName ainstein.it
    ServerAlias www.ainstein.it
    DocumentRoot /var/www/ainstein.it/public_html/public

    <Directory /var/www/ainstein.it/public_html/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/ainstein-error.log
    CustomLog ${APACHE_LOG_DIR}/ainstein-access.log combined
</VirtualHost>
```

> Dopo SSL, Certbot aggiunge automaticamente il blocco `:443` con i certificati.

### Moduli Apache attivi

```
rewrite, proxy_fcgi, setenvif, headers, expires, ssl
```

---

## Configurazione PHP

### FPM (`/etc/php/8.3/fpm/php.ini`)

```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
date.timezone = Europe/Rome
opcache.enable = 1
opcache.memory_consumption = 128
```

### CLI (`/etc/php/8.3/cli/php.ini`)

```ini
memory_limit = 512M
max_execution_time = 0
date.timezone = Europe/Rome
```

---

## Configurazione MySQL

### Custom config (`/etc/mysql/mysql.conf.d/ainstein.cnf`)

```ini
[mysqld]
innodb_buffer_pool_size = 1G
max_connections = 150
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
default_time_zone = '+01:00'
```

---

## Sicurezza

| Misura | Stato |
|--------|-------|
| SSH root login | Disabilitato |
| SSH password auth | Disabilitato |
| Firewall UFW | Attivo (22, 80, 443) |
| Fail2ban | Attivo |
| .env | `640 ainstein:www-data` |
| API keys | In database, non in file |
| HTTPS | Let's Encrypt (da attivare dopo DNS) |

### Chiavi SSH sul VPS

| File | Scopo |
|------|-------|
| `/home/ainstein/.ssh/github_deploy` | Deploy key GitHub (read-only) |
| Authorized keys | Chiave locale `~/.ssh/ainstein_hetzner.pub` |

---

## Troubleshooting

| Problema | Fix |
|----------|-----|
| 500 Error | `tail -f /var/log/apache2/ainstein-error.log` |
| PHP errors | `tail -f /var/www/ainstein.it/public_html/storage/logs/error.log` |
| Cron non gira | `tail -f /var/log/ainstein/cron.log` |
| MySQL connection | Verificare `.env` credenziali, `sudo systemctl status mysql` |
| Apache down | `sudo systemctl restart apache2` |
| PHP-FPM down | `sudo systemctl restart php8.3-fpm` |
| Permessi .env | `sudo chown ainstein:www-data .env && chmod 640 .env` |
| Storage non scrivibile | `chmod -R 775 storage/ && chown -R ainstein:www-data storage/` |
| Disco pieno | `df -h`, controllare backup in `/home/ainstein/backups/` |
| SSL scaduto | `sudo certbot renew` |
| SSE non funziona | Su Hetzner funziona nativamente (no proxy come SiteGround) |

### Comandi diagnostici

```bash
# Stato servizi
sudo systemctl status apache2 php8.3-fpm mysql

# Log in tempo reale
tail -f /var/log/apache2/ainstein-error.log
tail -f /var/log/ainstein/cron.log
tail -f /var/www/ainstein.it/public_html/storage/logs/error.log

# Spazio disco
df -h

# Memoria
free -m

# Processi PHP attivi
ps aux | grep php

# Test PHP dal terminale
php -r "phpinfo();" | grep memory_limit
```

---

## Checklist Deploy Nuovo Modulo

```
[ ] Codice pushato su main e pullato sul VPS
[ ] Migrazioni DB eseguite
[ ] Modulo inserito in tabella `modules` (is_active=1)
[ ] Permessi file corretti
[ ] Cron job aggiunto (se necessario)
[ ] Test manuale dal browser
[ ] Documentazione aggiornata
```

---

## Scalabilita

Se il server CPX22 non basta, si scala verticalmente dalla Hetzner Console:

1. Server > ainstein-vps > Rescale
2. Scegli piano superiore (es. CPX32: 4 vCPU, 8GB RAM)
3. Il server si riavvia (~30 secondi di downtime)
4. Nessuna riconfigurazione necessaria

---

*Documento per Ainstein SEO Toolkit — Aggiornare ad ogni modifica infrastrutturale*
