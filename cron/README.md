# Cron Jobs - SEO Toolkit

Questa cartella contiene gli script per l'esecuzione automatica di task di manutenzione.

## File

### bootstrap.php
Bootstrap leggero per script CLI. Carica autoloader e Database senza routing/sessioni.

### cleanup-ai-logs.php
Elimina i log AI piu vecchi di 30 giorni dalla tabella `ai_logs`.

---

## Configurazione Cron

### Linux (crontab)

Esegui `crontab -e` e aggiungi:

```bash
# Cleanup AI logs - Ogni giorno alle 03:00
0 3 * * * php /var/www/seo-toolkit/cron/cleanup-ai-logs.php >> /var/www/seo-toolkit/storage/logs/cron.log 2>&1
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

I log vengono salvati in: `storage/logs/ai-cleanup.log`

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
php cron/cleanup-ai-logs.php
```

---

## Parametri configurabili

Nel file `cleanup-ai-logs.php`:

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `$retentionDays` | 30 | Giorni di retention dei log |
| `$batchSize` | 1000 | Record eliminati per batch |
