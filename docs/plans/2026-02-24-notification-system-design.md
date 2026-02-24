# Design: Sistema Notifiche In-App

> Data: 2026-02-24
> Stato: Approvato
> Approccio: Tabella `notifications` + NotificationService centralizzato + polling AJAX

---

## Requisiti

- **Campanellino** nella top bar con badge contatore non-lette
- **Polling leggero** ogni 30s per aggiornare il badge
- **Dropdown semplice** con ultime 15 notifiche al click
- **Tipi v1**: inviti progetto + completamento/fallimento operazioni
- **Email condizionale**: in-app per tutto, email per i tipi importanti (configurabile dall'utente)

---

## 1. Schema Database

### Tabella `notifications`

```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) DEFAULT 'blue',
    action_url VARCHAR(500) NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, read_at, created_at),
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabella `notification_preferences`

```sql
CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    email_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_type (user_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tipi di notifica (v1)

| Type | Titolo esempio | action_url | Email default |
|------|---------------|-----------|---------------|
| `project_invite` | "Invito a collaborare su SEO Blog" | `/projects` | Si |
| `project_invite_accepted` | "Mario ha accettato l'invito" | `/projects/5/sharing` | No |
| `project_invite_declined` | "Mario ha rifiutato l'invito" | `/projects/5/sharing` | No |
| `operation_completed` | "Analisi SEO completata" | `/seo-audit/projects/3/results` | Si |
| `operation_failed` | "Rank check fallito" | `/seo-tracking/projects/2/keywords` | Si |

---

## 2. NotificationService

Service centralizzato in `services/NotificationService.php`:

```php
class NotificationService
{
    public static function send(int $userId, string $type, string $title, array $options = []): int
    public static function sendToMany(array $userIds, string $type, string $title, array $options = []): int
    public static function getUnreadCount(int $userId): int
    public static function getRecent(int $userId, int $limit = 15): array
    public static function markAsRead(int $notificationId, int $userId): bool
    public static function markAllAsRead(int $userId): int
    public static function isEmailEnabled(int $userId, string $type): bool
}
```

`$options`: `body`, `icon`, `color`, `action_url`, `data` (array), `skip_email` (bool)

### Flusso `send()`:
1. INSERT in `notifications`
2. Check `isEmailEnabled()` — default da `EMAIL_DEFAULTS` costante
3. Se email abilitata e non `skip_email`: `EmailService::sendTemplate('notification', ...)`

### Costante EMAIL_DEFAULTS:
```php
const EMAIL_DEFAULTS = [
    'project_invite' => true,
    'project_invite_accepted' => false,
    'project_invite_declined' => false,
    'operation_completed' => true,
    'operation_failed' => true,
];
```

---

## 3. API Endpoints

| Route | Metodo | Descrizione |
|-------|--------|-------------|
| `/notifications/unread-count` | GET | `{"count": N}` — polling ogni 30s |
| `/notifications/recent` | GET | Ultime 15 notifiche con read status |
| `/notifications/{id}/read` | POST | Segna singola come letta |
| `/notifications/read-all` | POST | Segna tutte come lette |

Controller: `controllers/NotificationController.php`

---

## 4. UI — Campanellino + Dropdown

### Posizione
Top bar di `layout.php`, tra dark mode toggle e user dropdown.

### Componente Alpine.js `notificationBell()`
- `startPolling()`: fetch count ogni 30s
- `togglePanel()`: apre dropdown, lazy-load notifiche al primo click
- `markRead(id)`: segna letta + naviga a action_url
- `markAllRead()`: segna tutte lette

### Dropdown
- Header: "Notifiche" + link "Segna tutte come lette"
- Lista notifiche: icona colorata + titolo + tempo relativo + pallino non-letta
- Click su notifica: segna come letta, naviga a action_url
- Empty state: "Nessuna notifica"
- Max height con scroll: `max-h-[480px] overflow-y-auto`

### Badge
- Pallino rosso con numero (`bg-red-500 text-white`)
- "9+" se > 9 non lette
- Nascosto se 0 non lette

---

## 5. Preferenze Email — Profilo

Sezione "Preferenze Notifiche" nella pagina `/profile`:
- Toggle per tipo con label descrittiva
- Default da `EMAIL_DEFAULTS`
- Salvataggio in `notification_preferences` (solo override del default)

---

## 6. Integrazione Moduli

| Punto | File | Tipo |
|-------|------|------|
| Invito utente esistente | `ProjectSharingService::invite()` | `project_invite` |
| Invito accettato | `ProjectSharingService::acceptInternal()` / `acceptByToken()` | `project_invite_accepted` |
| Invito rifiutato | `ProjectSharingService::declineInternal()` | `project_invite_declined` |
| Crawl SEO completato | `seo-audit/CrawlController` processStream | `operation_completed` |
| Rank check completato | `seo-tracking/RankCheckController` processStream | `operation_completed` |
| Job AI completato | `ai-content/cron/dispatcher.php` | `operation_completed` |
| Analisi keyword completata | `keyword-research/ResearchController` SSE | `operation_completed` |
| Operazione fallita | Stessi punti, error handler | `operation_failed` |

---

## 7. Cron Cleanup

Aggiungere a `cron/cleanup-data.php`: DELETE notifiche > 90 giorni.

---

## 8. Fuori scope (YAGNI)

- Push browser (ServiceWorker)
- Notifiche Telegram/Slack
- Raggruppamento notifiche
- Notifiche azioni collaboratori
- Pagina dedicata /notifications
- Notifiche admin
