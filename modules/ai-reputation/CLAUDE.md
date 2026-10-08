# AI Reputation Radar — Istruzioni Claude (modulo `ai-reputation`)

> Caricato quando si lavora in questa cartella. Il CLAUDE.md root di Ainstein vale sempre
> (Golden Rules, pattern, comandi). Questo file aggiunge solo ciò che è specifico del modulo.
> Ultimo aggiornamento: 2026-10-08

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
| **Milestone attiva** | M1 MVP chiuso in locale (run 4 = demo) → revisione Clemente, poi call con Gabriele e messa online |

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
| `docs/design.md` | analisi + architettura + data model (v0.2, validato dai test empirici) |
| `docs/decisions.md` | ADR-001..013 |
| `docs/roadmap.md` | M0 → M4 |
| `docs/TASKS.md` | stato e prossimo passo |
| `docs/test-empirici/` | risultati dei test API |

## Ambiente locale (XAMPP)

- **Si lavora nel checkout principale `C:\xampp\htdocs\seo-toolkit`**, branch
  `claude/ai-reputation-radar-dd9004`, servito da XAMPP su `http://localhost/seo-toolkit`.
  **Niente worktree** (deciso da Clemente il 2026-10-05: complicavano e basta). Il worktree
  `.claude/worktrees/ai-reputation-radar-dd9004/` della prima sessione è a HEAD staccato: si può
  cancellare con `git worktree remove --force .claude/worktrees/ai-reputation-radar-dd9004`.
- Il branch Editorial (`feat/editorial-m1`) è intatto (avanzi del 2026-05-14 messi in un commit WIP).
- MySQL va acceso da XAMPP prima dei test. Gli script in `scripts/` girano con `php` dalla root del repo.
- API key: in DB (`settings`), mai in file. Si incollano dal pannello `/admin/settings` (campi
  OpenAI, Anthropic, Gemini, Perplexity). Mai chiederle in chat, mai inserirle al posto di Clemente.
- Postazione LaptopoClem (Laragon): si lavora nel worktree `.claude/worktrees/ai-reputation-m0-3-69e6ee` sul branch del modulo; per vedere le modifiche su `http://localhost/seo-toolkit` si lancia `bash modules/ai-reputation/scripts/sync-to-main.sh` dopo il commit (il checkout principale è sul branch Editorial).
