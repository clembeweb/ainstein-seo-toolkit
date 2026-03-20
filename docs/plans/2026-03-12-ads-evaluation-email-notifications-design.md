# Design: Email Notifications per Valutazioni AI Ads Analyzer

> Data: 2026-03-12

## Obiettivo

Aggiungere notifiche email opzionali quando l'auto-evaluate AI di ads-analyzer rileva anomalie significative (positive o negative). L'AI decide autonomamente se notificare tramite campo `should_notify` nell'output JSON.

## Requisiti

- Solo email (no notifica in-app campanella)
- Opzionale: toggle in preferenze utente `/profile`
- Default: abilitato
- Trigger: solo quando AI decide `should_notify: true`
- Contenuto minimal: subject con progetto + score, body con summary + reason + link valutazione

## Flusso

```
sync-dispatcher.php (ogni 6h, esistente)
  → enqueue in ga_auto_eval_queue (esistente)
    → auto-evaluate.php (ogni 5min, esistente)
      → CampaignEvaluatorService::evaluateWithContext() (modificato: +2 campi output)
        → AI ritorna should_notify + notification_reason
          → if should_notify === true
            → NotificationService::send() con skip_email=false
              → isEmailEnabled() check preferenza utente
                → EmailService::sendTemplate('notification', ...) template generico
```

## Modifiche per File

### 1. `services/NotificationService.php`

Aggiungere nuovo tipo:

```php
const TYPE_LABELS = [
    // ... esistenti ...
    'ads_evaluation_alert' => 'Alert valutazione Google Ads',
];

const EMAIL_DEFAULTS = [
    // ... esistenti ...
    'ads_evaluation_alert' => true,
];
```

### 2. `modules/ads-analyzer/services/CampaignEvaluatorService.php`

Aggiungere al prompt di `evaluateWithContext()` la richiesta di due campi extra nell'output JSON:

```
Aggiungi anche:
- "should_notify": true/false — se la situazione merita una notifica all'utente.
  Notifica SOLO per anomalie significative: cali drastici di performance, problemi critici nuovi,
  miglioramenti eccezionali. NON notificare per situazioni normali, variazioni minime, o valutazioni
  di routine senza cambiamenti rilevanti.
- "notification_reason": stringa breve (max 200 char) che spiega perche l'utente dovrebbe essere avvisato.
  Lascia vuota se should_notify e false.
```

### 3. `modules/ads-analyzer/cron/auto-evaluate.php`

Dopo il salvataggio della valutazione (riga dove fa UPDATE `ga_campaign_evaluations`), aggiungere:

```php
// Notifica email se AI suggerisce
$aiData = json_decode($aiResponse, true);
if (!empty($aiData['should_notify'])) {
    try {
        Database::reconnect();
        $projectName = $project['name'] ?? "Progetto #{$item['project_id']}";
        $score = $aiData['overall_score'] ?? '?';
        $reason = $aiData['notification_reason'] ?? $aiData['summary'] ?? '';

        \Services\NotificationService::send(
            $project['user_id'],
            'ads_evaluation_alert',
            "Alert campagna: {$projectName} (Score: {$score}/10)",
            [
                'icon' => 'exclamation-triangle',
                'color' => 'rose',
                'action_url' => "/ads-analyzer/projects/{$item['project_id']}/campaigns/evaluations/{$evalId}",
                'body' => $reason,
                'data' => [
                    'module' => 'ads-analyzer',
                    'project_id' => $item['project_id'],
                    'evaluation_id' => $evalId,
                    'score' => $score,
                ],
            ]
        );
    } catch (\Exception $e) {
        // Non-blocking: notification failure non deve bloccare il cron
        error_log("[auto-evaluate] Notification error: " . $e->getMessage());
    }
}
```

### 4. `public/index.php`

Aggiungere `'ads_evaluation_alert'` all'array `$types` nella route `POST /profile/notification-preferences`.

## Template Email

Usa il template generico `notification` (gia esistente in DB e file fallback). Risultato:

- **Subject**: "Alert campagna: {nome progetto} (Score: {score}/10) — Ainstein"
- **Body**: notification_reason dell'AI + bottone "Vai alla pagina" → link valutazione

## Gestione Edge Cases

- `should_notify` mancante nel JSON AI → default `false` (nessuna notifica)
- `notification_reason` vuoto → fallback a `summary`
- Eccezione in NotificationService → catch + log, non blocca il cron
- Utente disabilita preferenza → `isEmailEnabled()` ritorna false, email non inviata
- Prima valutazione (nessun storico) → AI puo comunque decidere di notificare se trova problemi

## Cosa NON cambia

- Frequenza sync (ogni 6h)
- Logica auto-evaluate esistente
- Nessuna nuova tabella DB
- Nessun nuovo cron job
- Nessun template email custom (usa generico)
