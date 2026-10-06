<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Services\AiService;

/**
 * JudgeService - legge una risposta raccolta dal collector e produce un giudizio JSON rigido.
 *
 * Via AiService (Golden Rule 1): il judge gira sempre sul modello configurato per il modulo
 * (riproducibilità tra run). Non è un engine misurato: è lo strumento di misura.
 * Regole dal design §3.4 e ADR-008: verifica che le fonti parlino del soggetto, omonimia mai dedotta.
 */
class JudgeService
{
    private const SLUG = 'ai-reputation';
    private AiService $ai;

    public function __construct()
    {
        $this->ai = new AiService(self::SLUG);
    }

    public function model(): string
    {
        return $this->ai->getProvider() . '/' . $this->ai->getModel();
    }

    /**
     * @param array $project riga ar_projects (subject_name, subject_type, website, city, disambiguation_notes)
     * @param array $response riga ar_responses con prompt_text, prompt_cluster, text, citations[], sources_read[]
     * @return array giudizio normalizzato (vedi normalize()) oppure ['error' => string]
     */
    public function judge(int $creditUserId, array $project, array $response): array
    {
        $system = $this->systemPrompt();
        $user = $this->userPrompt($project, $response);

        $result = $this->ai->complete($creditUserId, [
            ['role' => 'user', 'content' => $user],
        ], [
            'system' => $system,
            'max_tokens' => 2000,
        ], self::SLUG);

        Database::reconnect();

        if (!empty($result['error']) || empty($result['result'])) {
            return ['error' => $result['message'] ?? $result['error'] ?? 'Risposta AI vuota'];
        }

        try {
            $data = $this->parseJson((string) $result['result']);
        } catch (\Throwable $e) {
            return ['error' => 'JSON judge non valido: ' . $e->getMessage(), 'raw_text' => $result['result']];
        }

        return $this->normalize($data, $response, $project);
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Sei il "judge" di un sistema che monitora la reputazione di un soggetto (persona o azienda) nelle risposte delle AI.
Ricevi: il soggetto monitorato, una domanda posta a un motore AI, la risposta del motore e le fonti che ha citato.
Devi giudicare la risposta in modo rigoroso e riproducibile. Rispondi SOLO con un oggetto JSON valido, senza markdown, senza testo prima o dopo.

Regole:
1. "outcome": "answered" se il motore ha risposto nel merito; "clarification_requested" se invece di rispondere ha chiesto a quale persona/azienda ci si riferisce o altri chiarimenti; "refused" se si è rifiutato; "empty" se la risposta è vuota o inutilizzabile.
2. "brand_mentioned": true solo se la risposta parla del soggetto monitorato (anche solo per nome). Se parla di un omonimo evidente, metti true e segnala in "is_homonym".
3. "is_homonym": "no" se la risposta parla chiaramente del soggetto; "yes" SOLO se il soggetto stesso ha dichiarato quell'omonimo nelle note di disambiguazione; "uncertain" se la risposta o una fonte attribuisce al nome fatti incompatibili col profilo (altra professione, altra città, altra epoca) e non puoi stabilire se sia la stessa persona. NON dedurre mai "yes" o "no" dal solo nome. Un omonimo ha lo STESSO nome e cognome del soggetto: persone con nome diverso e stesso cognome (es. parenti) NON sono omonimi, metti "no" e riportale in "claims". In "homonym_note" spiega in una frase cosa non torna (null se "no").
4. "sentiment": da -2 (molto negativo) a 2 (molto positivo), 0 neutro, riferito a come la risposta presenta il soggetto. Se non è menzionato: 0.
5. "verdict": "not_mentioned" se brand_mentioned è false; altrimenti "negative" se la risposta contiene fatti o giudizi negativi sul soggetto (procedimenti, truffe, sequestri, recensioni negative, dubbi espliciti sull'affidabilità attribuiti a lui); "positive" se lo presenta favorevolmente senza riserve; "mixed" se positivo e negativo insieme; "neutro" → usa "neutral" se descrittivo senza giudizio o se il motore dice di non poter giudicare.
6. "negative": true se il verdetto è "negative" o "mixed" con un fatto negativo concreto. "negative_reasons": elenco breve dei motivi. "negative_urls": gli URL delle fonti citate che sostengono o riportano i fatti negativi (sequestri, confische, inchieste, arresti, condanne, truffe, usura, mafia, recensioni negative). OBBLIGATORIO: se "negative" è true e tra le fonti citate ce n'è una il cui titolo o URL riguarda quei fatti, DEVE stare in "negative_urls". Una fonte negativa non può mai finire in "citations_noise" se la risposta la usa per parlare del soggetto.
7. Fonti: per ogni URL citato decidi se la pagina PARLA DEL SOGGETTO ("citations_about_subject") oppure è rumore ("citations_noise"). Rumore è SOLO una pagina chiaramente estranea: argomento diverso senza legame col soggetto, un omonimo dichiarato, una pagina indice generica. NON è rumore: il sito del soggetto, i suoi profili, articoli che lo citano o lo intervistano, e qualunque pagina che il motore usa per raccontare fatti del soggetto (anche cronaca giudiziaria il cui titolo non contiene il nome: se la risposta la usa per parlare di lui, parla di lui). Nel dubbio, "about_subject".
8b. "contradicts_profile": true se la risposta afferma qualcosa in contrasto con il PROFILO CONFERMATO (es. dice "nessun procedimento giudiziario" quando il profilo confermato riporta una confisca; attribuisce un'attività che il profilo non ha). In "contradiction_note" una frase su cosa contraddice. false se non c'è profilo confermato o non ci sono contrasti. OMETTERE NON È CONTRADDIRE: una risposta che non parla di un fatto (es. descrive solo l'attività) ha "contradicts_profile": false. Serve un'affermazione esplicita contraria (es. "non risultano procedimenti", "nessuna controversia").
2b. Se il motore chiede chiarimenti ma nomina il soggetto (anche tra le opzioni), "brand_mentioned" è true.
8. "competitors": nomi di persone o aziende che la risposta propone come alternative o come "i migliori" al posto del soggetto (solo se la domanda è commerciale o competitiva; altrimenti []).
9. "claims": massimo 6 affermazioni chiave sul soggetto, ciascuna {"text": "...", "negative": true|false}.
10. "summary": una frase in italiano, max 160 caratteri, che dice cosa risponde il motore sul soggetto. Lingua: italiano.

Formato esatto:
{"outcome":"answered","brand_mentioned":true,"mention_position":1,"is_homonym":"no","homonym_note":null,"sentiment":0,"verdict":"neutral","summary":"...","negative":false,"contradicts_profile":false,"contradiction_note":null,"negative_reasons":[],"negative_urls":[],"citations_about_subject":[],"citations_noise":[],"competitors":[],"claims":[]}
TXT;
    }

    private function userPrompt(array $project, array $response): string
    {
        $subject = $project['subject_name'];
        $type = ($project['subject_type'] ?? 'person') === 'company' ? 'azienda' : 'persona';
        $lines = [];
        $lines[] = "SOGGETTO MONITORATO: {$subject} ({$type})";
        if (!empty($project['city'])) {
            $lines[] = "Città: {$project['city']}";
        }
        if (!empty($project['website'])) {
            $lines[] = "Sito ufficiale: {$project['website']}";
        }
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = "Note di disambiguazione del soggetto (omonimi dichiarati, chi NON è lui): " . trim($project['disambiguation_notes']);
        } else {
            $lines[] = "Note di disambiguazione: nessuna (nessun omonimo dichiarato).";
        }
        $truth = $this->truth((int) $project['id']);
        if ($truth['facts']) {
            $lines[] = 'PROFILO CONFERMATO DAL SOGGETTO (verità di riferimento per claims e omonimia):';
            foreach ($truth['facts'] as $t) {
                $lines[] = "- {$t}";
            }
        }
        if ($truth['rejected']) {
            $lines[] = 'AFFERMAZIONI FALSE O DI ALTRI (se la risposta le ripete, segnalale in negative_reasons con sentiment negativo):';
            foreach ($truth['rejected'] as $t) {
                $lines[] = "- {$t}";
            }
        }
        $lines[] = '';
        $lines[] = "DOMANDA POSTA AL MOTORE (cluster {$response['prompt_cluster']}): " . $response['prompt_text'];
        $lines[] = "MOTORE: {$response['engine']}" . (!empty($response['model']) ? " ({$response['model']})" : '');
        $lines[] = '';
        $lines[] = 'RISPOSTA DEL MOTORE:';
        $lines[] = '"""';
        $lines[] = mb_substr(trim((string) $response['text']), 0, 7000);
        $lines[] = '"""';
        $lines[] = '';
        $citations = $response['citations'] ?? [];
        if (empty($citations)) {
            $lines[] = 'FONTI CITATE: nessuna.';
        } else {
            $lines[] = 'FONTI CITATE (url | titolo | snippet se disponibile):';
            $snippets = [];
            foreach ($response['sources_read'] ?? [] as $s) {
                if (!empty($s['url']) && !empty($s['snippet'])) {
                    $snippets[$s['url']] = $s['snippet'];
                }
            }
            foreach (array_slice($citations, 0, 20) as $c) {
                $line = '- ' . $c['url'] . ' | ' . ($c['title'] ?? '');
                if (!empty($snippets[$c['url']])) {
                    $line .= ' | ' . mb_substr($snippets[$c['url']], 0, 300);
                }
                $lines[] = $line;
            }
        }
        return implode("\n", $lines);
    }

    private array $truthCache = [];

    /** Verità del brand e righe rifiutate dal profilo (cache per progetto nel corso di un run) */
    private function truth(int $projectId): array
    {
        if (!isset($this->truthCache[$projectId])) {
            $facts = new \Modules\AiReputation\Models\ProfileFact();
            $flat = [];
            foreach ($facts->truth($projectId) as $cat => $items) {
                if ($cat === 'source') {
                    continue;
                }
                foreach ($items as $t) {
                    $flat[] = "[{$cat}] {$t}";
                }
            }
            $this->truthCache[$projectId] = ['facts' => array_slice($flat, 0, 40), 'rejected' => array_slice($facts->rejected($projectId), 0, 20)];
        }
        return $this->truthCache[$projectId];
    }

    private function parseJson(string $text): array
    {
        $s = preg_replace('/```json\s*/i', '', $text);
        $s = preg_replace('/```\s*/', '', $s);
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        if ($a === false || $b === false) {
            throw new \RuntimeException('nessun oggetto JSON');
        }
        $data = json_decode(substr($s, $a, $b - $a + 1), true);
        if (!is_array($data)) {
            throw new \RuntimeException(json_last_error_msg());
        }
        return $data;
    }

    /** Titolo/URL che parla di cronaca giudiziaria o reputazione negativa (fallback, non sostituisce il judge) */
    public static function looksNegative(string $text): bool
    {
        $text = mb_strtolower($text);
        $keywords = ['sequestr', 'confisc', 'ndranghet', 'mafi', 'camorr', 'arrest', 'inchiest', 'indagin', 'condann',
            'truff', 'frode', 'usura', 'riciclagg', 'procura', 'tribunal', 'cassazion', 'reato', 'antimafia', 'denunci',
            'bancarott', 'fallimento', 'corruzion', 'tangent', 'scandal', 'spaccio', 'cocaina', 'droga', 'omicid'];
        foreach ($keywords as $k) {
            if (str_contains($text, $k)) {
                return true;
            }
        }
        return false;
    }

    private function normalize(array $d, array $response, array $project = []): array
    {
        $outcome = in_array($d['outcome'] ?? '', ['answered', 'clarification_requested', 'refused', 'empty'], true) ? $d['outcome'] : 'answered';
        $mentioned = (bool) ($d['brand_mentioned'] ?? false);
        $verdict = is_string($d['verdict'] ?? null) ? $d['verdict'] : ($mentioned ? 'neutral' : 'not_mentioned');
        if ($verdict === 'neutro') {
            $verdict = 'neutral';
        }
        if (!in_array($verdict, ['positive', 'neutral', 'mixed', 'negative', 'not_mentioned'], true)) {
            $verdict = $mentioned ? 'neutral' : 'not_mentioned';
        }
        if (!$mentioned) {
            $verdict = 'not_mentioned';
        }
        $ih = is_string($d['is_homonym'] ?? null) ? $d['is_homonym'] : 'no';
        $isHomonym = in_array($ih, ['no', 'yes', 'uncertain'], true) ? $ih : 'no';
        $sentiment = max(-2, min(2, (int) ($d['sentiment'] ?? 0)));
        $negative = (bool) ($d['negative'] ?? ($verdict === 'negative'));

        $citedUrls = array_map(fn($c) => $c['url'], $response['citations'] ?? []);
        $about = array_values(array_intersect($citedUrls, (array) ($d['citations_about_subject'] ?? [])));
        $noise = array_values(array_intersect($citedUrls, (array) ($d['citations_noise'] ?? [])));
        // Controllo a campione 2026-10-06: il judge segnava come "rumore" il sito del soggetto e articoli su di lui.
        $ownDomain = !empty($project['website']) ? EngineCollectorService::domainOf((string) $project['website']) : null;
        $nameParts = preg_split('/\s+/', mb_strtolower(trim((string) ($project['subject_name'] ?? ''))));
        $surname = $nameParts ? end($nameParts) : '';
        $titles = [];
        foreach ($response['citations'] ?? [] as $c) {
            $titles[$c['url']] = (string) ($c['title'] ?? '');
        }
        $noise = array_values(array_filter($noise, function ($u) use ($ownDomain, $surname, $titles) {
            $hay = mb_strtolower(urldecode($u) . ' ' . ($titles[$u] ?? ''));
            if ($ownDomain && EngineCollectorService::domainOf($u) === $ownDomain) {
                return false;
            }
            return !(mb_strlen($surname) >= 4 && str_contains($hay, $surname));
        }));
        // URL citati non classificati dal judge → nel dubbio "about_subject"
        foreach ($citedUrls as $u) {
            if (!in_array($u, $about, true) && !in_array($u, $noise, true)) {
                $about[] = $u;
            }
        }
        $negativeUrls = array_values(array_intersect($citedUrls, (array) ($d['negative_urls'] ?? [])));
        // Rete di sicurezza deterministica: risposta negativa ma judge senza URL → fonti con titolo/URL da cronaca giudiziaria
        if ($negative && empty($negativeUrls)) {
            foreach ($response['citations'] ?? [] as $c) {
                if (self::looksNegative(($c['title'] ?? '') . ' ' . $c['url'])) {
                    $negativeUrls[] = $c['url'];
                }
            }
            $noise = array_values(array_diff($noise, $negativeUrls));
            foreach ($negativeUrls as $u) {
                if (!in_array($u, $about, true)) {
                    $about[] = $u;
                }
            }
        }
        $domains = [];
        foreach ($about as $u) {
            $dom = EngineCollectorService::domainOf($u);
            if ($dom) {
                $domains[$dom] = true;
            }
        }
        $claims = [];
        foreach (array_slice((array) ($d['claims'] ?? []), 0, 6) as $c) {
            if (is_array($c) && !empty($c['text'])) {
                $claims[] = ['text' => mb_substr((string) $c['text'], 0, 300), 'negative' => (bool) ($c['negative'] ?? false)];
            } elseif (is_string($c) && $c !== '') {
                $claims[] = ['text' => mb_substr($c, 0, 300), 'negative' => false];
            }
        }
        $competitors = [];
        foreach ((array) ($d['competitors'] ?? []) as $name) {
            if (is_string($name) && trim($name) !== '') {
                $competitors[] = mb_substr(trim($name), 0, 120);
            }
        }

        return [
            'outcome' => $outcome,
            'brand_mentioned' => $mentioned ? 1 : 0,
            'mention_position' => isset($d['mention_position']) && is_numeric($d['mention_position']) ? (int) $d['mention_position'] : null,
            'is_homonym' => $isHomonym,
            'homonym_note' => !empty($d['homonym_note']) ? mb_substr((string) $d['homonym_note'], 0, 500) : null,
            'sentiment' => $sentiment,
            'verdict' => $verdict,
            'summary' => mb_substr((string) ($d['summary'] ?? ''), 0, 500),
            'negative' => $negative ? 1 : 0,
            'negative_reasons' => array_values(array_filter(array_map(
                fn($x) => is_scalar($x) ? (string) $x : json_encode($x, JSON_UNESCAPED_UNICODE),
                (array) ($d['negative_reasons'] ?? [])
            ))),
            'negative_urls' => $negativeUrls,
            'cited_domains' => array_keys($domains),
            'citations_noise' => $noise,
            'contradicts_profile' => !empty($d['contradicts_profile']) ? 1 : 0,
            'contradiction_note' => !empty($d['contradiction_note']) && is_string($d['contradiction_note']) ? mb_substr($d['contradiction_note'], 0, 500) : null,
            'competitors' => array_values(array_unique($competitors)),
            'claims' => $claims,
            'judge_model' => $this->model(),
            'raw' => $d,
        ];
    }
}
