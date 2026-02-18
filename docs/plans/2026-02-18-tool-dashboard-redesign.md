# Tool Dashboard Redesign — Design Document

**Data:** 2026-02-18
**Scope:** AI Content Generator + Keyword Research (reference per altri moduli)
**Stato:** Approvato

---

## Decisioni di Design

| Aspetto | Decisione |
|---------|-----------|
| Visibilita | Sezioni educative **sempre visibili** (nessun toggle/dismiss) |
| Visual | **Illustrazioni CSS pure** — mock-up UI con HTML/CSS, zero immagini |
| Ordine pagina | **Stats operative prima**, poi sezioni educative sotto |
| Tono | **Mix** — titoli d'impatto + contenuto pratico con esempi concreti |
| Moduli target | AI Content + Keyword Research (pattern riusabile) |
| Approccio | **"Semrush Full"** — hero, feature sections alternate, step, use-case grid, FAQ, CTA |

---

## Struttura Pagina (Template Comune)

Ogni dashboard modulo segue questa struttura dall'alto in basso:

```
1. BLOCCO OPERATIVO (esistente)
   ├── Stats/KPI cards
   ├── Quick Actions
   └── Progetti/Items recenti

2. SEPARATOR VISIVO
   └── Linea gradiente colore-modulo + spazio generoso

3. HERO EDUCATIVO
   ├── Background gradiente leggero colore-modulo
   ├── Titolo d'impatto (beneficio principale)
   ├── Sottotitolo (descrizione pratica 2-3 righe)
   ├── CTA bottone colore-modulo
   └── Visual DX: mock CSS rappresentativo del tool

4. "COME FUNZIONA" (3-4 step)
   ├── Step numerati con icone Heroicons grandi
   ├── Titolo breve + descrizione con esempio concreto
   └── Layout: griglia orizzontale su desktop, verticale su mobile

5. FEATURE SECTIONS (3-4 sezioni alternate)
   ├── Alternano testo SX/visual DX e viceversa
   ├── Ogni sezione: titolo, paragrafo con esempio, CTA link, mock CSS
   └── Background alternato: bianco/slate-50 per ritmo visivo

6. GRIGLIA "COSA PUOI FARE" (6 card, 2x3)
   ├── Icona Heroicons + titolo + descrizione breve
   └── Colore accent modulo

7. FAQ ACCORDION (5-6 domande)
   ├── Domande frequenti specifiche per modulo
   └── Risposte concise e pratiche

8. CTA FINALE
   ├── Barra piena colore-modulo
   ├── Titolo call-to-action
   └── Bottone "Crea progetto"
```

---

## Colori per Modulo

| Modulo | Colore | Gradiente hero | CTA |
|--------|--------|----------------|-----|
| AI Content | Amber | amber-50 → orange-50 | bg-amber-500 hover:bg-amber-600 |
| Keyword Research | Purple | purple-50 → indigo-50 | bg-purple-500 hover:bg-purple-600 |
| SEO Audit | Emerald | emerald-50 → teal-50 | bg-emerald-500 hover:bg-emerald-600 |
| SEO Tracking | Blue | blue-50 → cyan-50 | bg-blue-500 hover:bg-blue-600 |
| Ads Analyzer | Rose | rose-50 → pink-50 | bg-rose-500 hover:bg-rose-600 |

---

## AI Content Generator — Contenuto Dettagliato

### Hero
- **Titolo:** "Genera contenuti SEO che si posizionano in prima pagina"
- **Sottotitolo:** "Dall'analisi keyword alla pubblicazione WordPress: il tuo assistente AI studia i competitor, crea brief strategici e scrive articoli ottimizzati — pronti per essere pubblicati con un click."
- **CTA:** "Crea il tuo primo progetto"
- **Visual:** Mock di un articolo con score bar + heading H1/H2

### Come Funziona (4 step)
1. **Aggiungi le keyword** — Inserisci keyword manualmente, via CSV, o importa da KR
2. **Studio SERP + Brief AI** — Analisi top 10 Google, estrazione struttura competitor, brief strategico
3. **Generazione articolo** — Articolo SEO completo con heading, meta tags, tone of voice
4. **Pubblicazione WordPress** — Pubblica direttamente, cover DALL-E, scheduling automatico

### Feature Sections
1. **"Analisi SERP che studia i tuoi competitor"** (testo SX, visual DX)
   - Visual: tabella SERP con posizioni, URL, word count
   - Esempio: keyword 'come scegliere un materasso' → top 10 coprono materiali, dimensioni, budget
2. **"Brief strategici generati dall'AI"** (visual SX, testo DX)
   - Visual: heading tree + checklist argomenti
   - Esempio: brief per 'ricette pasta al forno' → varianti regionali, tempi cottura, besciamella
3. **"Articoli SEO scritti dal tuo assistente AI"** (testo SX, visual DX)
   - Visual: editor con testo, score SEO laterale, meta tags
4. **"Pubblica su WordPress con un click"** (visual SX, testo DX)
   - Visual: card WordPress con stato "Programmato"
   - Esempio: programma 5 articoli/settimana, ogni lunedi alle 9:00

### Griglia Use-Case (6 card)
1. Contenuti per blog — Articoli long-form per keyword informazionali
2. Pagine prodotto — Descrizioni SEO per e-commerce
3. Guide complete — Tutorial step-by-step
4. Cluster tematici — Serie articoli interconnessi
5. Refresh contenuti — Riscrivi articoli esistenti con dati SERP aggiornati
6. Meta tags in bulk — Meta title/description per pagine esistenti

### FAQ
1. "Quanti crediti costa generare un articolo?"
2. "Posso modificare l'articolo prima della pubblicazione?"
3. "Come funziona la connessione WordPress?"
4. "Che differenza c'e tra brief AI e generazione diretta?"
5. "Posso importare keyword da altri moduli?"
6. "L'AI supporta piu lingue?"

### CTA Finale
"Pronto a generare il tuo primo articolo SEO?" → "Crea progetto"

---

## Keyword Research — Contenuto Dettagliato

### Hero
- **Titolo:** "Trova le keyword giuste e trasformale in un piano editoriale"
- **Sottotitolo:** "Dalla ricerca seed alla strategia contenuti completa: scopri keyword ad alto potenziale, organizzale in cluster tematici e genera un piano editoriale pronto per la produzione — tutto guidato dall'AI."
- **CTA:** "Inizia la tua prima ricerca"
- **Visual:** Mock di un cluster tree con 3-4 rami keyword

### Come Funziona (3 step)
1. **Parti da una keyword seed** — Keyword + settore → centinaia di varianti long-tail
   - Esempio: 'scarpe running' → 'migliori scarpe running principianti', 'scarpe running pronazione'
2. **Organizza in cluster** — AI raggruppa per intento e topic
   - Esempio: 'materassi memory foam' → vantaggi, prezzi, manutenzione, confronto lattice
3. **Genera il Piano Editoriale** — Cluster → titoli articolo, priorita, volume, export AI Content

### Feature Sections
1. **"4 modalita per ogni esigenza di ricerca"** (testo SX, visual DX)
   - Visual: 4 mini-card colorate (Research Guidata, Architettura, Piano Editoriale, Quick Check)
2. **"Clustering AI che organizza il caos"** (visual SX, testo DX)
   - Visual: mappa cluster con nodi e connessioni
   - Esempio: e-commerce mobili → 'tavolo da pranzo allungabile', 'tavolo 6 persone' nello stesso cluster
3. **"Dal piano editoriale alla produzione in un click"** (testo SX, visual DX)
   - Visual: tabella piano con checkbox + bottone "Esporta in AI Content"
   - Esempio: 10 keyword selezionate → 10 articoli in coda produzione

### Griglia Use-Case (6 card)
1. Ricerca keyword di nicchia — Long-tail bassa competizione
2. Analisi competitor — Keyword su cui si posizionano i competitor
3. Struttura sito web — Pianifica pagine basandoti su dati ricerca
4. Calendario contenuti — Piano editoriale mensile con priorita
5. Keyword gap analysis — Keyword dei competitor non coperte
6. Export automatico — Passa keyword alla produzione AI Content

### FAQ
1. "Quanti crediti costa una ricerca keyword?"
2. "Che differenza c'e tra le 4 modalita?"
3. "Posso esportare le keyword in AI Content?"
4. "Da dove vengono i dati di volume e difficolta?"
5. "Il Quick Check e davvero gratuito?"
6. "Posso fare ricerche in lingue diverse dall'italiano?"

### CTA Finale
"Scopri le keyword che i tuoi competitor non stanno sfruttando" → "Crea progetto"

---

## Specifiche Tecniche

### Illustrazioni CSS Pure
- Ogni mock e un `<div>` con Tailwind CSS, con dati finti statici
- Background `bg-slate-50 dark:bg-slate-800` per contenitore mock
- Bordi `border rounded-lg` per simulare UI reale
- Testo placeholder realistico (keyword vere, numeri plausibili)
- Supporto dark mode completo

### Responsive
- Hero: 2 colonne su desktop (testo + visual), 1 colonna su mobile
- Feature sections: 2 colonne alternate, 1 colonna su mobile
- Step: 4 colonne su desktop, 2 su tablet, 1 su mobile
- Use-case grid: 3 colonne su desktop, 2 su tablet, 1 su mobile
- FAQ: full-width su tutti i breakpoint

### Dark Mode
- Tutti i gradienti hero hanno varianti dark (`dark:from-amber-900/20 dark:to-orange-900/20`)
- Mock CSS con varianti dark per bordi e background
- FAQ accordion con dark mode support

### Implementazione
- File: modifica diretta di `modules/{slug}/views/dashboard.php`
- Nessun nuovo componente shared (ogni modulo ha contenuto unico)
- Alpine.js per FAQ accordion (x-data, x-show, @click toggle)
- Zero dipendenze aggiuntive

---

## Future: Pattern per Altri Moduli

Quando si estende agli altri moduli, seguire lo stesso template:
- **SEO Audit**: verde/emerald, focus su health score e categorie problemi
- **SEO Tracking**: blu, focus su posizioni keyword e report AI
- **Ads Analyzer**: rosa/rose, focus su analisi campagne e keyword negative
