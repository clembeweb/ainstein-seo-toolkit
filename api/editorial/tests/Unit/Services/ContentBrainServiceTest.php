<?php

declare(strict_types=1);

namespace Editorial\Tests\Unit\Services;

use Editorial\Services\ContentBrainService;
use PHPUnit\Framework\TestCase;
use Services\AiService;
use Services\ScraperService;

/**
 * Unit tests per ContentBrainService.
 *
 * Coperti: parseAiJson() (5 fixture), validatePatch() via update() (tone enum,
 * glossary shape, guidelines shape).
 *
 * NB: i metodi che toccano DB (scan, get, persistBrain, update con DB) NON
 * sono coperti qui — vedi tests/Integration in M2.2 + smoke manuale M2.8.
 */
final class ContentBrainServiceTest extends TestCase
{
    private function makeService(): ContentBrainService
    {
        $ai = $this->createMock(AiService::class);
        $scraper = $this->createMock(ScraperService::class);
        return new ContentBrainService($ai, $scraper);
    }

    // -------------------------------------------------------------------
    // parseAiJson
    // -------------------------------------------------------------------

    public function testParseAiJsonClean(): void
    {
        $svc = $this->makeService();
        $input = '{"tone":"friendly","summary":"Voce amichevole"}';
        $out = $svc->parseAiJson($input);
        $this->assertNotNull($out);
        $this->assertSame('friendly', $out['tone']);
        $this->assertSame('Voce amichevole', $out['summary']);
    }

    public function testParseAiJsonWithMarkdownWrapper(): void
    {
        $svc = $this->makeService();
        $input = "```json\n{\"tone\":\"professional\",\"language\":\"it\"}\n```";
        $out = $svc->parseAiJson($input);
        $this->assertNotNull($out);
        $this->assertSame('professional', $out['tone']);
        $this->assertSame('it', $out['language']);
    }

    public function testParseAiJsonWithGenericCodeFence(): void
    {
        $svc = $this->makeService();
        $input = "```\n{\"tone\":\"casual\"}\n```";
        $out = $svc->parseAiJson($input);
        $this->assertNotNull($out);
        $this->assertSame('casual', $out['tone']);
    }

    public function testParseAiJsonWithPrefacingText(): void
    {
        $svc = $this->makeService();
        $input = 'Ecco la mia analisi: {"tone":"fun","summary":"Tono divertente"}';
        $out = $svc->parseAiJson($input);
        $this->assertNotNull($out, 'Deve estrarre il primo blocco { ... }');
        $this->assertSame('fun', $out['tone']);
    }

    public function testParseAiJsonMalformedReturnsNull(): void
    {
        $svc = $this->makeService();
        $this->assertNull($svc->parseAiJson('{not valid json'));
    }

    public function testParseAiJsonEmptyReturnsNull(): void
    {
        $svc = $this->makeService();
        $this->assertNull($svc->parseAiJson(''));
        $this->assertNull($svc->parseAiJson('   '));
    }

    public function testParseAiJsonPlainTextReturnsNull(): void
    {
        $svc = $this->makeService();
        $this->assertNull($svc->parseAiJson('Mi dispiace, non sono in grado di rispondere.'));
    }

    // -------------------------------------------------------------------
    // validatePatch (via update — non tocca DB perché si ferma su validation)
    // -------------------------------------------------------------------

    public function testUpdateRejectsInvalidTone(): void
    {
        $svc = $this->makeService();
        $result = $svc->update(999, ['tone' => 'sarcastic']);
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame('tone', $result['errors'][0]['field']);
    }

    /**
     * @dataProvider validToneProvider
     */
    public function testValidatePatchAcceptsAllValidTones(string $tone): void
    {
        $svc = $this->makeService();
        $errors = $svc->validatePatch(['tone' => $tone]);
        $this->assertSame([], $errors, "Tono '{$tone}' deve essere accettato");
    }

    public static function validToneProvider(): array
    {
        return [
            ['professional'],
            ['friendly'],
            ['fun'],
            ['authoritative'],
            ['casual'],
        ];
    }

    public function testUpdateRejectsGlossaryNotObject(): void
    {
        $svc = $this->makeService();
        $result = $svc->update(999, ['glossary' => 'invalid string']);
        $this->assertFalse($result['success']);
        $this->assertTrue(
            $this->hasFieldError($result['errors'], 'glossary'),
            'Deve segnalare errore su glossary'
        );
    }

    public function testUpdateRejectsUnknownGlossaryKey(): void
    {
        $svc = $this->makeService();
        $result = $svc->update(999, [
            'glossary' => ['hashtags' => ['#vino']],  // chiave non valida
        ]);
        $this->assertFalse($result['success']);
        $this->assertTrue(
            $this->hasFieldError($result['errors'], 'glossary.hashtags'),
            'Deve segnalare chiave hashtags come invalida'
        );
    }

    public function testValidatePatchAcceptsValidGlossaryKeys(): void
    {
        $svc = $this->makeService();
        $errors = $svc->validatePatch([
            'glossary' => [
                'brand_names' => ['Brand X'],
                'products' => ['Prodotto Y'],
                'people' => [],
                'avoid_terms' => ['parolaccia'],
                'preferred_terms' => ['vino' => 'vino artigianale'],
            ],
        ]);
        $this->assertSame([], $errors);
    }

    public function testUpdateRejectsUnknownGuidelinesKey(): void
    {
        $svc = $this->makeService();
        $result = $svc->update(999, [
            'editorial_guidelines' => ['maybe' => ['cosa']],
        ]);
        $this->assertFalse($result['success']);
        $this->assertTrue(
            $this->hasFieldError($result['errors'], 'editorial_guidelines.maybe')
        );
    }

    public function testValidatePatchAcceptsValidGuidelinesKeys(): void
    {
        $svc = $this->makeService();
        $errors = $svc->validatePatch([
            'editorial_guidelines' => [
                'do' => ['Cita tradizione'],
                'dont' => ['Politica'],
                'emphasize' => ['Made in Italy'],
            ],
        ]);
        $this->assertSame([], $errors);
    }

    // -------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------

    private function hasFieldError(array $errors, string $field): bool
    {
        foreach ($errors as $err) {
            if (is_array($err) && ($err['field'] ?? '') === $field) {
                return true;
            }
        }
        return false;
    }
}
