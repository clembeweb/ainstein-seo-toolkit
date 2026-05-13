# Milestones — Spec & Implementation Plans

> Per ogni milestone del MVP, qui trovi un file dedicato con:
> - **Spec tecnica** dettagliata (cosa, come, perché)
> - **Piano di lavoro** task-by-task (con effort, dipendenze, deliverable)
> - **Definition of Done** (criteri verificabili)
> - **Risk register** (cosa può andare storto + mitigazioni)
> - **Test plan** (come verifichiamo che funziona)

## Workflow

1. **Architetto (Claude)** scrive spec + plan completo in `M{N}-{nome}.md`
2. **Cliente (proprietario)** legge, valida o richiede modifiche
3. Una volta approvato → Claude esegue task in ordine
4. Ogni task completato → checkbox `[x]` + breve commit
5. Fine milestone → `/editorial-save` consolidato + summary all'utente
6. Si passa a milestone successiva

## Milestones MVP

| # | File | Stato | Effort stimato | Dipendenze |
|---|------|-------|----------------|------------|
| M1 | [`M1-foundation.md`](M1-foundation.md) | 📋 Spec scritta, attende approvazione | 35-50 ore (1.5 settimane) | — |
| M2 | M2-content-brain.md | ⏳ Da scrivere dopo M1 | ~25-35 ore | M1 completata |
| M3 | M3-article-generation.md | ⏳ Da scrivere dopo M2 | ~50-70 ore | M2 completata |
| M4 | M4-keyword-research-autopilot.md | ⏳ Da scrivere dopo M3 | ~50-70 ore | M3 completata |
| M5 | M5-internal-linking-extras.md | ⏳ Da scrivere dopo M4 | ~30-40 ore | M4 completata |
| M6 | M6-polish-launch.md | ⏳ Da scrivere dopo M5 | ~20-30 ore | M5 completata |

**Totale effort stimato**: 210-295 ore = **5-7 settimane full-time** OR **14-20 settimane part-time** (15 ore/settimana).

## Convenzioni

### File naming
`M{N}-{nome-snake-kebab}.md` — esempio `M1-foundation.md`, `M3-article-generation.md`.

### Sezioni obbligatorie in ogni spec
1. Overview (cosa fa, perché)
2. Spec tecnica dettagliata
3. Out of scope (cosa NON fa)
4. Piano di lavoro (task con id `M{N}.{T}`)
5. Definition of Done
6. Risk register
7. Test plan
8. Effort totale stimato

### Task ID format
`M{N}.{T}` esempio `M1.3` = milestone 1, task 3.
Sotto-task: `M1.3.a`, `M1.3.b` (massimo 2 livelli, no ulteriore nesting).

### Status progress
- `[ ]` Pending
- `[/]` In progress (da aggiungere quando si inizia)
- `[x]` Completed
- `[!]` Blocked (con nota inline `[!] BLOCKED: motivo`)
