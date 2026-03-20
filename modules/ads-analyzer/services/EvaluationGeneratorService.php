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
     * @param string $type    fix_type: rewrite_ads|add_negatives|remove_duplicates|add_extensions (legacy: copy|keywords|extensions)
     * @param array  $context Dati specifici del problema (issue, recommendation, missing, campaign_name, ad_group_name)
     * @param array  $data    Dati campagna (campaigns, ads, extensions, keywords, business_context)
     * @return array Dati strutturati JSON
     */
    public function generate(int $userId, string $type, array $context, array $data): array
    {
        $prompt = match ($type) {
            'rewrite_ads', 'rewrite_ad' => $this->buildCopyPrompt($context, $data),
            'add_negatives'     => $this->buildNegativesPrompt($context, $data),
            'remove_duplicates' => $this->buildDuplicatesPrompt($context, $data),
            'add_extensions'    => $this->buildExtensionsPrompt($context, $data),
            'replace_asset'     => $this->buildReplaceAssetPrompt($context, $data),
            'add_asset'         => $this->buildAddAssetPrompt($context, $data),
            // Backwards compatibility
            'copy'              => $this->buildCopyPrompt($context, $data),
            'keywords'          => $this->buildNegativesPrompt($context, $data),
            'extensions'        => $this->buildExtensionsPrompt($context, $data),
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
        $scope = $context['scope'] ?? 'ad_group';

        if ($scope === 'asset_group') {
            return $this->buildPmaxCopyPrompt($context, $data);
        }

        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';
        $adGroupName = $context['ad_group_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Ads esistenti dell'ad group specifico (o campagna se non specificato)
        $existingAds = [];
        foreach (($data['ads'] ?? []) as $ad) {
            if (!empty($adGroupName) && ($ad['ad_group_name'] ?? '') !== $adGroupName) {
                continue;
            }
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
                $existingAds[] = "Ad Group: " . ($ad['ad_group_name'] ?? '?') . "\nHeadlines: " . implode(' | ', $headlines) . "\nDescriptions: " . implode(' | ', $descs);
            }
            if (count($existingAds) >= 5) break;
        }
        $adsText = !empty($existingAds) ? implode("\n\n", $existingAds) : 'Nessun annuncio esistente';

        // Keyword dell'ad group (per coerenza copy-keyword)
        $adGroupKeywords = [];
        foreach (($data['keywords'] ?? []) as $kw) {
            if (!empty($adGroupName) && ($kw['ad_group_name'] ?? '') !== $adGroupName) continue;
            if (!empty($campaignName) && ($kw['campaign_name'] ?? '') !== $campaignName) continue;
            $adGroupKeywords[] = ($kw['keyword_text'] ?? '') . ' [' . ($kw['match_type'] ?? '') . '] QS:' . ($kw['quality_score'] ?? '?');
            if (count($adGroupKeywords) >= 15) break;
        }
        $kwText = !empty($adGroupKeywords) ? implode("\n", $adGroupKeywords) : '';
        $kwBlock = !empty($kwText) ? "\nKEYWORD DELL'AD GROUP:\n{$kwText}\n" : '';

        $adGroupBlock = !empty($adGroupName) ? "\nAD GROUP: {$adGroupName}" : '';

        return <<<PROMPT
Sei un copywriter esperto Google Ads specializzato in annunci RSA ad alta conversione.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA: {$campaignName}{$adGroupBlock}
{$kwBlock}
ANNUNCI ESISTENTI:
{$adsText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Genera nuovi copy per risolvere il problema identificato.
I copy devono essere coerenti con le keyword dell'ad group.
Rispetta TASSATIVAMENTE i limiti caratteri Google Ads.
IMPORTANTE: I copy degli annunci devono essere nella STESSA LINGUA degli annunci esistenti e delle keyword.

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
- Includi le keyword principali dell'ad group nelle headline
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare nuovi asset per PMax asset group
     * Genera 15 headline (30 char) + 5 description (90 char) + 1 long headline (90 char)
     */
    private function buildPmaxCopyPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';
        $assetGroupName = $context['ad_group_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Get existing text assets from the evaluation data
        $existingAssets = [];
        // Try to find assets from the sync data passed in $data
        foreach (($data['ads'] ?? []) as $ad) {
            // In PMax context, ads array may contain asset group assets
            if (!empty($assetGroupName) && ($ad['ad_group_name'] ?? '') !== $assetGroupName) continue;
            for ($i = 1; $i <= 15; $i++) {
                $h = $ad["headline_{$i}"] ?? '';
                if ($h) $existingAssets[] = "Headline: {$h}";
            }
            for ($i = 1; $i <= 4; $i++) {
                $d = $ad["description_{$i}"] ?? '';
                if ($d) $existingAssets[] = "Description: {$d}";
            }
            if (count($existingAssets) >= 20) break;
        }
        $existingText = !empty($existingAssets) ? implode("\n", $existingAssets) : 'Nessun asset testuale esistente';

        return <<<PROMPT
Sei un esperto Performance Max Google Ads specializzato in asset creativi ad alta conversione.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA PERFORMANCE MAX: {$campaignName}
ASSET GROUP: {$assetGroupName}

ASSET TESTUALI ESISTENTI:
{$existingText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Genera nuovi asset testuali per risolvere il problema identificato nell'asset group Performance Max.
I testi devono essere diversificati: non ripetere lo stesso concetto con parole diverse.
IMPORTANTE: I testi devono essere nella STESSA LINGUA degli asset esistenti e del contesto business.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "headlines": ["H1", "H2", "H3", "H4", "H5", "H6", "H7", "H8", "H9", "H10", "H11", "H12", "H13", "H14", "H15"],
  "long_headlines": ["Long Headline 1"],
  "descriptions": ["Desc 1", "Desc 2", "Desc 3", "Desc 4", "Desc 5"]
}

Regole:
- Esattamente 15 headlines, ciascuna max 30 caratteri
- Esattamente 1 long headline, max 90 caratteri
- Esattamente 5 descriptions, ciascuna max 90 caratteri
- Headlines: mix di benefit, feature, CTA, social proof, urgency
- Diversifica: ogni headline deve comunicare un messaggio DIVERSO
- Includi keyword rilevanti per il business
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare keyword negative
     */
    private function buildNegativesPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';
        $adGroupName = $context['ad_group_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        $allKeywords = [];
        foreach (($data['keywords'] ?? []) as $kw) {
            if (!empty($campaignName) && ($kw['campaign_name'] ?? '') !== $campaignName) {
                continue;
            }
            $text = $kw['keyword_text'] ?? '';
            $match = $kw['match_type'] ?? '';
            $qs = $kw['quality_score'] ?? '';
            $ag = $kw['ad_group_name'] ?? '?';

            if ($text) {
                $allKeywords[] = "{$text} [{$match}]" . ($qs ? " QS:{$qs}" : '') . " — {$ag}";
            }
        }
        $kwText = !empty($allKeywords) ? implode("\n", array_slice($allKeywords, 0, 30)) : 'Nessuna keyword disponibile';
        $adGroupBlock = !empty($adGroupName) ? "\nAD GROUP IN ANALISI: {$adGroupName}" : '';

        return <<<PROMPT
Sei un esperto Google Ads di keyword strategy e gestione keyword negative.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA: {$campaignName}{$adGroupBlock}

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
     * Prompt per rimuovere keyword duplicate tra ad group
     */
    private function buildDuplicatesPrompt(array $context, array $data): string
    {
        $issue = $context['issue'] ?? $context['suggestion'] ?? '';
        $recommendation = $context['recommendation'] ?? $context['expected_impact'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';
        $adGroupName = $context['ad_group_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        $allKeywords = [];
        $keywordMap = [];
        foreach (($data['keywords'] ?? []) as $kw) {
            if (!empty($campaignName) && ($kw['campaign_name'] ?? '') !== $campaignName) {
                continue;
            }
            $text = $kw['keyword_text'] ?? '';
            $match = $kw['match_type'] ?? '';
            $qs = $kw['quality_score'] ?? '';
            $ag = $kw['ad_group_name'] ?? '?';
            $agId = $kw['ad_group_id_google'] ?? '';

            if ($text) {
                $allKeywords[] = "{$text} [{$match}]" . ($qs ? " QS:{$qs}" : '') . " — {$ag}";
                $key = mb_strtolower($text);
                if (!isset($keywordMap[$key])) $keywordMap[$key] = [];
                $keywordMap[$key][] = ['ad_group' => $ag, 'ad_group_id' => $agId, 'match_type' => $match, 'quality_score' => $qs, 'keyword_text' => $text];
            }
        }
        $kwText = !empty($allKeywords) ? implode("\n", array_slice($allKeywords, 0, 30)) : 'Nessuna keyword disponibile';
        $adGroupBlock = !empty($adGroupName) ? "\nAD GROUP IN ANALISI: {$adGroupName}" : '';

        // Elenca i duplicati trovati
        $duplicates = [];
        foreach ($keywordMap as $kwKey => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[$kwKey] = $occurrences;
            }
        }
        $dupText = '';
        foreach ($duplicates as $kwKey => $occs) {
            $dupText .= "\n- \"{$occs[0]['keyword_text']}\" appare in " . count($occs) . " ad group: " .
                implode(', ', array_map(fn($o) => $o['ad_group'] . ' [' . $o['match_type'] . '] QS:' . ($o['quality_score'] ?: '?'), $occs));
        }
        if (empty($dupText)) $dupText = "\nNessun duplicato trovato nei dati.";

        return <<<PROMPT
Sei un esperto Google Ads di keyword strategy e struttura account.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA: {$campaignName}{$adGroupBlock}

KEYWORD DUPLICATE TROVATE:
{$dupText}

TUTTE LE KEYWORD:
{$kwText}

PROBLEMA IDENTIFICATO:
{$issue}

RACCOMANDAZIONE:
{$recommendation}

ISTRUZIONI:
Analizza le keyword duplicate e per ciascuna indica dove RIMUOVERLE e dove MANTENERLE.
Per ogni keyword duplicata, decidi in quale ad group e piu pertinente e dove va rimossa.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "action": "remove_duplicates",
  "duplicates": [
    {
      "keyword": "keyword text",
      "keep_in": "Nome Ad Group dove mantenere",
      "remove_from": ["Nome Ad Group 1 da rimuovere", "Nome Ad Group 2"],
      "reason": "Motivazione breve"
    }
  ]
}

Regole:
- Elenca SOLO le keyword effettivamente duplicate (presenti in 2+ ad group)
- Per ogni duplicata, indica ESATTAMENTE dove mantenerla (l'ad group piu pertinente)
- remove_from: lista ad group da cui rimuovere
- Motivazione chiara e specifica
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare un asset sostitutivo per un asset PMax con performance LOW
     */
    private function buildReplaceAssetPrompt(array $context, array $data): string
    {
        $assetType = $context['asset_type'] ?? 'HEADLINE';
        $currentText = $context['current_text'] ?? '';
        $assetGroupName = $context['asset_group_name'] ?? $context['ad_group_name'] ?? '';
        $searchThemes = $context['search_themes'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Limiti caratteri per tipo asset
        $charLimits = [
            'HEADLINE' => 30,
            'LONG_HEADLINE' => 90,
            'DESCRIPTION' => 90,
        ];
        $maxChars = $charLimits[$assetType] ?? 90;

        // Add asset group context for better AI suggestions
        $assetGroupContext = $this->buildAssetGroupContext($data);

        return <<<PROMPT
Sei un esperto Google Ads Performance Max specializzato in asset creativi.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA PERFORMANCE MAX: {$campaignName}
ASSET GROUP: {$assetGroupName}
SEARCH THEMES: {$searchThemes}
{$assetGroupContext}
ASSET ATTUALE CON PERFORMANCE LOW:
Tipo: {$assetType}
Testo: "{$currentText}"

ISTRUZIONI:
Genera un asset {$assetType} sostitutivo per l'asset group '{$assetGroupName}'.
L'asset attuale '{$currentText}' ha performance LOW.
Genera un testo migliore, più specifico e pertinente rispetto ai search themes.
IMPORTANTE: Il testo deve essere nella STESSA LINGUA dell'asset attuale e del contesto business.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "asset_type": "{$assetType}",
  "original_text": "{$currentText}",
  "new_text": "Nuovo testo sostitutivo",
  "explanation": "Breve spiegazione del miglioramento"
}

Regole:
- Il nuovo testo deve essere max {$maxChars} caratteri
- Deve essere più specifico e pertinente ai search themes
- Non ripetere lo stesso concetto dell'originale
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Prompt per generare nuovi asset mancanti per un asset group PMax
     */
    private function buildAddAssetPrompt(array $context, array $data): string
    {
        $missingTypes = $context['missing_types'] ?? [];
        if (is_string($missingTypes)) {
            $missingTypes = json_decode($missingTypes, true) ?: explode(',', $missingTypes);
        }
        $missingList = implode(', ', array_map('trim', $missingTypes));

        $assetGroupName = $context['asset_group_name'] ?? $context['ad_group_name'] ?? '';
        $searchThemes = $context['search_themes'] ?? '';
        $campaignName = $context['campaign_name'] ?? '';

        $businessCtx = mb_substr($data['business_context'] ?? '', 0, 500);

        // Build JSON structure example based on missing types
        $jsonFields = [];
        foreach ($missingTypes as $mt) {
            $mt = strtoupper(trim($mt));
            if ($mt === 'HEADLINE') {
                $jsonFields[] = '"headlines": ["Headline 1 (max 30 char)", "Headline 2", "Headline 3"]';
            } elseif ($mt === 'LONG_HEADLINE') {
                $jsonFields[] = '"long_headlines": ["Long Headline 1 (max 90 char)"]';
            } elseif ($mt === 'DESCRIPTION') {
                $jsonFields[] = '"descriptions": ["Description 1 (max 90 char)", "Description 2"]';
            }
        }
        $jsonExample = "{\n  " . implode(",\n  ", $jsonFields) . "\n}";

        // Add asset group context for better AI suggestions
        $assetGroupContext = $this->buildAssetGroupContext($data);

        return <<<PROMPT
Sei un esperto Google Ads Performance Max specializzato in asset creativi.

CONTESTO BUSINESS:
{$businessCtx}

CAMPAGNA PERFORMANCE MAX: {$campaignName}
ASSET GROUP: {$assetGroupName}
SEARCH THEMES: {$searchThemes}
{$assetGroupContext}
ASSET MANCANTI DA GENERARE: {$missingList}

ISTRUZIONI:
L'asset group '{$assetGroupName}' necessita di nuovi asset per raggiungere il numero ottimale.
Genera i testi mancanti, diversificati e pertinenti ai search themes.
IMPORTANTE: I testi devono essere nella STESSA LINGUA del contesto business.

GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{$jsonExample}

Regole:
- Per HEADLINE: max 30 caratteri ciascuna
- Per LONG_HEADLINE: max 90 caratteri ciascuna
- Per DESCRIPTION: max 90 caratteri ciascuna
- Genera SOLO i tipi elencati in ASSET MANCANTI
- Headlines: mix di benefit, feature, CTA
- Diversifica: ogni testo deve comunicare un messaggio DIVERSO
- SOLO JSON valido, nessun commento o testo aggiuntivo
PROMPT;
    }

    /**
     * Build additional context from asset group data (other assets, search themes, audience signals)
     */
    private function buildAssetGroupContext(array $data): string
    {
        $context = '';

        $otherAssets = $data['other_assets'] ?? [];
        if (!empty($otherAssets)) {
            $context .= "\nALTRI ASSET NELLO STESSO GRUPPO (mantieni coerenza):\n";
            foreach (array_slice($otherAssets, 0, 20) as $a) {
                $type = $a['field_type'] ?? '';
                $text = $a['text_content'] ?? $a['url_content'] ?? '(media)';
                $context .= "- [{$type}] {$text}\n";
            }
        }

        $searchThemes = $data['search_themes_list'] ?? [];
        if (!empty($searchThemes)) {
            $context .= "\nSEARCH THEMES CONFIGURATI:\n";
            $context .= implode(', ', $searchThemes) . "\n";
        }

        $audienceSignals = $data['audience_signals'] ?? [];
        if (!empty($audienceSignals)) {
            $context .= "\nAUDIENCE SIGNALS CONFIGURATI:\n";
            foreach ($audienceSignals as $signal) {
                $type = $signal['type'] ?? 'unknown';
                $values = implode(', ', $signal['values'] ?? []);
                $context .= "- Tipo: {$type}, Valori: {$values}\n";
            }
        }

        return $context;
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
