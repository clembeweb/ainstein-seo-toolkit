# Design: Condivisione Progetti tra Utenti

> Data: 2026-02-24
> Stato: Approvato
> Approccio: Tabella `project_members` con permessi modulo (Approccio A)

---

## Requisiti

- **3 ruoli**: Owner (implicito da `projects.user_id`), Editor, Viewer
- **Inviti ibridi**: notifica interna se utente esiste, email se non registrato
- **Crediti**: sempre scalati dall'owner del progetto
- **Scope**: per modulo — l'owner sceglie quali moduli condividere per ogni membro

---

## 1. Schema Database

### Tabella `project_members`

```sql
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('editor','viewer') NOT NULL DEFAULT 'viewer',
    invited_by INT NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_user (project_id, user_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabella `project_member_modules`

```sql
CREATE TABLE project_member_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    module_slug VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_module (member_id, module_slug),
    FOREIGN KEY (member_id) REFERENCES project_members(id) ON DELETE CASCADE
);
```

### Tabella `project_invitations`

```sql
CREATE TABLE project_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('editor','viewer') NOT NULL DEFAULT 'viewer',
    modules JSON NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    invited_by INT NOT NULL,
    accepted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_email (project_id, email),
    INDEX idx_token (token),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
);
```

**Note**:
- L'owner NON e in `project_members` — identificato da `projects.user_id`
- `accepted_at = NULL` indica invito pendente (utente esistente)
- `project_invitations` per utenti non registrati; dopo registrazione+accettazione → migra a `project_members`
- `ON DELETE CASCADE` su project_id: eliminazione progetto pulisce tutto

---

## 2. Logica di Accesso — ProjectAccessService

Service centralizzato in `services/ProjectAccessService.php`:

```php
class ProjectAccessService {
    public static function getRole(int $projectId, int $userId): ?string
    // Ritorna 'owner', 'editor', 'viewer', o null

    public static function canView(int $projectId, int $userId): bool
    // owner|editor|viewer

    public static function canEdit(int $projectId, int $userId): bool
    // owner|editor

    public static function isOwner(int $projectId, int $userId): bool

    public static function canAccessModule(int $projectId, int $userId, string $moduleSlug): bool
    // owner (sempre) | membro con modulo in project_member_modules

    public static function getOwnerId(int $projectId): int
    // Per scalare crediti dall'owner

    public static function getAccessibleProjects(int $userId): array
    // Propri + condivisi (con ruolo e moduli)
}
```

### Cambiamento pattern controller

**Hub (GlobalProjectController)**:
- `find($id, $userId)` → `findAccessible($id, $userId)` (owner + membri accettati)
- Operazioni di modifica: check `canEdit()`
- Settings/condivisione/eliminazione: check `isOwner()`

**Moduli**:
- `find($id, $userId)` → `findAccessible($id, $userId)`
- Check aggiuntivo: `canAccessModule($globalProjectId, $userId, $moduleSlug)`
- Operazioni AI/import: check `canEdit()`
- Crediti: `Credits::consume(ProjectAccessService::getOwnerId($globalProjectId), ...)`

### Matrice permessi

| Azione | Owner | Editor | Viewer |
|--------|-------|--------|--------|
| Vede dati/risultati | Si | Si | Si |
| Lancia analisi AI | Si | Si | No |
| Importa URL/keyword | Si | Si | No |
| Modifica impostazioni progetto | Si | No | No |
| Gestisce membri/inviti | Si | No | No |
| Elimina progetto | Si | No | No |
| Attiva/disattiva moduli | Si | No | No |

---

## 3. UI e Flussi

### Tab "Condivisione" in `/projects/{id}/settings`

Visibile solo all'owner. Contiene:
- **Form invito**: campo email + select ruolo + checkbox moduli attivati + bottone "Invita"
- **Lista membri**: nome, email, ruolo, moduli, data, azioni (modifica, rimuovi)
- **Inviti pendenti**: email, ruolo, stato, azioni (reinvia, annulla)

### Dashboard progetti `/projects`

- Sezione "I miei progetti" + sezione "Condivisi con me"
- Badge ruolo (Editor azzurro, Viewer grigio)
- Avatar/iniziale dell'owner sui progetti condivisi

### Dentro i moduli

- **Viewer**: bottoni azione nascosti/disabilitati, badge "Sola lettura"
- **Editor**: esperienza identica all'owner (senza settings/condivisione)
- **Sidebar**: mostra solo moduli a cui il membro ha accesso

### Notifiche (minimali)

- Invito a utente esistente: banner in dashboard
- Invito a utente non registrato: email con link
- Nessuna notifica per accettazione/rimozione

---

## 4. Flusso Inviti

```
Owner clicca "Invita"
  ↓
Cerca email in users
  ├─ TROVATO → project_members (accepted_at=NULL) + project_member_modules
  │            → banner "Hai un invito" in dashboard utente
  │            → Accetta: accepted_at = NOW()
  │            → Rifiuta: DELETE
  │
  └─ NON TROVATO → project_invitations con token (scadenza 7gg)
                   → email con link /invite/accept?token=xxx
                   → registrazione → login → auto-accettazione
                   → migra a project_members + DELETE invito
```

### Casi limite

| Caso | Comportamento |
|------|--------------|
| Owner invita se stesso | Bloccato, errore |
| Email gia membro | Errore "Utente ha gia accesso" |
| Invito pendente stessa email | UPSERT ruolo/moduli |
| Token scaduto (>7gg) | Pagina "Invito scaduto" |
| Owner elimina progetto | CASCADE su membri e inviti |
| Owner rimuove membro | DELETE → CASCADE su moduli |
| Owner disattiva modulo | Rimuove righe da project_member_modules |
| Accesso URL diretto a modulo non condiviso | 403 |
| Crediti owner esauriti | Errore standard "Crediti insufficienti" |

---

## 5. Fuori scope (YAGNI)

- Notifiche push/real-time
- Activity log condiviso
- Trasferimento ownership
- Limite membri per progetto
- API pubblica gestione membri
- Ruoli custom
