# Cron Jobs - SEO Toolkit

Questa cartella contiene gli script per l'esecuzione automatica di task di manutenzione.

## File

### bootstrap.php
Bootstrap leggero per script CLI. Carica autoloader e Database senza routing/sessioni.

### cleanup-ai-logs.php
Elimina i log AI piu vecchi di 30 giorni dalla tabella `ai_logs`.

### cleanup-api-logs.php
Elimina i log API piu vecchi di 30 giorni dalla tabella `api_logs`.

### cleanup-data.php
Cleanup centralizzato di dati obsoleti da tutti i moduli. Esegue 9 operazioni indipendenti:

| Target | Retention | Azione |
|--------|-----------|--------|
| `sa_pages.html_content` | 90 giorni | SET NULL (metadata JSON intatti) |
| `il_urls.raw_html` | 30 giorni | SET NULL (content_html intatto) |
| `ga_script_runs` + dati collegati | Ultimi 5 per progetto | DELETE run vecchi |
| `st_gsc_data` | 16 mesi | DELETE |
| `st_keyword_positions` | 16 mesi | DELETE |
| `st_alerts` | 90 giorni (solo letti) | DELETE |
| `aic_process_jobs` | 30 giorni (completati) | DELETE |
| `st_rank_queue` | 7 giorni (completati) | DELETE |
| `ga_auto_eval_queue` | 30 giorni (completati) | DELETE |

---

## Configurazione Cron

### Linux (crontab)

Esegui `crontab -e` e aggiungi:

```bash
# Cleanup dati obsoleti - Ogni giorno alle 02:00
0 2 * * * php /path/to/seo-toolkit/cron/cleanup-data.php >> /path/to/seo-toolkit/storage/logs/cron.log 2>&1

# Cleanup AI logs - Ogni giorno alle 03:00
0 3 * * * php /path/to/seo-toolkit/cron/cleanup-ai-logs.php >> /path/to/seo-toolkit/storage/logs/cron.log 2>&1

# Cleanup API logs - Ogni giorno alle 04:00
0 4 * * * php /path/to/seo-toolkit/cron/cleanup-api-logs.php >> /path/to/seo-toolkit/storage/logs/cron.log 2>&1
```

### Windows (Task Scheduler)

1. Apri Task Scheduler
2. Crea un nuovo task:
   - **Nome**: SEO Toolkit - Cleanup AI Logs
   - **Trigger**: Giornaliero alle 03:00
   - **Azione**: Avvia programma
     - **Programma**: `C:\laragon\bin\php\php-8.x\php.exe`
     - **Argomenti**: `C:\laragon\www\seo-toolkit\cron\cleanup-ai-logs.php`
     - **Directory iniziale**: `C:\laragon\www\seo-toolkit\cron`

### Laragon (Windows alternativo)

Laragon non supporta cron nativamente, ma puoi:
1. Usare Task Scheduler (vedi sopra)
2. Oppure eseguire manualmente: Admin > AI Logs > Cleanup

---

## Log

I log vengono salvati in:
- `storage/logs/data-cleanup.log` - Cleanup dati centralizzato
- `storage/logs/ai-cleanup.log` - Cleanup AI logs
- `storage/logs/api-cleanup.log` - Cleanup API logs

Formato:
```
[2025-12-19 03:00:01] === Inizio cleanup AI logs ===
[2025-12-19 03:00:01] Log piu vecchi di 30 giorni: 150
[2025-12-19 03:00:01] Eliminati batch: 150 (totale: 150)
[2025-12-19 03:00:01] Cleanup completato. Totale eliminati: 150
[2025-12-19 03:00:01] Log rimanenti: 342 | Log piu vecchio: 2025-11-20 14:23:45
[2025-12-19 03:00:01] === Fine cleanup AI logs ===
```

---

## Test manuale

```bash
cd C:\laragon\www\seo-toolkit
php cron/cleanup-data.php
php cron/cleanup-ai-logs.php
php cron/cleanup-api-logs.php
```

---

## Parametri configurabili

### cleanup-data.php

Tutti i parametri sono nell'array `$config` all'inizio del file:

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `sa_pages_html_days` | 90 | Giorni prima di nullificare html_content |
| `il_urls_raw_html_days` | 30 | Giorni prima di nullificare raw_html |
| `ga_runs_keep_per_project` | 5 | Run Google Ads da mantenere per progetto |
| `st_gsc_data_months` | 16 | Mesi di dati GSC da mantenere |
| `st_positions_months` | 16 | Mesi di posizioni keyword da mantenere |
| `st_alerts_days` | 90 | Giorni per alert letti/dismissati |
| `aic_jobs_days` | 30 | Giorni per process jobs completati |
| `st_rank_queue_days` | 7 | Giorni per rank queue completati |
| `ga_eval_queue_days` | 30 | Giorni per auto-eval queue completati |

### cleanup-ai-logs.php / cleanup-api-logs.php

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `$retentionDays` | 30 | Giorni di retention dei log |
| `$batchSize` | 1000 | Record eliminati per batch |
