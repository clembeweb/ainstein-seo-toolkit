<?php

namespace Modules\InternalLinks\Services;

use Core\Database;
use Modules\InternalLinks\Models\Suggestion;
use Modules\InternalLinks\Models\Url;
use Modules\InternalLinks\Models\InternalLink;

/**
 * SuggestionService
 *
 * Hybrid deterministic + AI engine for generating internal link suggestions.
 * Ported from Amevista internal-linking-plan.php and adapted for multi-tenant SaaS.
 *
 * Phase 1: Keyword extraction + similarity scoring (free, no AI credits)
 *   - Extracts keywords from il_urls.keyword (triple-weighted) + content_html
 *   - Computes positional keyword overlap between all page pairs
 *   - Infers categories from URL path structure (replaces WP taxonomy)
 *   - Plan A: Hub pages with high inbound but few outgoing links
 *   - Plan B: Orphan pages with 0 inbound links
 *
 * Phase 2: AI validation + anchor generation (uses AiService) -- Task 4
 * Phase 3: AI snippet generation on-demand (uses AiService) -- Task 4
 */
class SuggestionService
{
    private Suggestion $suggestion;
    private Url $urlModel;
    private InternalLink $linkModel;

    /** @var array Cached keyword index: url_id => [keywords] */
    private array $keywordIndex = [];

    /** @var array Cached URL data: url_id => [url, keyword, content_excerpt] */
    private array $urlData = [];

    /** @var array Cached link map: source_url_id => [destination_url_id => true] */
    private array $existingLinks = [];

    /** @var array Cached inbound counts: url_id => count */
    private array $inboundCounts = [];

    /** @var array Cached outbound counts: url_id => count */
    private array $outboundCounts = [];

    /** @var array Cached category per URL: url_id => category_slug */
    private array $urlCategories = [];

    /**
     * Multilingual stopwords (IT, EN, DE, ES, FR, NL, PT)
     *
     * Merged from Amevista internal-linking-plan.php (most complete source)
     * and plan spec. Covers articles, prepositions, conjunctions, auxiliaries,
     * pronouns, and common function words for all 7 supported languages.
     */
    private const STOPWORDS = [
        // Italian
        'il','lo','la','le','gli','i','un','una','di','del','della','delle','dei','degli',
        'da','in','con','su','per','tra','fra','che','come','sono','è','e','a','o','al',
        'alla','alle','ai','agli','dal','dalla','nel','nella','nelle','nei','non','si',
        'ci','se','più','anche','questo','questa','questi','queste','ogni','tutto',
        'tuo','suo','loro','nostro','vostro','molto','poco','quale','quali',
        // English
        'the','and','or','but','in','on','at','to','for','of','with','by','from',
        'is','are','was','were','be','been','being','have','has','had','do','does','did',
        'will','would','shall','should','can','could','may','might','must','not','no',
        'so','if','that','this','these','those','it','its','you','your','we','our',
        'they','their','he','she','him','her','who','which','what','how','why','when',
        'where','all','each','every','both','few','more','most','some','any','other',
        'into','about','than','very',
        // German
        'die','der','das','den','dem','des','ein','eine','einen','einem','einer',
        'und','oder','aber','auf','zu','für','mit','von','bei','nach','über','unter',
        'durch','als','wie','ist','sind','war','hat','haben','wird','werden','kann',
        'können','nicht','auch','noch','nur','so','wenn','dass','es','er','sie','wir','ihr',
        // Spanish
        'el','los','las','una','unos','unas','pero','del','con','por','para',
        'sin','sobre','entre','que','como','son','está','están','ser','estar','hay',
        'más','también','muy','todo','cada','este','esta','estos','estas','ese','esa',
        'su','sus','sí',
        // French
        'le','les','des','du','de','et','ou','mais','dans','sur','pour','par','avec','sans',
        'qui','est','sont','ont','pas','ne','plus','aussi','très','ce','cette','ces',
        'son','sa','ses','leur','leurs','tout','tous','comme',
        // Dutch
        'het','een','en','maar','op','aan','voor','met','van','bij','naar','over',
        'onder','door','als','zijn','was','heeft','hebben','wordt','worden','kan',
        'kunnen','niet','ook','nog','wel','zo','dat','die','dit','deze',
        // Portuguese
        'os','as','um','uma','uns','umas','mas','em','do','da','dos','das',
        'no','na','nos','nas','com','sem','entre','é','são','está','estão',
        'ser','estar','há','não','sim','mais','também','muito','todo','cada',
        'seu','sua','seus','suas',
    ];

    private array $stopwordsFlipped;

    /**
     * Related category pairs for bonus scoring.
     * Bidirectional pairs: "cat1|cat2" means cat1 is related to cat2.
     * Based on Amevista's related_pairs extended for generic SaaS use.
     */
    private const RELATED_PAIRS = [
        'guide|technology', 'technology|guide',
        'guide|trends', 'trends|guide',
        'guide|reviews', 'reviews|guide',
        'news|trends', 'trends|news',
        'tutorials|guide', 'guide|tutorials',
        'tutorials|technology', 'technology|tutorials',
        'reviews|technology', 'technology|reviews',
        'blog|news', 'news|blog',
        'blog|guide', 'guide|blog',
        'blog|tutorials', 'tutorials|blog',
    ];

    /** Category bonus: same category */
    private const CATEGORY_SAME_BONUS = 8;

    /** Category bonus: related pair */
    private const CATEGORY_RELATED_BONUS = 3;

    /** Minimum score threshold for Plan B candidates */
    private const PLAN_B_MIN_SCORE = 5;

    /** Max suggestions per source URL in Plan A */
    private const PLAN_A_PER_SOURCE = 3;

    /** Max linker suggestions per orphan in Plan B */
    private const PLAN_B_PER_ORPHAN = 3;

    /** Max outbound links before a hub is excluded from Plan A */
    private const PLAN_A_MAX_OUTBOUND = 3;

    public function __construct()
    {
        $this->suggestion = new Suggestion();
        $this->urlModel = new Url();
        $this->linkModel = new InternalLink();
        $this->stopwordsFlipped = array_flip(self::STOPWORDS);
    }

    /**
     * Phase 1: Generate deterministic suggestions
     *
     * @return array ['total_candidates' => int, 'plan_a' => int, 'plan_b' => int]
     */
    public function generateDeterministic(int $projectId): array
    {
        // Clear old non-applied suggestions
        $this->suggestion->deleteByProject($projectId, true);

        // Load all data into memory
        $this->loadProjectData($projectId);

        if (count($this->urlData) < 5) {
            return ['total_candidates' => 0, 'plan_a' => 0, 'plan_b' => 0, 'error' => 'Servono almeno 5 URL scrapate'];
        }

        // Build keyword index
        $this->buildKeywordIndex();

        $suggestions = [];

        // Plan A: Hub pages with few outgoing links
        $planA = $this->buildPlanA($projectId);
        $suggestions = array_merge($suggestions, $planA);

        // Plan B: Orphan pages needing inbound links
        $planB = $this->buildPlanB($projectId);
        $suggestions = array_merge($suggestions, $planB);

        // Cap per source URL: max 8 suggestions
        $suggestions = $this->capPerSource($suggestions, 8);

        // Bulk insert
        $inserted = $this->suggestion->bulkInsert($projectId, $suggestions);

        return [
            'total_candidates' => $inserted,
            'plan_a' => count($planA),
            'plan_b' => count($planB),
        ];
    }

    /**
     * Load all project data into memory for fast processing
     *
     * Loads scraped URLs, existing internal links, computes inbound/outbound
     * counts per URL, and infers categories from URL structure.
     * All data is cached in instance properties for O(1) access during scoring.
     */
    private function loadProjectData(int $projectId): void
    {
        // Load scraped URLs with content
        $urls = Database::fetchAll(
            "SELECT id, url, keyword, content_html FROM il_urls WHERE project_id = ? AND status = 'scraped' AND content_html IS NOT NULL",
            [$projectId]
        );

        $this->urlData = [];
        foreach ($urls as $u) {
            $this->urlData[$u['id']] = $u;
        }

        if (empty($this->urlData)) return;

        $urlIds = array_keys($this->urlData);

        // Load existing internal links for this project
        $links = Database::fetchAll(
            "SELECT source_url_id, destination_url FROM il_internal_links WHERE project_id = ? AND is_internal = 1",
            [$projectId]
        );

        $this->existingLinks = [];
        $this->inboundCounts = array_fill_keys($urlIds, 0);
        $this->outboundCounts = array_fill_keys($urlIds, 0);

        // Build URL-to-ID lookup
        $urlToId = [];
        foreach ($this->urlData as $id => $u) {
            $normalized = rtrim(strtolower($u['url']), '/');
            $urlToId[$normalized] = $id;
        }

        foreach ($links as $link) {
            $sourceId = $link['source_url_id'];
            $destNormalized = rtrim(strtolower($link['destination_url']), '/');
            $destId = $urlToId[$destNormalized] ?? null;

            if (!isset($this->existingLinks[$sourceId])) {
                $this->existingLinks[$sourceId] = [];
            }

            if ($destId !== null) {
                $this->existingLinks[$sourceId][$destId] = true;
                $this->inboundCounts[$destId] = ($this->inboundCounts[$destId] ?? 0) + 1;
            }

            $this->outboundCounts[$sourceId] = ($this->outboundCounts[$sourceId] ?? 0) + 1;
        }

        // Infer categories from URL structure
        $this->inferCategories();
    }

    /**
     * Infer page categories from URL path structure
     *
     * Parses URL path segments to guess the category. Common patterns:
     * - /blog/technology/post-slug -> "technology"
     * - /guide/seo/keyword-research -> "seo"
     * - /news/2024/03/article -> "news"
     *
     * Walks backwards from second-to-last segment, skipping:
     * - Numeric segments (years, IDs, pagination)
     * - Very short segments (language codes like 'it', 'en')
     * - Common structural segments (blog, posts, articles, page, etc.)
     */
    private function inferCategories(): void
    {
        // Common structural segments that are not categories
        $skipSegments = [
            'blog', 'posts', 'articles', 'pages', 'page', 'category', 'tag',
            'wp-content', 'uploads', 'archive', 'author', 'search', 'feed',
        ];

        $this->urlCategories = [];
        foreach ($this->urlData as $id => $u) {
            $path = parse_url($u['url'], PHP_URL_PATH) ?? '';
            $segments = array_values(array_filter(explode('/', $path)));

            if (count($segments) < 2) continue;

            // Walk backwards from second-to-last looking for a category-like segment
            for ($i = count($segments) - 2; $i >= 0; $i--) {
                $segment = strtolower($segments[$i]);

                // Skip numeric segments (years, IDs, pagination)
                if (is_numeric($segment)) continue;

                // Skip very short segments (language codes like 'it', 'en', 'de')
                if (mb_strlen($segment) <= 2) continue;

                // Skip common structural segments
                if (in_array($segment, $skipSegments)) continue;

                $this->urlCategories[$id] = $segment;
                break;
            }
        }
    }

    /**
     * Build keyword index for all URLs
     *
     * For each URL, extracts top 20 keywords from:
     * - Keyword field (triple-weighted, like Amevista's title weighting)
     * - First 800 chars of content_html (stripped of tags)
     */
    private function buildKeywordIndex(): void
    {
        $this->keywordIndex = [];
        foreach ($this->urlData as $id => $u) {
            $text = '';
            // Triple weight for keyword
            if (!empty($u['keyword'])) {
                $text = str_repeat($u['keyword'] . ' ', 3);
            }
            // First 800 chars of content
            if (!empty($u['content_html'])) {
                $text .= mb_substr(strip_tags($u['content_html']), 0, 800);
            }
            $this->keywordIndex[$id] = $this->extractKeywords($text, 20);
        }
    }

    /**
     * Extract top keywords from text
     *
     * Ported from Amevista's extract_keywords(). Strips HTML tags,
     * lowercases, removes non-alphanumeric characters, filters stopwords
     * and short words (< 3 chars), counts frequency, returns top N.
     *
     * @param string $text Raw or HTML text
     * @param int $limit Maximum keywords to return
     * @return array Ordered list of keyword strings (most frequent first)
     */
    private function extractKeywords(string $text, int $limit = 20): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $kw = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 3) continue;
            if (isset($this->stopwordsFlipped[$w])) continue;
            $kw[$w] = ($kw[$w] ?? 0) + 1;
        }
        arsort($kw);
        return array_slice(array_keys($kw), 0, $limit);
    }

    /**
     * Calculate keyword overlap score with positional weighting
     *
     * Ported from Amevista's keyword_overlap(). Keywords at lower indices
     * (i.e., from the keyword/title field, triple-weighted) get higher weight.
     * This ensures pages sharing important terms score higher than pages
     * sharing only incidental content words.
     *
     * Scoring: For each shared word, adds:
     *   weight1 = max(1, 10 - position_in_kw1)
     *   weight2 = max(1, 10 - position_in_kw2)
     *   total += weight1 + weight2
     *
     * @param array $kw1 Keywords from source URL
     * @param array $kw2 Keywords from candidate URL
     * @return int Combined positional overlap score
     */
    private function keywordOverlap(array $kw1, array $kw2): int
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

    /**
     * Find related URLs by keyword similarity + category bonus
     *
     * Ported from Amevista's find_related_posts(). For each candidate URL,
     * computes keyword overlap score and adds category bonuses:
     * - Same category: +CATEGORY_SAME_BONUS points
     * - Related category pair: +CATEGORY_RELATED_BONUS points
     *
     * Returns top N candidates sorted by score descending.
     *
     * @param int $urlId Source URL ID
     * @param array $excludeIds URL IDs to exclude (already linked)
     * @param int $limit Maximum results
     * @return array [url_id => ['score' => int, 'category_bonus' => int], ...]
     */
    private function findRelated(int $urlId, array $excludeIds = [], int $limit = 5): array
    {
        if (!isset($this->keywordIndex[$urlId])) return [];

        $kw = $this->keywordIndex[$urlId];
        $sourceCategory = $this->urlCategories[$urlId] ?? 'unknown';
        $scores = [];

        // Convert excludeIds to a set for O(1) lookup
        $excludeSet = array_flip($excludeIds);

        foreach ($this->keywordIndex as $candidateId => $candidateKw) {
            if ($candidateId === $urlId) continue;
            if (isset($excludeSet[$candidateId])) continue;

            $score = $this->keywordOverlap($kw, $candidateKw);

            // Category bonus
            $candidateCategory = $this->urlCategories[$candidateId] ?? 'unknown';
            $catBonus = 0;

            if ($sourceCategory !== 'unknown' && $candidateCategory === $sourceCategory) {
                $score += self::CATEGORY_SAME_BONUS;
                $catBonus = self::CATEGORY_SAME_BONUS;
            } elseif ($sourceCategory !== 'unknown' && $candidateCategory !== 'unknown') {
                $pair = "$sourceCategory|$candidateCategory";
                if (in_array($pair, self::RELATED_PAIRS)) {
                    $score += self::CATEGORY_RELATED_BONUS;
                    $catBonus = self::CATEGORY_RELATED_BONUS;
                }
            }

            if ($score > 0) {
                $scores[$candidateId] = [
                    'score' => $score,
                    'category_bonus' => $catBonus,
                ];
            }
        }

        uasort($scores, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($scores, 0, $limit, true);
    }

    /**
     * Plan A: Hub pages with high inbound but few outgoing internal links
     *
     * Identifies pages that receive many inbound links (hub authority)
     * but have fewer than PLAN_A_MAX_OUTBOUND outgoing internal links.
     * These are prime candidates for adding contextual outgoing links
     * to distribute link juice more effectively.
     *
     * Ported from Amevista Plan A with adaptation:
     * - Uses il_urls + il_internal_links instead of wp_posts
     * - No language filtering (single-site projects in SaaS)
     *
     * @return array Suggestion records ready for bulkInsert
     */
    private function buildPlanA(int $projectId): array
    {
        $suggestions = [];

        // Sort by inbound count desc (most authoritative first)
        arsort($this->inboundCounts);

        foreach ($this->inboundCounts as $urlId => $inboundCount) {
            // Skip pages with no inbound links
            if ($inboundCount === 0) continue;

            // Skip pages that already have enough outgoing links
            $outbound = $this->outboundCounts[$urlId] ?? 0;
            if ($outbound >= self::PLAN_A_MAX_OUTBOUND) continue;

            // Exclude already-linked destinations
            $alreadyLinked = array_keys($this->existingLinks[$urlId] ?? []);
            $related = $this->findRelated($urlId, $alreadyLinked, 5);

            $count = 0;
            foreach ($related as $targetId => $scoreData) {
                if ($count >= self::PLAN_A_PER_SOURCE) break;

                $suggestions[] = [
                    'source_url_id' => $urlId,
                    'destination_url_id' => $targetId,
                    'keyword_score' => $scoreData['score'] - $scoreData['category_bonus'],
                    'category_bonus' => $scoreData['category_bonus'],
                    'total_score' => $scoreData['score'],
                    'reason' => 'hub_needs_outgoing',
                ];
                $count++;
            }
        }

        return $suggestions;
    }

    /**
     * Plan B: Orphan pages with 0 inbound links
     *
     * Identifies pages that receive zero internal links (orphans) and
     * finds the best candidate pages to link FROM. For each orphan,
     * looks for topically similar pages that could naturally include
     * a link to the orphan.
     *
     * Key difference from Plan A: here the ORPHAN is the destination,
     * and the found candidates become the SOURCE of the new link.
     *
     * Ported from Amevista Plan B with adaptation:
     * - Score threshold of PLAN_B_MIN_SCORE to avoid low-quality suggestions
     * - No language filtering (single-site projects in SaaS)
     *
     * @return array Suggestion records ready for bulkInsert
     */
    private function buildPlanB(int $projectId): array
    {
        $suggestions = [];

        foreach ($this->urlData as $urlId => $url) {
            // Only process true orphans (0 inbound)
            if (($this->inboundCounts[$urlId] ?? 0) > 0) continue;

            // Find pages that SHOULD link to this orphan
            $kw = $this->keywordIndex[$urlId] ?? [];
            if (empty($kw)) continue;

            $sourceCategory = $this->urlCategories[$urlId] ?? 'unknown';
            $candidates = [];

            foreach ($this->keywordIndex as $candidateId => $candidateKw) {
                if ($candidateId === $urlId) continue;
                // Skip if candidate already links to this orphan
                if (isset($this->existingLinks[$candidateId][$urlId])) continue;

                $score = $this->keywordOverlap($kw, $candidateKw);
                $candidateCategory = $this->urlCategories[$candidateId] ?? 'unknown';
                $catBonus = 0;

                if ($sourceCategory !== 'unknown' && $candidateCategory === $sourceCategory) {
                    $score += self::CATEGORY_SAME_BONUS;
                    $catBonus = self::CATEGORY_SAME_BONUS;
                }

                // Only include candidates above minimum threshold
                if ($score > self::PLAN_B_MIN_SCORE) {
                    $candidates[$candidateId] = ['score' => $score, 'category_bonus' => $catBonus];
                }
            }

            uasort($candidates, fn($a, $b) => $b['score'] - $a['score']);
            $topCandidates = array_slice($candidates, 0, self::PLAN_B_PER_ORPHAN, true);

            foreach ($topCandidates as $linkerId => $scoreData) {
                $suggestions[] = [
                    'source_url_id' => $linkerId,       // The page that should ADD the link
                    'destination_url_id' => $urlId,      // The orphan page that needs inbound
                    'keyword_score' => $scoreData['score'] - $scoreData['category_bonus'],
                    'category_bonus' => $scoreData['category_bonus'],
                    'total_score' => $scoreData['score'],
                    'reason' => 'orphan_needs_inbound',
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Cap suggestions per source URL
     *
     * Prevents overwhelming a single page with too many suggested links.
     * Keeps the highest-scoring suggestions per source, drops the rest.
     *
     * @param array $suggestions All generated suggestions
     * @param int $max Maximum suggestions per source URL
     * @return array Filtered suggestions
     */
    private function capPerSource(array $suggestions, int $max = 8): array
    {
        // Sort by total_score desc within each source
        usort($suggestions, fn($a, $b) => $b['total_score'] - $a['total_score']);

        $perSource = [];
        $capped = [];

        foreach ($suggestions as $s) {
            $sourceId = $s['source_url_id'];
            $perSource[$sourceId] = ($perSource[$sourceId] ?? 0) + 1;
            if ($perSource[$sourceId] <= $max) {
                $capped[] = $s;
            }
        }

        return $capped;
    }

    // ─── Phase 2: AI Validation (stubs -- implemented in Task 4) ─────────────

    /**
     * Build AI validation prompt (Phase 2)
     *
     * Evaluates suggestion candidates, generates diverse anchor text variants,
     * and checks anchor distribution for over-optimization.
     *
     * @param array $suggestions Suggestions with source/destination content
     * @param array $anchorDistribution Existing anchor frequency distribution
     * @return string The prompt for AiService
     */
    public function buildValidationPrompt(array $suggestions, array $anchorDistribution): string
    {
        // Implemented in Task 4
        return '';
    }

    /**
     * Build AI snippet prompt (Phase 3)
     *
     * Finds the best insertion point in the source content and generates
     * a ready-to-use HTML snippet with the link naturally inserted.
     *
     * @param string $sourceContentHtml Full HTML content of source page
     * @param string $destinationUrl URL of the link destination
     * @param string $destinationTitle Title/keyword of destination
     * @param string $destinationKeyword Focus keyword of destination
     * @param array $suggestedAnchors AI-suggested anchor text variants
     * @param array $existingAnchorsInPage Anchors already in source page
     * @param array $existingAnchorsForDest Anchors already used for this destination
     * @param int $totalLinksInPage Total links already in source page
     * @return string The prompt for AiService
     */
    public function buildSnippetPrompt(
        string $sourceContentHtml,
        string $destinationUrl,
        string $destinationTitle,
        string $destinationKeyword,
        array $suggestedAnchors,
        array $existingAnchorsInPage,
        array $existingAnchorsForDest,
        int $totalLinksInPage
    ): string {
        // Implemented in Task 4
        return '';
    }

    /**
     * Parse AI validation response (Phase 2)
     *
     * Extracts structured validation results from AI response JSON.
     *
     * @param string $response Raw AI response text
     * @param array $suggestions The original suggestion batch (for index mapping)
     * @return array Parsed results indexed by candidate index
     */
    public function parseValidationResponse(string $response, array $suggestions): array
    {
        // Implemented in Task 4
        return [];
    }

    /**
     * Parse AI snippet response (Phase 3)
     *
     * Extracts structured snippet data from AI response JSON.
     *
     * @param string $response Raw AI response text
     * @return array|null Parsed snippet data or null on failure
     */
    public function parseSnippetResponse(string $response): ?array
    {
        // Implemented in Task 4
        return null;
    }
}
