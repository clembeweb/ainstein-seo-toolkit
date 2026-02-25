# Dashboard Utente — Redesign

> Data: 2026-02-25 | Stato: Design approvato

---

## Problema

La sezione "Da fare" attuale della dashboard utente è inefficace:

1. **Nessun contesto progetto** — "974 problemi nell'ultimo audit" ma di quale sito?
2. **Azioni generiche** — Link alla home del modulo, non al punto specifico
3. **Poco informativa** — Solo testo + bottone, nessuna metrica visibile
4. **Launchpad piatto** — 5 card generiche che non mostrano cosa puoi fare davvero con ogni modulo

## Soluzione

Dashboard **project-centric** con due modalità:

- **Utenti nuovi (0 progetti)**: blocchi "Cosa puoi fare" per modulo con azioni specifiche e costi
- **Utenti attivi (1+ progetti)**: project cards con KPI inline + azione urgente contestualizzata

---

## Design — Utenti Attivi (1+ progetti)

### Sezione 1: Header migliorato

```
Buongiorno, Marco!                              [⚡ 47.5 crediti]
3 progetti attivi · 2 azioni da completare
```

- Saluto + nome (come ora)
- Sotto: riassunto contestuale — N progetti attivi, N azioni urgenti
- Badge crediti a destra, visibile
- Rimuovere "Crediti usati oggi/mese" (poco utile qui, c'è nel profilo)

### Sezione 2: Alert globali (solo account-level)

Appaiono SOLO per problemi non legati a un progetto specifico:

| Condizione | Stile | Messaggio |
|-----------|-------|-----------|
| Crediti < 3 | Banner rosso | "Crediti quasi esauriti (X). Ricarica per continuare." |
| Crediti 3-10 | Banner amber | "Crediti in esaurimento (X). Considera una ricarica." |

Se crediti > 10: nessun banner.

### Sezione 3: Project Cards (sostituisce "Da fare" + "I tuoi progetti")

Grid responsive: 1 colonna mobile, 2 colonne desktop (lg). Ordinate per urgenza (rosso → amber → verde).

#### Struttura card

```
┌──────────────────────────────────────────────────────────────┐
│  🟢  MioSito.it                          [🛡] [📈] [🔑]    │
│      www.miosito.it                      (icone moduli)      │
│                                                              │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐            │
│  │    85/100   │  │    142     │  │  3 pronti   │            │
│  │ Health Score│  │  Keywords  │  │  Articoli   │            │
│  └────────────┘  └────────────┘  └────────────┘            │
│                                                              │
│  ⚠ 23 problemi critici nel SEO Audit        [Vedi piano →]  │
└──────────────────────────────────────────────────────────────┘
```

#### Componenti della card

| Elemento | Descrizione |
|----------|-------------|
| **Indicatore salute** | Pallino verde/amber/rosso basato sul peggior stato tra i moduli |
| **Nome progetto** | Testo bold, cliccabile → `/projects/{id}` |
| **URL sito** | Testo piccolo sotto il nome (dal campo `website_url` del progetto) |
| **Icone moduli** | In alto a destra, piccole icone colorate dei moduli attivi |
| **KPI row** | Max 3 metriche, dinamiche per modulo (vedi tabella sotto) |
| **Azione primaria** | 1 azione urgente con deep-link, o suggerimento se tutto ok |

#### KPI per modulo

| Modulo | KPI | Formato |
|--------|-----|---------|
| SEO Audit | Health Score | `85/100` con colore (verde >70, amber 40-70, rosso <40) |
| SEO Tracking | Keywords tracciate | `142 keywords` |
| AI Content | Articoli | `3 pronti` (ready) o `24 totali` se nessuno ready |
| Keyword Research | Ricerche | `5 ricerche` |
| Ads Analyzer | Campagne | `3 campagne` |
| Internal Links | Progetti | `2 analisi` |

Se un progetto ha > 3 moduli attivi, mostrare i 3 KPI più rilevanti (priorità: audit → tracking → content → kw → ads → links).

#### Logica azione primaria (per progetto)

Ordine priorità (mostra la prima che matcha):

1. **SEO Audit — problemi critici** → "N problemi critici in SEO Audit" → CTA "Vedi piano" → deep-link al progetto audit
2. **AI Content — articoli pronti** → "N articoli pronti da pubblicare" → CTA "Pubblica" → deep-link al progetto content
3. **AI Content — WordPress non collegato** → "Collega WordPress per pubblicare" → CTA "Collega" → `/ai-content/wordpress`
4. **SEO Tracking — GSC non collegato** → "Collega GSC per dati reali" → CTA "Collega" → deep-link al progetto tracking
5. **Keyword — in coda senza articoli** → "Keywords in coda, avvia generazione" → CTA "Genera" → `/ai-content`
6. **Nessuna azione urgente** → Suggerimento: "Attiva [modulo non attivo] per [beneficio]" → CTA "Attiva" → `/projects/{id}`

#### Indicatore salute progetto

| Colore | Condizione |
|--------|-----------|
| Rosso | Audit health < 40 OPPURE crediti < 3 |
| Amber | Audit health 40-70 OPPURE azioni urgenti presenti |
| Verde | Nessun problema rilevato |
| Grigio | Nessun dato disponibile (moduli appena attivati) |

#### Progetto senza moduli attivi

Card minimal:
```
┌──────────────────────────────────────────────────────────┐
│  ⚪  Nuovo Progetto                                      │
│      Nessun modulo attivo                                │
│                                                          │
│      Attiva il primo modulo per iniziare     [Attiva →]  │
└──────────────────────────────────────────────────────────┘
```

#### Limite visualizzazione

- Mostra max 6 project cards
- Se > 6: link "Vedi tutti i N progetti" → `/projects`

### Sezione 4: Strumenti rapidi (semplificato)

Riga compatta di icone+nome per accesso diretto ai moduli. Non più card grandi.

```html
<div class="flex flex-wrap gap-2">
  <!-- Per ogni modulo attivo -->
  <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border ...">
    <icon class="w-4 h-4 {colore-modulo}" />
    <span class="text-sm">Nome Modulo</span>
  </a>
</div>
```

### Sezione 5: "Scopri cosa puoi fare" (moduli non usati)

Mostra blocchi SOLO per moduli che l'utente non ha ancora attivato in nessun progetto. Se l'utente usa già tutti i moduli, questa sezione non appare.

(Stessa struttura dei blocchi per utenti nuovi — vedi sotto)

---

## Design — Utenti Nuovi (0 progetti)

### Sezione 1: Header

```
Benvenuto su Ainstein, Marco!
Scegli uno strumento per iniziare.
```

### Sezione 2: "Cosa vuoi fare?" — Blocchi per modulo

Grid 1 colonna mobile, 2 colonne desktop. Ogni modulo = un blocco con le azioni specifiche.

#### Struttura blocco

```
┌─── 🔑 Keyword Research ──────────────────────────────────────┐
│                                                               │
│  Trova le keyword giuste per il tuo business                  │
│                                                               │
│  ✦ Ricerca guidata con AI              da 2 crediti          │
│  ✦ Architettura sito completa          da 5 crediti          │
│  ✦ Piano editoriale                    da 5 crediti          │
│  ✦ Quick check keyword                 gratis                │
│                                                    [Inizia →] │
└───────────────────────────────────────────────────────────────┘
```

#### Dati per ogni modulo

**Keyword Research** (purple)
- Tagline: "Trova le keyword giuste per il tuo business"
- Azioni: Ricerca guidata (2 cr), Architettura sito (5 cr), Piano editoriale (5 cr), Quick check (gratis)

**AI Content Generator** (amber)
- Tagline: "Scrivi e pubblica articoli SEO ottimizzati"
- Azioni: Articoli SEO completi (3 cr), Meta tag ottimizzati (1 cr), Pubblicazione WordPress (automatica)

**SEO Audit** (emerald)
- Tagline: "Scopri cosa migliorare nel tuo sito"
- Azioni: Audit tecnico completo (2 cr), Piano d'azione prioritizzato (incluso), Report esportabile (incluso)

**SEO Tracking** (blue)
- Tagline: "Monitora le posizioni su Google ogni giorno"
- Azioni: Tracking keyword giornaliero (1 cr/check), Dati reali da Google Search Console (gratis), Report AI settimanale (1 cr)

**Google Ads Analyzer** (rose)
- Tagline: "Analizza o crea campagne Google Ads"
- Azioni: Analisi campagna esistente (2 cr), Creazione campagna da zero (3 cr), Valutazione performance (2 cr)

**Internal Links** (cyan) — se attivo
- Tagline: "Ottimizza la struttura dei link interni"
- Azioni: Scansione struttura link (1 cr), Mappa link interni (incluso)

#### CTA di ogni blocco

"Inizia" → porta a `/projects/create` (per creare un progetto con quel modulo preselezionato) oppure alla landing del modulo.

### Sezione 3: Suggerimento docs

Rimane il banner attuale:
> Primo accesso? Leggi la guida rapida per capire come funziona Ainstein in 2 minuti.

---

## Dati necessari dal controller

### Query aggiuntive per project cards

Per ogni global project, servono i KPI dei moduli collegati:

```php
// Per ogni global project
$projectKpis[$gpId] = [];

// SEO Audit → health_score, issues_count
SELECT health_score, issues_count
FROM sa_projects
WHERE global_project_id = ? AND user_id = ? AND status = 'completed'
ORDER BY completed_at DESC LIMIT 1

// SEO Tracking → keywords_count, gsc_connected
SELECT COUNT(k.id) as keywords, p.gsc_connected
FROM st_projects p
LEFT JOIN st_keywords k ON k.project_id = p.id
WHERE p.global_project_id = ? AND p.user_id = ?

// AI Content → articles_total, articles_ready
SELECT COUNT(*) as total,
       SUM(CASE WHEN status='ready' THEN 1 ELSE 0 END) as ready
FROM aic_articles a
JOIN aic_projects ap ON a.project_id = ap.id
WHERE ap.global_project_id = ? AND ap.user_id = ?

// Keyword Research → projects_count
SELECT COUNT(*) as cnt
FROM kr_projects
WHERE global_project_id = ? AND user_id = ?

// Ads Analyzer → campaigns_count
SELECT COUNT(*) as cnt
FROM ga_projects
WHERE global_project_id = ? AND user_id = ?

// Internal Links → projects_count
SELECT COUNT(*) as cnt
FROM il_projects
WHERE global_project_id = ? AND user_id = ?
```

### Ottimizzazione

- Max 6 progetti → max 6×6 = 36 query extra
- Si può ridurre con JOIN o batch query per modulo
- Cacheable: i KPI cambiano di rado, si può cacheare 5 min

### Variabili passate alla view

```php
return View::render('dashboard', [
    'title' => 'Dashboard',
    'user' => $user,
    'modules' => $modules,               // Moduli attivi utente
    'usageToday' => $usageToday,
    'usageMonth' => $usageMonth,
    'globalProjects' => $globalProjects,  // Con KPI annidati
    'projectKpis' => $projectKpis,        // KPI per progetto per modulo
    'unusedModules' => $unusedModules,     // Moduli non usati (per "Scopri")
]);
```

---

## Fuori scope

- Dashboard admin (resta invariata)
- Notifiche in-app (restano nel bell dropdown)
- Onboarding wizard (potenziale futuro, non ora)
- Widget drag-and-drop (overengineering)

---

## Riepilogo visuale

### Utente attivo
```
[Header: Saluto + riassunto + badge crediti     ]
[Alert globale crediti (se < 10)                ]
[Project Card 1          ] [Project Card 2      ]
[Project Card 3          ] [Project Card 4      ]
[Strumenti rapidi: icona+nome inline            ]
[Scopri cosa puoi fare (moduli non usati)       ]
```

### Utente nuovo
```
[Header: Benvenuto + tagline                    ]
[Blocco KR               ] [Blocco AI Content   ]
[Blocco SEO Audit        ] [Blocco SEO Tracking ]
[Blocco Ads Analyzer     ] [Blocco Int. Links   ]
[Suggerimento guida rapida                      ]
```
