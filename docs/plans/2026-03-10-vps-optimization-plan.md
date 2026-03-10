# Piano Ottimizzazione VPS — Rimozione Workaround SiteGround

> Data: 2026-03-10 | Contesto: Migrazione da SiteGround shared hosting a Hetzner VPS (CPX22)

## Obiettivo

Eliminare i workaround introdotti per le limitazioni di SiteGround (proxy SSE, timeout 30s, no redirect cron, risorse condivise) e sfruttare le capacità del VPS dedicato per migliorare performance, throughput e affidabilità.

---

## Fase 1 — Cron Batch Optimization (Alta priorità, basso rischio)

### 1.1 Rank Dispatcher: da 1 keyword/run a batch

**File**: `modules/seo-tracking/cron/rank-dispatcher.php`
**Stato attuale**: Processa **1 keyword per esecuzione** (LIMIT 1, riga 534-541). Con cron ogni 5 min = max 12 keyword/ora.
**Problema**: Su SiteGround ogni processo doveva terminare entro 30s, quindi 1 keyword era il massimo sicuro.

**Azione**:
- Cambiare `getNextPendingItem()` → `getNextPendingItems($batchSize)` con LIMIT 10
- Processare in loop fino a batch completo o timeout 240s
- `Database::reconnect()` solo ogni 5 iterazioni (non ogni keyword)
- Throughput: da 12 keyword/ora → ~120 keyword/ora

### 1.2 Auto Evaluate: da 3 a 10 items/run

**File**: `modules/ads-analyzer/cron/auto-evaluate.php`
**Stato attuale**: `$maxItemsPerRun = 3` (riga 35), `sleep(2)` tra items (riga 286).
**Problema**: Con 3 items ogni 5 min + 2s sleep = max 36 valutazioni/ora.

**Azione**:
- Cambiare `$maxItemsPerRun = 3` → `$maxItemsPerRun = 10`
- Ridurre `sleep(2)` → `sleep(1)` (rate limit API non è un problema per AI calls)
- `Database::reconnect()` solo dopo AI call (non dopo scraping/credits)
- Throughput: da 36/ora → ~120/ora

### 1.3 AI Content Dispatcher: batch multi-keyword

**File**: `modules/ai-content/cron/dispatcher.php`
**Stato attuale**: 1 keyword per progetto per esecuzione (riga 329).
**Problema**: Su SiteGround, AI call + scraping poteva superare 30s per keyword singola.

**Azione**:
- Processare fino a 3 keyword per progetto per run (con guard timeout 240s)
- Mantenere il reconnect retry 3-attempt (righe 163-174) — buon pattern
- Ridurre i reconnect inutili (righe 89, 111, 129 dopo query brevi)

---

## Fase 2 — SSE Cleanup (Media priorità, basso rischio)

### 2.1 Rimuovere polling fallback dal SEO Audit crawl

**File**: `modules/seo-audit/views/partials/crawl-control.php`
**Stato attuale**: SSE + polling fallback complesso (righe 467-814). Quando SSE si disconnette (proxy SiteGround timeout 30s), passa automaticamente a polling ogni 3s.

**Azione**:
- Rimuovere la variabile `polling` e il relativo state management
- Rimuovere l'indicatore UI "Connessione in modalità polling" (righe 264-273)
- Rimuovere `startPolling()` function (non più necessaria)
- Mantenere SSE con riconnessione automatica (retry nativo EventSource)
- Mantenere il check discovery timeout 6s (riga 624) — utile per UX

### 2.2 Semplificare SSE in altri moduli

**File**: Verificare se seo-tracking e keyword-research hanno polling simile.
**Azione**: Stesso cleanup se presente.

---

## Fase 3 — PHP Timeout & Reconnect (Media priorità, medio rischio)

### 3.1 Sostituire set_time_limit(0) con set_time_limit(300)

**File**: Tutti i controller con operazioni lunghe
**Stato attuale**: `set_time_limit(0)` (illimitato) in:
- `seo-audit/controllers/CrawlController.php` (riga 589-590)
- `ai-content/controllers/WizardController.php` (righe 42-43, 214-215)
- Tutti i cron scripts

**Azione**:
- AJAX endpoints: `set_time_limit(300)` (match con PHP FPM config)
- SSE streams: mantenere `set_time_limit(0)` (crawl può durare ore)
- Cron scripts: `set_time_limit(0)` OK (CLI non ha limiti di default)

### 3.2 Ridurre Database::reconnect() nel loop di crawl

**File**: `modules/seo-audit/controllers/CrawlController.php`
**Stato attuale**: `Database::reconnect()` chiamato 4 volte per pagina nel loop processStream (righe 744, 838, 873, 914).
**Problema**: Su SiteGround il connection pool era aggressivo, su Hetzner la connessione MySQL è diretta e stabile.

**Azione**:
- Mantenere reconnect a inizio loop (riga 744) — safety net
- Rimuovere reconnect dopo issue detection (riga 873) — query breve
- Rimuovere reconnect nell'error handler (riga 914) — già fatto a inizio loop
- Risultato: da 4 reconnect/pagina → 2 reconnect/pagina (inizio loop + dopo crawl)

### 3.3 Ridurre reconnect nel cron AI Content

**File**: `modules/ai-content/cron/dispatcher.php`
**Stato attuale**: 10+ `Database::reconnect()` nel flusso (righe 89, 111, 129, 196, 210, 230, 369, 386, 419).

**Azione**:
- Mantenere reconnect dopo AI calls (il pattern è corretto, AI dura 30-120s)
- Rimuovere reconnect dopo query brevi (SELECT/UPDATE rapidi)
- Da 10 reconnect → 3-4 reconnect per keyword processata

---

## Fase 4 — Job Recovery Tighter (Bassa priorità, basso rischio)

### 4.1 Ridurre timeout stuck job da 30min a 10min

**File**: `modules/seo-audit/cron/crawl-dispatcher.php`
**Stato attuale**: `resetStuckJobs(30)` e sessioni orfane 30 min (righe 33, 62).
**Problema**: Su SiteGround i processi venivano killati spesso, timeout largo per non resettare job validi. Su VPS i processi sono stabili.

**Azione**:
- Cambiare `resetStuckJobs(30)` → `resetStuckJobs(10)`
- Cambiare orphaned session timeout da 30min → 10min
- Utenti vedono il reset più rapidamente in caso di problema reale

### 4.2 Migrare file-based last_run a database

**File**: `modules/seo-tracking/cron/rank-dispatcher.php` (righe 38-77), `gsc-sync-dispatcher.php` (righe 35-74)
**Stato attuale**: `storage/logs/rank_auto_last_run.txt` e `gsc_sync_last_run.txt`
**Problema**: File-based è fragile (permessi, pulizia storage, non atomico).

**Azione**:
- Creare migration: `ALTER TABLE modules ADD COLUMN last_cron_run DATETIME NULL`
- Oppure usare `ModuleLoader::updateModuleSettings()` con chiave `last_cron_run`
- Aggiornare `getLastRunDate()` e `setLastRunDate()` per leggere/scrivere da DB
- Rimuovere file `*_last_run.txt`

---

## Fase 5 — Cron Logging Unificato (Bassa priorità, basso rischio)

### 5.1 Usare redirect di sistema invece di logging custom

**Stato attuale**: Ogni cron script ha la propria funzione `logDispatcher()` che scrive in `storage/logs/{modulo}-dispatcher.log`.
**Problema**: Su SiteGround non si poteva usare `>>` nel cron. Su Hetzner sì, con logrotate configurato.

**Azione**:
- Mantenere il logging interno (utile per debug fine-grained)
- Aggiungere `>> /var/log/ainstein/cron.log 2>&1` nel crontab per catturare errori fatali
- Non è un refactoring critico — il sistema attuale funziona bene

---

## Sequenza di Implementazione

| Step | Fase | Rischio | Impatto | Tempo stimato |
|------|------|---------|---------|---------------|
| 1 | 1.1 Rank batch 10 | Basso | Alto (10x throughput) | 30 min |
| 2 | 1.2 Auto-eval batch 10 | Basso | Medio (3x throughput) | 15 min |
| 3 | 1.3 AI Content batch 3 | Basso | Medio | 20 min |
| 4 | 2.1 SSE polling cleanup | Basso | UX + codice pulito | 30 min |
| 5 | 3.1 set_time_limit(300) | Basso | Safety | 10 min |
| 6 | 3.2 Reconnect crawl loop | Medio | Performance DB | 15 min |
| 7 | 3.3 Reconnect AI cron | Medio | Performance DB | 15 min |
| 8 | 4.1 Stuck job 10min | Basso | UX recovery | 5 min |
| 9 | 4.2 Last_run to DB | Basso | Reliability | 20 min |
| 10 | 5.1 Cron logging | Basso | Ops | 10 min |

**Tempo totale stimato**: ~3 ore

---

## Note Importanti

- **NON toccare**: `ignore_user_abort(true)` in SSE — ancora necessario
- **NON toccare**: `session_write_close()` — buona pratica universale
- **NON toccare**: `ob_start()` in AJAX lunghi — protegge da output sporco
- **NON toccare**: request_delay nel crawl (200ms) — è rate limiting responsabile
- **Testare ogni fase** singolarmente in produzione prima della successiva
- **Rollback facile**: ogni modifica è isolata in un file specifico

---

## Metriche di Successo

| Metrica | Prima (SiteGround) | Dopo (VPS ottimizzato) |
|---------|--------------------|-----------------------|
| Rank check throughput | 12 keyword/ora | ~120 keyword/ora |
| Auto-evaluate throughput | 36 items/ora | ~120 items/ora |
| Stuck job recovery | 30 min | 10 min |
| DB reconnect overhead | 4/pagina crawl | 2/pagina crawl |
| SSE reliability | 70% (proxy trunca) | 99%+ (nativo) |
| Polling fallback code | ~200 righe JS | 0 righe (rimosso) |
