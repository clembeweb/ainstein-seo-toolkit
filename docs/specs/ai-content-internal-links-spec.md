# AI Content - Feature Link Interni Automatici

**Data:** 2026-01-28  
**Modulo:** `ai-content`  
**Priorità:** Alta

---

## OBIETTIVO

Aggiungere inserimento automatico di link interni negli articoli generati. L'utente importa URL dal proprio sito (via sitemap), il sistema estrae title/description, e l'AI usa questi dati per inserire link contestuali durante la generazione.

---

## PRIMA DI INIZIARE (OBBLIGATORIO)

### 1. Backup

```bash
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it

# Backup modulo
cp -r ~/www/ainstein.it/public_html/modules/ai-content ~/www/ainstein.it/public_html/modules/ai-content_backup_$(date +%Y%m%d)

# Backup DB
mysqldump -u u6iaaermphtha -p dbj0xoiwysdlk1 --tables aic_keywords aic_serp_results aic_paa_questions aic_sources aic_articles aic_wp_sites aic_wp_publish_log > ~/aic_backup_$(date +%Y%m%d).sql
```

### 2. Analisi Stato Attuale

Prima di scrivere codice, analizza:

1. **Struttura ai-content** - Come sono organizzati controller, model, views, services
2. **SitemapService.php** - Già gestisce sitemap annidate (Yoast). RIUSALO, non ricreare
3. **ScraperService.php** - Per fetch title/description. RIUSALO
4. **ArticleGeneratorService.php** - Dove integrare il pool link nel prompt AI
5. **Pattern UI esistente** - Come sono strutturate le altre tab/views del modulo

### 3. Leggi Documentazione

```
docs/GOLDEN-RULES.md
docs/PLATFORM_STANDARDS.md  
docs/IMPORT_STANDARDS.md
```

---

## APPROCCIO: ORCHESTRATORE + AGENTI

Questa feature è complessa. Usa approccio multi-agente:

### Orchestratore
Coordina l'implementazione in fasi, verifica completamento ogni fase prima di procedere.

### Agenti specializzati

1. **Agente Analisi** - Analizza codice esistente, identifica pattern da seguire
2. **Agente Database** - Crea schema, migration, verifica FK
3. **Agente Backend** - Controller, Model, Routes, Services
4. **Agente Frontend** - Views, Alpine.js, UI components
5. **Agente Integrazione** - Modifica ArticleGeneratorService per usare pool
6. **Agente Test** - Verifica funzionamento end-to-end

---

## REQUISITI FUNZIONALI

### Flusso Utente

```
1. Progetto ai-content → Nuova tab "Link Interni"
2. Click "Importa URL" → Inserisce URL sito (es. https://example.com)
3. Sistema legge robots.txt → trova sitemap → gestisce sitemap annidate
4. Mostra URL raggruppate per sitemap sorgente (post-sitemap, page-sitemap, ecc.)
5. Utente seleziona quali URL importare (checkbox per gruppo o singole)
6. Sistema fa scraping title + meta description delle URL selezionate
7. Pool link interni pronto e visibile in tabella
8. Durante generazione articolo → AI riceve pool e inserisce 2-4 link naturali
```

### Gestione Sitemap Annidate

Yoast genera `sitemap_index.xml` che contiene link ad altre sitemap:
```
sitemap_index.xml
  ├── post-sitemap.xml (contiene URL articoli)
  ├── page-sitemap.xml (contiene URL pagine)
  └── category-sitemap.xml (contiene URL categorie)
```

**SitemapService.php già gestisce questo.** Verificare e riusare.

### UI Pool Link Interni

- Tabella con: URL, Title, Description (troncata), Stato (pronto/pending/errore), Azioni
- Bulk select/deselect
- Filtro ricerca
- Paginazione
- Toggle attivo/escluso per singola URL
- Edit manuale title/description

### UI Wizard Import (3 step)

**Step 1:** Input URL sito
**Step 2:** Selezione sitemap trovate (checkbox per sitemap, mostra conteggio URL)
**Step 3:** Selezione URL (raggruppate per sitemap, expand/collapse, select all per gruppo)

### Impostazioni (in tab Impostazioni progetto)

- Link per articolo: 0 (disabilitato), 2-3, 3-4, 4-5
- Posizionamento: naturale nel testo / sezione finale / entrambi

### Integrazione Generazione Articoli

Quando si genera un articolo:
1. Recupera pool link attivi del progetto
2. Aggiungi al prompt AI lista URL con title/description
3. Istruisci AI: "Inserisci 2-4 link interni naturalmente, usa anchor text descrittivi"
4. Nella preview articolo, mostra sezione "Link Interni Inseriti" con lista

---

## DATABASE

### Nuova tabella: `aic_internal_links_pool`

| Campo | Tipo | Note |
|-------|------|------|
| id | INT PK AUTO_INCREMENT | |
| project_id | INT FK | Riferimento progetto ai-content |
| url | VARCHAR(700) | URL completa |
| title | VARCHAR(500) NULL | Estratto da scraping |
| description | TEXT NULL | Meta description estratta |
| sitemap_source | VARCHAR(500) NULL | Da quale sitemap proviene |
| scrape_status | ENUM('pending','completed','error') | Stato scraping |
| scrape_error | TEXT NULL | Eventuale errore |
| is_active | TINYINT(1) DEFAULT 1 | 1=incluso, 0=escluso |
| scraped_at | DATETIME NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Indici:** project_id, scrape_status, UNIQUE(project_id, url)

---

## FILE DA CREARE

```
modules/ai-content/
├── controllers/
│   └── InternalLinksController.php
├── models/
│   └── InternalLinksPool.php
└── views/
    └── internal-links/
        ├── index.php          # Lista pool
        ├── import.php         # Wizard import
        └── edit.php           # Edit singola URL
```

## FILE DA MODIFICARE

```
modules/ai-content/
├── routes.php                          # Aggiungere routes
├── services/ArticleGeneratorService.php # Integrare pool nel prompt
└── views/
    └── (navigazione progetto)          # Aggiungere tab "Link Interni"
```

---

## ROUTES DA AGGIUNGERE

```
GET  /ai-content/project/{id}/internal-links
GET  /ai-content/project/{id}/internal-links/import
POST /ai-content/project/{id}/internal-links/discover
POST /ai-content/project/{id}/internal-links/preview
POST /ai-content/project/{id}/internal-links/store
POST /ai-content/project/{id}/internal-links/scrape
GET  /ai-content/project/{id}/internal-links/{linkId}/edit
POST /ai-content/project/{id}/internal-links/{linkId}/update
POST /ai-content/project/{id}/internal-links/{linkId}/delete
POST /ai-content/project/{id}/internal-links/bulk
```

---

## RIFERIMENTI CODICE ESISTENTE

| Cosa | Dove | Perché |
|------|------|--------|
| Parser sitemap + annidate | `services/SitemapService.php` | RIUSARE, non ricreare |
| Scraping HTTP | `services/ScraperService.php` | RIUSARE per fetch title/desc |
| Pattern import URL | `modules/internal-links/` | Riferimento UI simile |
| Generazione articoli | `modules/ai-content/services/ArticleGeneratorService.php` | Dove integrare pool |
| Pattern controller | `modules/ai-content/controllers/` | Seguire stile esistente |
| Pattern views | `modules/ai-content/views/` | Seguire stile esistente |

---

## CREDITI

| Azione | Costo |
|--------|-------|
| Discover sitemap | 0 |
| Import URL | 0 |
| Scraping title/desc | 0.1 per URL |

---

## CHECKLIST IMPLEMENTAZIONE

### Fase 1: Setup e Analisi
- [ ] Eseguire backup
- [ ] Analizzare struttura ai-content esistente
- [ ] Verificare SitemapService gestisce sitemap annidate
- [ ] Identificare dove aggiungere tab nella navigazione

### Fase 2: Database
- [ ] Creare tabella aic_internal_links_pool
- [ ] Verificare FK con tabella progetti esistente

### Fase 3: Backend
- [ ] Model InternalLinksPool con metodi CRUD + getForPrompt()
- [ ] Controller con tutti gli endpoint
- [ ] Routes

### Fase 4: Frontend
- [ ] Vista index (tabella pool)
- [ ] Wizard import 3 step con Alpine.js
- [ ] Vista edit singola URL
- [ ] Tab nella navigazione progetto

### Fase 5: Integrazione AI
- [ ] Modificare ArticleGeneratorService
- [ ] Aggiungere pool al prompt con istruzioni
- [ ] Preview link inseriti nella vista articolo

### Fase 6: Test
- [ ] Test sitemap standard
- [ ] Test sitemap annidate Yoast
- [ ] Test scraping batch
- [ ] Test generazione articolo con link
- [ ] Test su produzione

---

## REGOLE GOLDEN (RISPETTARE SEMPRE)

1. **Heroicons SVG inline** - MAI Lucide o FontAwesome
2. **Testi UI in italiano**
3. **Prepared statements** per query SQL
4. **CSRF token** su form POST
5. **AiService centralizzato** se serve AI
6. **RIUSARE servizi esistenti** (SitemapService, ScraperService)
7. **Seguire pattern esistenti** nel modulo ai-content

---

## FASE 2 FUTURA (NON IMPLEMENTARE ORA)

Integrazione WordPress quando connesso:
- Import diretto da WP REST API
- Dati arricchiti: categorie, tag
- Zero scraping necessario

Questa fase verrà sviluppata separatamente dopo completamento Fase 1.

---

*Specifiche Link Interni Automatici - ai-content*
*2026-01-28*
