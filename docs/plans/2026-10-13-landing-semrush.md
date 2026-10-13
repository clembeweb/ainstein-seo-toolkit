# Landing in stile Semrush + controllo AI gratuito — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** nuova home pubblica di Ainstein in stile Semrush con un controllo gratuito "le AI leggono il tuo sito?" che
mostra gli highlight a tutti e i dettagli dopo l'iscrizione.

**Architecture:** pagina PHP pubblica `public/landing5.php` (CSS proprio come `pricing.php`, header/footer condivisi)
servita su `/`. Il controllo riusa `GeoTechService::check()` (modulo ai-reputation, nessuna AI) tramite un nuovo
servizio `services/PublicAiCheckService.php` che aggiunge: validazione URL anti-SSRF, scraper "sicuro" con IP pinnato e
redirect verificati, limiti per IP, cache per host, voto su 5, filtro highlight. Due route in `public/index.php`:
`POST /check-ai` (JSON pubblico) e `GET /check/{token}` (login). Design: `docs/plans/2026-10-13-landing-semrush-design.md`.
Mockup approvato: `_INBOX/2026-10-13-mockup-landing-semrush.html`.

**Tech Stack:** PHP 8.3, MySQL, cURL, CSS custom + DM Sans, JS vanilla (niente Alpine nella pagina pubblica).

---

### Task 1: Tabella `public_ai_checks`
**Files:** Create `database/migrations/2026_10_13_public_ai_checks.sql`
```sql
CREATE TABLE IF NOT EXISTS public_ai_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token CHAR(32) NOT NULL UNIQUE,
  url VARCHAR(500) NOT NULL,
  host VARCHAR(255) NOT NULL,
  result LONGTEXT NOT NULL,
  score TINYINT NOT NULL DEFAULT 0,
  ip_hash CHAR(64) NOT NULL,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip_hash, created_at), INDEX idx_host (host, created_at), INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Run locally: `mysql -u root seo_toolkit < database/migrations/2026_10_13_public_ai_checks.sql`. Commit.

### Task 2: `PublicAiCheckService` — parti pure (test prima)
**Files:** Create `services/PublicAiCheckService.php`, test `scripts/test-public-ai-check.php`.
Funzioni statiche pure, testate senza rete:
- `normalizeUrl(string $in): ?string` → aggiunge https://, rifiuta schemi diversi da http/https, porte diverse da
  80/443, userinfo (`user@host`), host senza punto, IP letterali privati.
- `isPublicIp(string $ip): bool` → `filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)`
  e in più esclude 100.64.0.0/10, 169.254.0.0/16, 0.0.0.0/8, ::1, fc00::/7, fe80::/10.
- `score(array $findings): array` → 5 punti: `access` ok, nessun `readable` bad, `schema` ok/warn, `theme:company` non
  bad, nessun `files` warn sulla sitemap. Ritorna `['score'=>n,'total'=>5,'items'=>[...]]`.
- `highlights(array $findings): array` → solo `level` e `title` (mai `detail`), esclusi gli `info`, max 6.
Test attesi: `127.0.0.1`, `10.0.0.1`, `192.168.1.1`, `169.254.169.254`, `::1` → false; `8.8.8.8` → true;
`ftp://x.it` → null; `http://x.it:8080` → null; `esempio.it` → `https://esempio.it`; highlights senza chiave `detail`.

### Task 3: scraper sicuro + esecuzione del controllo
**Files:** Modify `services/PublicAiCheckService.php`; create class `Services\SafeScraper extends ScraperService`
(stesso file o `services/SafeScraper.php`) che ridefinisce `fetchRaw()`:
risolve l'host (`gethostbynamel` + `dns_get_record AAAA`), rifiuta se un IP non è pubblico, pinna con
`CURLOPT_RESOLVE ["host:port:ip"]`, `CURLOPT_FOLLOWLOCATION=false`, segue a mano fino a 5 redirect rivalidando ogni
`CURLINFO_REDIRECT_URL`, `CURLOPT_PROTOCOLS = HTTP|HTTPS`, timeout 15 s, body max 2 MB.
`PublicAiCheckService::run(string $url, string $ip): array`:
1. normalizza; 2. limiti (`ip_hash` = sha256(ip + APP_KEY/salt): 3/ora, 10/giorno; globale 300/giorno) → errore
   leggibile; 3. stesso host < 6 h → riusa il risultato salvato (nuovo token che punta agli stessi dati);
4. `new GeoTechService(new SafeScraper())->check($url)`; 5. `findings()` + `score()`; 6. salva; 7. ritorna token,
   host, score, total, highlights.

### Task 4: route `POST /check-ai` e `GET /check/{token}`
**Files:** Modify `public/index.php` (vicino alle route pubbliche), create `shared/views/check/result.php`.
- `POST /check-ai`: `Middleware::csrf()`, campo trappola `website` vuoto, pattern AJAX lungo (ignore_user_abort,
  set_time_limit(90), ob_start, ob_end_clean prima di ogni echo), `session_write_close()` prima del controllo.
- `GET /check/{token}`: `Middleware::auth()`; se `user_id` NULL lo lega all'utente; mostra voto, tutti i findings con
  `detail`, CTA "Crea il progetto per questo sito" → `/projects/create?website=…`. View con layout (`'user' => $user`).
- Registrazione: dopo `Auth::login` in `POST /register` usare `$_SESSION['_intended_url']` se presente.

### Task 5: `public/landing5.php`
Porta il mockup in PHP: header/footer condivisi, CSS del mockup ripulito, testi definitivi, esempi inventati
(niente Stabia nella casella e nelle anteprime; il caso 79/2 resta anonimo), numeri veri (`Credits::signupBonus()`,
`count(GeoTechService::BOTS)`), prezzi da `plans` (`is_active=1 AND price_monthly>0`), linguette JS vanilla,
form del controllo in fetch con `response.ok` prima di `.json()` (GR #24), stati: in corso (barra), risultato, errore,
limite superato. Link moduli → `/docs/<slug>`. Meta title/description, OG, accessibilità (label, focus).
Route `/` → `landing5.php` (landing4 resta nel repo).

### Task 6: pulizia, docs, verifica, deploy
- `cron/cleanup-data.php`: `DELETE FROM public_ai_checks WHERE user_id IS NULL AND created_at < NOW() - INTERVAL 30 DAY`.
- Data model (`docs/data-model.html`): tabella `public_ai_checks`.
- Verifica in locale: landing 1280 e 390, controllo su un sito vero, limite superato, IP privato rifiutato,
  gate → registrazione → `/check/{token}` con i dettagli.
- Commit, push, pull in produzione, migrazione in produzione, controllo dal vivo su ainstein.it.
