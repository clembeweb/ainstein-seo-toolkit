# Audit UX/UI Completo — Ainstein SEO Toolkit

> Data: 2026-03-11
> Scope: 6 moduli produzione + core/admin + hub progetti
> Focus: UX disconnessa, complessità inutile, leggibilità dati, coerenza cross-modulo

---

## Executive Summary

L'audit ha identificato **42 issue UX** classificate in 4 categorie:
- **A. Funzionalità disconnesse/fuori posto** (7 issue) — features che funzionano ma non servono dove sono
- **B. Complessità UX inutile** (12 issue) — flussi troppo complessi per il valore che danno
- **C. Leggibilità e fruibilità dati** (13 issue) — dati mostrati ma non actionable o poco leggibili
- **D. Incoerenze cross-modulo** (10 issue) — pattern diversi per lo stesso tipo di operazione

---

## A. FUNZIONALITÀ DISCONNESSE O FUORI POSTO

### A1. Meta Tags in AI Content — NON APPARTIENE QUI [CRITICO]

**Stato attuale**: Il modulo `ai-content` ha una sezione "Meta Tags" (dashboard, list, import, preview) con un pipeline a 4 step: Import URL → Scrape → Genera con AI → Preview/Pubblica.

**Problema**: Questo è un tool SEO On-Page, non un tool di Content Generation. L'utente che usa AI Content vuole generare articoli, non ottimizzare meta title/description di pagine esistenti. Il flusso è completamente scollegato dal wizard keyword → articolo.

**Proposta**:
- Spostare Meta Tags nel modulo `seo-onpage` (in sviluppo) o `seo-audit` come azione correttiva post-audit
- In alternativa, renderlo uno strumento standalone nel hub progetti ("Ottimizza Meta Tags")
- Rimuovere dalla sidebar di ai-content

### A2. Internal Links in AI Content — INTEGRAZIONE NEL FLUSSO, NON SEZIONE SEPARATA [CRITICO]

**Stato attuale**: `ai-content` ha una sezione "Internal Links" (index, edit, import) dove l'utente importa URL e anchor text. Questi link interni dovrebbero essere usati dal generatore per inserire link coerenti negli articoli (manuali e automatici).

**Problema**: La sezione è isolata dal flusso di generazione. L'utente importa i link in una pagina, poi genera articoli in un'altra, e il collegamento tra i due non è evidente. Non è chiaro QUANDO e COME i link importati vengono effettivamente usati durante la generazione.

**Proposta**:
- Integrare la gestione link interni come step nel wizard di generazione articoli: "Step 3: Seleziona link interni da inserire"
- Nel flusso auto: mostrare chiaramente nelle impostazioni "Link interni: 12 URL disponibili, verranno inseriti automaticamente"
- Nella sezione import link: aggiungere indicatore "Usato in X articoli" per ogni link
- Rimuovere come sezione separata nella sidebar → renderlo accessibile dal wizard e dalle impostazioni auto

### A3. Quick Check in Keyword Research — SCOLLEGATO [MEDIO]

**Stato attuale**: Quick Check è gratis e dà volume + CPC + competition per keyword singola. Ma i risultati non sono salvati, non sono collegati a nessun progetto, e scompaiono al refresh.

**Problema**: Feature utile ma "usa e getta". Non alimenta nessun workflow. L'utente controlla un volume e poi deve riscriverlo manualmente altrove.

**Proposta**:
- Aggiungere "Salva nel progetto" che inserisce la keyword nel tracking o nel piano editoriale
- Oppure integrare Quick Check come widget inline in altri moduli (es. dentro l'editor content-creator)

### A4. Crawl Budget in SEO Audit — CONFUSIONE NAVIGAZIONE [MEDIO]

**Stato attuale**: Crawl Budget è stato "mergiato" in SEO Audit ma mantiene le sue 4 sotto-pagine (overview, waste, redirects, indexability) con navigazione separata nella sidebar.

**Problema**: L'utente vede due sezioni distinte ("Audit" e "Crawl Budget") dentro lo stesso modulo. Non è chiaro se sono due analisi diverse o la stessa con angolazioni diverse.

**Proposta**:
- Integrare i dati crawl budget come tab nell'audit dashboard (non sezione separata)
- Oppure renderlo un "report aggiuntivo" generato dallo stesso crawl, non una navigazione indipendente

### ~~A5. Campaign Creator in Ads Analyzer~~ — GIÀ IMPLEMENTATO [RIMOSSO]

**Verifica**: Il Campaign Creator ha già export CSV per Google Ads Editor E pubblicazione diretta su Google Ads via API (`CampaignCreatorController::publishToGoogleAds()`). Il wizard mostra bottone "Esporta CSV" e sezione "Pubblica su Google Ads" con selezione account.

### A6. Links Graph in SEO Audit — PRESENTE MA NON UTILE [BASSO]

**Stato attuale**: Pagina `links/graph.php` mostra un grafo visuale dei link interni.

**Problema**: Il grafo è visivamente interessante ma non actionable. Non si possono identificare facilmente pagine orfane o hub importanti. Con siti grandi diventa illeggibile.

**Proposta**:
- Sostituire con una vista tabellare "Top Linked Pages" + "Pagine Orfane" con metriche chiare
- Il grafo può restare come vista secondaria opzionale

### A7. Alert in SEO Tracking — REATTIVE, NON PROATTIVE [BASSO]

**Stato attuale**: Le alert notificano quando una posizione è GIÀ scesa.

**Problema**: L'utente riceve l'alert quando il danno è fatto. Nessun "trending down" warning preventivo.

**Proposta**:
- Aggiungere alert predittive basate su trend (es. "keyword X è scesa per 3 check consecutivi, potrebbe uscire dalla top 10")
- Priorità bassa — miglioramento futuro

---

## B. COMPLESSITÀ UX INUTILE

### B1. Evaluation Ads Analyzer — SUGGERIMENTI APPLICABILI GIÀ OK [INFO]

**Stato attuale**: La valutazione campagna ha 5 tab (Panoramica, Campagne, Estensioni, Landing, Azioni) con suggerimenti AI e bottone "Applica su Google Ads" già funzionante tramite `renderAiGenerator()` + modal doppia conferma. Si attiva quando l'account GAds è collegato.

**Non è un problema**: La struttura tab va bene, i suggerimenti sono già applicabili direttamente su GAds.

**UX polish minore**: Verificare che il bottone "Applica" sia visibile e coerente in tutti i tab con suggerimenti, non solo nel tab "Azioni".

### B2. Import CSV in Content Creator — MAPPING PER INDICE [ALTO]

**Stato attuale**: L'import CSV chiede di specificare colonne per indice numerico (0, 1, 2...) con 5 campi di configurazione.

**Problema**: L'utente deve aprire il CSV, contare le colonne, e inserire numeri. Nessun preview dei dati prima dell'import.

**Proposta**:
- Upload → preview prime 5 righe → drag-and-drop mapping colonne
- Auto-detect delimiter e headers

### B3. Piano Editoriale in Keyword Research — FORM COMPLESSO [ALTO]

**Stato attuale**: Il form ha 3 toggle + 2 date picker + selezione geografia + articoli/mese + target. 5+ controlli per una singola decisione.

**Problema**: Troppe opzioni per un utente che vuole semplicemente "crea un piano editoriale per queste keyword".

**Proposta**:
- Default smart: periodo = 3 mesi, articoli = 4/mese, target = generico
- Mostrare solo "Tema" e "Keyword seed" inizialmente
- Toggle "Opzioni avanzate" per il resto

### B4. Wizard Keyword Research — NESSUN SALVATAGGIO INTERMEDIO [ALTO]

**Stato attuale**: Il wizard a 3 step (brief → collection → analysis) perde tutto al refresh della pagina.

**Problema**: Se l'utente chiude il browser durante lo step 1 (brief compilato), deve ricominciare da zero.

**Proposta**:
- Auto-save del brief in `kr_projects` o localStorage
- "Continua dove eri rimasto" quando l'utente torna

### B5. Rank Check Provider Selection — CONFUSO [MEDIO]

**Stato attuale**: La barra provider mostra "Serper.dev" + "SERP API (fallback)" inline. L'utente vede due nomi di servizi tecnici.

**Problema**: L'utente non sa (e non deve sapere) quali API vengono usate. È un dettaglio implementativo.

**Proposta**:
- Nascondere i nomi dei provider
- Mostrare solo "Verifica posizioni" con indicatore di stato (attivo/non configurato)
- Provider visibile solo in admin settings

### ~~B6. Publish-to-WordPress Flow~~ — GIÀ OK [RIMOSSO]

**Verifica**: Il modal di pubblicazione è già single-step (selezione sito + categoria + stato in un unico form). Non necessita di semplificazione.

### B7. SSE Progress Bars Content Creator — DUE BARRE SEPARATE [MEDIO]

**Stato attuale**: Scraping e generazione hanno due barre progresso SSE separate visibili contemporaneamente.

**Problema**: Confusione visiva, specialmente su mobile dove si sovrappongono.

**Proposta**:
- Unificare in una sola barra con step: "Scraping (3/10) → Generazione (0/10)"
- La fase attiva è evidenziata, le altre grigie

### B8. Cluster Filtering in Keyword Research — NO SORTING/SEARCH [MEDIO]

**Stato attuale**: I risultati cluster si filtrano per intent ma non si possono ordinare per volume, numero keyword, o cercare una keyword specifica.

**Problema**: Con 20+ cluster, l'utente deve scorrere per trovare quello che cerca.

**Proposta**:
- Aggiungere sort per volume totale, numero keyword, e search box
- Cluster con volume più alto in cima di default

### ~~B9. GSC Sync Manuale~~ — GIÀ PRESENTE [RIMOSSO]

**Verifica**: Banner "Sincronizza GSC per scoprire keyword" già presente in `seo-tracking/views/keywords/index.php` (linea 399) con funzionalità `executeRefreshSync()`.

### B10. Admin Module Cards — NO DRAG AND DROP [BASSO]

**Stato attuale**: I moduli si attivano/disattivano con toggle, ma l'ordine nella sidebar è fisso.

**Proposta**:
- Aggiungere riordino drag-and-drop per personalizzare la sidebar
- Priorità bassa

### B11. Delete Senza Undo — OVUNQUE [BASSO]

**Stato attuale**: Tutte le operazioni di cancellazione (articoli, keyword, progetti) sono permanenti. Nessun soft-delete.

**Proposta**:
- Implementare soft-delete con "Annulla" per 10 secondi (toast con undo)
- Almeno per articoli generati (che costano crediti)

### B12. Date Filter Hardcoded — NESSUN RANGE CUSTOM [BASSO]

**Stato attuale**: I filtri periodo sono "7 giorni, 14 giorni, 30 giorni" senza opzione custom.

**Proposta**:
- Aggiungere "Personalizzato" con date picker range
- Priorità bassa

---

## C. LEGGIBILITÀ E FRUIBILITÀ DATI

### C1. SEO Audit Dashboard — TROPPI DATI SENZA GERARCHIA [CRITICO]

**Stato attuale**: Dashboard mostra 5+ sezioni: gauge score, issue summary, category grid, top issues, crawl controls. 12+ cards visibili senza priorità visiva.

**Problema**: L'utente non sa dove guardare prima. Tutto ha lo stesso peso visivo.

**Proposta**:
- **Above the fold**: Solo score + delta + "3 cose da fare subito" (azioni prioritarie)
- **Below**: Category breakdown + issues list
- Pattern: "Punteggio → Cosa fare → Dettagli"

### C2. SEO Tracking Dashboard — 6-7 WIDGET DENSI [CRITICO]

**Stato attuale**: Dashboard ha 4 KPI card + distribution chart + AI summary + 3 mini-tabelle + top gainers/losers. 50+ data point visibili.

**Problema**: Information overload. L'utente viene sommerso da numeri senza sapere quale è importante.

**Proposta**:
- **Hero section**: Posizione media + variazione + keyword in top 3/10/30 (basta)
- **Sezione trend**: Grafico posizione media ultimi 30gg (solo quello)
- **Sezione alert**: "Attenzione: 3 keyword sono scese" (actionable)
- **Tutto il resto**: tab "Analisi dettagliata" per chi vuole approfondire

### C3. KPI Delta Senza Contesto [ALTO]

**Stato attuale**: Tutti i moduli mostrano "↑ 12%" o "↓ 5%" senza spiegare perché.

**Problema**: Il numero è inutile senza contesto. +12% di CTR è buono, ma da cosa dipende?

**Proposta**:
- Tooltip o mini-insight: "+12% CTR — probabilmente dovuto a nuovi meta title aggiornati il 5/03"
- Dove non calcolabile, almeno mostrare il periodo di confronto ("vs. settimana precedente")

### C4. Progress Bar Content Creator — SEGMENTI INVISIBILI [ALTO]

**Stato attuale**: Barra stacked con 5 segmenti colorati (scraped/generated/approved/published/error). Segmenti <5% sono invisibili.

**Problema**: Se hai 100 URL e 2 errori, il segmento errore non si vede.

**Proposta**:
- Soglia minima visiva del 3% per ogni segmento
- Oppure sostituire con contatori numerici separati (più chiari)

### C5. Cluster Card Keyword Research — TROPPO COMPATTE [ALTO]

**Stato attuale**: Ogni cluster è una card collapsibile. Con 20+ cluster, serve molto scroll.

**Problema**: L'utente perde il quadro d'insieme. Non vede quale cluster è più importante.

**Proposta**:
- Vista "sommario" con tabella: Cluster | Keywords | Volume totale | Intent | Azione
- Click sulla riga → espande dettaglio
- Ordinamento per volume (default) o alphabetico

### C6. Campaign Detail — STRUTTURA SENZA SUGGERIMENTI [ALTO]

**Stato attuale**: La vista dettaglio campagna mostra struttura, budget, performance, ad copy — ma nessun suggerimento inline.

**Problema**: L'utente vede i dati ma non sa cosa migliorare senza lanciare l'evaluation.

**Proposta**:
- Quick-insight inline: "CTR sotto media settore (3.2% vs 5.1%)" evidenziato in amber
- CTA: "Lancia valutazione completa" se non ancora fatta

### C7. Tabelle Senza Sort Indicator [MEDIO]

**Stato attuale**: Alcune tabelle (search terms, campaign list) permettono sort cliccando l'header ma non mostrano quale colonna è attiva e in che direzione.

**Proposta**:
- Arrow icon su colonna attiva (già implementato in alcuni moduli, standardizzare ovunque)

### C8. Pagination Lontana dalla Tabella [MEDIO]

**Stato attuale**: La pagination è in fondo alla tabella. Con tabelle lunghe, l'utente deve scrollare fino in fondo.

**Proposta**:
- Aggiungere pagination anche sopra la tabella (o sticky bottom)
- Mostrare "Pagina X di Y" inline col search/filter

### C9. Empty State Variabili [MEDIO]

**Stato attuale**: La maggior parte dei moduli ha empty state con messaggio e CTA, ma alcuni mostrano solo tabella vuota senza guida.

**Proposta**:
- Standardizzare: tutti gli empty state devono avere icona + messaggio + CTA primaria
- Usare il componente `table-empty-state` ovunque

### C10. GSC Data — NESSUNA SPIEGAZIONE METRICHE [MEDIO]

**Stato attuale**: CTR, impressioni, posizione media mostrate senza spiegazione per utenti non tecnici.

**Proposta**:
- Tooltip (?) accanto a ogni metrica con definizione breve
- Es: "CTR — percentuale di persone che cliccano dopo aver visto il risultato"

### C11. Notification Dropdown — MAX 5, NO FILTER [BASSO]

**Stato attuale**: Il dropdown bell mostra max 5 notifiche recenti. Per il resto, link a `/notifications`.

**Proposta**:
- Mostrare 10 nel dropdown
- Quick-filter: "Solo azioni richieste" vs "Tutte"

### C12. Credit Cost Non Visibile Prima dell'Azione [BASSO]

**Stato attuale**: Alcuni moduli mostrano il costo in crediti prima dell'azione, altri no.

**Proposta**:
- Standardizzare: SEMPRE mostrare "Questa operazione costa X crediti" prima della conferma
- Badge costo accanto al bottone azione

### C13. Docs Search — "COMING SOON" [BASSO]

**Stato attuale**: La pagina docs ha un campo ricerca con placeholder "Coming soon" — disabilitato.

**Proposta**:
- Implementare o rimuovere. Un campo disabilitato dà impressione di prodotto incompleto.

---

## D. INCOERENZE CROSS-MODULO

### ~~D1. Landing Page Educativa~~ — GIÀ PRESENTI OVUNQUE [RIMOSSO]

**Verifica**: Tutti e 6 i moduli attivi hanno landing page educative in `views/projects/index.php` (non in `views/dashboard.php` come inizialmente cercato). Pattern "Scopri cosa puoi fare" + "Come funziona" + FAQ presente in tutti.

### D2. Dashboard Module vs Project Dashboard — CONFUSIONE LIVELLI [CRITICO]

**Stato attuale**: Ogni modulo ha un "Module Dashboard" (entry point globale) e un "Project Dashboard" (scoped al progetto). L'utente spesso non capisce su quale livello si trova.

**Problema**: "Dashboard" appare 2 volte nella sidebar dello stesso modulo con contenuti diversi.

**Proposta**:
- Module-level: rinominare "Panoramica modulo" o "Home [NomeModulo]"
- Project-level: mantenere "Dashboard"
- Breadcrumb sempre visibile: Modulo > Progetto > Sezione

### D3. Bulk Actions — PATTERN DIVERSI [ALTO]

**Stato attuale**:
- Content Creator: toolbar bulk fissa in alto
- SEO Audit: nessuna bulk action sulle issues
- AI Content articles: azioni singole per riga
- SEO Tracking keywords: checkbox + barra bulk

**Proposta**:
- Standardizzare il pattern `table-bulk-bar` ovunque ci sono liste
- Checkbox colonna → barra azioni contestuale (come seo-tracking)

### D4. Export — DISPONIBILE IN MODO INCONSISTENTE [ALTO]

**Stato attuale**: CSV export disponibile in audit issues e keyword results, ma non in campaign list, content creator results, o tracking keywords.

**Proposta**:
- Ogni tabella con 10+ righe deve avere bottone "Esporta CSV"
- Posizione standard: in alto a destra, accanto ai filtri

### D5. Modal vs Page — SCELTE INCONSISTENTI [MEDIO]

**Stato attuale**:
- Dettaglio issue audit → nuova pagina
- Dettaglio articolo → nuova pagina
- WordPress publish → modal
- Negative keyword apply → modal
- Cluster expand → accordion inline

**Proposta**:
- Regola: azioni brevi (conferma, selezione) = modal. Contenuti da leggere/analizzare = pagina.
- Uniformare

### D6. Period Selector — POSIZIONE E FORMATO DIVERSI [MEDIO]

**Stato attuale**:
- Ads analyzer: bottoni pill (7d/14d/30d) in alto a destra
- SEO Tracking trend: date picker con preset
- Audit history: nessun filtro periodo

**Proposta**:
- Componente shared `period-selector` con preset + custom range
- Posizione standard: sopra il contenuto, allineato a destra

### D7. Status Badge Colors — QUASI CONSISTENTI [MEDIO]

**Stato attuale**: La maggior parte usa emerald=successo, red=errore, amber=warning, blue=in progress. Ma ci sono eccezioni (es. "PAUSED" a volte grigio, a volte amber).

**Proposta**:
- Palette standard documentata e applicata ovunque
- Componente `status-badge($status, $module)` centralizzato

### D8. Form Validation — POST-SUBMIT OVUNQUE [MEDIO]

**Stato attuale**: Nessun modulo fa validazione client-side. Tutti validano su POST e mostrano errore dopo il submit.

**Proposta**:
- Aggiungere validazione inline (HTML5 required + pattern) per campi obbligatori
- Feedback real-time per email, URL, numeri

### D9. Loading/Skeleton States — MANCANTI IN AJAX [BASSO]

**Stato attuale**: Quando i dati si caricano via AJAX (es. period switch ads-analyzer), il contenuto scompare e riappare. Nessun skeleton loader.

**Proposta**:
- Skeleton loader (barre grigie animate) durante il caricamento AJAX
- Previene il layout shift

### D10. Mobile Table Overflow — NON GESTITO [BASSO]

**Stato attuale**: Le tabelle usano `w-full` ma con 6+ colonne su mobile diventano illeggibili. Nessun pattern di responsive table.

**Proposta**:
- Tabelle con scroll orizzontale (overflow-x-auto) + prima colonna sticky
- Oppure collapsible rows su mobile (card view)

---

*Fine audit UX/UI — 42 issue identificate*
