<?php

/**
 * Default email template content for reset functionality.
 * Contiene gli stessi contenuti seed della migration 2026_02_24_email_templates.sql.
 */
return [
    'welcome' => [
        'subject' => 'Benvenuto su {{app_name}}!',
        'body_html' => '<h1>Benvenuto su {{app_name}}!</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Grazie per esserti registrato. Il tuo account e attivo e pronto all\'uso.</p>

<div class="info-box">
    <p><strong>{{free_credits}} crediti gratuiti</strong> sono stati aggiunti al tuo account per iniziare subito a utilizzare tutti gli strumenti della piattaforma.</p>
</div>

<p>Con {{app_name}} puoi:</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0;">
    <tr>
        <td style="padding: 6px 12px 6px 0; vertical-align: top; color: #006e96; font-size: 18px;">&#10003;</td>
        <td style="padding: 6px 0; font-size: 14px; color: #475569;">Generare contenuti SEO con l\'intelligenza artificiale</td>
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
    Hai ricevuto questa email perche ti sei registrato su {{app_name}} con l\'indirizzo {{user_email}}.
</p>',
    ],

    'password-reset' => [
        'subject' => 'Reimposta la tua password — {{app_name}}',
        'body_html' => '<h1>Reimposta la tua password</h1>

<p>Abbiamo ricevuto una richiesta per reimpostare la password associata all\'account <strong>{{user_email}}</strong>.</p>

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
</p>',
    ],

    'password-changed' => [
        'subject' => 'Password modificata — {{app_name}}',
        'body_html' => '<h1>Password modificata con successo</h1>

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
</div>',
    ],

    'email-changed' => [
        'subject' => 'Email aggiornata — {{app_name}}',
        'body_html' => '<h1>Indirizzo email aggiornato</h1>

<p>Ciao <strong>{{user_name}}</strong>,</p>

<p>Ti confermiamo che l\'indirizzo email del tuo account e stato aggiornato con successo.</p>

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
</div>',
    ],

    'project-invite' => [
        'subject' => 'Invito a collaborare su {{project_name}} — {{app_name}}',
        'body_html' => '<h1>Invito a collaborare</h1>

<p>Ciao,</p>

<p><strong>{{inviter_name}}</strong> ti ha invitato a collaborare sul progetto <strong>{{project_name}}</strong> su {{app_name}}.</p>

<div class="info-box">
    <p>Il tuo ruolo nel progetto sara: <strong>{{role}}</strong></p>
</div>

<p>Clicca il pulsante qui sotto per accettare l\'invito e accedere al progetto:</p>

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
</p>',
    ],

    'notification' => [
        'subject' => '{{title}} — {{app_name}}',
        'body_html' => '<h1>{{title}}</h1>

<p>{{body}}</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="{{action_url}}" class="btn">{{action_label}}</a>
</div>

<hr class="divider">

<p style="font-size: 13px; color: #94a3b8;">
    Questa e una notifica automatica da {{app_name}}.
</p>',
    ],

    'seo-alert' => [
        'subject' => '[SEO Tracking] {{project_name}} — {{alert_count}} nuovi alert',
        'body_html' => '<h1>Alert SEO Tracking</h1>

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
</p>',
    ],

    'admin-report' => [
        'subject' => 'Report piattaforma {{period}} — {{app_name}}',
        'body_html' => '<h1>Report Piattaforma</h1>

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
</p>',
    ],
];
