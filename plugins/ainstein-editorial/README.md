# Ainstein Editorial

> _Il tuo SEO Pro autonomo per WordPress. Trova le keyword, scrive gli articoli, fa internal linking, pubblica. Tutto da solo._

**Stato**: Pre-development (design phase) · **Data inizio progetto**: 2026-05-12

---

## In una frase

Plugin WordPress che esegue end-to-end il lavoro di content marketing SEO di un blog/magazine in modalità auto-pilot: keyword research → calendario editoriale → scrittura articoli SERP-driven → internal linking automatico → meta-tag → cover image → pubblicazione bozza in WP.

## Per chi

**ICP primario** — Piccoli imprenditori e blogger su WordPress che:
- Hanno un blog/magazine come canale di acquisizione clienti
- Non sanno (o non vogliono imparare) la SEO tecnica
- Non possono permettersi un SEO consultant (€500-2000/mese in Italia)
- Vogliono un sistema che funzioni "in autonomia" senza supervisione tecnica

**ICP secondario** — Web agency e freelancer SEO che gestiscono blog di 5-50 clienti e vogliono automatizzare la parte content del workflow.

## Il "quid": cosa lo rende diverso dai competitor

A differenza di RankMath AI, AIOSEO AI, Jasper for WP — tutti tool che si limitano a "scrivimi un articolo da questa keyword" — Ainstein Editorial è un **sistema closed-loop completo**:

1. **Content Brain**: scansiona il sito esistente e impara brand voice, glossario, audience, editorial guidelines → ogni articolo generato è coerente con la voce del sito.
2. **AI Keyword Research integrato**: l'utente non deve sapere cosa è una keyword — l'AI propone calendario editoriale di 30 idee partendo dal tema sito.
3. **Auto-pilot vero**: cron settimanale genera articolo + lo collega via internal linking (bidirezionale) ad articoli esistenti.
4. **Output professionale di default**: meta-tag SEO, cover image AI, schema markup, internal linking — tutto incluso, non add-on a parte.

**Nessun altro plugin WP fa questo workflow end-to-end integrato.**

## Pricing (sintesi)

| Tier | Prezzo | Articoli/mese | Siti | Per chi |
|------|--------|---------------|------|---------|
| Starter | €29/mo | 4 | 1 | Blogger solo |
| **Pro** | €69/mo | 12 | 3 | PMI con blog attivo |
| Business | €149/mo | 30 + GSC | 10 | PMI serie + freelance SEO |
| Agency | €349/mo | 100 + white-label | 50 | Web agency |

Top-up packs disponibili. Billing annuale -20%. Dettagli in `docs/design.md`.

**Tagline marketing**: _"Il tuo SEO Pro autonomo, a meno di un caffè al giorno"_ (riferito al tier Starter €29 = €0.97/giorno).

## House of brands

Il plugin è il **primo prodotto** della famiglia di plugin WordPress di Ainstein. Nel tempo seguiranno altri prodotti verticali (es. plugin per image generation, per SEO tracking, per ads optimization), ognuno con suo brand consumer ma tutti "Made by Ainstein".

L'utente del plugin **NON vede mai Ainstein come URL operativo** (zero registrazione su ainstein.it, zero login Ainstein). Il backend è invisibile. "An Ainstein product" appare solo nel footer.

## Come navigare questa documentazione

Leggi nell'ordine:

1. **README.md** (questo file) → overview strategica
2. **CLAUDE.md** → istruzioni operative per Claude (se lavori con AI assistant)
3. **docs/design.md** → architettura tecnica completa (cosa costruiamo e come)
4. **docs/roadmap.md** → fasi, milestone settimanali, validation plan, definition of done
5. **docs/decisions.md** → cronologia decisioni prese (e perché) — leggi se ti chiedi "perché abbiamo scelto X invece di Y"

## Stato attuale e prossimi step

- ✅ Brainstorming completato (2026-05-12)
- ✅ Vision + design + roadmap documentati
- 🔵 Validation pre-development → spostata a launch time (ADR-023, build-first)
- ⏳ **IN CORSO**: MVP — Milestone M1 Foundation (backend API + plugin shell + license)
- ⏳ Soft launch closed beta
- ⏳ Public launch

## Build & distribuzione

Genera lo zip installabile del plugin:

```bash
cd plugins/ainstein-editorial
./build.sh
# → dist/ainstein-editorial-v{X.Y.Z}.zip
```

Lo script legge la versione dall'header di `src/plugin.php`, esegue `composer install --no-dev`,
unisce `src/` + `assets/` (+ `languages/` da M5) e produce lo zip con root `ainstein-editorial/`.
Esclude `tests/`, `docs/`, `node_modules/`, `.env*`. Installa lo zip da WP Admin → Plugin → Aggiungi nuovo → Carica.

**Sviluppo locale**: in `wp-config.php` definisci `define('AIED_API_BASE', 'http://localhost/seo-toolkit/api/editorial/v1');`
per puntare al backend locale invece che alla produzione.

## Repository structure

Il plugin vive come **sub-progetto monorepo** dentro `seo-toolkit/` (codebase Ainstein). Backend condiviso con Ainstein (tabelle namespacate `aied_*`, API endpoint dedicati `/api/editorial/*`). Plugin distribuibile via `build.sh` che zippa solo `plugins/ainstein-editorial/` con dipendenze.

Quando il prodotto sarà maturo e/o si vorrà venderlo come asset separato, l'estrazione in repo standalone è una operazione di mezza giornata (cartella già self-contained).
