# Design: Progetti Globali Cross-Modulo

> Data: 2026-02-19
> Stato: Approvato
> Approccio: Progetto globale come parent (FK opzionale)

---

## Problema

Ogni modulo Ainstein ha i propri progetti isolati. Per lavorare sullo stesso cliente con piu' moduli, l'utente deve creare progetti separati in ogni tool, duplicando nome e dominio. Non c'e' una vista unificata delle attivita' per cliente.

## Soluzione

Un sistema di **progetti globali** che fa da "hub" per i progetti dei singoli moduli. Ogni progetto globale puo' avere uno o piu' moduli attivati, e la dashboard del progetto mostra KPI sintetici da tutti i moduli collegati.

**Dual mode**: i moduli funzionano sia con progetto globale che standalone (retrocompatibile).

---

## 1. Database

### Tabella `projects` (nuova, globale)

```sql
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Modifica tabelle modulo

Aggiunta `global_project_id` FK nullable a ogni tabella progetti modulo:

```sql
ALTER TABLE aic_projects ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_global_project (global_project_id),
    ADD FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- Ripetere per: sa_projects, st_projects, kr_projects, ga_projects, il_projects, cc_projects
```

`ON DELETE SET NULL`: eliminando il progetto globale, i progetti modulo sopravvivono come standalone.

### Campi tabella `projects`

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | INT PK | ID progetto globale |
| `user_id` | INT FK | Proprietario |
| `name` | VARCHAR(255) | Nome progetto/cliente |
| `domain` | VARCHAR(500) NULL | Dominio sito (opzionale, non tutti i moduli lo richiedono) |
| `description` | TEXT NULL | Descrizione libera |
| `color` | VARCHAR(7) | Colore hex per badge/bordi UI |
| `status` | ENUM | active / archived |
| `created_at` | TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | Ultimo aggiornamento |

---

## 2. Backend

### Model: `core/Models/GlobalProject.php`

Nel core perche' trasversale a tutti i moduli.

**Metodi:**

| Metodo | Descrizione |
|--------|-------------|
| `find($id, $userId)` | Trova progetto con verifica ownership |
| `allByUser($userId, $status)` | Lista progetti utente (filtro status) |
| `allWithModuleStats($userId)` | Progetti + conteggio moduli attivi + ultimo aggiornamento |
| `create($data): int` | Crea progetto globale |
| `update($id, $data)` | Aggiorna campi progetto |
| `delete($id, $userId)` | Elimina (SET NULL sui moduli) |
| `getActiveModules($id)` | Query su tutte le `{prefix}_projects` per trovare quelle con `global_project_id = $id` |
| `getModuleStats($id)` | Chiama `getProjectKpi()` su ogni modulo attivato |
| `activateModule($id, $slug, $userId)` | Crea progetto modulo collegato, eredita nome/dominio |
| `countByUser($userId)` | Per enforcement limiti piano |

### Controller: `core/Controllers/GlobalProjectController.php`

| Azione | Route | Descrizione |
|--------|-------|-------------|
| `index()` | GET `/projects` | Lista progetti globali |
| `create()` | GET `/projects/create` | Form creazione |
| `store()` | POST `/projects` | Salva nuovo progetto |
| `dashboard($id)` | GET `/projects/{id}` | Dashboard progetto con KPI moduli |
| `settings($id)` | GET `/projects/{id}/settings` | Form impostazioni |
| `update($id)` | POST `/projects/{id}/settings` | Salva impostazioni |
| `activateModule($id)` | POST `/projects/{id}/activate-module` | Attiva modulo nel progetto |
| `destroy($id)` | POST `/projects/{id}/delete` | Elimina progetto |

### Integrazione nei moduli esistenti

Ogni model `{prefix}/Project.php` riceve un metodo:

```php
public static function getProjectKpi(int $moduleProjectId): array
```

Ritorna un array standardizzato:

```php
[
    'label' => 'SEO Tracking',
    'icon' => '<svg>...</svg>',
    'metrics' => [
        ['label' => 'Keyword monitorate', 'value' => 45],
        ['label' => 'Posizione media', 'value' => 12.3, 'delta' => -2.1, 'deltaGood' => true],
    ],
    'link' => '/seo-tracking/project/5',
    'lastActivity' => '2026-02-18 14:30:00',
]
```

**KPI per modulo:**

| Modulo | KPI sintetici |
|--------|--------------|
| SEO Tracking | Keyword monitorate, posizione media (delta), keyword in top 10 |
| AI Content | Articoli totali, articoli in coda, ultimo articolo generato |
| SEO Audit | Health score (delta), issues totali, ultima scansione |
| Keyword Research | Ricerche completate, keyword totali, ultimo aggiornamento |
| Internal Links | Pagine analizzate, link suggeriti, copertura % |
| Ads Analyzer | Ultima analisi (score + delta vs precedente), campagne analizzate |
| Content Creator | Contenuti generati, contenuti pubblicati, ultimo contenuto |

---

## 3. Flusso Utente

### Creazione progetto globale

1. Dashboard principale → "Nuovo Progetto"
2. Form: nome, dominio (opzionale), descrizione, colore
3. Dopo creazione → redirect alla dashboard progetto
4. Dashboard mostra card per ogni modulo disponibile con "Attiva"

### Attivazione modulo

1. Dashboard progetto → click "Attiva" su un modulo
2. Se il modulo richiede configurazione extra (es. SEO Audit: crawl_mode), mostra un mini-form
3. Crea `{prefix}_projects` con dati ereditati + `global_project_id`
4. Redirect alla sezione del modulo (o back alla dashboard progetto)

### Creazione progetto da dentro un modulo

Quando l'utente clicca "Nuovo Progetto" dentro un modulo (es. `/seo-tracking`):

1. **Popup/modal con 3 opzioni:**
   - "Collega a progetto esistente" → seleziona progetto globale da dropdown → crea modulo progetto con FK
   - "Crea nuovo progetto globale" → redirect a `/projects/create?module=seo-tracking` → dopo creazione attiva il modulo
   - "Crea progetto standalone" → flusso attuale invariato (no global_project_id)

### Navigazione

- **Sidebar**: nuova voce "Progetti" in cima (sopra i moduli)
- **Dentro progetto globale**: sidebar mostra i moduli attivati per quel progetto con link diretti
- **Breadcrumb**: Progetti > NomeProgetto > SEO Tracking > Keywords

---

## 4. Dashboard Principale (`/dashboard`)

La dashboard si trasforma in due sezioni:

### Sezione 1: Progetti Globali (in alto)
Griglia di card (stile Semrush):
- Nome progetto + dominio + badge colore
- Mini-KPI: numero moduli attivi, ultimo aggiornamento
- Click → dashboard progetto

### Sezione 2: Moduli Standalone (sotto)
Widget attuali per progetti non collegati a un progetto globale.
Si riduce man mano che l'utente adotta i progetti globali.

### Empty state
Se nessun progetto: CTA prominente "Crea il tuo primo progetto" con spiegazione dei vantaggi.

---

## 5. Impatto sui Moduli Esistenti

### Modifiche minime per modulo

| Tipo modifica | Dettaglio |
|---------------|-----------|
| **Schema** | `ALTER TABLE ADD global_project_id` (nullable) |
| **Model** | Aggiungere `getProjectKpi()` statico |
| **Controller** | Nessuna modifica |
| **View** | Nessuna modifica |
| **Routes** | Nessuna modifica |

### Modifica opzionale (fase 2)

Il form "Nuovo Progetto" di ogni modulo potra' mostrare il popup di scelta (collega/crea/standalone). Questo e' un enhancement, non un requisito per la v1.

---

## 6. File da Creare/Modificare

### Nuovi file

| File | Descrizione |
|------|-------------|
| `core/Models/GlobalProject.php` | Model progetti globali |
| `core/Controllers/GlobalProjectController.php` | Controller CRUD + dashboard |
| `shared/views/projects/index.php` | Lista progetti globali |
| `shared/views/projects/create.php` | Form creazione |
| `shared/views/projects/dashboard.php` | Dashboard progetto con KPI moduli |
| `shared/views/projects/settings.php` | Impostazioni progetto |
| `database/migrations/xxx_create_global_projects.sql` | Migration DB |

### File da modificare

| File | Modifica |
|------|----------|
| `public/index.php` | Aggiungere routes `/projects/*`, modificare dashboard |
| `shared/views/layout.php` | Aggiungere "Progetti" nella sidebar |
| `shared/views/components/nav-items.php` | Nuova voce navigazione |
| `modules/*/models/Project.php` (x7) | Aggiungere `getProjectKpi()` |
| `shared/views/dashboard.php` | Ristrutturare con sezione progetti |

---

## 7. Non in scope (v1)

- Migrazione automatica progetti esistenti
- Report unificato cross-modulo
- Billing/fatturazione per progetto
- Permessi/sharing progetto tra utenti
- Import/export progetto
- Template progetto (preset di moduli pre-attivati)

Questi possono essere aggiunti in future iterazioni sfruttando la struttura `projects`.

---

## 8. Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Performance query KPI aggregati | Cache KPI con TTL breve (5 min) o calcolo lazy |
| Confusione utente dual mode | Onboarding chiaro, empty state con CTA verso progetti globali |
| Complessita' navigazione | Breadcrumb chiaro, sidebar context-aware |
| Query N+1 nella dashboard | `getModuleStats()` fa query batch, non una per modulo |
