-- Migration: Email Templates System
-- Data: 2026-02-24
-- Descrizione: Crea tabelle per sistema email template centralizzato + seed 8 template default

-- ============================================================
-- 1. Tabella email_templates
-- ============================================================
CREATE TABLE IF NOT EXISTS email_templates (
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

-- ============================================================
-- 2. Tabella email_unsubscribe_tokens
-- ============================================================
CREATE TABLE IF NOT EXISTS email_unsubscribe_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Email branding settings
-- ============================================================
INSERT IGNORE INTO settings (key_name, value, updated_by) VALUES
('email_logo_url', '', NULL),
('email_brand_color', '#006e96', NULL),
('email_footer_text', '', NULL);

-- ============================================================
-- 4. Seed 8 template email default
-- ============================================================

-- 4.1 Welcome (auth)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('welcome', 'Email di Benvenuto', 'Benvenuto su {{app_name}}!', '<h1>Benvenuto su {{app_name}}!</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Grazie per esserti registrato. Il tuo account e attivo e pronto all''uso.</p>

<div class="info-box">
    <p><strong>{{free_credits}} crediti gratuiti</strong> sono stati aggiunti al tuo account per iniziare subito a utilizzare tutti gli strumenti della piattaforma.</p>
</div>

<p>Con {{app_name}} puoi:</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0;">
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Generare contenuti SEO con l''intelligenza artificiale</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Monitorare il posizionamento delle tue keyword</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Effettuare audit SEO completi del tuo sito</td>
    </tr>
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Ricercare keyword e pianificare contenuti editoriali</td>
    </tr>
</table>

<div style="text-align: center; margin: 28px 0 12px;">
    <a href="{{app_url}}/dashboard" class="btn">Vai alla Dashboard</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Hai ricevuto questa email perche ti sei registrato su {{app_name}} con l''indirizzo {{user_email}}.
</p>', 'Inviata alla registrazione di un nuovo utente', '["user_name", "user_email", "free_credits", "app_name", "app_url", "unsubscribe_url"]', 'auth');

-- 4.2 Password Reset (auth)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('password-reset', 'Reset Password', 'Reimposta la tua password — {{app_name}}', '<h1>Reimposta la tua password</h1>

<p>Abbiamo ricevuto una richiesta per reimpostare la password associata all''account <strong>{{user_email}}</strong>.</p>

<p>Clicca il pulsante qui sotto per scegliere una nuova password:</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="{{reset_url}}" class="btn">Reimposta Password</a>
</div>

<div class="info-box">
    <p>Questo link e valido per <strong>1 ora</strong>. Se scade, puoi richiedere un nuovo link dalla pagina di login.</p>
</div>

<p style="font-size: 13px; color: #64748b;">Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
<p style="font-size: 12px; color: #94a3b8; word-break: break-all;">{{reset_url}}</p>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Se non hai richiesto il reset della password, ignora questa email. Il tuo account e al sicuro.
</p>', 'Inviata quando l''utente richiede il reset della password', '["user_email", "reset_url", "app_name", "app_url", "unsubscribe_url"]', 'auth');

-- 4.3 Password Changed (auth)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('password-changed', 'Password Modificata', 'Password modificata — {{app_name}}', '<h1>Password modificata con successo</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Ti confermiamo che la password del tuo account <strong>{{user_email}}</strong> e stata modificata con successo.</p>

<div class="info-box">
    <p>La modifica e avvenuta il <strong>{{changed_at}}</strong>.</p>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    <strong>Non sei stato tu?</strong> Se non hai effettuato questa modifica, contatta immediatamente il supporto per proteggere il tuo account.
</p>

<div style="text-align: center; margin: 28px 0 12px;">
    <a href="{{app_url}}/profile" class="btn">Vai al tuo Profilo</a>
</div>', 'Inviata dopo la modifica della password', '["user_name", "user_email", "changed_at", "app_name", "app_url", "unsubscribe_url"]', 'auth');

-- 4.4 Email Changed (auth)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('email-changed', 'Email Modificata', 'Email aggiornata — {{app_name}}', '<h1>Indirizzo email aggiornato</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Ti confermiamo che l''indirizzo email del tuo account e stato aggiornato con successo.</p>

<div class="info-box">
    <p>Il tuo nuovo indirizzo email e: <strong>{{new_email}}</strong></p>
</div>

<p>Da questo momento, tutte le comunicazioni saranno inviate al nuovo indirizzo.</p>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    <strong>Non sei stato tu?</strong> Se non hai effettuato questa modifica, contatta immediatamente il supporto per proteggere il tuo account.
</p>

<div style="text-align: center; margin: 28px 0 12px;">
    <a href="{{app_url}}/profile" class="btn">Vai al tuo Profilo</a>
</div>', 'Inviata dopo la modifica dell''indirizzo email', '["user_name", "user_email", "new_email", "app_name", "app_url", "unsubscribe_url"]', 'auth');

-- 4.5 Project Invite (notification)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('project-invite', 'Invito Progetto', 'Invito a collaborare su {{project_name}} — {{app_name}}', '<h1>Invito a collaborare</h1>

<p>Ciao,</p>

<p><strong>{{inviter_name}}</strong> ti ha invitato a collaborare sul progetto <strong>{{project_name}}</strong> su {{app_name}}.</p>

<div class="info-box">
    <p>Il tuo ruolo nel progetto sara: <strong>{{role}}</strong></p>
</div>

<p>Clicca il pulsante qui sotto per accettare l''invito e accedere al progetto:</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="{{accept_url}}" class="btn">Accetta Invito</a>
</div>

<div class="info-box">
    <p>Questo invito e valido per <strong>7 giorni</strong>. Se scade, chiedi a {{inviter_name}} di inviarti un nuovo invito.</p>
</div>

<p style="font-size: 13px; color: #64748b;">Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
<p style="font-size: 12px; color: #94a3b8; word-break: break-all;">{{accept_url}}</p>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Se non conosci la persona che ti ha invitato o ritieni che questo invito sia stato inviato per errore, puoi ignorare questa email.
</p>', 'Inviata quando un utente viene invitato a collaborare su un progetto', '["project_name", "inviter_name", "role", "accept_url", "app_name", "app_url", "unsubscribe_url"]', 'notification');

-- 4.6 Notification (notification)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('notification', 'Notifica Generica', '{{title}} — {{app_name}}', '<h1>{{title}}</h1>

<p>{{body}}</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="{{action_url}}" class="btn">{{action_label}}</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Questa e una notifica automatica da {{app_name}}.
</p>', 'Email generica per notifiche (completamento operazioni, inviti accettati/rifiutati)', '["title", "body", "action_url", "action_label", "app_name", "app_url", "unsubscribe_url"]', 'notification');

-- 4.7 SEO Alert (module)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('seo-alert', 'Alert SEO Tracking', '[SEO Tracking] {{project_name}} — {{alert_count}} nuovi alert', '<h1>Alert SEO Tracking</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Sono stati rilevati <strong>{{alert_count}} nuovi alert</strong> per il progetto <strong>{{project_name}}</strong>.</p>

<div class="info-box">
    <p>Periodo di riferimento: <strong>{{period}}</strong></p>
</div>

{{alerts_html}}

<div style="text-align: center; margin: 28px 0;">
    <a href="{{dashboard_url}}" class="btn">Vai alla Dashboard</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Ricevi questa email perche hai attivato le notifiche per il progetto {{project_name}} su {{app_name}}.
</p>', 'Digest alert posizionamento e traffico dal modulo SEO Tracking', '["user_name", "project_name", "alert_count", "period", "alerts_html", "dashboard_url", "app_name", "app_url", "unsubscribe_url"]', 'module');

-- 4.8 Admin Report (report)
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, description, available_vars, category) VALUES
('admin-report', 'Report Piattaforma', 'Report piattaforma {{period}} — {{app_name}}', '<h1>Report Piattaforma</h1>

<p>Ecco il riepilogo delle metriche della piattaforma per il periodo <strong>{{period}}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 20px 0; border-collapse: collapse;">
    <tr style="background-color: #f8fafc;">
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Nuovi utenti</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{new_users}}</td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Utenti totali</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{total_users}}</td>
    </tr>
    <tr style="background-color: #f8fafc;">
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Utenti attivi</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{active_users}}</td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Crediti consumati</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{credits_consumed}}</td>
    </tr>
    <tr style="background-color: #f8fafc;">
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Errori API</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{api_errors}}</td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; color: #64748b;">Job falliti</td>
        <td style="padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; text-align: right;">{{failed_jobs}}</td>
    </tr>
</table>

<h2>Moduli piu utilizzati</h2>

{{top_modules_html}}

<div style="text-align: center; margin: 28px 0;">
    <a href="{{app_url}}/admin" class="btn">Vai al Pannello Admin</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Questo report e stato generato automaticamente da {{app_name}}.
</p>', 'Report periodico con metriche piattaforma per admin', '["period", "new_users", "total_users", "active_users", "credits_consumed", "top_modules_html", "api_errors", "failed_jobs", "app_name", "app_url", "unsubscribe_url"]', 'report');
