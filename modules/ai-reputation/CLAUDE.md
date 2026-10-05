# AI Reputation Radar — Istruzioni Claude (modulo `ai-reputation`)

> Caricato quando si lavora in questa cartella. Il CLAUDE.md root di Ainstein vale sempre
> (Golden Rules, pattern, comandi). Questo file aggiunge solo ciò che è specifico del modulo.
> Ultimo aggiornamento: 2026-10-05

## A inizio sessione (sempre)
1. Leggi `docs/TASKS.md` → di' a Clemente in 2 righe **dove siamo e il prossimo passo**.
2. Se è il primo giro sul progetto, leggi anche `docs/design.md` e `docs/decisions.md`.
3. Una cosa alla volta, un solo step per messaggio. Prima verifica online, poi costruisci.

## Contesto

| Aspetto | Dettaglio |
|---|---|
| **Cosa** | Monitoraggio reputazione/visibilità di un soggetto nelle risposte delle AI (ChatGPT, Perplexity, Gemini, Claude) con piano d'azione |
| **Per chi** | Tutela Digitale (agenzia reputazione, Bologna, CEO Gabriele), white-label per i loro clienti. Primo soggetto: Federico Marcaccini |
| **Slug / prefisso DB** | `ai-reputation` / `ar_` |
| **Branch** | `claude/ai-reputation-radar-dd9004` |
| **Milestone attiva** | M0 test empirici → M1 MVP per la call (settimana del 2026-10-06) |

## Regole specifiche del modulo

```
1. Collector FUORI da AiService, senza fallback   → ADR-002. Gli engine sono l'oggetto misurato.
2. Onboarding, prompt engine e judge via AiService → come ogni altro modulo (Golden Rule 1).
3. Nessun codice prima del test empirico online   → ADR-006. Docs + listino + script + JSON salvato.
4. Mai esporre il metodo nei report/demo           → solo output (regola commerciale di Gabriele).
5. Mai costi delle testate nel tool o nei report   → regola commerciale.
6. Judge sempre sullo stesso modello               → riproducibilità tra run.
7. Decisioni nuove → docs/decisions.md             → nella stessa mossa, formato ADR.
```

## 📁 Dove vanno i file

| Se produci… | Va in… | Nome |
|---|---|---|
| risultato grezzo di un test API | `docs/test-empirici/` | `AAAA-MM-GG-<engine>.json` + nota `AAAA-MM-GG-<engine>.md` |
| script di test/prova | `scripts/` | `test-engine-<engine>.php` |
| decisione architetturale | `docs/decisions.md` | ADR-NNN appesa in fondo |
| aggiornamento stato lavoro | `docs/TASKS.md` | sostituisci, non appendere |
| design / spec tecnica | `docs/design.md` | una versione sola, numerata in testa |
| migrazione DB | `database/` | `AAAA-MM-GG-<descrizione>.sql` |
| codice modulo | `controllers/`, `models/`, `services/`, `views/`, `routes.php`, `module.json` | pattern di `modules/ai-content/` |
| proposta economica, note call, email a Gabriele | **cartella gemella** (sotto) | `AAAA-MM-GG-<descrizione>` |

Regole di crescita: file nuovo di tipo non in tabella → resta in `docs/` col nome `AAAA-MM-GG-…`;
la seconda volta si crea la sottocartella e si aggiorna questa tabella. Nel dubbio `_INBOX/` del
repo. Mai file sciolti nella root del modulo.

## Cartella gemella (tutto ciò che non è codice)

`G:\Il mio Drive\1-CLIENTI\tutela-digitale\` → proposta economica, note della call, comunicazioni
con Gabriele. Da creare la sottocartella `ai-reputation-radar\` al primo file (non prima).
Dentro c'è già `marcaccini\` (storico digital PR per mese, **non toccare**) e `concetti\` (altro
cliente TD, Riccardo Concetti, **non c'entra** con questo progetto).

## Documenti di questo modulo

| File | Cosa |
|---|---|
| `docs/brief-2026-10-05.md` | brief di origine, integrale |
| `docs/design.md` | analisi + architettura + data model (bozza finché M0 non è chiuso) |
| `docs/decisions.md` | ADR-001..006 |
| `docs/roadmap.md` | M0 → M4 |
| `docs/TASKS.md` | stato e prossimo passo |
| `docs/test-empirici/` | risultati dei test API |

## Ambiente locale (XAMPP)

- **Si sviluppa qui, nel worktree** (branch `claude/ai-reputation-radar-dd9004`): la sessione Claude è
  legata a questa cartella e l'app blocca le modifiche al checkout principale.
- **XAMPP serve il checkout principale** `C:\xampp\htdocs\seo-toolkit` su `http://localhost/seo-toolkit`,
  che sta sul branch di servizio `serve-ai-reputation`. **Dopo ogni commit qui**, allineare il servito:
  ```
  git -C C:/xampp/htdocs/seo-toolkit merge --ff-only claude/ai-reputation-radar-dd9004
  ```
  Il branch Editorial (`feat/editorial-m1`) è intatto (avanzi del 2026-05-14 messi in un commit WIP).
- Il worktree non ha `vendor/` (lock non installabile) né `.env` (copiato a mano, gitignored): gli
  script CLI caricano l'autoloader del checkout principale. MySQL va acceso da XAMPP prima dei test.
- API key: in DB (`settings`), mai in file. Si incollano dal pannello `/admin/settings` (campi
  OpenAI, Anthropic, Gemini, Perplexity). Da terminale: `scripts/set-api-key.php <key_name>`.
