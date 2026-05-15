<?php

declare(strict_types=1);

namespace Editorial\Tests\Unit\Services;

use Editorial\Services\LicenseManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test mirato per LicenseManager::normalizeDomain (regression per Issue #1 di M1.8).
 *
 * Il bug originale: parse_url('localhost') ritorna ['path' => 'localhost'] (no 'host'),
 * il regex fallback estraeva stringa vuota → activate failed con "Missing required fields".
 *
 * Usiamo ReflectionMethod per testare il metodo private senza refactor production.
 */
final class LicenseManagerNormalizeDomainTest extends TestCase
{
    private ReflectionMethod $normalize;
    private LicenseManager $manager;

    protected function setUp(): void
    {
        $this->manager = new LicenseManager();
        $ref = new ReflectionClass(LicenseManager::class);
        $this->normalize = $ref->getMethod('normalizeDomain');
        $this->normalize->setAccessible(true);
    }

    /**
     * @dataProvider domainProvider
     */
    public function testNormalizeDomain(string $input, string $expected, string $description): void
    {
        $actual = $this->normalize->invoke($this->manager, $input);
        $this->assertSame($expected, $actual, $description);
    }

    public static function domainProvider(): array
    {
        return [
            // [input, expected, description]
            'localhost senza schema'                  => ['localhost', 'localhost', 'Issue #1 regression: deve preservare localhost'],
            'localhost con http://'                   => ['http://localhost', 'localhost', 'Schema rimosso'],
            'localhost con https://'                  => ['https://localhost', 'localhost', 'Schema https rimosso'],
            'localhost con trailing slash'            => ['http://localhost/', 'localhost', 'Trailing slash rimosso'],
            'localhost con path'                      => ['http://localhost/wp-admin', 'localhost', 'Path rimosso'],
            'host case-insensitive (uppercase)'       => ['HTTP://Test1.COM', 'test1.com', 'Schema e host lowercased'],
            'host con path mixed case'                => ['https://Test1.com/path/', 'test1.com', 'Esempio dal docblock'],
            'host senza schema con path'              => ['example.com/some/path', 'example.com', 'No-schema + path: prende primo segmento'],
            'host con whitespace'                     => ['  example.com  ', 'example.com', 'Trim applicato'],
            'host con port (con schema)'              => ['http://localhost:8080', 'localhost:8080', 'Port preservato in parse_url host'],
            'host con port (senza schema)'            => ['localhost:8080', 'localhost:8080', 'Port preservato anche senza schema'],
            'host con subdomain'                      => ['https://blog.example.com', 'blog.example.com', 'Subdomain preservato'],
            'host con IDN ascii'                      => ['https://xn--bcher-kva.de', 'xn--bcher-kva.de', 'Punycode preservato'],
            'IP address con schema'                   => ['http://127.0.0.1', '127.0.0.1', 'IP gestito come host'],
            'IP address senza schema'                 => ['192.168.1.1', '192.168.1.1', 'IP senza schema'],
            'IP con port'                             => ['http://127.0.0.1:8000', '127.0.0.1:8000', 'IP+port'],
            'dominio vuoto'                           => ['', '', 'Stringa vuota → stringa vuota'],
            'solo whitespace'                         => ['   ', '', 'Whitespace puro → vuoto dopo trim'],
            'solo slash'                              => ['/', '', 'Edge: solo slash, explode prima parte vuota'],
            'WP siteurl con trailing slash'           => ['http://localhost/ai-tester/', 'localhost', 'Caso reale M1.8'],
            'WP siteurl multi-path'                   => ['http://localhost/wp/sites/site1/', 'localhost', 'Multi-path: solo host'],
            // Schemi non-http(s) sono fuori target (mai inviati da WP plugin). Output documentato come degenerate.
            'protocollo non standard (degenerate)'    => ['ftp://example.com', 'ftp:', 'Schemi non-http(s) ricadono nel fallback explode; output degenere accettato (use case non valido)'],
            'doppio slash leading'                    => ['//example.com', '', 'Schema relativo senza http: fallback explode prima parte = ""'],
        ];
    }
}
