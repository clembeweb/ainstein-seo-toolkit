# Design: AI Link Suggester per Internal Links

**Data:** 2026-03-03
**Modulo:** Internal Links (`il_`)
**Stato modulo attuale:** 85% (manca AI Suggester)
**Origine:** Procedura internal linking di Amevista Magazine (3 script CLI: audit, plan, execute)

---

## Obiettivo

Completare il modulo Internal Links con la feature mancante: **suggerimento automatico di nuovi link interni** con possibilita di applicarli tramite snippet copiabile o push diretto via CMS connector.

L'algoritmo si basa sulla procedura testata su Amevista Magazine (414 articoli, 7 lingue, 500+ link aggiunti), adattata al contesto SaaS multi-tenant.

---

## Architettura

### Approccio ibrido: Deterministico + AI

```
Fase 1 (Deterministica, gratuita)
    Keyword extraction da il_urls (keyword + content_html)
    Similarity scoring (keyword overlap posizionale + bonus categoria)
    Plan A: hub pages con pochi outgoing -> suggerisci target
    Plan B: orphan pages con 0 inbound -> suggerisci linker
    Output: candidati con score di rilevanza

Fase 2 (AI Prompt 1, batch, costo crediti)
    Valida/filtra candidati con contesto semantico
    Genera 3 varianti anchor text per suggerimento
    Analizza distribuzione anchor esistenti per diversificazione
    Output: suggerimenti validati in il_link_suggestions

Fase 3 (AI Prompt 2, on-demand, costo crediti)
    Quando l'utente clicca "Trova punto di inserimento"
    Analizza contenuto HTML completo della pagina sorgente
    Individua il paragrafo piu coerente
    Genera snippet HTML pronto da copiare o applicare via CMS
```

### Perche ibrido

- La fase deterministica e gratuita e filtra il 90% dei candidati irrilevanti
- L'AI interviene solo sui candidati validi (risparmio crediti)
- Il Prompt 2 on-demand evita di analizzare contenuti completi per suggerimenti che l'utente potrebbe ignorare

---

## Database

### Nuova tabella: `il_link_suggestions`

```sql
CREATE TABLE il_link_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    source_url_id INT NOT NULL,
    destination_url_id INT NOT NULL,

    -- Scoring deterministico (Fase 1)
    keyword_score INT DEFAULT 0,
    category_bonus INT DEFAULT 0,
    total_score INT DEFAULT 0,
    reason ENUM('hub_needs_outgoing', 'orphan_needs_inbound', 'topical_relevance') NOT NULL,

    -- AI enrichment (Fase 2)
    ai_relevance_score TINYINT NULL,          -- 1-10
    ai_suggested_anchors JSON NULL,            -- ["anchor1", "anchor2", "anchor3"]
    ai_placement_hint TEXT NULL,               -- "Il paragrafo che parla di X..."
    ai_confidence ENUM('high','medium','low') NULL,
    ai_analyzed_at DATETIME NULL,

    -- AI insertion point (Fase 3, on-demand)
    ai_snippet_html TEXT NULL,                 -- Paragrafo con link inserito
    ai_insertion_method ENUM('inline_existing_text','contextual_sentence') NULL,
    ai_anchor_used VARCHAR(255) NULL,
    ai_snippet_generated_at DATETIME NULL,

    -- Status
    status ENUM('pending','ai_validated','snippet_ready','applied','dismissed') DEFAULT 'pending',
    applied_at DATETIME NULL,
    applied_method ENUM('manual_copy','cms_push') NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project_status (project_id, status),
    INDEX idx_source (source_url_id),
    INDEX idx_destination (destination_url_id),
    UNIQUE KEY unique_suggestion (project_id, source_url_id, destination_url_id),
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (source_url_id) REFERENCES il_urls(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_url_id) REFERENCES il_urls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Modifica tabella esistente: `il_projects`

```sql
ALTER TABLE il_projects ADD COLUMN connector_id INT NULL;
-- FK verso cc_connectors.id (riutilizzo connectors di Content Creator)
```

### Modifica tabella stats: `il_project_stats`

```sql
ALTER TABLE il_project_stats
    ADD COLUMN total_suggestions INT DEFAULT 0,
    ADD COLUMN pending_suggestions INT DEFAULT 0,
    ADD COLUMN applied_suggestions INT DEFAULT 0;
```

---

## AI Prompts

### Prompt 1 — "Suggestion Validator" (batch, Fase 2)

```
Input:
- Lista candidati da fase deterministica (source -> destination, score, reason)
- Per ogni URL coinvolta: titolo, keyword, primi 500 char contenuto
- Mappa link esistenti del progetto (source -> [destinations])
- Top 20 anchor text con frequenza (per diversificazione)
- Conteggio inbound/outbound per URL

Istruzioni AI:
- Valuta la rilevanza semantica reale tra source e destination (non solo keyword overlap)
- Filtra suggerimenti forzati o irrilevanti (confidence: low -> scartare)
- Per ogni suggerimento valido, genera 3 varianti di anchor text:
  - Variante 1: keyword-focused (es. "occhiali da sole polarizzati")
  - Variante 2: contesto naturale (es. "scopri come scegliere lenti polarizzate")
  - Variante 3: diversificata (evita anchor gia sovra-usate nel progetto)
- Segnala se un'ancora e gia troppo frequente nel progetto

Output JSON:
[
    {
        "source_url_id": 123,
        "destination_url_id": 456,
        "relevance_score": 8,
        "confidence": "high",
        "suggested_anchors": ["anchor1", "anchor2", "anchor3"],
        "placement_hint": "Il paragrafo che tratta di [topic] e il punto migliore",
        "anchor_diversity_note": "L'ancora 'occhiali da sole' e gia usata 12 volte, preferire varianti"
    }
]
```

### Prompt 2 — "Anchor Finder" (on-demand, Fase 3)

```
Input:
- Contenuto HTML COMPLETO della pagina sorgente
- URL, titolo e keyword della pagina destinazione
- 3 anchor suggerite dal Prompt 1
- Lista ancore GIA usate per questa destinazione nel progetto
- Lista ancore gia presenti nella pagina sorgente
- Conteggio totale link nella pagina sorgente

Istruzioni AI:
- Trova il paragrafo PIU COERENTE con il tema della destinazione
- NON forzare: se nessun paragrafo e naturalmente collegato, rispondi con method: "contextual_sentence"
- Per inline: scegli parole GIA presenti nel testo come ancora (minima modifica)
- Per contextual: genera una frase nella lingua del contenuto (detect automatico)
- Verifica che il punto scelto NON sia dentro un heading, un link esistente, o un attributo HTML
- Genera lo snippet HTML del paragrafo modificato, pronto da copiare

Output JSON:
{
    "paragraph_excerpt": "primi 150 char del paragrafo individuato...",
    "anchor_text": "l'ancora scelta",
    "anchor_alternatives": ["variante1", "variante2"],
    "insertion_method": "inline_existing_text",
    "confidence": "high",
    "reason": "Il paragrafo discute di lenti polarizzate, direttamente collegato alla guida acquisto",
    "snippet_html": "<p>...testo con <a href=\"url\">ancora</a> inserita...</p>",
    "original_paragraph": "<p>...testo originale senza modifica...</p>"
}
```

---

## Flusso Utente

### Tab "Suggerimenti" (nuova, nella navigazione del progetto IL)

```
/internal-links/project/{id}/suggestions

1. Stato iniziale: banner "Genera suggerimenti di link interni"
   - Prerequisiti: almeno 10 URL scrapate con contenuto
   - Pulsante "Genera Suggerimenti" (consuma crediti per fase AI)

2. Generazione (AJAX lungo / SSE):
   - Fase 1 (deterministica): barra progresso "Analisi keyword..."
   - Fase 2 (AI batch): barra progresso "Validazione AI..."
   - Risultato: N suggerimenti generati

3. Lista suggerimenti:
   - Filtri: status, score range, reason (hub/orphan/topical)
   - Colonne: Source -> Destination, Score, Anchor suggerite, Status
   - Azioni per riga:
     a) "Genera Snippet" -> Prompt 2 AI -> mostra snippet copiabile
     b) "Copia Snippet" -> clipboard HTML
     c) "Applica via CMS" -> fetch + modify + push (se connector configurato)
     d) "Segna applicato" -> manual status change
     e) "Ignora" -> dismiss

4. Azioni bulk:
   - "Genera snippet per selezionati" (batch Prompt 2)
   - "Applica via CMS" (batch push)
   - "Esporta piano" -> CSV / report HTML (stile Amevista)
   - "Ignora selezionati"

5. Dashboard widget: "X suggerimenti da applicare" nel project show
```

### Settings progetto (aggiunta)

```
Sezione "Connettore CMS" nelle impostazioni progetto:
- Dropdown: seleziona tra cc_connectors dell'utente
- Se nessun connector: link "Configura in Content Creator"
- Pulsante "Test Connessione"
- Nota: "Necessario per applicare link automaticamente via CMS"
```

---

## CMS Connector — Integrazione

### Riutilizzo architettura Content Creator

- `cc_connectors` come tabella condivisa (gia user-scoped)
- `ConnectorInterface` e implementazioni riutilizzate via `use`
- Factory pattern identico: tipo -> istanza connector
- API logging con provider `wordpress_api` (gia configurato)

### Plugin WordPress — Nuovo endpoint

```php
// Aggiungere in seo-toolkit-connector.php -> registerEndpoints()
register_rest_route('seo-toolkit/v1', '/posts/(?P<id>\d+)/raw-content', [
    'methods' => 'GET',
    'callback' => [$this, 'getPostRawContent'],
    'permission_callback' => [$this, 'verifyApiKey']
]);
```

Questo endpoint restituisce `post_content` HTML raw (non stripped), necessario per:
1. Fase 3 AI: analisi contenuto completo per trovare punto inserimento
2. Applicazione link: fetch raw -> modifica -> push aggiornato

Response:
```json
{
    "success": true,
    "post_id": 123,
    "title": "Titolo post",
    "content": "<p>HTML raw...</p>",
    "url": "https://...",
    "categories": ["cat1", "cat2"],
    "word_count": 500
}
```

### Flusso "Applica via CMS"

```
1. GET /posts/{cms_entity_id}/raw-content -> HTML attuale
2. AI Prompt 2: individua punto inserimento nel contenuto
3. Modifica contenuto HTML lato SaaS (try_inline_link o insert_fallback)
4. PUT /posts/{cms_entity_id} con { content: modified_html }
5. Aggiorna il_link_suggestions: status=applied, applied_method=cms_push
6. Log in il_activity_logs
```

### Mapping URL -> CMS Entity ID

Per il push serve sapere quale post WordPress corrisponde a quale `il_urls` record.
Opzioni:
- **Matching per URL**: confronta `il_urls.url` con le URL restituite da `GET /all-content`
- **Campo esplicito**: `il_urls.cms_entity_id` (nullable) — popolato al primo match

---

## Algoritmo Deterministico (da Amevista)

### Keyword Extraction

```php
function extractKeywords(string $keyword, string $contentHtml, int $limit = 20): array
{
    // Peso triplo per keyword del progetto
    $text = str_repeat($keyword . ' ', 3);
    // Primi 800 char del contenuto
    $text .= mb_substr(strip_tags($contentHtml), 0, 800);

    $text = mb_strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    // Filtra stopwords multilingue + parole < 3 char
    $kw = [];
    foreach ($words as $w) {
        if (mb_strlen($w) < 3 || isset($stopwords[$w])) continue;
        $kw[$w] = ($kw[$w] ?? 0) + 1;
    }
    arsort($kw);
    return array_slice(array_keys($kw), 0, $limit);
}
```

### Similarity Scoring

```php
function keywordOverlap(array $kw1, array $kw2): int
{
    $set1 = array_flip($kw1);
    $score = 0;
    foreach ($kw2 as $i => $w) {
        if (isset($set1[$w])) {
            $weight1 = max(1, 10 - (array_search($w, $kw1) ?? 10));
            $weight2 = max(1, 10 - $i);
            $score += ($weight1 + $weight2);
        }
    }
    return $score;
}
```

### Bonus categoria

Le categorie vengono inferite dalla struttura URL:
```php
// /blog/technology/post-slug -> categoria "technology"
// /guida-acquisto/post-slug -> categoria "buying-guide"
$segments = parse_url($url, PHP_URL_PATH);
// Secondo segmento dopo il dominio = probabile categoria
```

Bonus: +8 per stessa categoria, +3 per categorie correlate.

### Plan A e Plan B

- **Plan A**: URL con `inbound_count > 0` e `outbound_magazine_count < 3` -> suggerisci target correlati (max 5 candidati, cap 3 per post)
- **Plan B**: URL orfane (`inbound_count = 0`) -> trova pagine correlate che dovrebbero linkarle (max 3 linker per orfano)
- **Cap globale**: max 8 suggerimenti per URL sorgente

---

## Costi Crediti

| Operazione | Crediti | Note |
|-----------|---------|------|
| Generazione suggerimenti (Fase 1) | 0 | Algoritmo deterministico |
| Validazione AI batch (Fase 2) | 1 per batch di 10 suggerimenti | `cost_ai_suggestions` |
| Trova punto inserimento (Fase 3) | 1 per suggerimento | `cost_ai_snippet` |
| Applicazione via CMS | 0 | Solo API call, no AI |

---

## File da creare/modificare

### Nuovi file

| File | Scopo |
|------|-------|
| `modules/internal-links/services/SuggestionService.php` | Algoritmo deterministico + orchestrazione AI |
| `modules/internal-links/models/Suggestion.php` | Model per `il_link_suggestions` |
| `modules/internal-links/views/suggestions/index.php` | UI lista suggerimenti |

### File da modificare

| File | Modifica |
|------|----------|
| `modules/internal-links/routes.php` | Nuove route per suggestions |
| `modules/internal-links/database/schema.sql` | Tabella `il_link_suggestions`, ALTER su `il_projects` e `il_project_stats` |
| `modules/internal-links/models/Project.php` | Aggiunta `connector_id`, metodo `updateStats()` con suggestions count |
| `modules/internal-links/views/projects/show.php` | Widget suggerimenti nel dashboard |
| `modules/internal-links/views/projects/settings.php` | Sezione connettore CMS |
| `modules/internal-links/module.json` | Nuovi costi: `cost_ai_suggestions`, `cost_ai_snippet` |
| `storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php` | Endpoint `GET /posts/{id}/raw-content` |

---

## Non in scope

- Supporto connectors diversi da WordPress per il push automatico (futuro: Shopify, PrestaShop, Magento)
- Suggerimenti cross-progetto (ogni progetto e isolato)
- Scheduling automatico dei suggerimenti (solo on-demand)
- Modifica del modulo SEO Audit (resta read-only per i link)
