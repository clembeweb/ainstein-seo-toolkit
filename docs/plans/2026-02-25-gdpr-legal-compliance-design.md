# Design: GDPR & Legal Compliance per Ainstein

> Data: 2026-02-25
> Normative: GDPR (Reg. UE 2016/679), D.Lgs. 196/2003, Linee Guida Garante Privacy cookie (giugno 2021)

---

## Contesto

Ainstein SEO Toolkit e una SaaS italiana che raccoglie dati personali (email, nome, password), si integra con servizi terzi (Claude AI, OpenAI, Google, Stripe, DataForSEO, RapidAPI) e gestisce dati SEO degli utenti. Deve essere conforme al GDPR e alla normativa italiana sulla privacy.

### Stato attuale

| Area | Stato |
|------|-------|
| Privacy Policy | Link nel footer ma pagina non esiste |
| Termini di Servizio | Link in registrazione ma pagina non esiste |
| Cookie Policy | Link nel footer ma pagina non esiste |
| Consenso registrazione | Checkbox UI presente ma non validato ne registrato nel DB |
| Cookie banner | Non implementato |
| Cancellazione account | Non implementata |
| Export dati (portabilita) | Non implementato |
| Data retention | Implementato nel cron cleanup |
| Email unsubscribe | Implementato |
| Admin GDPR tools | Non presenti |

### Decisioni prese

- **Testi legali**: creati in-house con placeholder per dati titolare (configurabili da admin settings)
- **Cookie**: solo tecnici (sessione PHP + remember_token) — nessun analytics/marketing
- **Pagamenti**: Stripe — dati carta gestiti da Stripe, noi salviamo solo riferimenti
- **Cancellazione account**: hard delete immediata con doppia conferma
- **Dati titolare**: inseriti dall'admin tramite impostazioni admin

---

## 1. Architettura generale

Modulo "legal/compliance" trasversale integrato nel core della piattaforma (non un modulo Ainstein classico).

### Componenti

| Componente | Descrizione |
|-----------|-------------|
| **Admin Legal Settings** | Sezione admin per dati titolare + versione testi legali |
| **3 pagine legali** | Privacy Policy, Termini di Servizio, Cookie Policy — dinamiche da admin settings |
| **Cookie banner** | Banner informativo leggero (solo cookie tecnici = no opt-in, solo informativa) |
| **Consent tracking** | Registrazione consenso a registrazione con timestamp + versione |
| **Account deletion** | Hard delete immediata con doppia conferma + cascading su tutti i moduli |
| **Data export** | Export JSON di tutti i dati utente (Art. 20 GDPR) |
| **Sezione profilo GDPR** | Nel profilo: download dati, cancella account, gestione consensi |
| **Admin GDPR panel** | Audit consensi, log cancellazioni, richieste export |

### Flusso dati legali

```
Admin Settings (dati titolare, legal_version)
  -> Privacy Policy / ToS / Cookie Policy (pagine dinamiche con dati iniettati)
  -> Registrazione (checkbox linkato a versione corrente)
  -> consent_records (timestamp + versione accettata)
  -> Login check: se legal_version utente < legal_version corrente -> banner ri-accettazione
```

---

## 2. Database Schema

### Nuove tabelle

```sql
-- Consensi utente (tracciabilita GDPR)
CREATE TABLE consent_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consent_type ENUM('terms', 'privacy', 'cookie', 'marketing') NOT NULL,
    version VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    accepted_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    INDEX idx_consent_user (user_id),
    INDEX idx_consent_type (consent_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log cancellazioni account (audit trail)
CREATE TABLE account_deletion_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    deleted_data_summary JSON NULL,
    deleted_at DATETIME NOT NULL,
    deleted_by ENUM('user', 'admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Richieste export dati (DSAR tracking)
CREATE TABLE data_export_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'expired') DEFAULT 'pending',
    file_path VARCHAR(500) NULL,
    requested_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    INDEX idx_export_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Modifiche tabella `users`

```sql
ALTER TABLE users ADD COLUMN privacy_accepted_at DATETIME NULL;
ALTER TABLE users ADD COLUMN terms_accepted_at DATETIME NULL;
ALTER TABLE users ADD COLUMN legal_version_accepted VARCHAR(20) NULL;
```

### Admin settings (tabella `settings` esistente)

| Key | Esempio | Descrizione |
|-----|---------|-------------|
| `legal_company_name` | Ainstein S.r.l. | Ragione sociale |
| `legal_vat_number` | IT12345678901 | Partita IVA |
| `legal_address` | Via Roma 1, 00100 Roma (RM) | Sede legale |
| `legal_pec` | ainstein@pec.it | PEC |
| `legal_email` | privacy@ainstein.it | Email privacy |
| `legal_dpo_email` | dpo@ainstein.it | Email DPO (opzionale) |
| `legal_version` | 2026.1 | Versione corrente documenti legali |
| `legal_last_updated` | 2026-02-25 | Data ultimo aggiornamento |

---

## 3. Pagine legali

Tutte in `shared/views/legal/` — layout pubblico, accessibili senza login. I dati del titolare vengono iniettati da admin settings.

### 3a. Privacy Policy (conforme Art. 13-14 GDPR)

Sezioni obbligatorie:

1. **Titolare del trattamento** — dati azienda da settings
2. **Dati raccolti** — nome, email, password (hash), IP, dati navigazione
3. **Dati forniti volontariamente** — URL siti web, keyword, contenuti SEO
4. **Finalita e base giuridica**:
   - Esecuzione contratto (Art. 6.1.b) — erogazione servizio SaaS
   - Consenso (Art. 6.1.a) — comunicazioni marketing (se applicabile)
   - Obbligo legale (Art. 6.1.c) — fatturazione, contabilita
   - Legittimo interesse (Art. 6.1.f) — sicurezza, antifrode, miglioramento servizio
5. **Servizi terzi e sub-responsabili**:
   - Anthropic (Claude AI) — analisi AI, USA, SCC
   - OpenAI — analisi AI fallback, USA, SCC
   - Google (GSC/GA4/Ads) — dati SEO, USA, SCC
   - Stripe — pagamenti, USA, SCC
   - DataForSEO — dati posizionamento, USA/EU
   - RapidAPI — volumi keyword, USA, SCC
   - SERP API — risultati ricerca, USA
6. **Trasferimento dati extra-UE** — USA tramite Standard Contractual Clauses (SCC)
7. **Periodo di conservazione** — dettagli per categoria dati (da cron cleanup esistente):
   - Dati account: fino a cancellazione
   - Audit HTML: 90 giorni
   - Link analysis HTML: 30 giorni
   - Dati GSC: 16 mesi
   - Posizioni keyword: 16 mesi
   - Notifiche: 90 giorni
   - Log attivita: 180 giorni
8. **Diritti dell'interessato** — accesso (Art. 15), rettifica (Art. 16), cancellazione (Art. 17), limitazione (Art. 18), portabilita (Art. 20), opposizione (Art. 21), reclamo al Garante Privacy
9. **Cookie** — rimando a cookie policy
10. **Sicurezza** — HTTPS, bcrypt password, prepared statements, CSRF protection, role-based access
11. **Modifiche alla policy** — notifica via email per modifiche sostanziali, versioning

### 3b. Termini di Servizio

Sezioni:

1. **Definizioni** — piattaforma, utente, servizi, crediti, progetto
2. **Oggetto** — descrizione SaaS SEO Toolkit e moduli disponibili
3. **Registrazione e account** — dati veritieri, responsabilita credenziali, un account per persona
4. **Sistema crediti** — acquisto tramite Stripe, consumo per operazione, non rimborsabilita, scadenza
5. **Uso accettabile** — divieti: scraping abusivo, spam, reverse engineering, violazione copyright, uso illecito AI
6. **Proprieta intellettuale** — contenuti generati dall'AI: licenza d'uso all'utente, nessuna garanzia originalita
7. **Limitazione responsabilita** — accuratezza analisi AI non garantita, best-effort disponibilita servizio, forza maggiore
8. **SLA e disponibilita** — nessun SLA garantito (SaaS self-service), manutenzione programmata
9. **Recesso e cancellazione** — diritto di recesso, procedura cancellazione account con effetti immediati
10. **Modifiche ai termini** — notifica via piattaforma, accettazione necessaria per uso continuato
11. **Legge applicabile** — legge italiana
12. **Foro competente** — foro della sede legale del titolare
13. **Contatti** — dati da admin settings

### 3c. Cookie Policy

Struttura semplificata (solo cookie tecnici):

1. **Cosa sono i cookie** — spiegazione accessibile
2. **Cookie utilizzati**:
   - `PHPSESSID` — cookie di sessione, tecnico, durata sessione browser
   - `remember_token` — autenticazione persistente, tecnico funzionale, 30 giorni
   - `cookie_notice_seen` — memorizza chiusura banner, tecnico, 6 mesi
3. **Nessun cookie di profilazione** — dichiarazione esplicita che non vengono utilizzati cookie di profilazione, analytics o marketing
4. **Base giuridica** — Art. 122 D.Lgs. 196/2003: cookie tecnici esenti da consenso
5. **Come gestire i cookie** — istruzioni per Chrome, Firefox, Safari, Edge
6. **Contatti** — rimando a privacy policy

---

## 4. Cookie banner

Solo cookie tecnici = banner **informativo** (non di consenso). Conforme alle Linee Guida Garante 2021.

### Comportamento

- Appare alla prima visita su qualsiasi pagina
- Testo: "Questo sito utilizza solo cookie tecnici necessari al funzionamento. Nessun cookie di profilazione. [Cookie Policy](/legal/cookies)"
- Bottone "OK, ho capito" per chiudere
- Salva `cookie_notice_seen=1` (cookie tecnico, scadenza 6 mesi)
- Non riappare per 6 mesi (conforme linee guida Garante)
- Implementazione: Alpine.js puro in `layout.php`, nessuna libreria esterna

### Perche non serve opt-in

Il Garante Privacy italiano (Linee Guida giugno 2021) stabilisce che i cookie tecnici:
- NON richiedono consenso preventivo
- Richiedono solo informativa (banner + cookie policy)
- Il banner puo avere solo "OK" senza accept/reject/customize

Se in futuro si aggiungessero cookie analytics/marketing, il banner dovra essere aggiornato con 3 opzioni (accetta/rifiuta/personalizza).

---

## 5. Flusso registrazione aggiornato

### Modifiche

1. **Checkbox** (gia presente) — testo aggiornato:
   > "Ho letto e accetto la [Privacy Policy](/legal/privacy) e i [Termini di Servizio](/legal/terms) *"

2. **Validazione backend** — verificare `$_POST['terms']` nel controller registrazione

3. **Salvataggio consenso** — dopo creazione utente:
   ```php
   // INSERT consent_records per 'terms' e 'privacy'
   // Con: user_id, version (da Settings::get('legal_version')),
   //      ip_address ($_SERVER['REMOTE_ADDR']), user_agent, accepted_at
   ```

4. **Aggiornamento `users`**:
   ```php
   // privacy_accepted_at = NOW()
   // terms_accepted_at = NOW()
   // legal_version_accepted = Settings::get('legal_version')
   ```

### Banner ri-accettazione (cambio versione legale)

- Al login, se `user.legal_version_accepted < Settings::get('legal_version')`:
  - Redirect a pagina "Aggiornamento Termini"
  - Mostra diff/riepilogo modifiche
  - 3 opzioni: Accetta, Scarica i miei dati, Elimina account
  - Se accetta: aggiorna `consent_records` + `users.legal_version_accepted`
  - Se non accetta: non puo usare la piattaforma

---

## 6. Cancellazione account (Art. 17 - Diritto all'oblio)

### Flusso utente

```
Profilo -> Sezione "Privacy e Dati" -> "Elimina il mio account"
  -> Modal di conferma con:
     - Lista chiara di cosa verra eliminato
     - Campo "Motivo" (opzionale, textarea)
     - Input email (deve riscrivere la propria email)
     - Input password (verifica identita)
  -> POST /profile/delete-account
  -> Verifica password
  -> Cascading delete
  -> Log in account_deletion_logs
  -> Invalidazione sessione
  -> Redirect a landing con messaggio conferma
```

### Cascading delete — ordine operazioni

| Step | Area | Azione |
|------|------|--------|
| 1 | Progetti condivisi (owner) | Elimina progetto + notifica membri |
| 2 | Progetti condivisi (member) | Rimuovi membership |
| 3 | `aic_*` tabelle | DELETE WHERE relazione a progetti utente |
| 4 | `sa_*` tabelle | DELETE |
| 5 | `st_*` tabelle | DELETE |
| 6 | `kr_*` tabelle | DELETE |
| 7 | `ga_*` tabelle | DELETE |
| 8 | `cc_*` tabelle | DELETE |
| 9 | `il_*` tabelle | DELETE |
| 10 | `ao_*`, `so_*` tabelle | DELETE |
| 11 | `projects` (globali) | DELETE dove owner |
| 12 | `project_members`, `project_invitations` | DELETE |
| 13 | `notifications`, `notification_preferences` | DELETE |
| 14 | `data_export_requests` + file fisici | DELETE |
| 15 | `credit_transactions` | Anonimizza (user_id -> NULL) |
| 16 | `ai_logs` | Anonimizza |
| 17 | `api_logs` | Anonimizza |
| 18 | `password_resets` | DELETE |
| 19 | `email_unsubscribe_tokens` | DELETE |
| 20 | `users` | DELETE |

**Non vengono cancellati** (obbligo legale):
- `consent_records` — necessari per dimostrare compliance in audit
- `account_deletion_logs` — prova della cancellazione avvenuta

**Anonimizzati** (non cancellati per obblighi fiscali/contabili):
- `credit_transactions` — user_id → NULL, riferimento anonimo
- `ai_logs`, `api_logs` — user_id → NULL

### Service

`AccountDeletionService` con metodo `deleteAccount($userId, $reason, $deletedBy)` che:
- Raccoglie riepilogo dati (conteggio per tabella)
- Esegue cascading delete in transazione
- Salva log in `account_deletion_logs`
- Restituisce summary

---

## 7. Export dati (Art. 20 - Portabilita)

### Flusso utente

```
Profilo -> "Scarica i tuoi dati"
  -> POST /profile/export-data
  -> Genera export asincrono (pattern AJAX lungo)
  -> File JSON pronto -> notifica + link download
  -> GET /profile/download-data/{id}
  -> File disponibile 48 ore, poi eliminato
```

### Contenuto export JSON

```json
{
  "export_info": {
    "platform": "Ainstein SEO Toolkit",
    "exported_at": "2026-02-25T10:00:00Z",
    "user_id": 123
  },
  "profile": {
    "name": "...",
    "email": "...",
    "created_at": "...",
    "plan": "..."
  },
  "consent_records": [...],
  "notification_preferences": [...],
  "projects": [
    {
      "name": "...",
      "domain": "...",
      "modules": {
        "ai-content": { "items_count": 15, "items": [...] },
        "seo-audit": { "sessions_count": 3, "pages_analyzed": 150 },
        "seo-tracking": { "keywords_count": 50, "positions": [...] },
        ...
      }
    }
  ],
  "credit_history": [...]
}
```

### Limiti

- 1 richiesta ogni 24 ore (anti-abuse)
- File disponibile per 48 ore
- Cron cleanup elimina file scaduti
- Storage: `storage/exports/{user_id}/` (directory utente)

---

## 8. Admin GDPR Panel

Nuova sezione `/admin/gdpr` con tab:

### Tab 1: Impostazioni legali

Form con i campi settings `legal_*`:
- Ragione sociale, P.IVA, sede legale, PEC, email privacy, email DPO
- Versione documenti legali + data aggiornamento
- Bottone "Aggiorna versione" che incrementa `legal_version` e forza ri-accettazione

### Tab 2: Registro consensi

Tabella filtrabile con componenti shared standard:
- Colonne: Utente, Tipo consenso, Versione, Data accettazione, Data revoca
- Filtri: tipo, versione, date range
- Export CSV

### Tab 3: Log cancellazioni

Tabella:
- Colonne: Email, Data cancellazione, Motivo, Eliminato da, Riepilogo dati
- Dettaglio espandibile con JSON summary

### Tab 4: Richieste export

Tabella:
- Colonne: Utente, Data richiesta, Stato, Data completamento
- Possibilita admin di forzare generazione export

---

## 9. Routes

```
# Pagine legali (pubbliche, no auth)
GET  /legal/privacy              -> LegalController::privacy()
GET  /legal/terms                -> LegalController::terms()
GET  /legal/cookies              -> LegalController::cookies()

# Azioni utente (auth required)
POST /profile/export-data        -> ProfileController::requestExport()
GET  /profile/download-data/{id} -> ProfileController::downloadExport()
POST /profile/delete-account     -> ProfileController::deleteAccount()

# Re-accettazione termini (auth required)
GET  /legal/accept               -> LegalController::showAcceptForm()
POST /legal/accept               -> LegalController::acceptUpdated()

# Admin GDPR (admin required)
GET  /admin/gdpr                 -> AdminGdprController::index()
POST /admin/gdpr/settings        -> AdminGdprController::saveSettings()
GET  /admin/gdpr/consents        -> AdminGdprController::consents()
GET  /admin/gdpr/deletions       -> AdminGdprController::deletions()
GET  /admin/gdpr/exports         -> AdminGdprController::exports()
```

Redirect legacy (footer attuale punta a `/docs/privacy`, `/docs/terms`, `/docs/cookies`):
```
GET /docs/privacy  -> 301 /legal/privacy
GET /docs/terms    -> 301 /legal/terms
GET /docs/cookies  -> 301 /legal/cookies
GET /privacy       -> 301 /legal/privacy
GET /terms         -> 301 /legal/terms
```

---

## 10. File da creare/modificare

### Nuovi file

| File | Scopo |
|------|-------|
| `controllers/LegalController.php` | Controller pagine legali + re-accettazione |
| `admin/controllers/AdminGdprController.php` | Admin GDPR panel |
| `services/AccountDeletionService.php` | Cascading delete + logging |
| `services/DataExportService.php` | Generazione export JSON |
| `services/ConsentService.php` | Gestione consensi (record, verifica versione) |
| `shared/views/legal/privacy.php` | Privacy Policy |
| `shared/views/legal/terms.php` | Termini di Servizio |
| `shared/views/legal/cookies.php` | Cookie Policy |
| `shared/views/legal/accept.php` | Pagina ri-accettazione termini |
| `shared/views/admin/gdpr/index.php` | Admin GDPR dashboard |
| `shared/views/admin/gdpr/consents.php` | Admin registro consensi |
| `shared/views/admin/gdpr/deletions.php` | Admin log cancellazioni |
| `shared/views/admin/gdpr/exports.php` | Admin richieste export |
| `shared/views/components/cookie-banner.php` | Cookie banner Alpine.js |
| `shared/views/components/legal-acceptance-banner.php` | Banner ri-accettazione |
| `shared/views/profile/privacy-section.php` | Sezione GDPR nel profilo |
| `migrations/2026_02_25_gdpr_compliance.sql` | Schema DB |

### File da modificare

| File | Modifica |
|------|----------|
| `public/index.php` | Aggiungere routes legali, redirect legacy, middleware versione legale |
| `shared/views/auth/register.php` | Aggiornare testo checkbox, link corretti |
| `shared/views/profile.php` | Aggiungere sezione "Privacy e Dati" |
| `shared/views/layout.php` | Include cookie banner + legal acceptance banner |
| `public/includes/site-footer.php` | Aggiornare link a `/legal/*` |
| `core/Middleware.php` | Aggiungere check versione legale post-login |
| `shared/views/components/nav-items.php` | Aggiungere voce GDPR in admin sidebar |
| `cron/cleanup-data.php` | Aggiungere cleanup file export scaduti |

---

## 11. Fonti normative

- [GDPR Compliance Guide 2026](https://secureprivacy.ai/blog/gdpr-compliance-2026)
- [GDPR Compliance Checklist for SaaS](https://complydog.com/blog/gdpr-compliance-checklist-complete-guide-b2b-saas-companies)
- [Linee Guida Garante Privacy Cookie (2021)](https://www.garanteprivacy.it/home/docweb/-/docweb-display/docweb/9677876)
- [Cookie Consent Requirements Italy](https://www.cookieyes.com/blog/cookie-consent-requirements-in-italy/)
- [GDPR Right to Erasure](https://complydog.com/blog/right-to-be-forgotten-gdpr-erasure-rights-guide)
- [GDPR Art. 20 Data Portability](https://gdpr-info.eu/art-20-gdpr/)
- [SaaS Privacy Compliance 2025](https://secureprivacy.ai/blog/saas-privacy-compliance-requirements-2025-guide)
- [Italy Data Protection Overview](https://securiti.ai/italy-data-protection/)
- [Italian Cookie Law Guide 2025](https://legalblink.it/post/cookie-law-2025.html)
