---
name: platform-standards
description: "Pattern UI/UX e coding standard verificati della piattaforma Ainstein. Usata dagli agenti QA per verificare conformita. Include credenziali test e rubrica scoring."
---

## Credenziali Test

- **URL**: https://ainstein.it
- **Email**: admin@seo-toolkit.local
- **Password**: admin123

## Standard CSS Tabelle

{Da popolare con /qa-pattern-sync}

## Componenti Shared

{Da popolare con /qa-pattern-sync}

## Pattern Landing Page

{Da popolare con /qa-pattern-sync}

## Pattern Controller

{Da popolare con /qa-pattern-sync}

## Icone

{Da popolare con /qa-pattern-sync}

## Lingua UI

{Da popolare con /qa-pattern-sync}

## Rubrica Scoring

Ogni agente valuta l'area con 5 criteri (scala 1-10):

| Criterio | Peso | Descrizione |
|----------|------|-------------|
| UX Flow | 25% | Flussi intuitivi, l'utente sa cosa fare |
| UI Polish | 20% | Estetica, coerenza, responsive |
| Pattern Compliance | 20% | Conformita standard piattaforma |
| Funzionalita | 20% | I workflow completano senza errori |
| Valore Aggiunto | 15% | Il tool da davvero valore vs alternative |

Score finale = media pesata. Scala interpretativa:
- 9-10: Eccellente, pronto per il mercato
- 7-8: Buono, issue minori
- 5-6: Sufficiente, serve lavoro
- 3-4: Insufficiente, problemi gravi
- 1-2: Inutilizzabile

## Output Format

Ogni agente QA deve produrre un file con questa struttura esatta:

# QA Review — {Area}
Persona: {Nome}, {Ruolo} | Data: {YYYY-MM-DD}
Ambiente: https://ainstein.it

## Scoring

| Criterio | Voto | Note |
|----------|------|------|
| UX Flow | X/10 | ... |
| UI Polish | X/10 | ... |
| Pattern Compliance | X/10 | ... |
| Funzionalita | X/10 | ... |
| Valore Aggiunto | X/10 | ... |
| **Score Finale** | **X.X/10** | |

## Sommario
Critici: N | Alti: N | Medi: N | Bassi: N

## Giudizio Professionale
{2-3 paragrafi dal punto di vista della persona}

## Issues

### [CRITICO] #1 — {Titolo}
- **Tipo**: UX | UI | Funzionale | Pattern | Performance
- **Pagina**: {URL relativo}
- **Screenshot**: screenshots/{area}-{pagina}.png
- **Problema**: {descrizione dal punto di vista dell'utente}
- **File coinvolti**: `{path}:{righe}`
- **Fix proposto**: {cosa fare concretamente}
- **Pattern violato**: {Golden Rule / standard, oppure "Nessuno"}
- [ ] Da eseguire

### [ALTO] #2 — ...

## Sezioni Non Testate
{Lista di pagine/workflow non testati con motivo}

## Nota per l'Esecuzione
{Dipendenze tra fix, ordine suggerito, rischi}

## Politica Dati Produzione

| Azione | Permessa |
|--------|----------|
| Navigare pagine esistenti | Si |
| Aprire progetti esistenti | Si |
| Cliccare bottoni di navigazione | Si |
| Compilare form senza submit | Si |
| Testare export (CSV/PDF) | Si |
| Creare nuovi progetti | NO |
| Lanciare crawl/sync/analisi | NO |
| Modificare dati esistenti | NO |
| Cancellare dati | NO |

## Error Handling

Se un agente incontra un errore:
1. Logga l'errore con contesto (step, pagina, azione tentata)
2. Continua con lo step/pagina/workflow successivo
3. Marca le sezioni non testate come [NON TESTATO] — {motivo}
4. Scrivi il report comunque (anche se parziale)

Se non ci sono progetti con dati per un modulo:
1. Segnala "Nessun progetto con dati trovato"
2. Valuta solo pagine raggiungibili (landing, empty states, form UX)
3. Scoring parziale con nota "Score basato su analisi limitata"
