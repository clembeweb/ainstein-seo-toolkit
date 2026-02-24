<?php

namespace Modules\AdsAnalyzer\Services;

use Core\Database;

require_once __DIR__ . '/../../../services/AiService.php';

/**
 * Genera contenuto AI per risolvere problemi identificati dalla valutazione campagne.
 * Pattern: CampaignEvaluatorService (costruttore AiService, prompt builder, AI call).
 */
class EvaluationGeneratorService
{
    private \Services\AiService $aiService;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ads-analyzer');
    }

    /**
     * Genera contenuto per un tipo specifico di fix
     *
     * @param int    $userId
     * @param string $type    extensions|copy|keywords
     * @param array  $context Dati specifici del problema (issue, recommendation, missing, campaign_name)
     * @param array  $data    Dati campagna (campaigns, ads, extensions, keywords, business_context)
     * @return array Dati strutturati JSON
     */
    public function generate(int $userId, string $type, array $context, array $data): array
    {
        $prompt = match (true) {
            str_contains($type, 'extensions') => $this->buildExtensionsPrompt($context, $data),
            str_contains($type, 'copy')       => $this->buildCopyPrompt($context, $data),
            str_contains($type, 'keywords')   => $this->buildKeywordsPrompt($context, $data),
            default => throw new \Exception('Tipo generazione non supportato: ' . $type),
        };

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->aiService->complete(
            $userId,
            $messages,
            ['max_tokens' => 3000],
            'ads-analyzer'
        );

        Database::reconnect();

        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }

        return $this->parseJsonResponse($response['result']);
    }

    /**
     * Prompt per generare estensioni mancanti
     */
    private function buildExtensionsPrompt(array $context, array $data): string
    {
        $missing = $context['missing'] ?? [];
        $missingList = implode(', ', $missing);

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Nomi campagne
        $campaignNames = array_map(fn($c) => $c['campaign_name'] ?? '', $data['campaigns'] ?? []);
        $campaignList = implode(', ', array_slice($campaignNames, 0, 5));

        // Estensioni esistenti (breve)
        $existingExt = [];
        foreach (($data['extensions'] ?? []) as $ext) {
            $type = $ext['extension_type'] ?? '';
            if (!in_array($type, $existingExt)) {
                $existingExt[] = $type;
            }
        }
        $existingList = !empty($existingExt) ? implode(', ', $existingExt) : 'Nessuna';

        return <<<PROMPT
Sei un esperto Google Ads certificato. Genera le estensioni annunci mancanti per queste campagne.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNE: {$campaignList}
ESTENSIONI ESISTENTI: {$existingList}
ESTENSIONI MANCANTI DA GENERARE: {$missingList}

ISTRUZIONI:
Per ogni tipo di estensione mancante, genera contenuti pronti da copiare in Google Ads.
Rispetta TASSATIVAMENTE i limiti caratteri di Google Ads.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "sitelinks": [
    {"title": "Titolo (max 25)", "desc1": "Desc 1 (max 35)", "desc2": "Desc 2 (max 35)", "url": "/percorso"}
  ],
  "callouts": ["Callout 1 (max 25)", "Callout 2"],
  "structured_snippets": [
    {"header": "Servizi", "values": ["Valore 1 (max 25)", "Valore 2"]}
  ]
}

Regole:
- 4 sitelink se SITELINK e tra le mancanti
- 6 callout se CALLOUT e tra le mancanti
- 1-2 structured snippet se STRUCTURED SNIPPET e tra le mancanti
- Genera SOLO i tipi elencati in ESTENSIONI MANCANTI
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare nuovi copy annunci (headline + description)
     */
    private function buildCopyPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Ads esistenti della campagna specifica (top 3)
        $existingAds = [];
        foreach (($data['ads'] ?? []) as $ad) {
            if (!empty($campaignName) && ($ad['campaign_name'] ?? '') !== $campaignName) {
                continue;
            }
            $headlines = [];
            for ($i = 1; $i <= 15; $i++) {
                $h = $ad["headline_{$i}"] ?? '';
                if ($h) $headlines[] = $h;
            }
            $descs = [];
            for ($i = 1; $i <= 4; $i++) {
                $d = $ad["description_{$i}"] ?? '';
                if ($d) $descs[] = $d;
            }
            if (!empty($headlines)) {
                $existingAds[] = "Headlines: " . implode(' | ', $headlines) . "\nDescriptions: " . implode(' | ', $descs);
            }
            if (count($existingAds) >= 3) break;
        }
        $adsText = !empty($existingAds) ? implode("\n\n", $existingAds) : 'Nessun annuncio esistente';

        return <<<PROMPT
Sei un copywriter esperto Google Ads specializzato in annunci RSA ad alta conversione.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA: {$campaignName}

ANNUNCI ESISTENTI:
{$adsText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Genera nuovi copy per risolvere il problema identificato.
Rispetta TASSATIVAMENTE i limiti caratteri Google Ads.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "headlines": ["Headline 1", "Headline 2", "Headline 3", "Headline 4", "Headline 5"],
  "descriptions": ["Description 1", "Description 2", "Description 3"],
  "paths": {"path1": "percorso1", "path2": "percorso2"}
}

Regole:
- Esattamente 5 headlines, ciascuna max 30 caratteri
- Esattamente 3 descriptions, ciascuna max 90 caratteri
- Paths opzionali, max 15 caratteri ciascuno
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare keyword negative
     */
    private function buildKeywordsPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Keyword esistenti della campagna (top 10)
        $existingKw = [];
        foreach (($data['keywords'] ?? []) as $kw) {
            if (!empty($campaignName) && ($kw['campaign_name'] ?? '') !== $campaignName) {
                continue;
            }
            $text = $kw['keyword_text'] ?? '';
            $match = $kw['match_type'] ?? '';
            $qs = $kw['quality_score'] ?? '';
            if ($text) {
                $existingKw[] = "{$text} [{$match}]" . ($qs ? " QS:{$qs}" : '');
            }
            if (count($existingKw) >= 10) break;
        }
        $kwText = !empty($existingKw) ? implode("\n", $existingKw) : 'Nessuna keyword disponibile';

        return <<<PROMPT
Sei un esperto Google Ads di keyword strategy e gestione keyword negative.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA: {$campaignName}

KEYWORD ESISTENTI:
{$kwText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Genera una lista di keyword negative per risolvere il problema identificato.
Raggruppa per tema/categoria.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "keywords": [
    {"keyword": "keyword text", "match_type": "phrase", "is_negative": true, "reason": "Motivazione breve"}
  ]
}

Regole:
- 15-20 keyword negative
- match_type: "exact" o "phrase"
- is_negative: sempre true
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Parsa la risposta AI come JSON, rimuovendo eventuali markdown wrapper
     */
    private function parseJsonResponse(string $text): array
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\n?```$/', '', $text);
        $text = trim($text);

        $result = json_decode($text, true);
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Risposta AI non valida (JSON): ' . json_last_error_msg());
        }
        return $result;
    }
}
