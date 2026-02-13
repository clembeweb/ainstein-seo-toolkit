# Piano Controllo Consistenza View - Tutti i Moduli

> Creato: 2026-02-13
> Basato su: Fix ai-content (6 issue trovati e risolti)

---

## Obiettivo

Applicare gli stessi controlli eseguiti su `ai-content` a tutti i moduli con project-nav per trovare:

1. **Bottoni CTA** che usano `CustomEvent` cross-pagina (non funzionano)
2. **Paginazione** che perde contesto progetto (URL legacy)
3. **Link interni** che usano percorsi legacy invece di project-scoped
4. **CSS typo** (classi duplicate, errori dimensioni)
5. **Valori hardcoded** nelle view (crediti, limiti, configurazioni)
6. **Dead code** nei controller (metodi non raggiungibili da routes)

---

## Moduli da Controllare

| # | Modulo | project-nav.php | Righe | Priorità |
|---|--------|-----------------|-------|----------|
| 1 | **seo-audit** | `modules/seo-audit/views/partials/project-nav.php` | 120 | Alta (100% completato) |
| 2 | **seo-tracking** | `modules/seo-tracking/views/partials/project-nav.php` | 105 | Alta (95% completato) |
| 3 | **ads-analyzer** | `modules/ads-analyzer/views/partials/project-nav.php` | 134 | Alta (100% completato) |
| 4 | **content-creator** | `modules/content-creator/views/partials/project-nav.php` | 103 | Media (nuovo) |
| 5 | **seo-onpage** | `modules/seo-onpage/views/partials/project-nav.php` | 66 | Bassa (WIP) |
| 6 | **ai-optimizer** | `modules/ai-optimizer/views/partials/project-nav.php` | 64 | Bassa (WIP) |

---

## Checklist per Modulo

Per ogni modulo, controllare:

### A. project-nav.php
- [ ] Bottoni CTA usano `<a>` link (NON `CustomEvent` per navigazione cross-pagina)
- [ ] Tutti i link usano `$basePath` project-scoped
- [ ] Nessun link hardcoded a percorsi legacy
- [ ] Icone SVG corrette (w-X h-X senza duplicati)

### B. Views Dashboard
- [ ] Link a sotto-sezioni usano `$baseUrl` project-scoped
- [ ] Valori statistiche non hardcoded
- [ ] Costi crediti (se mostrati) da `Credits::getCost()`
- [ ] Nessun CSS typo (classi duplicate)

### C. Views con Paginazione
- [ ] Link paginazione preservano contesto progetto
- [ ] Filtri/sort preservano contesto progetto
- [ ] "Torna indietro" usa percorso project-scoped

### D. Controller
- [ ] Nessun metodo dead code (non raggiungibile da routes)
- [ ] Valori dinamici passati alla view (non hardcoded)
- [ ] Redirect usano percorsi corretti

### E. Routes
- [ ] Tutte le route definite hanno controller/metodo corrispondente
- [ ] Nessuna route orfana (controller inesistente)

---

## Procedura per Modulo

### Step 1: Leggere project-nav.php
Cercare:
```bash
grep -n "CustomEvent\|onclick\|dispatchEvent" modules/{modulo}/views/partials/project-nav.php
grep -n "hardcoded-path\|/modulo/" modules/{modulo}/views/partials/project-nav.php
```

### Step 2: Leggere tutte le view con paginazione
```bash
grep -rn "page=" modules/{modulo}/views/ --include="*.php"
grep -rn "pagination" modules/{modulo}/views/ --include="*.php"
```

### Step 3: Verificare link project-scoped
```bash
# Cercare link che NON usano $basePath/$baseUrl
grep -rn "url('/" modules/{modulo}/views/ --include="*.php" | grep -v "basePath\|baseUrl\|url('/admin"
```

### Step 4: Cercare valori hardcoded
```bash
grep -rn "text-2xl font-bold.*>[0-9]" modules/{modulo}/views/ --include="*.php"
grep -rn "Credits\|crediti\|costi" modules/{modulo}/views/ --include="*.php"
```

### Step 5: Cercare CSS typo
```bash
grep -rn "w-[0-9]* w-[0-9]*\|h-[0-9]* h-[0-9]*" modules/{modulo}/views/ --include="*.php"
```

### Step 6: Verificare dead code controller
```bash
# Lista metodi nei controller
grep -rn "function " modules/{modulo}/controllers/ --include="*.php"
# Lista route
grep -n "Controller@\|Controller::" modules/{modulo}/routes.php
# Confrontare: metodi senza route = dead code potenziale
```

---

## Priorità Esecuzione

1. **seo-audit** — Modulo più grande dopo ai-content, molte sotto-pagine
2. **seo-tracking** — Modulo complesso con molte view
3. **ads-analyzer** — Due tipi progetto (come ai-content), probabile stesso pattern
4. **content-creator** — Nuovo modulo, potrebbe avere issue fresco
5. **seo-onpage** — WIP, controllo leggero
6. **ai-optimizer** — WIP, controllo leggero

---

## Note

- I moduli `keyword-research` e `internal-links` NON hanno project-nav.php → skip
- Il modulo `_template` è solo reference → skip
- Il backup `seo-tracking_backup_*` → skip
- Dopo il check, aggiornare `AGENT-{MODULO}.md` con eventuali fix
