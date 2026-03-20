# Sidebar Redesign — Design Document

> Data: 2026-03-04 | Stato: Approvato

## Obiettivo

Riorganizzare la sidebar di Ainstein raggruppando 9 moduli in 4 macro-aree stile Semrush, con accordion collassabili, rinominando i moduli con nomi piu coerenti e branding "AI" nelle macro-aree.

## Struttura Finale

```
Dashboard                          (invariato)
Progetti                           (invariato)

v AI SEO                           (accordion, auto-expand se modulo attivo)
    Rank Tracker                   (era: SEO Tracking, slug invariato)
    Keyword Research               (era: AI Keyword Research, slug invariato)
    On-Page                        (era: SEO On-Page, slug invariato)

> AI CONTENT                       (collassato di default)
    Writer                         (era: AI Content Generator, slug invariato)
    Publisher                      (era: Content Creator, slug invariato)
    Content Optimizer              (era: AI Optimizer, slug invariato)

> AI AUDIT                         (collassato di default)
    Site Audit                     (era: SEO Audit, slug invariato)
    Internal Linking               (era: Internal Links, slug invariato)

> AI ADS                           (collassato di default)
    Ads Analyzer                   (era: Google Ads Analyzer, slug invariato)

--- solo admin ---

v UTENTI & PIANI
    Overview
    Utenti
    Piani
    Finance

> CONFIGURAZIONE
    Moduli
    Impostazioni
    Template Email

> MONITORAGGIO
    AI Logs
    API Logs
    Jobs Monitor
    Cache & Log
```

**Nota**: Crawl Budget Optimizer (slug `crawl-budget`) e stato mergiato in SEO Audit ed e legacy — NON incluso nei gruppi sidebar.

## Mapping Moduli -> Macro-Aree

| Macro-Area | Slug Modulo | Display Name | Route (invariata) |
|------------|-------------|--------------|-------------------|
| AI SEO | seo-tracking | Rank Tracker | /seo-tracking |
| AI SEO | keyword-research | Keyword Research | /keyword-research |
| AI SEO | seo-onpage | On-Page | /seo-onpage |
| AI CONTENT | ai-content | Writer | /ai-content |
| AI CONTENT | content-creator | Publisher | /content-creator |
| AI CONTENT | ai-optimizer | Content Optimizer | /ai-optimizer |
| AI AUDIT | seo-audit | Site Audit | /seo-audit |
| AI AUDIT | internal-links | Internal Linking | /internal-links |
| AI ADS | ads-analyzer | Ads Analyzer | /ads-analyzer |

## Rinominazione Moduli

Il rename dei display name viene fatto manualmente dall'admin via pannello Admin > Moduli.

| Slug | Nome Consigliato |
|------|-----------------|
| `seo-tracking` | Rank Tracker |
| `keyword-research` | Keyword Research |
| `seo-onpage` | On-Page |
| `ai-content` | Writer |
| `content-creator` | Publisher |
| `ai-optimizer` | Content Optimizer |
| `seo-audit` | Site Audit |
| `internal-links` | Internal Linking |
| `ads-analyzer` | Ads Analyzer |

## Comportamento Accordion

1. **Auto-expand**: la macro-area che contiene il modulo attivo (match su `$currentPath`) si apre automaticamente
2. **Memoria stato**: `localStorage.getItem('sidebarGroups')` salva JSON con stato open/closed per ogni gruppo
3. **Click header**: toggle expand/collapse con animazione Alpine.js (identica all'attuale)
4. **Sub-nav progetto**: INVARIATA — quando si entra in un progetto, il sotto-menu si espande sotto il modulo come ora

## Visual Design Macro-Area Header

```html
<!-- Esempio header macro-area -->
<button @click="toggleGroup('ai-seo')"
        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors">
    <span class="flex items-center gap-2">
        <svg class="w-4 h-4"><!-- icona gruppo --></svg>
        AI SEO
    </span>
    <svg class="w-3.5 h-3.5 transition-transform" :class="isGroupOpen('ai-seo') && 'rotate-180'">
        <!-- chevron -->
    </svg>
</button>
```

Stile coerente con l'header "Moduli" e "Amministrazione" attuale, ma cliccabile e con chevron.

## Icone Macro-Aree (Heroicons)

| Gruppo | Icona |
|--------|-------|
| AI SEO | chart-bar-square (metriche/ranking) |
| AI CONTENT | document-text (contenuti) |
| AI AUDIT | shield-check (audit/sicurezza) |
| AI ADS | chart-pie (advertising analytics) |
| UTENTI & PIANI | user-group |
| CONFIGURAZIONE | cog-6-tooth |
| MONITORAGGIO | computer-desktop |

## Cosa NON Cambia

- **Route**: tutte invariate (/seo-tracking, /ai-content, etc.)
- **Slug moduli**: invariati
- **Sub-navigazione progetto**: identica, con tutti gli accordion modulo-specifici
- **Shared access filtering**: resta, applicato dentro ogni macro-area
- **Tour buttons**: restano accanto ai moduli
- **Footer utente**: invariato
- **Top-bar**: invariata
- **Sidebar.php**: nessuna modifica
- **Controller/Model**: nessuna modifica

## File Impattati

| File | Tipo Modifica |
|------|---------------|
| `shared/views/components/nav-items.php` | Refactor: wrap moduli in accordion groups |
| `shared/views/layout.php` | Aggiunta Alpine.js state per accordion gruppi |

## Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Utenti persi dopo rename | I moduli restano nella stessa posizione relativa |
| nav-items.php lungo e fragile | Refactor con array config per i gruppi, loop unico |
| Sub-nav progetto si rompe | Non tocchiamo la logica sub-nav, solo il wrapper esterno |
| localStorage non disponibile | Fallback: tutti i gruppi aperti (degradazione graziosa) |

## Testing

1. Verificare che ogni modulo sia raggiungibile dalla sidebar
2. Verificare auto-expand quando si naviga direttamente a un URL modulo
3. Verificare sub-nav progetto per ogni modulo con accordion
4. Verificare shared access filtering (utente non-owner vede solo moduli autorizzati)
5. Verificare mobile sidebar
6. Verificare stato persistito in localStorage
7. Verificare admin vede i 3 gruppi admin
