# Design: Multi-Type Module Activation in Global Projects

> Data: 2026-02-23
> Stato: Approvato
> Approccio: Bottone "+" sulla card attiva

---

## Problema

Quando un modulo tipizzato (ai-content, keyword-research, ads-analyzer) viene attivato con UN tipo nella dashboard di un progetto globale, **sparisce completamente** dalla sezione "Moduli disponibili". L'utente non puo' aggiungere altri tipi (es. "Articoli Manuali" + "SEO Meta Tags") senza creare un nuovo progetto globale.

**Moduli tipizzati interessati:**

| Modulo | Tipi | Esempio scenario |
|--------|------|-----------------|
| ai-content | manual, auto, meta-tag | Utente vuole articoli manuali E meta tags per lo stesso sito |
| keyword-research | research, architecture, editorial | Utente vuole ricerca guidata E piano editoriale per lo stesso progetto |
| ads-analyzer | campaign, campaign-creator | Utente vuole analizzare campagne E crearne di nuove |

**Codice colpevole:**

1. `dashboard.php` riga 154: `!in_array($slug, $activeSlugs)` nasconde tutto il modulo se anche un solo tipo e' attivo
2. `getActiveModules()`: non include il campo `type` nei dati ritornati
3. `getModuleStats()`: indicizza per slug (`$stats[$slug]`), quindi record multipli dello stesso modulo si sovrascrivono

---

## Soluzione

La card del modulo attivo nella dashboard progetto mostra **sub-card per ogni tipo attivato** con KPI separati, piu' un bottone "+ Aggiungi [tipo]" per i tipi non ancora attivati.

Un modulo tipizzato sparisce da "Moduli disponibili" **solo quando tutti i tipi sono attivi**.

I moduli senza tipi (seo-audit, seo-tracking, internal-links, content-creator) restano invariati.

---

## 1. Modifiche Backend

### `GlobalProject::getActiveModules()`

Aggiungere `type` e `type_label` ai dati ritornati per ogni record:

```php
// Prima:
$rows = Database::fetchAll("SELECT id FROM {table} WHERE global_project_id = ?", [$id]);
$active[] = ['slug' => $slug, 'module_project_id' => $row['id'], ...];

// Dopo:
$rows = Database::fetchAll("SELECT id, type FROM {table} WHERE global_project_id = ?", [$id]);
$active[] = [
    'slug' => $slug,
    'module_project_id' => $row['id'],
    'type' => $row['type'] ?? null,          // null per moduli senza tipi
    'type_label' => $typeLabel ?? null,        // label dal MODULE_TYPES
    ...
];
```

La colonna `type` esiste gia' in `aic_projects`, `kr_projects`, `ga_projects`. Per le altre tabelle (`sa_projects`, `st_projects`, `il_projects`, `cc_projects`) la query `SELECT id, type` restituira' errore — usare `SELECT id` per quei moduli oppure try/catch sulla colonna.

**Approccio scelto:** Verificare se il modulo ha tipi prima della query. Se slug e' in `MODULE_TYPES`, fare `SELECT id, type`, altrimenti `SELECT id`.

### `GlobalProject::getModuleStats()`

Cambiare la chiave da `$stats[$slug]` a chiave composita per moduli tipizzati:

```php
// Prima:
$stats[$slug] = $model->getProjectKpi($moduleProjectId);

// Dopo — chiave composita "slug:type" per moduli tipizzati:
$key = $module['type'] ? "{$slug}:{$module['type']}" : $slug;
$stats[$key] = $model->getProjectKpi($moduleProjectId);
```

### Nuovo metodo: `GlobalProject::getRemainingTypes()`

```php
public function getRemainingTypes(int $id): array
```

Per ogni modulo tipizzato, ritorna i tipi NON ancora attivati per quel progetto globale:

```php
// Esempio output:
[
    'ai-content' => [
        'auto' => ['label' => 'Articoli Automatici', 'description' => '...', 'icon' => '...'],
    ],
    // keyword-research non appare se tutti i tipi sono attivati
]
```

Logica:
1. Per ogni modulo in `MODULE_TYPES`, query tipi attivati: `SELECT DISTINCT type FROM {table} WHERE global_project_id = ?`
2. Diff con tipi disponibili in `MODULE_TYPES[$slug]`
3. Ritorna solo i tipi mancanti

---

## 2. Modifiche Dashboard View

### Raggruppamento moduli attivi per slug

La view riceve `$activeModules` (array flat) e deve raggruppare per slug:

```php
$groupedModules = [];
foreach ($activeModules as $module) {
    $groupedModules[$module['slug']][] = $module;
}
```

### Card modulo con tipi multipli

```
+---------------------------------------------+
| [icon] AI Content Generator                  |
|                                              |
|  +- Articoli Manuali -------------------+   |
|  |  Articoli: 12  |  In coda: 3        |   |
|  |  Vai al modulo ->                     |   |
|  +---------------------------------------+   |
|                                              |
|  +- SEO Meta Tags -----------------------+   |
|  |  Pagine: 45  |  Generati: 38        |   |
|  |  Vai al modulo ->                     |   |
|  +---------------------------------------+   |
|                                              |
|  [+ Aggiungi Articoli Automatici]            |
+---------------------------------------------+
```

### Card modulo con un solo tipo (compatta)

Quando c'e' un solo tipo attivo, la card resta simile a quella attuale ma con:
- Badge del tipo accanto al nome modulo
- Bottone "+ Aggiungi [tipo]" sotto le metriche (se ci sono tipi rimanenti)

```
+---------------------------------------------+
| [icon] AI Content Generator                  |
|   [badge: Articoli Manuali]                  |
|                                              |
|  Articoli: 12  |  In coda: 3  |  Parole: 8k |
|  Vai al modulo ->                            |
|                                              |
|  [+ Aggiungi tipo]                           |
+---------------------------------------------+
```

Il bottone "+ Aggiungi tipo" apre la stessa modal di selezione tipo, ma filtrando i tipi gia' attivati.

### Sezione "Moduli disponibili" — logica aggiornata

```php
// Prima: nasconde tutto il modulo se qualsiasi tipo e' attivo
if (!in_array($slug, $activeSlugs)) { ... }

// Dopo: per moduli tipizzati, nasconde solo se TUTTI i tipi sono attivi
$isFullyActivated = false;
if (isset($moduleTypes[$slug])) {
    $activeTypesForSlug = count($remainingTypes[$slug] ?? []) === 0;
    $isFullyActivated = in_array($slug, $activeSlugs) && $activeTypesForSlug;
} else {
    $isFullyActivated = in_array($slug, $activeSlugs);
}

if (!$isFullyActivated && in_array($slug, $systemActiveSlugs)) {
    $nonActivated[$slug] = $config;
}
```

**Nota:** Questa logica e' un fallback per moduli tipizzati — il bottone "+" sulla card attiva e' il flusso principale. Ma se l'utente scrolla fino a "Moduli disponibili", vedra' comunque il modulo con i tipi rimanenti disponibili.

### Modal selezione tipo — filtro tipi attivi

La modal di selezione tipo deve nascondere i tipi gia' attivati:

```php
// JS: filtra tipi gia' attivi prima di mostrare la modal
const activeTypes = <?= json_encode($activeTypesPerModule) ?>;
// Quando apre la modal per un modulo, mostra solo tipi non in activeTypes[slug]
```

---

## 3. Modifiche Controller

### `GlobalProjectController::dashboard()`

Passare dati aggiuntivi alla view:

```php
$remainingTypes = $this->project->getRemainingTypes($id);

// Tipi attivi per modulo (per filtrare la modal)
$activeTypesPerModule = [];
foreach ($activeModules as $m) {
    if ($m['type']) {
        $activeTypesPerModule[$m['slug']][] = $m['type'];
    }
}

return View::render('projects/dashboard', [
    // ... dati esistenti ...
    'remainingTypes' => $remainingTypes,
    'activeTypesPerModule' => $activeTypesPerModule,
]);
```

---

## 4. File da Modificare

| File | Modifica |
|------|----------|
| `core/Models/GlobalProject.php` | `getActiveModules()`: aggiungere type/type_label. `getModuleStats()`: chiave composita. Nuovo `getRemainingTypes()`. |
| `controllers/GlobalProjectController.php` | `dashboard()`: passare remainingTypes e activeTypesPerModule alla view. |
| `shared/views/projects/dashboard.php` | Raggruppamento per slug, sub-card per tipo, bottone "+", filtro modal tipi attivi, logica "disponibili" aggiornata. |

---

## 5. UX Landing Moduli (verifica)

La landing page dei moduli tipizzati (es. `/ai-content`) gia' funziona bene:
- ai-content: tabs per tipo (manual, auto, meta-tag) con card progetto
- keyword-research: tabs per tipo (research, architecture, editorial)
- ads-analyzer: lista progetti con badge tipo

**Problema minore identificato:** le card dei progetti non mostrano nessuna indicazione di appartenenza a un progetto globale. Un progetto creato da `/projects` e uno standalone sono indistinguibili.

**Enhancement opzionale (non bloccante):** aggiungere un piccolo badge/link sulla card del progetto quando `global_project_id IS NOT NULL`, es:
```
[Clemente] <- link a /projects/{global_project_id}
```

Questo e' un nice-to-have, non un requisito per la v1 del fix multi-tipo.

---

## 6. Non in scope

- Migrazione progetti esistenti (i progetti con un solo tipo continuano a funzionare)
- Disattivazione singolo tipo (solo eliminazione manuale del progetto modulo)
- Ordinamento tipi nella card
- KPI aggregati cross-tipo (ogni tipo ha i propri KPI separati)

---

## 6. Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Query `SELECT id, type` su tabelle senza colonna `type` | Check `MODULE_TYPES` prima della query — solo moduli tipizzati chiedono `type` |
| Modal filtrata vuota (tutti tipi attivi) | Il bottone "+" non appare se `remainingTypes[$slug]` e' vuoto |
| Confusione UX con troppe sub-card | Max 3 tipi per modulo, layout chiaro con separazione visiva |
