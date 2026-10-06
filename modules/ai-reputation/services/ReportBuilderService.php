<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Modules\AiReputation\Models\Project;

/**
 * ReportBuilderService - dai giudizi del judge produce metriche, Source Map, competitor,
 * piano d'azione (regole, ADR-007) e righe "omonimo da confermare" (ADR-008).
 * Nessuna chiamata AI: tutto derivato (design §3.5, §3.6).
 */
class ReportBuilderService
{
    /**
     * Metriche di un run a partire da risposte + analisi (indicizzate per response_id).
     *
     * ADR-011: share of voice, negative, divergenza e rischio si calcolano SOLO sulle domande neutre.
     * Le domande "mirate" (che nominano già un fatto negativo) misurano un'altra cosa — cosa esce se
     * qualcuno sa già cosa cercare — e vanno in un blocco a parte.
     *
     * Rischio = % pesata (per engine) di risposte negative alle domande neutre del cluster rep.
     * Non satura: è 100 solo se tutte le AI rispondono in negativo a tutte le domande reputazionali.
     */
    public function metrics(array $responses, array $analyses, array $engines, array $project): array
    {
        $weights = [];
        foreach ($engines as $e) {
            $weights[$e] = Project::engineWeight($e);
        }

        $ok = 0;
        $judged = 0;
        $wTotal = 0.0;
        $wMentioned = 0.0;
        $mentionedN = 0;
        $sentSum = 0;
        $sentN = 0;
        $negative = 0;
        $negByEngine = [];
        $verdicts = ['positive' => 0, 'neutral' => 0, 'mixed' => 0, 'negative' => 0, 'not_mentioned' => 0];
        $clarifications = 0;
        $homonymUncertain = 0;
        $negRepW = 0.0;
        $repW = 0.0;
        $repN = 0;
        $repNeg = 0;
        $negDomains = [];
        $byPrompt = [];
        $lead = ['total' => 0, 'negative' => 0, 'by_engine' => [], 'prompts' => []];

        foreach ($responses as $r) {
            if ($r['status'] !== 'ok') {
                continue;
            }
            $ok++;
            $a = $analyses[(int) $r['id']] ?? null;
            if (!$a) {
                continue;
            }
            $isNeg = (int) $a['negative'] === 1;
            foreach ($a['negative_urls'] as $u) {
                $d = EngineCollectorService::domainOf($u);
                if ($d) {
                    $negDomains[$d] = true; // le fonti negative sono reali comunque si sia arrivati a leggerle
                }
            }

            // Domande mirate: blocco a parte
            if ((int) ($r['prompt_leading'] ?? 0) === 1) {
                $lead['total']++;
                $lead['prompts'][(int) $r['prompt_id']] = $r['prompt_text'];
                if ($isNeg) {
                    $lead['negative']++;
                    $lead['by_engine'][$r['engine']] = ($lead['by_engine'][$r['engine']] ?? 0) + 1;
                }
                continue;
            }

            $judged++;
            $w = $weights[$r['engine']] ?? 1.0;
            $wTotal += $w;
            if ((int) $a['brand_mentioned'] === 1) {
                $wMentioned += $w;
                $mentionedN++;
                $sentSum += (int) $a['sentiment'];
                $sentN++;
            }
            $verdicts[$a['verdict']] = ($verdicts[$a['verdict']] ?? 0) + 1;
            if ($a['outcome'] === 'clarification_requested') {
                $clarifications++;
            }
            if ($a['is_homonym'] === 'uncertain') {
                $homonymUncertain++;
            }
            if ($isNeg) {
                $negative++;
                $negByEngine[$r['engine']] = ($negByEngine[$r['engine']] ?? 0) + 1;
            }
            if ($r['prompt_cluster'] === 'rep') {
                $repW += $w;
                $repN++;
                if ($isNeg) {
                    $negRepW += $w;
                    $repNeg++;
                }
            }
            $pid = (int) $r['prompt_id'];
            $byPrompt[$pid] ??= ['prompt' => $r['prompt_text'], 'cluster' => $r['prompt_cluster'], 'engines' => [], 'negative' => [], 'positive' => []];
            $byPrompt[$pid]['engines'][$r['engine']] = true;
            if ($isNeg) {
                $byPrompt[$pid]['negative'][$r['engine']] = true;
            } elseif ($a['verdict'] === 'positive') {
                $byPrompt[$pid]['positive'][$r['engine']] = true;
            }
        }

        // Divergenza (solo domande neutre): almeno un engine negativo e almeno uno no
        $divergent = [];
        foreach ($byPrompt as $pid => $p) {
            $n = count($p['engines']);
            $neg = count($p['negative']);
            if ($neg > 0 && $neg < $n) {
                $divergent[] = ['prompt_id' => $pid, 'prompt' => $p['prompt'], 'cluster' => $p['cluster'], 'negative' => $neg, 'total' => $n,
                    'negative_engines' => array_keys($p['negative']), 'positive_engines' => array_keys($p['positive'])];
            }
        }
        usort($divergent, fn($a, $b) => ($b['cluster'] === 'rep') <=> ($a['cluster'] === 'rep'));

        // Rischio: % pesata di risposte negative alle domande neutre sulla reputazione
        if ($repW > 0) {
            $risk = (int) round($negRepW / $repW * 100);
            $riskBasis = "{$repNeg} su {$repN} risposte neutre sulla reputazione";
        } else {
            $risk = $wTotal > 0 ? (int) round($negative / max(1, $judged) * 100) : 0;
            $riskBasis = "{$negative} su {$judged} risposte neutre";
        }
        $riskLabel = $judged === 0 ? '–' : ($risk >= 30 ? 'Alto' : ($risk >= 10 ? 'Medio' : 'Basso'));

        return [
            'ok' => $ok,
            'judged' => $judged,
            'share' => $wTotal > 0 ? (int) round($wMentioned / $wTotal * 100) : 0,
            'mentioned_n' => $mentionedN,
            'sentiment' => $sentN > 0 ? round($sentSum / $sentN, 1) : null,
            'negative' => $negative,
            'negative_by_engine' => $negByEngine,
            'verdicts' => $verdicts,
            'clarifications' => $clarifications,
            'homonym_uncertain' => $homonymUncertain,
            'risk' => $risk,
            'risk_label' => $riskLabel,
            'risk_basis' => $riskBasis,
            'negative_domains' => count($negDomains),
            'divergent' => $divergent,
            'leading' => $lead,
        ];
    }

    /**
     * Source Map del run: domini citati con conteggi, stato (ok / negativa / rumore), pagine ed engine.
     */
    public function sources(array $responses, array $analyses): array
    {
        $domains = [];
        foreach ($responses as $r) {
            if ($r['status'] !== 'ok') {
                continue;
            }
            $a = $analyses[(int) $r['id']] ?? null;
            $noise = $a ? array_flip($a['citations_noise']) : [];
            $negUrls = $a ? array_flip($a['negative_urls']) : [];
            foreach ($r['citations'] as $c) {
                $d = $c['domain'] ?? EngineCollectorService::domainOf($c['url']);
                if (!$d) {
                    continue;
                }
                $domains[$d] ??= ['domain' => $d, 'count' => 0, 'noise' => 0, 'negative' => 0, 'engines' => [], 'urls' => []];
                $domains[$d]['count']++;
                $domains[$d]['engines'][$r['engine']] = true;
                if (isset($noise[$c['url']])) {
                    $domains[$d]['noise']++;
                }
                if (isset($negUrls[$c['url']])) {
                    $domains[$d]['negative']++;
                }
                $domains[$d]['urls'][$c['url']] ??= ['title' => $c['title'] ?? '', 'negative' => isset($negUrls[$c['url']]), 'noise' => isset($noise[$c['url']])];
            }
        }
        foreach ($domains as &$d) {
            $d['status'] = $d['negative'] > 0 ? 'negative' : ($d['noise'] >= $d['count'] ? 'noise' : 'ok');
        }
        unset($d);
        usort($domains, fn($a, $b) => [$b['negative'] > 0, $b['count']] <=> [$a['negative'] > 0, $a['count']]);
        return array_values($domains);
    }

    /**
     * Competitor emersi dalle analisi, con conteggio menzioni.
     */
    public function competitors(array $analyses): array
    {
        $names = [];
        foreach ($analyses as $a) {
            foreach ($a['competitors'] as $name) {
                $key = mb_strtolower(trim($name));
                $names[$key] ??= ['name' => trim($name), 'count' => 0];
                $names[$key]['count']++;
            }
        }
        usort($names, fn($a, $b) => $b['count'] <=> $a['count']);
        return array_values($names);
    }

    /**
     * Piano d'azione (regole deterministiche, design §3.6 + ADR-007). Ritorna righe pronte per ar_actions.
     */
    public function actions(array $project, array $responses, array $analyses, array $sources, array $engineLabels): array
    {
        $actions = [];
        $ownDomain = !empty($project['website']) ? EngineCollectorService::domainOf($project['website']) : null;

        // Testata suggerita: dominio ok più citato, non il sito del soggetto
        $suggested = null;
        foreach ($sources as $s) {
            if ($s['status'] === 'ok' && $s['domain'] !== $ownDomain) {
                $suggested = $s['domain'];
                break;
            }
        }

        // 1. removal: ogni URL negativo citato
        $seenUrl = [];
        $repNegative = [];
        $gapByPrompt = [];
        foreach ($responses as $r) {
            $a = $analyses[(int) $r['id']] ?? null;
            if (!$a) {
                continue;
            }
            $engine = $engineLabels[$r['engine']] ?? $r['engine'];
            foreach ($a['negative_urls'] as $u) {
                $seenUrl[$u]['engines'][$engine] = true;
                $seenUrl[$u]['prompt'] = $r['prompt_text'];
                foreach ($r['citations'] as $c) {
                    if ($c['url'] === $u) {
                        $seenUrl[$u]['title'] = $c['title'] ?? '';
                    }
                }
            }
            if ($r['prompt_cluster'] === 'rep' && ((int) $a['negative'] === 1 || $a['is_homonym'] === 'uncertain')) {
                $repNegative[$r['prompt_id']]['prompt'] = $r['prompt_text'];
                $repNegative[$r['prompt_id']]['engines'][$engine] = true;
                $repNegative[$r['prompt_id']]['homonym'] = ($repNegative[$r['prompt_id']]['homonym'] ?? false) || $a['is_homonym'] === 'uncertain';
            }
            if (in_array($r['prompt_cluster'], ['comm', 'comp'], true) && (int) $a['brand_mentioned'] === 0 && !empty($a['competitors'])) {
                $gapByPrompt[$r['prompt_id']]['prompt'] = $r['prompt_text'];
                foreach ($a['competitors'] as $name) {
                    $gapByPrompt[$r['prompt_id']]['competitors'][$name] = true;
                }
                $gapByPrompt[$r['prompt_id']]['engines'][$engine] = true;
            }
        }
        foreach ($seenUrl as $url => $info) {
            $domain = EngineCollectorService::domainOf($url);
            $actions[] = [
                'type' => 'removal',
                'target_url' => $url,
                'target_domain' => $domain,
                'title' => 'Fonte negativa: ' . ($info['title'] ?: $url),
                'rationale' => 'Citata da ' . implode(', ', array_keys($info['engines'])) . ' alla domanda "' . $info['prompt'] . '". Valutare rimozione, deindicizzazione o aggiornamento della pagina.',
            ];
        }
        // 2. counter_content: UNA azione per tutte le domande rep negative (+ una di disambiguazione se serve).
        //    Una pagina autorevole ben fatta risponde a tutte: dieci azioni quasi uguali gonfiano il piano.
        if ($repNegative) {
            $subject = $project['subject_name'];
            $engines = [];
            $prompts = [];
            $homonym = false;
            foreach ($repNegative as $info) {
                $prompts[] = $info['prompt'];
                $engines += $info['engines'];
                $homonym = $homonym || $info['homonym'];
            }
            $examples = array_slice($prompts, 0, 3);
            $more = count($prompts) - count($examples);
            $actions[] = [
                'type' => 'counter_content',
                'target_url' => null,
                'target_domain' => $suggested,
                'title' => "Pagina autorevole che risponda a \"{$subject} è affidabile?\"",
                'rationale' => 'Le AI rispondono in negativo o con dubbi a ' . count($prompts) . ' domande reputazionali, tra cui: "'
                    . implode('", "', $examples) . '"' . ($more > 0 ? " e altre {$more}" : '') . '. '
                    . 'Una sola pagina ben fatta le copre tutte: chi è, cosa fa oggi, i fatti passati con esito e contesto, referenze verificabili'
                    . ($suggested ? ", pubblicata su una testata che le AI già citano ({$suggested})." : '.')
                    . ' Engine coinvolti: ' . implode(', ', array_keys($engines)) . '.',
            ];
            if ($homonym) {
                $actions[] = [
                    'type' => 'counter_content',
                    'target_url' => null,
                    'target_domain' => $suggested,
                    'title' => "Disambiguazione: chi è (e chi non è) {$subject}",
                    'rationale' => 'Alcune risposte mescolano il soggetto con persone omonime o attribuiscono fatti incerti. '
                        . 'Serve un contenuto che separi chiaramente le identità (attività, città, periodo), da collegare al sito ufficiale.',
                ];
            }
        }
        // 3. gap_article: domande comm/comp senza menzione ma con competitor
        foreach ($gapByPrompt as $info) {
            $names = array_slice(array_keys($info['competitors']), 0, 6);
            $actions[] = [
                'type' => 'gap_article',
                'target_url' => null,
                'target_domain' => $suggested,
                'title' => 'Non citato: "' . $info['prompt'] . '"',
                'rationale' => 'Le AI citano ' . implode(', ', $names) . ' e non il soggetto. Serve un contenuto che posizioni il soggetto su questa domanda'
                    . ($suggested ? ", su {$suggested} o testata equivalente." : '.'),
            ];
        }
        return $actions;
    }

    /**
     * Persiste Source Map, competitor, piano d'azione e omonimi da confermare per un run.
     */
    public function persist(int $runId, int $projectId, array $project, array $responses, array $analyses, array $engineLabels): array
    {
        $sources = $this->sources($responses, $analyses);
        $competitors = $this->competitors($analyses);
        $actions = $this->actions($project, $responses, $analyses, $sources, $engineLabels);

        foreach ($sources as $s) {
            $sentiments = [];
            Database::execute("
                INSERT INTO ar_sources (project_id, domain, citations_count, negative_count, first_seen_run_id, last_seen_run_id)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE citations_count = citations_count + VALUES(citations_count),
                    negative_count = negative_count + VALUES(negative_count), last_seen_run_id = VALUES(last_seen_run_id)
            ", [$projectId, $s['domain'], $s['count'], $s['negative'], $runId, $runId]);
        }
        foreach ($competitors as $c) {
            Database::execute("
                INSERT INTO ar_competitors (project_id, name, mentions_count, first_seen_run_id, last_seen_run_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE mentions_count = mentions_count + VALUES(mentions_count), last_seen_run_id = VALUES(last_seen_run_id)
            ", [$projectId, $c['name'], $c['count'], $runId, $runId]);
        }
        Database::delete('ar_actions', 'run_id = ?', [$runId]);
        foreach ($actions as $a) {
            Database::insert('ar_actions', array_merge($a, ['project_id' => $projectId, 'run_id' => $runId, 'status' => 'proposed']));
        }

        // Omonimi da confermare (ADR-008): una riga per nota distinta, solo se non già presente
        $notes = [];
        foreach ($analyses as $a) {
            // Solo risposte nel merito: chi chiede "quale Marcaccini?" non attribuisce nulla (ADR-008)
            if ($a['is_homonym'] === 'uncertain' && !empty($a['homonym_note']) && $a['outcome'] === 'answered') {
                $notes[mb_strtolower(trim($a['homonym_note']))] = trim($a['homonym_note']);
            }
        }
        foreach ($notes as $note) {
            $exists = Database::fetch("SELECT id FROM ar_profile_facts WHERE project_id = ? AND category = 'homonym' AND text = ?", [$projectId, $note]);
            if (!$exists) {
                Database::insert('ar_profile_facts', [
                    'project_id' => $projectId, 'category' => 'homonym', 'text' => $note,
                    'status' => 'proposed', 'origin' => 'run', 'run_id' => $runId,
                ]);
            }
        }

        return ['sources' => $sources, 'competitors' => $competitors, 'actions' => $actions, 'homonyms' => count($notes)];
    }
}
