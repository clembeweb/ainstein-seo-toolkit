# Guida al Deploy - Ainstein SEO Toolkit

Istruzioni per il deploy su SiteGround o hosting simili.

## Requisiti Server

- **PHP:** 8.0 o superiore
- **MySQL:** 5.7+ o MariaDB 10.3+
- **Estensioni PHP:** pdo_mysql, curl, json, mbstring, openssl
- **Accesso SSH:** Consigliato per deploy
- **SSL:** Certificato HTTPS (Let's Encrypt o altro)

## Preparazione

### 1. Crea Database

Da SiteGround Site Tools > Site > MySQL:

```
Database name: ainstein_seo_toolkit
Database user: ainstein_user
Password: [genera password sicura]
```

### 2. Prepara File .env Produzione

```env
# Applicazione
APP_NAME="Ainstein SEO Toolkit"
APP_URL=https://tuodominio.com
APP_DEBUG=false

# Database (usa i dati creati sopra)
DB_HOST=localhost
DB_NAME=ainstein_seo_toolkit
DB_USER=ainstein_user
DB_PASS=password_sicura

# Claude API
CLAUDE_API_KEY=sk-ant-api...
CLAUDE_MODEL=claude-sonnet-4-20250514

# SerpAPI
SERPAPI_KEY=your_serpapi_key

# SMTP (SiteGround fornisce SMTP)
SMTP_HOST=mail.tuodominio.com
SMTP_PORT=465
SMTP_USER=noreply@tuodominio.com
SMTP_PASS=password_email
SMTP_FROM_EMAIL=noreply@tuodominio.com
SMTP_FROM_NAME="Ainstein SEO Toolkit"

# Google APIs (opzionale)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# Stripe (opzionale)
STRIPE_ENABLED=false
```

## Deploy via SSH

### 1. Connessione SSH

```bash
ssh user@tuodominio.com
cd public_html
# oppure nella directory desiderata
```

### 2. Clone Repository

```bash
git clone https://github.com/clembeweb/ainstein-seo-toolkit.git
cd ainstein-seo-toolkit
```

### 3. Configurazione Environment

```bash
cp .env.example .env
nano .env
# Inserisci i valori di produzione
```

### 4. Permessi Directory

```bash
chmod 755 -R .
chmod 777 -R storage/
chmod 777 -R storage/logs/
chmod 777 -R storage/cache/
```

### 5. Import Database

```bash
mysql -u ainstein_user -p ainstein_seo_toolkit < database/schema.sql
```

## Configurazione Webserver

### Apache (.htaccess)

Il file `public/.htaccess` e' gia' configurato. Assicurati che:

1. **mod_rewrite** sia abilitato
2. **AllowOverride All** sia impostato

### Document Root

Configura il document root per puntare a:
```
/home/user/public_html/ainstein-seo-toolkit/public/
```

Su SiteGround, usa Site Tools > Site > Domain per configurare.

### Configurazione Dominio/Sottodominio

Opzione A - **Sottodominio:**
```
seo.tuodominio.com -> /ainstein-seo-toolkit/public/
```

Opzione B - **Sottodirectory:**
Modifica `APP_URL` in `.env`:
```env
APP_URL=https://tuodominio.com/seo-toolkit
```

## Aggiornamenti

### Pull Aggiornamenti

```bash
cd /path/to/ainstein-seo-toolkit
git pull origin main
```

### Migrazioni Database

Se ci sono nuove tabelle:
```bash
mysql -u user -p database < database/migrations/xxx.sql
```

## Cron Jobs

Configura i seguenti cron jobs:

```bash
# Sync giornaliero GSC/GA4 (ore 3:00)
0 3 * * * php /path/to/ainstein-seo-toolkit/modules/seo-tracking/cron/daily-sync.php

# Report settimanali (lunedi ore 6:00)
0 6 * * 1 php /path/to/ainstein-seo-toolkit/modules/seo-tracking/cron/weekly-reports.php

# Report mensili (1° del mese ore 7:00)
0 7 1 * * php /path/to/ainstein-seo-toolkit/modules/seo-tracking/cron/monthly-reports.php

# Pulizia log AI (ogni giorno ore 4:00)
0 4 * * * php /path/to/ainstein-seo-toolkit/cron/cleanup-ai-logs.php
```

## Troubleshooting

### Errore 500 - Internal Server Error

1. Controlla i log PHP:
   ```bash
   tail -f /path/to/error.log
   ```

2. Verifica permessi:
   ```bash
   chmod 755 -R .
   chmod 777 -R storage/
   ```

3. Verifica .htaccess:
   ```bash
   cat public/.htaccess
   ```

### Errore Database Connection

1. Verifica credenziali in `.env`
2. Testa connessione:
   ```bash
   mysql -u user -p -h localhost database
   ```

### Pagina Bianca

1. Abilita debug temporaneamente:
   ```env
   APP_DEBUG=true
   ```

2. Controlla log:
   ```bash
   tail -f storage/logs/error.log
   ```

### Errore Claude API

1. Verifica API key in `.env`
2. Controlla crediti account Anthropic
3. Verifica log chiamate in Admin > AI Logs

### CSS/JS Non Caricati

1. Verifica `APP_URL` in `.env`
2. Controlla che punti correttamente a `public/`
3. Svuota cache browser

## Checklist Pre-Go-Live

- [ ] `.env` configurato con valori produzione
- [ ] `APP_DEBUG=false`
- [ ] Database importato
- [ ] Permessi directory corretti
- [ ] SSL/HTTPS attivo
- [ ] Cron jobs configurati
- [ ] Test login funzionante
- [ ] Test moduli principali
- [ ] Backup automatici configurati

## Backup

### Database

```bash
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

### File

```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz ainstein-seo-toolkit/
```

### Automatico su SiteGround

Site Tools > Security > Backups - Abilita backup giornalieri.

---

Per supporto: consulta `docs/` o apri issue su GitHub.
