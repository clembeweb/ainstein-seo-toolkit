# Decision Log — AI Reputation Radar

> ADR semplificati, in ordine cronologico. Status: Proposed · Accepted · Superseded.
> Ogni decisione presa in sessione va aggiunta qui, nella stessa mossa.

---

## ADR-001: Modulo Ainstein, non applicazione separata

**Date**: 2026-10-05 · **Status**: Accepted

**Context**: il brief chiedeva di innestare il tool su un'app esistente con AI integrata. Ainstein
ha già multi-tenant, white-label, notifiche, cron, fetcher, log API, crediti e UI standard.

**Decision**: modulo `ai-reputation`, prefisso DB `ar_`, directory `modules/ai-reputation/`,
collegato a Global Projects come gli altri moduli. Working title: AI Reputation Radar.

**Alternatives**: worker CLI standalone + report HTML (più veloce per la call, ma da rifare dopo);
prodotto separato come Editorial (il cliente è un'agenzia che usa un cruscotto, non un sito WP).

**Consequences**: la demo per la call è già il prodotto; valgono le Golden Rules di Ainstein.

## ADR-002: Il collector NON passa da AiService e NON ha fallback

**Date**: 2026-10-05 · **Status**: Accepted

**Context**: Golden Rule 1 impone AiService per ogni chiamata AI. Qui però gli engine (OpenAI,
Perplexity, Gemini, Claude) sono **l'oggetto misurato**, non il cervello dell'app. AiService parla
solo chat completions senza web search e fa fallback automatico tra provider.

**Decision**: servizio dedicato `AiEngineCollectorService` con un adapter per engine, endpoint
nativi con web search, zero fallback (un errore è un dato), log via `ApiLoggerService`.
Prompt engine, onboarding e analyzer usano AiService normalmente.

**Consequences**: eccezione documentata alla Golden Rule 1 (da scrivere in `docs/GOLDEN-RULES.md`
quando il modulo entra in main); due API key nuove in admin (Perplexity, Gemini testuale).

## ADR-003: Onboarding automatico, zero campi obbligatori, competitor come output

**Date**: 2026-10-05 · **Status**: Accepted (decisione di Clemente nel brief)

**Decision**: input = solo il nome; un agente compila la bozza di profilo; conferma riga per riga
(✅/❌/✏️); solo i confermati sono "verità del brand"; i ❌ diventano errori da monitorare.
I competitor emergono dalle risposte, aggiungibili a mano dopo.

**Consequences**: tabella di fatti con stato (`ar_profile_facts`), non un record con 20 colonne.

## ADR-004: Scope demo per la call con Tutela Digitale

**Date**: 2026-10-05 · **Status**: Accepted

**Decision**: solo Marcaccini (niente secondo soggetto, per ora); 2 engine (OpenAI + Perplexity),
1 repeat; una sola vista "report run" con metriche base e piano d'azione. Fuori: dashboard trend,
scheduling, diff, alert, PDF, Gemini/Anthropic.

**Context**: call di 30 min la settimana del 2026-10-06; mostrare output, non metodo.

**Consequences**: la demo non mostra il lato "rimozione" su dati reali (Marcaccini è pulito):
argomenti da usare sono storico, diff e scala.

## ADR-005: La misura è "via API" e viene dichiarata come tale

**Date**: 2026-10-05 · **Status**: Accepted

**Context**: le risposte via API non coincidono con ChatGPT/Gemini consumer (memoria,
personalizzazione, system prompt del prodotto). Gabriele potrebbe confrontare a mano.

**Decision**: ogni report dichiara "misura riproducibile via API, stesso modello e stesse
condizioni a ogni run". Niente scraping dei prodotti consumer.

**Consequences**: confrontabilità nel tempo (il vero valore); una frase pronta per la call.

## ADR-006: Semplice e funzionante; verifica online prima di costruire

**Date**: 2026-10-05 · **Status**: Accepted (regola di Clemente)

**Decision**: nessun codice di modulo prima dei test empirici. Per ogni engine: (1) leggere online
la documentazione e il listino attuali, (2) uno script CLI minimo che salva la risposta grezza in
`docs/test-empirici/`, (3) aggiornare `design.md`. Si sceglie sempre la soluzione più semplice che
funziona; se esiste già un servizio o un'API che fa la cosa, si usa quello invece di costruirlo.

**Consequences**: `design.md` resta bozza finché M0 non è chiuso; ogni ⚠️ nel design è un test da fare.

## ADR-007: Il piano d'azione include il "contro-contenuto" reputazionale

**Date**: 2026-10-05 · **Status**: Accepted (intuizione di Clemente dopo il test OpenAI)

**Context**: la SERP di Google su Marcaccini è piena di articoli sponsorizzati, ma alla domanda
"è affidabile?" l'AI fa una ricerca mirata ("sequestro beni … confisca") e pesca **un documento su
cento** (III Rapporto Mafie Lazio). Il volume di articoli non protegge dalla domanda reputazionale:
serve un contenuto che **risponda a quella domanda**.

**Decision**: quarto tipo di azione `counter_content`: quando un prompt del cluster `rep` produce
una risposta negativa o ambigua (omonimia), il piano propone un articolo che risponde direttamente
alla domanda (es. "Chi è Federico Marcaccini: profilo, attività, affidabilità"), su una testata che
le AI citano (Source Map). Si affianca a `removal` (TD) e vale sia se la fonte è un omonimo sia se no.

**Consequences**: `ar_actions.type` ∈ {removal, gap_article, correction, counter_content};
il cluster `rep` è quello che genera più valore commerciale per entrambi (TD e Clemente).

## ADR-008: Omonimi dichiarati dall'utente in onboarding; omonimie emerse dai run si confermano, non si deducono

**Date**: 2026-10-05 · **Status**: Accepted (decisione di Clemente dopo il test Perplexity)

**Context**: tre engine su quattro attribuiscono a Marcaccini la confisca del 2013 (Il Tempo,
dirittiglobali, rapporto mafie Lazio). Le AI non stabiliscono se sia la stessa persona: o gliela
attribuiscono o la mettono accanto al nome. Noi non possiamo deciderlo dai dati, e non dobbiamo:
**l'omonimia la conferma l'utente**, non il tool. (Per Marcaccini Clemente ha confermato il 2026-10-05:
nessun omonimo, dovrebbe essere lui.)

**Decision**:
1. In onboarding un **campo libero** "Omonimi e soggetti da non confondere" (persone **e aziende**:
   es. "Marcaccini S.r.l. di Ancona non c'entra", "Federica Marcaccini è un'altra persona"). Facoltativo,
   coerente con ADR-003 (zero campi obbligatori). Va nel prompt dell'onboarding agent e del judge.
2. Quando un run fa emergere una **possibile omonimia non dichiarata** (judge: `is_homonym = uncertain`
   o fonte che descrive il soggetto in modo incompatibile col profilo confermato), il tool **non decide**:
   la segnala come riga `ar_profile_facts` di categoria `homonym` con stato `proposed` e chiede conferma
   all'utente (✅ è lui / ❌ è un altro). Finché non è confermata, l'analisi la tratta come "attribuita
   dalle AI", non come vera né come falsa, e il report la mostra in una sezione "Da confermare".
3. MVP call: solo il campo (1) e la segnalazione (2) in report. Il blocco del run in attesa di conferma
   è v1, non MVP.

**Consequences**: `ar_projects.disambiguation_notes` (TEXT); categoria `homonym` già prevista in
`ar_profile_facts`; `ar_analyses.is_homonym` diventa enum `no|yes|uncertain`; il piano d'azione
distingue `counter_content` di disambiguazione (omonimo confermato) da gestione fonte negativa (non omonimo).

## ADR-009: Engine dell'MVP = OpenAI + Gemini; si costruisce per fette verticali

**Date**: 2026-10-05 · **Status**: Accepted (decisione di Clemente dopo M0)

**Context**: i 4 engine sono testati e funzionano. Per peso reale sugli utenti italiani contano
ChatGPT e poi Gemini (è dentro la ricerca Google); Claude ha pubblico piccolo e costo alto; Perplexity
quasi nessuno la usa, ma costa 0,0014 $ a domanda e ha trovato le fonti negative peggiori.

**Decision**:
1. Engine dell'MVP: **OpenAI (`gpt-5-mini`) + Gemini (`gemini-3.8-flash`)**. Perplexity `fast` resta
   attiva come terzo occhio a **peso basso** (costo nullo, utile per le fonti), dichiarata come tale nel
   report. Anthropic in v1 (adapter pronto nello script).
2. Ordine di costruzione per **fette verticali**, per vedere qualcosa prima:
   - fetta 1: migrazione + modulo + collector con prompt scritti a mano + pagina report minima (3-4 h);
   - fetta 2: judge + metriche + piano d'azione (3-4 h);
   - fetta 3: onboarding con campo omonimi + prompt engine (4-5 h).
   Sostituisce la sequenza di `design.md` §7 (che resta valida come elenco, non come ordine).

**Consequences**: `ar_projects.engines` default `["openai","gemini","perplexity"]` con peso per engine
in `module.json` (openai 1, gemini 1, perplexity 0.3) usato dalle metriche aggregate; i prompt della
fetta 1 sono righe `ar_prompts` con `origin = manual`.

## ADR-010: Il "blocco" su ChatGPT non vale per l'API: il report misura due cose diverse e lo dice

**Date**: 2026-10-06 · **Status**: Accepted (dopo il messaggio di Gabriele "abbiamo bloccato la query su ChatGPT")

**Context**: Tutela Digitale ha ottenuto che ChatGPT (app consumer) non risponda più su Marcaccini, presumibilmente
con una richiesta privacy/diritto all'oblio a OpenAI (privacy.openai.com, "Remove my personal data from ChatGPT
responses"). Test del 2026-10-06 sull'API Responses (`gpt-5-mini` + web_search): risponde ancora, con fonti, a
"Chi è Federico Marcaccini?". Le richieste di rimozione OpenAI sono documentate per le risposte di ChatGPT; il
filtro non risulta applicato all'API.

**Decision**: il tool continua a misurare via API (ADR-005), ma il report lo dichiara in chiaro: "ChatGPT (API)"
non è "ChatGPT app". Per i soggetti con rimozione ottenuta, un controllo manuale sull'app resta il dato di verità
per quel canale. Il blocco è anche un argomento commerciale: blocca un canale, non l'informazione (le stesse fonti
restano leggibili da API, Gemini, Perplexity e da chiunque costruisca su quei modelli).

**Consequences**: etichetta engine "ChatGPT (API)" nei report; campo futuro per segnare "rimozione ottenuta su
app" per engine (v1). Test di riferimento: `scratchpad` 2026-10-06, nessun costo rilevante.
