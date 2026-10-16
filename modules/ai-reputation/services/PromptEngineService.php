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
    /**
     * $set: 'reputation' (cosa dicono di te) oppure 'purchase' (domande d'acquisto: il cliente cerca il servizio).
     */
    public function generate(int $creditUserId, array $project, int $target = 40, string $set = 'reputation'): array
    {
        $set = $set === 'purchase' ? 'purchase' : 'reputation';
        $facts = new ProfileFact();
        $truth = $facts->truth((int) $project['id']);
        $rejected = $facts->rejected((int) $project['id']);
        $prompts = new Prompt();
        $existing = array_map(fn($p) => $p['text'], $prompts->allByProject((int) $project['id']));

        $ai = new AiService(self::SLUG);
        $result = $ai->complete($creditUserId, [
            ['role' => 'user', 'content' => $this->userPrompt($project, $truth, $rejected, $existing, $target, $set)],
        ], ['system' => $set === 'purchase' ? self::purchaseSystemPrompt() : $this->systemPrompt(), 'max_tokens' => 8000, 'credit_metadata' => ['project_id' => (int) $project['id'], 'step' => 'prompts']], self::SLUG);
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
            if ($set === 'purchase' && !in_array($cluster, ['comm', 'comp'], true)) {
                $cluster = 'comp'; // le domande d'acquisto sono di bisogno (comp) o sul soggetto da cliente (comm)
            }
            $id = $prompts->create((int) $project['id'], $text, $cluster, $lang, 'ai', $set === 'reputation' && !empty($p['leading']), $set);
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

    /**
     * Domande d'acquisto (idea di Clemente 2026-10-13, caso Stabia: "tour privato in barca a Capri da Castellammare").
     * Misurano se le AI citano il soggetto quando un cliente cerca il servizio senza conoscerlo.
     */
    public static function purchaseSystemPrompt(): string
    {
        return <<<'TXT'
Sei il "prompt engine" di un sistema che misura se ChatGPT, Gemini e le altre AI citano un'attività quando un potenziale cliente cerca il servizio. Scrivi le domande che clienti reali farebbero a un assistente AI per decidere cosa comprare, prenotare o a chi rivolgersi.
Rispondi SOLO con JSON valido, senza markdown.

Cluster (campo "cluster"):
- comp: domande di bisogno SENZA il nome del soggetto (circa 75%). Chi chiede non conosce ancora il soggetto: vuole un servizio, un prodotto, una soluzione, un consiglio.
- comm: domande CON il nome del soggetto, da cliente che lo sta valutando: prezzi, cosa include, come prenotare, tempi, condizioni, confronto con un'alternativa (circa 25%).

Regole:
1. Parti da servizi, prodotti, luoghi, tipi di cliente e occasioni presenti nel profilo e nel sito. Usa i luoghi veri: di partenza e di arrivo, zone servite, città vicine da cui arrivano i clienti.
2. Varia l'intento: "come faccio a…", "dove posso…", "quanto costa…", "qual è il migliore per…", "conviene… o…?", "cosa mi consigli per…", "si può… da X a Y?". Includi domande pratiche con origine e destinazione (es. "come fare un tour in barca privata per Capri partendo da Castellammare di Stabia").
3. Varia il cliente (campo "persona", una parola): famiglia, coppia, gruppo, turista, azienda, principiante, esperto, budget, lusso…, e le occasioni (stagione, evento, festa, ultimo minuto).
4. Lingua (campo "lang"): "it" o "en". Se il profilo o il sito indicano clienti stranieri o turisti, fino al 40% in inglese, scritte come le scriverebbe uno straniero (anche con i nomi inglesi dei luoghi, es. "Amalfi Coast").
5. Le domande comp non nominano mai il soggetto né i suoi marchi. Non inventare servizi che il profilo non sostiene.
6. Domande naturali e brevi, come in chat. Niente duplicati o parafrasi. Non ripetere le domande già esistenti.
7. Niente domande sulla reputazione (affidabilità, truffe, recensioni negative): sono un altro audit.

Formato:
{"prompts":[{"cluster":"comp","lang":"it","persona":"famiglia","text":"..."}, ...]}
TXT;
    }

    private function userPrompt(array $project, array $truth, array $rejected, array $existing, int $target, string $set = 'reputation'): string
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
        if ($rejected && $set === 'reputation') {
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
        $lines[] = $set === 'purchase'
            ? "\nGenera {$target} domande d'acquisto nuove (circa 75% comp senza nome, 25% comm col nome)."
            : "\nGenera {$target} domande nuove.";
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
