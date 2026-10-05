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
