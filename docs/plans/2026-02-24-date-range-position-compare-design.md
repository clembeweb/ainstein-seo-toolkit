# Date Range Position Compare - Design

> Data: 2026-02-24

## Obiettivo

Aggiungere un selettore di date al modulo seo-tracking per confrontare le posizioni keyword tra due date. Implementare in due punti: pagina Keywords (inline) e miglioramento pagina Trend (fonte dati aggiuntiva).

## Parte 1: Date Range nella pagina Keywords

### UI

Barra compatta sopra i filtri esistenti, con:
- Preset rapidi: 7gg, 14gg, 28gg, 3 mesi
- Date picker custom (data inizio + data fine)
- Pulsante "Applica"
- Pulsante "Chiudi confronto" per tornare alla vista normale

Quando attivo, la tabella cambia colonne:
- Keyword | Pos. Inizio | Pos. Fine | Delta | Volume | Gruppo | Aggiornato

Delta visualizzato con:
- Freccia verde su (migliorato, posizione diminuita)
- Freccia rossa giu (peggiorato, posizione aumentata)
- Badge "NUOVA" se presente solo a fine periodo
- Badge "PERSA" se presente solo a inizio periodo
- Trattino se nessun cambiamento significativo

### Backend

**Nuovo metodo** in `Keyword` model:
```php
public function allWithPositionComparison(
    int $projectId,
    string $dateStart,
    string $dateEnd,
    array $filters = []
): array
```

Query: per ogni keyword, prende la posizione dalla `st_keyword_positions` alla data piu vicina a `dateStart` e `dateEnd` (entro +-3 giorni), calcola il delta.

**Controller**: `KeywordController::index()` accetta `date_from` e `date_to` via GET. Se presenti, usa `allWithPositionComparison()` invece di `allWithPositions()`.

**Vista**: Componente Alpine.js che:
1. Toggle la barra date range
2. Submit via form GET (no AJAX, coerente col pattern filtri esistenti)
3. Alterna colonne tabella in base alla presenza dei parametri date

### Fonte dati

`st_keyword_positions` (granularita DATE):
- `keyword_id`, `date`, `avg_position`, `position_change`
- Lookup: per ogni keyword trova il record con `date` piu vicino alla data selezionata

## Parte 2: Miglioramento pagina Trend

### Stato attuale

`PositionCompareService` usa SOLO `st_gsc_data` per il confronto. Questo copre tutte le query GSC ma:
- Non include keyword tracciate senza dati GSC
- Posizioni vengono da GSC (media ponderata) non da SERP check

### Miglioramento

Aggiungere a `PositionCompareService` un metodo alternativo:
```php
public function compareFromPositions(
    string $dateStart,
    string $dateEnd,
    array $filters = []
): array
```

Che usa `st_keyword_positions` per le keyword tracciate (con `is_tracked = 1`).

Nella vista Trend, aggiungere toggle "Fonte dati":
- GSC (default, tutte le query)
- Keyword tracciate (st_keyword_positions)

## File coinvolti

| File | Modifica |
|------|----------|
| `models/Keyword.php` | Nuovo metodo `allWithPositionComparison()` |
| `controllers/KeywordController.php` | Gestione parametri date_from/date_to |
| `views/keywords/index.php` | Barra date range + tabella condizionale |
| `services/PositionCompareService.php` | Nuovo metodo `compareFromPositions()` |
| `controllers/CompareController.php` | Supporto toggle fonte dati |
| `views/trend/index.php` | Toggle fonte dati |

## Non-goals

- Non aggiungere nuove tabelle DB
- Non creare nuove pagine/routes
- Non cambiare il comportamento default (senza date = vista attuale)
