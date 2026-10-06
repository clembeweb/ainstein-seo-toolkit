<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Services\AiService;
use Modules\AiReputation\Models\Prompt;
use Modules\AiReputation\Models\ProfileFact;

/**
 * PromptEngineService - dal profilo confermato genera le domande che la gente fa alle AI (design §3.2).
 * Una chiamata AiService → 40-60 prompt (cluster, lingua, persona), salvati con origin = ai.
 */
class PromptEngineService
{
    private const SLUG = 'ai-reputation';

    /** @return array ['success' => bool, 'added' => int, 'skipped' => int, 'error' => ?string] */
    public function generate(int $creditUserId, array $project, int $target = 40): array
    {
        $facts = new ProfileFact();
        $truth = $facts->truth((int) $project['id']);
        $rejected = $facts->rejected((int) $project['id']);
        $prompts = new Prompt();
        $existing = array_map(fn($p) => $p['text'], $prompts->allByProject((int) $project['id']));

        $ai = new AiService(self::SLUG);
        $result = $ai->complete($creditUserId, [
            ['role' => 'user', 'content' => $this->userPrompt($project, $truth, $rejected, $existing, $target)],
        ], ['system' => $this->systemPrompt(), 'max_tokens' => 6000], self::SLUG);
        Database::reconnect();

        if (!empty($result['error']) || empty($result['result'])) {
            return ['success' => false, 'error' => $result['message'] ?? $result['error'] ?? 'Risposta AI vuota'];
        }
        try {
            $data = $this->parseJson((string) $result['result']);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'JSON domande non valido: ' . $e->getMessage()];
        }

        $seen = array_map(fn($t) => mb_strtolower(trim($t)), $existing);
        $added = 0;
        $skipped = 0;
        foreach ((array) ($data['prompts'] ?? []) as $p) {
            if (!is_array($p) || empty($p['text'])) {
                continue;
            }
            $text = trim((string) $p['text']);
            $key = mb_strtolower($text);
            if (mb_strlen($text) < 8 || in_array($key, $seen, true)) {
                $skipped++;
                continue;
            }
            $seen[] = $key;
            $cluster = in_array($p['cluster'] ?? '', array_keys(Prompt::CLUSTERS), true) ? $p['cluster'] : 'nav';
            $lang = in_array($p['lang'] ?? 'it', ['it', 'en'], true) ? $p['lang'] : 'it';
            $id = $prompts->create((int) $project['id'], $text, $cluster, $lang, 'ai', !empty($p['leading']));
            $persona = preg_replace('/[^a-z_]/', '', mb_strtolower((string) ($p['persona'] ?? 'neutro'))) ?: 'neutro';
            Database::update('ar_prompts', ['persona' => mb_substr($persona, 0, 50)], 'id = ?', [$id]);
            $added++;
        }
        return ['success' => true, 'added' => $added, 'skipped' => $skipped];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Sei il "prompt engine" di un sistema che misura cosa rispondono ChatGPT, Gemini e le altre AI su un soggetto (persona o azienda). Devi scrivere le domande che persone reali porrebbero a un assistente AI quando incontrano quel nome o cercano quel tipo di servizio.
Rispondi SOLO con JSON valido, senza markdown.

Cluster (campo "cluster"):
- nav: navigazionale — chi è, cosa fa, dove, contatti, sito, storia (circa 20%)
- rep: reputazionale — è affidabile? recensioni? problemi, controversie, notizie negative, "posso fidarmi?", "cosa dicono di lui" (circa 30%)
- comm: commerciale — domande di chi sta valutando di rivolgersi a lui/all'azienda: perché sceglierlo, costi, punti di forza, esperienza, casi (circa 25%)
- comp: competitivo — domande di categoria dove il soggetto potrebbe o dovrebbe comparire: "i migliori X a Y", "chi è esperto di Z", "alternative a", confronti (circa 25%)

Persona (campo "persona", una parola): neutro | investitore | cliente | giornalista | cliente_arrabbiato | partner | recruiter
Lingua (campo "lang"): "it" per quasi tutte; al massimo 15% in "en" se il soggetto ha una dimensione internazionale.

Regole:
1. Domande naturali, come le scrive la gente in chat: brevi, dirette, a volte colloquiali. Varia la forma (chi/cosa/perché/è vero che/mi conviene).
2. Le domande comp NON devono contenere il nome del soggetto: sono domande di categoria calibrate sul suo posizionamento reale (settore, città, specializzazione presi dal profilo). Servono a vedere se le AI lo citano spontaneamente.
3. Le domande rep devono includere sia forme neutre ("è affidabile?") sia forme ostili ("è stato coinvolto in...?", "truffa?") sia forme su fatti specifici dei temi di rischio del profilo, se presenti.
4. Niente duplicati o parafrasi della stessa domanda. Non ripetere le domande già esistenti elencate.
5. Usa il nome nella forma più usata ("Nome Cognome").
6. Campo "leading": true se la domanda NOMINA o PRESUPPONE un fatto negativo specifico del soggetto (es. "cosa è successo con la confisca del 2013?", "ha risolto i problemi legali?"); false per le domande generiche che chiunque fa ("è affidabile?", "ci sono notizie negative?", "truffe?"). Al massimo il 20% delle domande rep può essere "leading": il sistema misura soprattutto se l'AI tira fuori il negativo da sola.

Formato:
{"prompts":[{"cluster":"rep","lang":"it","persona":"investitore","leading":false,"text":"..."}, ...]}
TXT;
    }

    private function userPrompt(array $project, array $truth, array $rejected, array $existing, int $target): string
    {
        $subject = $project['subject_name'];
        $type = ($project['subject_type'] ?? 'person') === 'company' ? 'azienda' : 'persona';
        $lines = ["SOGGETTO: {$subject} ({$type})"];
        if (!empty($project['city'])) {
            $lines[] = "Città: {$project['city']}";
        }
        if (!empty($project['website'])) {
            $lines[] = "Sito: {$project['website']}";
        }
        if ($truth) {
            $lines[] = "\nPROFILO CONFERMATO:";
            foreach ($truth as $cat => $items) {
                foreach ($items as $t) {
                    $lines[] = "- [{$cat}] {$t}";
                }
            }
        } else {
            $lines[] = "\nPROFILO: non ancora confermato. Usa nome, tipo, città e sito; tieni le domande comp generiche sul settore che deduci.";
        }
        if ($rejected) {
            $lines[] = "\nCOSE CHE LE AI DICONO MA NON SONO VERE / NON SONO LUI (fai 1-2 domande rep per verificarle):";
            foreach ($rejected as $t) {
                $lines[] = "- {$t}";
            }
        }
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = "\nOMONIMI DICHIARATI: " . trim($project['disambiguation_notes']);
        }
        if ($existing) {
            $lines[] = "\nDOMANDE GIÀ ESISTENTI (non ripeterle):";
            foreach (array_slice($existing, 0, 80) as $t) {
                $lines[] = "- {$t}";
            }
        }
        $lines[] = "\nGenera {$target} domande nuove.";
        return implode("\n", $lines);
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
}
