# AI Keywords: nuovo ingresso dei 3 strumenti — design

> 2026-10-16. Richiesta di Clemente durante il test in prod: "partire da una kw iniziale non va bene, e se l'utente non
> ne ha idea? Lascerei solo descrizione libera oppure sito web; se c'è il profilo del GEO Audit importiamolo da lì.
> Vale per tutte e 3 le modalità." Più i 2 P2 del giro manuale (`docs/2026-10-16-test-manuali.md`): vedere le keyword
> raccolte prima del filtro AI; "e adesso?" dopo i risultati. Clemente: "fai tutto tu".

## Prova fatta prima di decidere (prod, 0 crediti, Keyword Planner)

Su Stabia Boat Rental: Google "partendo dal sito" (urlSeed/siteSeed) restituisce 1.000 ricerche generiche e in inglese
("amalfi map", "positano amalfi coast"): inutili. Con 5 parole del tipo "noleggio barche castellammare di stabia",
"tour privato in barca capri" escono 195 ricerche pertinenti in italiano. Seed+URL insieme: solo 5 risultati.
**Decisione: niente seme dal sito; le parole di partenza le propone l'AI dal profilo, i volumi li dà Google.**

## Come funziona

1. **Profilo dell'attività, uno per progetto.** Testo di 5-8 righe (cosa fa, dove, per chi, servizi). Si salva nella
   descrizione del progetto globale (`projects.description`, oggi vuota ovunque), così servirà anche agli altri moduli;
   per un progetto AI Keywords senza progetto globale va in `kr_projects.description`.
   La prima volta, nel riquadro "La tua attività" in cima ai 3 strumenti, si sceglie da dove partire:
   - **dal GEO Audit** se il progetto ha un profilo con informazioni confermate: le informazioni (identità, attività,
     fatti) vengono riassunte nel profilo;
   - **dal sito** se il progetto ha un dominio: leggiamo la home + fino a 4 pagine interne (SafeScraper) e l'AI scrive
     il profilo;
   - **scrivo io**: testo libero.
   Il profilo si vede sempre e si può modificare. Gratis (chiamate AI con `charge_credits => false`).
2. **Proposte dell'AI dal profilo** (una chiamata, salvata in `kr_projects.suggestions` JSON, rifatta solo se il profilo
   cambia): 5 parole di partenza, 5 categorie per il blog, tipo di sito, target. Gratis.
3. **Ricerca keyword:** passo 1 = profilo + "Cosa cerchiamo su Google" (le 5 parole proposte, si possono togliere o
   aggiungere) + paese + parole da escludere (facoltativo). Spariscono descrizione, target, obiettivo.
   Passo 2 = raccolta (gratis) e **elenco delle keyword trovate** con volume e concorrenza, tutte spuntate: si tolgono
   quelle che non servono e poi "Raggruppa con AI" (3 crediti). Solo le spuntate vanno all'AI.
4. **Architettura sito:** passo 1 = profilo + parole proposte + paese. Tipo di sito e target li prende dalle proposte.
5. **Piano editoriale:** passo 1 = profilo + categorie proposte (o le pagine dell'Architettura, come oggi) + mesi e
   articoli al mese. Il "tema del blog" è il profilo.
6. **Risultati della Ricerca, "E adesso?":** "Monitora in AI Rank" manda la keyword principale dei gruppi scelti al
   progetto AI Rank dello stesso sito (un gruppo AI Rank per gruppo di keyword); "Scrivi con AI Writer" le aggiunge
   alle keyword del progetto AI Writer (articoli) dello stesso sito, pronte da scrivere (nessun addebito automatico).
   Se il modulo non è attivo sul progetto: link per attivarlo.
7. Testi P3 di AI Keywords: numeri all'italiana, niente "API: 1.0s | AI: 12.2s", "Location", "competition",
   concorrenza in italiano, nomi dei passi senza inglese.

## Lasciato fuori (YAGNI)

- Google "dal sito" (bocciato dalla prova).
- Riuso della raccolta della Ricerca in Architettura/Piano: il Piano raccoglie per categorie con le SERP, il guadagno
  è piccolo.
- Ricerche di Search Console come parole di partenza: solo per chi ha AI Rank collegato; dopo il lancio.
- Raggruppamento AI (keyword in target escluse, gruppi simili): Clemente "per adesso non tocchiamo"; il profilo vero
  al posto di due righe dovrebbe già aiutare, da verificare su Stabia.

## Dati

- `kr_projects.suggestions JSON NULL` (nuova colonna; migrazione in `modules/keyword-research/database/`).
- Nessuna tabella nuova. `projects.description` esistente.
