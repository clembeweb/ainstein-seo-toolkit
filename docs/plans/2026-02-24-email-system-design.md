# Design: Sistema Email Admin-Customizzabile

> Data: 2026-02-24
> Stato: Approvato
> Approccio: Template in DB (admin-editabili) + layout wrapper file PHP + rendering `{{placeholder}}`
> Integrazione: Estende il piano notifiche (`notification-system-plan.md`) senza invalidarlo

---

## Requisiti

- **Template email in DB** editabili dall'admin con textarea + preview live
- **Layout base** (header/footer/stili) in file PHP come wrapper consistente
- **Placeholder system**: `{{variabile}}` sostituiti con `str_replace`
- **Fallback**: se template non in DB, usa file PHP (backward compatible)
- **Categorie**: auth, notification, module, report
- **Unsubscribe**: link token-based in ogni email, pagina gestione senza login
- **Report admin**: digest piattaforma settimanale/mensile
- **Migrazione**: SEO Tracking alerts da `mail()` a EmailService

---

## 1. Schema Database

### Tabella `email_templates`

```sql
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    description TEXT NULL,
    available_vars JSON NOT NULL,
    category VARCHAR(50) DEFAULT 'system',
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabella `email_unsubscribe_tokens`

```sql
CREATE TABLE email_unsubscribe_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Template Predefiniti (Seed)

| Slug | Categoria | Nome | Descrizione |
|------|-----------|------|-------------|
| `welcome` | auth | Email di Benvenuto | Inviata alla registrazione |
| `password-reset` | auth | Reset Password | Link per reimpostare password |
| `password-changed` | auth | Password Modificata | Conferma cambio password |
| `email-changed` | auth | Email Modificata | Conferma cambio email |
| `project-invite` | notification | Invito Progetto | Invito collaborazione progetto |
| `notification` | notification | Notifica Generica | Email per notifiche in-app (operation completed/failed, invite accepted/declined) |
| `seo-alert` | module | Alert SEO | Alert posizione/traffico dal modulo SEO Tracking |
| `admin-report` | report | Report Piattaforma | Digest periodico per admin |

### Variabili per Template

Globali (sempre disponibili): `app_name`, `app_url`, `year`, `unsubscribe_url`

| Slug | Variabili specifiche |
|------|---------------------|
| `welcome` | `user_name`, `user_email`, `free_credits`, `login_url` |
| `password-reset` | `user_email`, `reset_url` |
| `password-changed` | `user_name`, `user_email` |
| `email-changed` | `user_name`, `old_email`, `new_email` |
| `project-invite` | `project_name`, `inviter_name`, `role`, `accept_url` |
| `notification` | `title`, `body`, `action_url`, `action_label` |
| `seo-alert` | `project_name`, `alert_summary`, `alerts_html`, `dashboard_url` |
| `admin-report` | `period`, `new_users`, `total_users`, `credits_consumed`, `top_modules_html`, `api_errors`, `failed_jobs` |

---

## 2. Rendering Engine (EmailService Enhancement)

### Flusso `sendTemplate($to, $subject, $slug, $data)`

1. Query `email_templates` per `slug` dove `is_active = 1`
2. **Se trovato in DB**:
   - Usa `subject` e `body_html` dal record
   - `str_replace` di ogni `{{variabile}}` con valori da `$data`
   - Aggiunge variabili globali: `{{app_name}}`, `{{app_url}}`, `{{year}}`, `{{unsubscribe_url}}`
3. **Se NON trovato (fallback)**:
   - Carica file PHP `shared/views/emails/{slug}.php` (comportamento attuale)
4. Wrappa body nel layout `base.php` (header, stili, footer)
5. Footer include link unsubscribe
6. Invia via SMTP con PHPMailer

### Nuovo metodo: `renderPreview($slug, $sampleData): string`
- Per il pannello admin: renderizza template con dati di esempio
- Return HTML completo (layout + body)

### Nuovo metodo: `getDefaultTemplate($slug): array`
- Ritorna il template default hardcoded (per "Ripristina default")
- `['subject' => '...', 'body_html' => '...', 'available_vars' => [...]]`

---

## 3. Pannello Admin Email

### Route: `/admin/email-templates`

**Lista template** (`GET /admin/email-templates`):
- Tabella: Nome, Slug, Categoria, Stato (badge attivo/disattivato), Ultimo aggiornamento
- Filtro per categoria
- Ogni riga: bottone "Modifica"

**Modifica template** (`GET /admin/email-templates/{slug}`):
- Form layout a 2 colonne:
  - **Sinistra**: Subject (input) + Body HTML (textarea monospace, ~20 righe) + chips variabili cliccabili
  - **Destra**: Preview live (aggiornata in tempo reale con `@input` Alpine.js + dati esempio)
- Toggle attivo/disattivo
- Bottoni: "Salva", "Ripristina default", "Invia test"
- POST salva in `email_templates` (update)

**Invia test** (`POST /admin/email-templates/{slug}/test`):
- Renderizza template con dati esempio
- Invia all'email admin corrente
- Feedback toast "Email di test inviata"

### Settings Globali Email (in `/admin/settings`)

Nella tab esistente (o nuova tab "Email"):
- Logo URL per header email
- Colore primario brand (default `#006e96`)
- Testo footer personalizzabile
- (SMTP settings gia presenti)

Settings salvati nella tabella `settings` con chiavi: `email_logo_url`, `email_brand_color`, `email_footer_text`.

---

## 4. Email Auth

### Template `welcome` (migrato da file a DB)
Contenuto: Benvenuto, crediti gratuiti, CTA login.
Trigger: `public/index.php` register route + `OAuthController.php`

### Template `password-reset` (migrato da file a DB)
Contenuto: Link reset con scadenza.
Trigger: `public/index.php` forgot-password route

### Template `password-changed` (nuovo)
Contenuto: "La tua password e stata modificata con successo. Se non sei stato tu, contatta il supporto."
Trigger: route di cambio password nel profilo

### Template `email-changed` (nuovo)
Contenuto: "La tua email e stata aggiornata a {{new_email}}. Se non sei stato tu, contatta il supporto."
Trigger: route di cambio email nel profilo

---

## 5. Migrazione Alert SEO Tracking

`AlertService::sendEmailDigest()` attualmente usa `mail()` nativo con plain text.

**Migrazione:**
- Sostituire `mail()` con `EmailService::sendTemplate('seo-alert', $data)`
- Template `seo-alert` contiene tabella HTML formattata con alert per tipo
- Variabile `alerts_html`: tabella pre-renderizzata con righe colorate per severity

---

## 6. Report Admin Periodico

**Nuovo cron**: `cron/admin-report.php` (settimanale, `0 8 * * 1`)

**Metriche raccolte:**
- Nuovi utenti (periodo)
- Totale utenti attivi
- Crediti consumati (totale + per modulo)
- Moduli piu usati (top 5)
- Errori API (conteggio per provider)
- Job falliti

**Flusso:**
1. Query metriche dal DB
2. Genera HTML per `top_modules_html` (tabella)
3. `EmailService::sendTemplate('admin-report', $data)` a tutti gli utenti con `is_admin = 1`

---

## 7. Unsubscribe

### Token
- Generato al primo invio email per utente (64 char random)
- Salvato in `email_unsubscribe_tokens`, riutilizzato
- `EmailService` genera/recupera token prima di inviare

### Link nel footer
Ogni email include: "Non vuoi ricevere queste email? [Gestisci preferenze](/email/preferences?token=XXX)"

### Pagina pubblica
`GET /email/preferences?token=XXX`:
- Autentica utente via token (no login richiesto)
- Mostra toggle per ogni tipo email (stesse categorie di `notification_preferences`)
- POST salva in `notification_preferences`
- Design: pagina standalone, minimale, branded

---

## 8. Integrazione con Piano Notifiche

Il piano notifiche (`notification-system-plan.md`) resta valido. Punto di contatto:

- `NotificationService::send()` chiama `EmailService::sendTemplate('notification', $data)`
- `EmailService::sendTemplate()` ora legge da DB (admin-customizzabile) con fallback su file
- `notification_preferences.email_enabled` controlla l'invio (gestito da NotificationService)
- La pagina unsubscribe scrive nella stessa tabella `notification_preferences`

**Task 3 del piano notifiche** (creare file `notification.php`) viene sostituito dal seed DB del template `notification`.

---

## 9. Fuori Scope (YAGNI)

- WYSIWYG / drag-and-drop email builder
- Email marketing / campagne
- A/B testing template
- Scheduling invio email
- Report utente sui propri progetti (solo report admin piattaforma)
- Template multi-lingua (UI solo italiano)
