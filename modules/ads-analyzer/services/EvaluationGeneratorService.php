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
     * @return string Testo formattato pronto per copia
     */
    public function generate(int $userId, string $type, array $context, array $data): string
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

        return $this->cleanResponse($response['result']);
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

FORMATI PER TIPO:
- SITELINK: Genera 4 sitelink. Per ciascuno: Titolo (max 25 char) | Descrizione 1 (max 35 char) | Descrizione 2 (max 35 char) | URL suggerito
- CALLOUT: Genera 6 callout (max 25 char ciascuno)
- STRUCTURED SNIPPET: Genera 1-2 snippet con Header (es: Servizi, Tipi, Destinazioni) + 4-5 valori (max 25 char ciascuno)
- PRICE EXTENSION: Genera 3-4 item con: Intestazione (max 25 char) | Descrizione (max 25 char) | Prezzo suggerito
- PROMOTION EXTENSION: Genera 1-2 promozioni con: Occasione | Descrizione (max 20 char)

Formatta come testo leggibile con sezioni separate per tipo. Mostra il conteggio caratteri tra parentesi.
Genera SOLO i tipi elencati in ESTENSIONI MANCANTI.
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

GENERA:
- 5 Headlines (max 30 caratteri ciascuna)
- 3 Descriptions (max 90 caratteri ciascuna)

Ogni headline e description deve essere diversa dagli annunci esistenti.
Formatta come testo leggibile. Mostra il conteggio caratteri tra parentesi per ogni elemento.
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

GENERA:
- 15-20 keyword negative
- Per ciascuna: keyword | match type consigliato (phrase o exact) | motivazione breve

Formatta come testo leggibile raggruppato per categoria.
PROMPT;
    }

    /**
     * Pulisce la risposta AI da eventuali markdown wrapper
     */
    private function cleanResponse(string $text): string
    {
        $text = preg_replace('/^```[\w]*\n?/', '', trim($text));
        $text = preg_replace('/\n?```$/', '', $text);
        return trim($text);
    }
}
