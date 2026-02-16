<?php

namespace Modules\SeoAudit\Models;

use Core\Database;

/**
 * Issue Model
 *
 * Gestisce la tabella sa_issues con i problemi SEO rilevati
 */
class Issue
{
    protected string $table = 'sa_issues';

    /**
     * Definizione categorie con label italiane
     */
    public const CATEGORIES = [
        'meta' => 'Meta Tags',
        'headings' => 'Intestazioni',
        'images' => 'Immagini',
        'links' => 'Link',
        'content' => 'Contenuti',
        'technical' => 'Tecnico',
        'schema' => 'Schema Markup',
        'security' => 'Sicurezza',
        'sitemap' => 'Sitemap',
        'robots' => 'Robots.txt',
    ];

    /**
     * Definizione issue types con dettagli
     */
    public const ISSUE_TYPES = [
        // Meta Tags
        'missing_title' => [
            'category' => 'meta',
            'severity' => 'critical',
            'title' => 'Titolo mancante',
            'recommendation' => 'Aggiungi un tag <title> unico e descrittivo di 50-60 caratteri.',
        ],
        'title_too_short' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Titolo troppo corto',
            'recommendation' => 'Il titolo dovrebbe avere almeno 30 caratteri per essere efficace.',
        ],
        'title_too_long' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Titolo troppo lungo',
            'recommendation' => 'Riduci il titolo a max 60 caratteri per evitare troncamenti nei risultati di ricerca.',
        ],
        'missing_description' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Meta description mancante',
            'recommendation' => 'Aggiungi una meta description di 120-160 caratteri che descriva il contenuto della pagina.',
        ],
        'description_too_short' => [
            'category' => 'meta',
            'severity' => 'notice',
            'title' => 'Meta description troppo corta',
            'recommendation' => 'La meta description dovrebbe avere almeno 70 caratteri.',
        ],
        'description_too_long' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Meta description troppo lunga',
            'recommendation' => 'Riduci la meta description a max 160 caratteri.',
        ],
        'duplicate_title' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Titolo duplicato',
            'recommendation' => 'Ogni pagina dovrebbe avere un titolo unico. Modifica questo titolo per differenziarlo.',
        ],
        'duplicate_description' => [
            'category' => 'meta',
            'severity' => 'warning',
            'title' => 'Meta description duplicata',
            'recommendation' => 'Ogni pagina dovrebbe avere una meta description unica.',
        ],
        'missing_og_tags' => [
            'category' => 'meta',
            'severity' => 'notice',
            'title' => 'Open Graph mancanti',
            'recommendation' => 'Aggiungi i tag Open Graph (og:title, og:description, og:image) per migliorare la condivisione social.',
        ],

        // Headings
        'missing_h1' => [
            'category' => 'headings',
            'severity' => 'critical',
            'title' => 'H1 mancante',
            'recommendation' => 'Ogni pagina deve avere un tag H1 che descriva il contenuto principale.',
        ],
        'multiple_h1' => [
            'category' => 'headings',
            'severity' => 'warning',
            'title' => 'H1 multipli',
            'recommendation' => 'Usa un solo tag H1 per pagina. Gli altri titoli possono essere H2, H3, ecc.',
        ],
        'h1_too_long' => [
            'category' => 'headings',
            'severity' => 'notice',
            'title' => 'H1 troppo lungo',
            'recommendation' => 'L\'H1 dovrebbe essere conciso, idealmente sotto i 70 caratteri.',
        ],
        'empty_heading' => [
            'category' => 'headings',
            'severity' => 'warning',
            'title' => 'Intestazione vuota',
            'recommendation' => 'Rimuovi i tag heading vuoti o aggiungi contenuto significativo.',
        ],
        'skipped_heading_level' => [
            'category' => 'headings',
            'severity' => 'notice',
            'title' => 'Livello heading saltato',
            'recommendation' => 'Mantieni una gerarchia corretta degli heading (H1 > H2 > H3, ecc.).',
        ],

        // Images
        'missing_alt' => [
            'category' => 'images',
            'severity' => 'warning',
            'title' => 'Alt mancante',
            'recommendation' => 'Aggiungi un attributo alt descrittivo per l\'accessibilità e la SEO.',
        ],
        'alt_too_long' => [
            'category' => 'images',
            'severity' => 'notice',
            'title' => 'Alt troppo lungo',
            'recommendation' => 'L\'attributo alt dovrebbe essere sotto i 125 caratteri.',
        ],
        'missing_dimensions' => [
            'category' => 'images',
            'severity' => 'notice',
            'title' => 'Dimensioni mancanti',
            'recommendation' => 'Specifica width e height per evitare layout shift (CLS).',
        ],
        'large_image' => [
            'category' => 'images',
            'severity' => 'notice',
            'title' => 'Immagine troppo pesante',
            'recommendation' => 'Ottimizza l\'immagine per ridurre il peso sotto i 200KB.',
        ],

        // Links
        'broken_internal_link' => [
            'category' => 'links',
            'severity' => 'critical',
            'title' => 'Link interno rotto',
            'recommendation' => 'Correggi o rimuovi il link che porta a una pagina 404.',
        ],
        'broken_external_link' => [
            'category' => 'links',
            'severity' => 'warning',
            'title' => 'Link esterno rotto',
            'recommendation' => 'Aggiorna o rimuovi il link esterno non funzionante.',
        ],
        'redirect_chain' => [
            'category' => 'links',
            'severity' => 'warning',
            'title' => 'Catena di redirect',
            'recommendation' => 'Aggiorna il link per puntare direttamente alla destinazione finale.',
        ],
        'orphan_page' => [
            'category' => 'links',
            'severity' => 'warning',
            'title' => 'Pagina orfana',
            'recommendation' => 'Aggiungi link interni a questa pagina per migliorare la crawlability.',
        ],
        'too_many_links' => [
            'category' => 'links',
            'severity' => 'notice',
            'title' => 'Troppi link',
            'recommendation' => 'Riduci il numero di link per pagina (consigliato max 100).',
        ],
        'nofollow_internal' => [
            'category' => 'links',
            'severity' => 'notice',
            'title' => 'Nofollow su link interno',
            'recommendation' => 'Rimuovi rel="nofollow" dai link interni per distribuire il PageRank.',
        ],

        // Content
        'thin_content' => [
            'category' => 'content',
            'severity' => 'warning',
            'title' => 'Contenuto scarso',
            'recommendation' => 'Espandi il contenuto della pagina ad almeno 300 parole.',
        ],
        'duplicate_content' => [
            'category' => 'content',
            'severity' => 'warning',
            'title' => 'Contenuto duplicato',
            'recommendation' => 'Riscrivi il contenuto o usa canonical per indicare la versione principale.',
        ],
        'no_content' => [
            'category' => 'content',
            'severity' => 'critical',
            'title' => 'Nessun contenuto',
            'recommendation' => 'Aggiungi contenuto testuale alla pagina.',
        ],

        // Technical
        'missing_canonical' => [
            'category' => 'technical',
            'severity' => 'warning',
            'title' => 'Canonical mancante',
            'recommendation' => 'Aggiungi un tag canonical per indicare la versione preferita della pagina.',
        ],
        'wrong_canonical' => [
            'category' => 'technical',
            'severity' => 'critical',
            'title' => 'Canonical errato',
            'recommendation' => 'Correggi il tag canonical per puntare a un URL valido.',
        ],
        'noindex_in_sitemap' => [
            'category' => 'technical',
            'severity' => 'warning',
            'title' => 'Noindex presente in sitemap',
            'recommendation' => 'Rimuovi dalla sitemap le pagine con noindex.',
        ],
        'blocked_by_robots' => [
            'category' => 'technical',
            'severity' => 'notice',
            'title' => 'Bloccata da robots.txt',
            'recommendation' => 'Verifica se il blocco è intenzionale.',
        ],
        'missing_hreflang' => [
            'category' => 'technical',
            'severity' => 'notice',
            'title' => 'Hreflang mancante',
            'recommendation' => 'Aggiungi i tag hreflang per siti multilingua.',
        ],
        'conflicting_hreflang' => [
            'category' => 'technical',
            'severity' => 'warning',
            'title' => 'Hreflang in conflitto',
            'recommendation' => 'Verifica che i tag hreflang siano reciproci tra le versioni linguistiche.',
        ],

        // Schema
        'missing_schema' => [
            'category' => 'schema',
            'severity' => 'notice',
            'title' => 'Schema markup mancante',
            'recommendation' => 'Aggiungi structured data appropriati per il tipo di contenuto.',
        ],
        'invalid_schema' => [
            'category' => 'schema',
            'severity' => 'warning',
            'title' => 'Schema markup non valido',
            'recommendation' => 'Correggi gli errori di sintassi nello structured data.',
        ],

        // Security
        'not_https' => [
            'category' => 'security',
            'severity' => 'critical',
            'title' => 'Sito non sicuro (HTTP)',
            'recommendation' => 'Migra il sito a HTTPS per sicurezza e ranking.',
        ],
        'mixed_content' => [
            'category' => 'security',
            'severity' => 'warning',
            'title' => 'Contenuto misto',
            'recommendation' => 'Aggiorna le risorse HTTP a HTTPS.',
        ],
        'ssl_expiring' => [
            'category' => 'security',
            'severity' => 'warning',
            'title' => 'Certificato SSL in scadenza',
            'recommendation' => 'Rinnova il certificato SSL prima della scadenza.',
        ],
        'ssl_invalid' => [
            'category' => 'security',
            'severity' => 'critical',
            'title' => 'Certificato SSL non valido',
            'recommendation' => 'Installa un certificato SSL valido.',
        ],

    ];

    /**
     * Trova issue per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Ottieni issues per progetto con paginazione
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $where = ['i.project_id = ?'];
        $params = [$projectId];

        // Filtro categoria
        if (!empty($filters['category'])) {
            $where[] = 'i.category = ?';
            $params[] = $filters['category'];
        }

        // Filtro severity
        if (!empty($filters['severity'])) {
            $where[] = 'i.severity = ?';
            $params[] = $filters['severity'];
        }

        // Filtro tipo
        if (!empty($filters['issue_type'])) {
            $where[] = 'i.issue_type = ?';
            $params[] = $filters['issue_type'];
        }

        // Filtro source
        if (!empty($filters['source'])) {
            $where[] = 'i.source = ?';
            $params[] = $filters['source'];
        }

        // Filtro ricerca
        if (!empty($filters['search'])) {
            $where[] = '(i.title LIKE ? OR p.url LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Count totale
        $countSql = "
            SELECT COUNT(*)  as total
            FROM {$this->table} i
            LEFT JOIN sa_pages p ON i.page_id = p.id
            WHERE {$whereClause}
        ";
        $countResult = Database::fetch($countSql, $params);
        $total = (int) $countResult['total'];

        // Dati paginati
        $sql = "
            SELECT
                i.*,
                p.url as page_url
            FROM {$this->table} i
            LEFT JOIN sa_pages p ON i.page_id = p.id
            WHERE {$whereClause}
            ORDER BY
                FIELD(i.severity, 'critical', 'warning', 'notice', 'info'),
                i.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPage;
        $params[] = $offset;

        $data = Database::fetchAll($sql, $params);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Ottieni issues per categoria
     */
    public function getByCategory(int $projectId, string $category, int $page = 1, int $perPage = 50): array
    {
        return $this->getByProject($projectId, $page, $perPage, ['category' => $category]);
    }

    /**
     * Ottieni issues per pagina
     */
    public function getByPage(int $pageId): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE page_id = ?
            ORDER BY FIELD(severity, 'critical', 'warning', 'notice', 'info')
        ";

        return Database::fetchAll($sql, [$pageId]);
    }

    /**
     * Crea nuova issue
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea issue da tipo predefinito
     */
    public function createFromType(int $projectId, ?int $pageId, string $issueType, ?string $affectedElement = null): int
    {
        if (!isset(self::ISSUE_TYPES[$issueType])) {
            throw new \InvalidArgumentException("Unknown issue type: {$issueType}");
        }

        $typeInfo = self::ISSUE_TYPES[$issueType];

        return $this->create([
            'project_id' => $projectId,
            'page_id' => $pageId,
            'category' => $typeInfo['category'],
            'issue_type' => $issueType,
            'severity' => $typeInfo['severity'],
            'title' => $typeInfo['title'],
            'recommendation' => $typeInfo['recommendation'],
            'affected_element' => $affectedElement,
            'source' => 'crawler',
        ]);
    }

    /**
     * Elimina issues per progetto
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Elimina issues per pagina
     */
    public function deleteByPage(int $pageId): int
    {
        return Database::delete($this->table, 'page_id = ?', [$pageId]);
    }

    /**
     * Elimina issues per categoria e progetto
     */
    public function deleteByCategory(int $projectId, string $category): int
    {
        return Database::delete($this->table, 'project_id = ? AND category = ?', [$projectId, $category]);
    }

    /**
     * Conta issues per severity
     */
    public function countBySeverity(int $projectId): array
    {
        $sql = "
            SELECT
                severity,
                COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY severity
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        $counts = [
            'critical' => 0,
            'warning' => 0,
            'notice' => 0,
            'info' => 0,
            'total' => 0,
        ];

        foreach ($results as $row) {
            $counts[$row['severity']] = (int) $row['count'];
            $counts['total'] += (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Conta issues per categoria
     */
    public function countByCategory(int $projectId): array
    {
        $sql = "
            SELECT
                category,
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END) as notice,
                SUM(CASE WHEN severity = 'info' THEN 1 ELSE 0 END) as info
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY category
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        // Trasforma in array associativo per categoria
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['category']] = [
                'total' => (int) $row['total'],
                'critical' => (int) $row['critical'],
                'warning' => (int) $row['warning'],
                'notice' => (int) $row['notice'],
                'info' => (int) $row['info'],
                'label' => self::CATEGORIES[$row['category']] ?? $row['category'],
            ];
        }

        return $counts;
    }

    /**
     * Conta issues per tipo
     */
    public function countByType(int $projectId): array
    {
        $sql = "
            SELECT
                issue_type,
                COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY issue_type
            ORDER BY count DESC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Ottieni label categoria
     */
    public static function getCategoryLabel(string $category): string
    {
        return self::CATEGORIES[$category] ?? $category;
    }

    /**
     * Ottieni info tipo issue
     */
    public static function getTypeInfo(string $issueType): ?array
    {
        return self::ISSUE_TYPES[$issueType] ?? null;
    }

}
