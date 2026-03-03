<?php

namespace Modules\SeoAudit\Services;

/**
 * RobotsTxtParser
 *
 * Parser standalone per robots.txt.
 * Ported da CrawlBudget\BudgetCrawlerService::parseRobotsTxt() e isUrlAllowed().
 *
 * Funzionalita:
 * - Parse del contenuto robots.txt in regole strutturate per User-Agent
 * - Verifica se un URL e consentito secondo le regole (priorita Googlebot > * > default allow)
 * - Estrazione URL sitemap dichiarate
 * - Estrazione valore Crawl-delay
 */
class RobotsTxtParser
{
    /**
     * Parse contenuto robots.txt in regole strutturate
     *
     * Restituisce un array associativo dove ogni chiave e un user-agent (lowercase)
     * con sotto-array 'allow' e 'disallow'. Se presente, aggiunge la chiave
     * speciale '_crawl_delay' con il valore numerico.
     *
     * @param string $content Contenuto raw del robots.txt
     * @return array ['agent_name' => ['allow' => [...], 'disallow' => [...]], '_crawl_delay' => int|null]
     */
    public function parse(string $content): array
    {
        $rules = [];
        $currentAgent = null;
        $crawlDelay = null;

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            // Rimuovi commenti (tutto dopo #)
            if (($pos = strpos($line, '#')) !== false) {
                $line = trim(substr($line, 0, $pos));
            }

            if (empty($line)) {
                continue;
            }

            // User-agent directive
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                $currentAgent = strtolower(trim($m[1]));
                if (!isset($rules[$currentAgent])) {
                    $rules[$currentAgent] = ['allow' => [], 'disallow' => []];
                }
            }
            // Allow directive
            elseif ($currentAgent !== null && preg_match('/^Allow:\s*(.+)$/i', $line, $m)) {
                $rules[$currentAgent]['allow'][] = trim($m[1]);
            }
            // Disallow directive
            elseif ($currentAgent !== null && preg_match('/^Disallow:\s*(.+)$/i', $line, $m)) {
                $rules[$currentAgent]['disallow'][] = trim($m[1]);
            }
            // Crawl-delay directive
            elseif (preg_match('/^Crawl-delay:\s*(\d+)/i', $line, $m)) {
                $crawlDelay = (int) $m[1];
            }
        }

        if ($crawlDelay !== null) {
            $rules['_crawl_delay'] = $crawlDelay;
        }

        return $rules;
    }

    /**
     * Verifica se un URL e consentito dalle regole robots.txt
     *
     * Priorita:
     * 1. Regole Googlebot (se presenti)
     * 2. Regole * (wildcard, se presenti)
     * 3. Default: consentito (se nessuna regola matcha)
     *
     * All'interno di ogni agent, Allow sovrascrive Disallow quando la regola Allow
     * e di lunghezza >= alla regola Disallow che matcha (regola piu specifica vince).
     *
     * @param string $url    URL completo da verificare
     * @param array  $rules  Regole ottenute da parse()
     * @return bool true se URL e consentito
     */
    public function isAllowed(string $url, array $rules): bool
    {
        if (empty($rules)) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $path .= '?' . $query;
        }

        // Controlla Googlebot prima, poi wildcard *
        foreach (['googlebot', '*'] as $agent) {
            if (!isset($rules[$agent])) {
                continue;
            }

            $agentRules = $rules[$agent];

            // Per ogni regola Disallow che matcha, verifica se esiste un Allow piu specifico
            foreach ($agentRules['disallow'] as $rule) {
                if ($this->ruleMatches($rule, $path)) {
                    // Verifica se c'e un Allow con lunghezza >= (piu specifico)
                    foreach ($agentRules['allow'] as $allowRule) {
                        if ($this->ruleMatches($allowRule, $path) && strlen($allowRule) >= strlen($rule)) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Estrai URL delle sitemap dal contenuto robots.txt
     *
     * Cerca tutte le direttive "Sitemap:" e restituisce un array di URL.
     *
     * @param string $content Contenuto raw del robots.txt
     * @return array Lista di URL sitemap
     */
    public function extractSitemaps(string $content): array
    {
        $sitemaps = [];

        if (preg_match_all('/^Sitemap:\s*(.+)$/mi', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $url = trim($url);
                if (!empty($url)) {
                    $sitemaps[] = $url;
                }
            }
        }

        return $sitemaps;
    }

    /**
     * Estrai valore Crawl-delay dal contenuto robots.txt
     *
     * Restituisce il primo valore Crawl-delay trovato, o null se assente.
     *
     * @param string $content Contenuto raw del robots.txt
     * @return int|null Valore Crawl-delay in secondi, o null
     */
    public function extractCrawlDelay(string $content): ?int
    {
        if (preg_match('/^Crawl-delay:\s*(\d+)/mi', $content, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Verifica se una regola robots.txt matcha un path
     *
     * Supporta:
     * - Path esatti (es. /admin/)
     * - Wildcards con * (es. /*.pdf)
     * - End-of-string anchor con $ (es. /*.php$)
     *
     * Regola vuota non matcha nulla. Regola "/" matcha tutto.
     *
     * @param string $rule Regola robots.txt (es. "/admin/", "/*.pdf$")
     * @param string $path Path dell'URL (inclusa query string)
     * @return bool
     */
    private function ruleMatches(string $rule, string $path): bool
    {
        if ($rule === '') {
            return false;
        }

        if ($rule === '/') {
            return true;
        }

        // Converti la regola in pattern regex:
        // 1. Escape caratteri speciali regex
        // 2. Ripristina * come wildcard (.*) e $ come end-of-string
        $pattern = preg_quote($rule, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\$', '$', $pattern);

        return (bool) preg_match('#^' . $pattern . '#', $path);
    }
}
