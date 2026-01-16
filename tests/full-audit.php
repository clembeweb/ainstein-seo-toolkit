<?php
/**
 * SEO Toolkit - Full Audit Test Suite
 *
 * Comprehensive testing for functional, standards compliance, and shared services
 *
 * Usage: php tests/full-audit.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600); // 10 minutes max

// Bootstrap
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/seo-toolkit'); // Adjust as needed

// Colors for CLI output
class Colors {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

class FullAuditTest {
    private array $results = [
        'functional' => [],
        'compliance' => [],
        'services' => [],
        'issues' => []
    ];

    private array $stats = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'critical' => 0,
        'warnings' => 0
    ];

    private ?PDO $db = null;
    private array $modules = [];
    private array $routes = [];
    private float $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
        $this->initDatabase();
        $this->loadModules();
    }

    private function initDatabase(): void {
        try {
            $config = require ROOT_PATH . '/config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=" . ($config['charset'] ?? 'utf8mb4');
            $this->db = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $this->addIssue('critical', 'core', 'Database', 'Cannot connect to database: ' . $e->getMessage(), 'Check database config');
        }
    }

    private function loadModules(): void {
        $modulesPath = ROOT_PATH . '/modules';
        foreach (scandir($modulesPath) as $dir) {
            if ($dir[0] === '.' || $dir === '_template') continue;
            $moduleJson = $modulesPath . '/' . $dir . '/module.json';
            if (file_exists($moduleJson)) {
                $this->modules[$dir] = json_decode(file_get_contents($moduleJson), true);
                $this->modules[$dir]['path'] = $modulesPath . '/' . $dir;
            }
        }
    }

    // ============================================
    // PART 1: FUNCTIONAL TESTS
    // ============================================

    public function runFunctionalTests(): void {
        $this->printHeader("PART 1: FUNCTIONAL TESTS");

        $this->testHttpRoutes();
        $this->testViewsRendering();
        $this->testDatabase();
        $this->testClasses();
    }

    // 1. HTTP Routes Test
    private function testHttpRoutes(): void {
        $this->printSection("1. HTTP ROUTES TEST");

        foreach ($this->modules as $slug => $module) {
            $routesFile = $module['path'] . '/routes.php';
            if (!file_exists($routesFile)) {
                $this->addResult('functional', $slug, 'Routes File', false, 'routes.php not found');
                continue;
            }

            $routes = $this->extractRoutes($routesFile);
            $passed = 0;
            $failed = 0;
            $totalTime = 0;

            foreach ($routes as $route) {
                // Only test GET routes for basic accessibility
                if ($route['method'] === 'GET') {
                    $testResult = $this->testRoute($route);
                    if ($testResult['success']) {
                        $passed++;
                    } else {
                        $failed++;
                        if ($testResult['code'] === 500 || $testResult['code'] === 404) {
                            $this->addIssue('critical', $slug, $routesFile . ':' . $route['line'],
                                "Route {$route['path']} returns {$testResult['code']}",
                                'Check controller method and view');
                        }
                    }
                    $totalTime += $testResult['time'];
                }
            }

            $this->addResult('functional', $slug, 'HTTP Routes',
                $failed === 0,
                "Passed: $passed, Failed: $failed, Avg Time: " . round($totalTime / max($passed + $failed, 1), 2) . "ms"
            );

            $this->results['functional'][$slug]['routes'] = [
                'total' => count($routes),
                'tested' => $passed + $failed,
                'passed' => $passed,
                'failed' => $failed,
                'time' => round($totalTime, 2)
            ];
        }
    }

    private function extractRoutes(string $file): array {
        $content = file_get_contents($file);
        $routes = [];

        // Match Router::get/post patterns
        preg_match_all(
            '/Router::(get|post)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            $content,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        foreach ($matches as $match) {
            $line = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;
            $routes[] = [
                'method' => strtoupper($match[1][0]),
                'path' => $match[2][0],
                'line' => $line
            ];
        }

        return $routes;
    }

    private function testRoute(array $route): array {
        $start = microtime(true);

        // Replace parameters with test values
        $path = preg_replace('/\{[^}]+\}/', '1', $route['path']);
        $url = BASE_URL . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => false,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIE => 'PHPSESSID=test_session_' . time(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $time = (microtime(true) - $start) * 1000;

        // 200, 302 (redirect), 401 (auth required) are acceptable
        $success = in_array($httpCode, [200, 302, 301, 401, 403]);

        return [
            'success' => $success,
            'code' => $httpCode,
            'time' => $time
        ];
    }

    // 2. Views Rendering Test
    private function testViewsRendering(): void {
        $this->printSection("2. VIEWS RENDERING TEST");

        foreach ($this->modules as $slug => $module) {
            $viewsPath = $module['path'] . '/views';
            if (!is_dir($viewsPath)) {
                $this->addResult('functional', $slug, 'Views Dir', false, 'views/ directory not found');
                continue;
            }

            $viewFiles = $this->getPhpFiles($viewsPath);
            $syntaxErrors = 0;
            $varIssues = 0;
            $passed = 0;

            foreach ($viewFiles as $file) {
                // Check PHP syntax
                $syntaxResult = $this->checkPhpSyntax($file);
                if (!$syntaxResult['valid']) {
                    $syntaxErrors++;
                    $this->addIssue('critical', $slug, $file . ':' . $syntaxResult['line'],
                        'PHP Syntax Error: ' . $syntaxResult['error'],
                        'Fix syntax error');
                    continue;
                }

                // Check for common issues
                $issues = $this->checkViewIssues($file);
                if (!empty($issues)) {
                    $varIssues += count($issues);
                    foreach ($issues as $issue) {
                        $this->addIssue('warning', $slug, $file . ':' . $issue['line'],
                            $issue['message'],
                            $issue['fix']);
                    }
                } else {
                    $passed++;
                }
            }

            $total = count($viewFiles);
            $this->addResult('functional', $slug, 'Views Rendering',
                $syntaxErrors === 0,
                "Total: $total, Passed: $passed, Syntax Errors: $syntaxErrors, Var Issues: $varIssues"
            );

            $this->results['functional'][$slug]['views'] = [
                'total' => $total,
                'passed' => $passed,
                'syntax_errors' => $syntaxErrors,
                'var_issues' => $varIssues
            ];
        }
    }

    private function getPhpFiles(string $dir): array {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function checkPhpSyntax(string $file): array {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode(' ', $output);
            preg_match('/on line (\d+)/', $error, $matches);
            return [
                'valid' => false,
                'error' => $error,
                'line' => $matches[1] ?? 0
            ];
        }

        return ['valid' => true];
    }

    private function checkViewIssues(string $file): array {
        $content = file_get_contents($file);
        $issues = [];
        $lines = explode("\n", $content);

        // Check for undefined variable patterns
        foreach ($lines as $lineNum => $line) {
            // Check for echo of potentially undefined vars (without isset/??/empty check nearby)
            if (preg_match('/echo\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*;/', $line, $matches)) {
                $varName = $matches[1];
                // Check if variable is defined or checked
                if (!$this->isVarDefinedInView($content, $varName, $lineNum)) {
                    // Skip common framework variables
                    if (!in_array($varName, ['title', 'user', 'modules', 'content', 'this'])) {
                        $issues[] = [
                            'line' => $lineNum + 1,
                            'message' => "Potentially undefined variable: \$$varName",
                            'fix' => "Use \$$varName ?? '' or check with isset()"
                        ];
                    }
                }
            }

            // Check for missing includes
            if (preg_match('/(?:include|require)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
                $includePath = $matches[1];
                if (!str_starts_with($includePath, '/') && !str_starts_with($includePath, '$')) {
                    $fullPath = dirname($file) . '/' . $includePath;
                    if (!file_exists($fullPath)) {
                        $issues[] = [
                            'line' => $lineNum + 1,
                            'message' => "Missing include file: $includePath",
                            'fix' => "Create file or fix path"
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    private function isVarDefinedInView(string $content, string $varName, int $currentLine): bool {
        // Check if variable is in extract(), passed as parameter, or has null coalesce
        $patterns = [
            '/\$' . $varName . '\s*=/',
            '/\$' . $varName . '\s*\?\?/',
            '/isset\s*\(\s*\$' . $varName . '\s*\)/',
            '/empty\s*\(\s*\$' . $varName . '\s*\)/',
            '/extract\s*\(/',
            '/foreach\s*\(\s*[^)]+\s+as\s+[^)]*\$' . $varName . '/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    // 3. Database Test
    private function testDatabase(): void {
        $this->printSection("3. DATABASE TEST");

        if (!$this->db) {
            $this->addResult('functional', 'core', 'Database Connection', false, 'Connection failed');
            return;
        }

        $this->addResult('functional', 'core', 'Database Connection', true, 'Connected successfully');

        // Get all tables
        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        // Module table prefixes
        $prefixes = [
            'internal-links' => 'il_',
            'ai-content' => 'aic_',
            'seo-audit' => 'sa_',
            'seo-tracking' => 'st_'
        ];

        foreach ($this->modules as $slug => $module) {
            $prefix = $prefixes[$slug] ?? '';
            $moduleTables = array_filter($tables, fn($t) => str_starts_with($t, $prefix));

            if (empty($moduleTables) && !empty($prefix)) {
                $this->addResult('functional', $slug, 'Database Tables', false, "No tables with prefix '$prefix' found");
                continue;
            }

            $tableStats = [];
            $fkIssues = 0;
            $indexIssues = 0;

            foreach ($moduleTables as $table) {
                // Get row count
                $count = $this->db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();

                // Get table structure
                $columns = $this->db->query("DESCRIBE `$table`")->fetchAll();

                // Check for foreign keys
                $fks = $this->db->query("
                    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '$table'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ")->fetchAll();

                // Check for indices
                $indices = $this->db->query("SHOW INDEX FROM `$table`")->fetchAll();

                // Validate foreign keys point to existing tables
                foreach ($fks as $fk) {
                    if (!in_array($fk['REFERENCED_TABLE_NAME'], $tables)) {
                        $fkIssues++;
                        $this->addIssue('critical', $slug, "$table:FK",
                            "FK references non-existent table: {$fk['REFERENCED_TABLE_NAME']}",
                            'Create referenced table or remove FK');
                    }
                }

                $tableStats[$table] = [
                    'rows' => $count,
                    'columns' => count($columns),
                    'fks' => count($fks),
                    'indices' => count($indices)
                ];
            }

            $this->addResult('functional', $slug, 'Database Tables',
                $fkIssues === 0,
                "Tables: " . count($moduleTables) . ", FK Issues: $fkIssues"
            );

            $this->results['functional'][$slug]['database'] = $tableStats;
        }
    }

    // 4. Classes Test
    private function testClasses(): void {
        $this->printSection("4. CLASSES TEST");

        // Setup autoloader
        spl_autoload_register(function ($class) {
            $paths = [
                ROOT_PATH . '/core/',
                ROOT_PATH . '/services/',
                ROOT_PATH . '/modules/',
            ];

            $class = str_replace('\\', '/', $class);

            foreach ($paths as $path) {
                $file = $path . $class . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }

            // Try module-specific paths
            if (preg_match('/^Modules\/([^\/]+)\/(.+)$/', $class, $matches)) {
                $modulePath = ROOT_PATH . '/modules/' . $this->toKebabCase($matches[1]) . '/' . $matches[2] . '.php';
                if (file_exists($modulePath)) {
                    require_once $modulePath;
                    return;
                }
            }
        });

        foreach ($this->modules as $slug => $module) {
            $controllersPath = $module['path'] . '/controllers';
            $modelsPath = $module['path'] . '/models';

            $controllerIssues = 0;
            $modelIssues = 0;
            $controllerFiles = [];
            $modelFiles = [];

            // Test Controllers
            if (is_dir($controllersPath)) {
                $controllerFiles = glob($controllersPath . '/*.php');
                foreach ($controllerFiles as $file) {
                    $syntax = $this->checkPhpSyntax($file);
                    if (!$syntax['valid']) {
                        $controllerIssues++;
                        $this->addIssue('critical', $slug, $file,
                            'Controller syntax error: ' . $syntax['error'],
                            'Fix syntax');
                    }
                }
            }

            // Test Models
            if (is_dir($modelsPath)) {
                $modelFiles = glob($modelsPath . '/*.php');
                foreach ($modelFiles as $file) {
                    $syntax = $this->checkPhpSyntax($file);
                    if (!$syntax['valid']) {
                        $modelIssues++;
                        $this->addIssue('critical', $slug, $file,
                            'Model syntax error: ' . $syntax['error'],
                            'Fix syntax');
                    }
                }
            }

            $totalClasses = count($controllerFiles) + count($modelFiles);
            $issues = $controllerIssues + $modelIssues;

            $this->addResult('functional', $slug, 'Classes',
                $issues === 0,
                "Controllers: " . count($controllerFiles) . ", Models: " . count($modelFiles) . ", Issues: $issues"
            );

            $this->results['functional'][$slug]['classes'] = [
                'controllers' => count($controllerFiles),
                'models' => count($modelFiles),
                'issues' => $issues
            ];
        }
    }

    // ============================================
    // PART 2: STANDARDS COMPLIANCE
    // ============================================

    public function runComplianceTests(): void {
        $this->printHeader("PART 2: STANDARDS COMPLIANCE");

        $this->testNamingConventions();
        $this->testModuleJson();
        $this->testFolderStructure();
        $this->testDatabaseConventions();
    }

    // 5. Naming Conventions Check
    private function testNamingConventions(): void {
        $this->printSection("5. NAMING CONVENTIONS CHECK");

        foreach ($this->modules as $slug => $module) {
            $scores = [];

            // Module slug: kebab-case
            $isKebabCase = preg_match('/^[a-z]+(-[a-z]+)*$/', $slug);
            $scores['slug'] = $isKebabCase;
            if (!$isKebabCase) {
                $this->addIssue('warning', $slug, 'module.json',
                    "Module slug '$slug' is not kebab-case",
                    'Rename to kebab-case format');
            }

            // Controllers: PascalCaseController.php
            $controllerIssues = 0;
            $controllersPath = $module['path'] . '/controllers';
            if (is_dir($controllersPath)) {
                foreach (glob($controllersPath . '/*.php') as $file) {
                    $filename = basename($file);
                    if (!preg_match('/^[A-Z][a-zA-Z0-9]*Controller\.php$/', $filename)) {
                        $controllerIssues++;
                        $this->addIssue('warning', $slug, $file,
                            "Controller '$filename' doesn't follow PascalCaseController.php convention",
                            'Rename to PascalCaseController.php');
                    }
                }
            }
            $scores['controllers'] = $controllerIssues === 0;

            // Models: PascalCase.php (singular)
            $modelIssues = 0;
            $modelsPath = $module['path'] . '/models';
            if (is_dir($modelsPath)) {
                foreach (glob($modelsPath . '/*.php') as $file) {
                    $filename = basename($file);
                    if (!preg_match('/^[A-Z][a-zA-Z0-9]*\.php$/', $filename)) {
                        $modelIssues++;
                        $this->addIssue('warning', $slug, $file,
                            "Model '$filename' doesn't follow PascalCase.php convention",
                            'Rename to PascalCase.php');
                    }
                }
            }
            $scores['models'] = $modelIssues === 0;

            // Views: kebab-case.php
            $viewIssues = 0;
            $viewsPath = $module['path'] . '/views';
            if (is_dir($viewsPath)) {
                foreach ($this->getPhpFiles($viewsPath) as $file) {
                    $filename = basename($file);
                    if (!preg_match('/^[a-z]+(-[a-z]+)*\.php$/', $filename)) {
                        $viewIssues++;
                        // Only add issue for non-component views
                        if (!str_contains($file, 'components')) {
                            $this->addIssue('warning', $slug, $file,
                                "View '$filename' doesn't follow kebab-case.php convention",
                                'Rename to kebab-case.php');
                        }
                    }
                }
            }
            $scores['views'] = $viewIssues === 0;

            $passed = array_sum($scores);
            $total = count($scores);
            $percentage = round(($passed / $total) * 100);

            $this->addResult('compliance', $slug, 'Naming Conventions',
                $percentage >= 75,
                "Score: $percentage% ($passed/$total)"
            );

            $this->results['compliance'][$slug]['naming'] = [
                'scores' => $scores,
                'percentage' => $percentage
            ];
        }
    }

    // 6. module.json Validation
    private function testModuleJson(): void {
        $this->printSection("6. MODULE.JSON VALIDATION");

        $requiredFields = ['name', 'slug', 'version', 'description', 'icon'];

        foreach ($this->modules as $slug => $module) {
            $missing = [];
            $invalid = [];

            // Check required fields
            foreach ($requiredFields as $field) {
                if (!isset($module[$field]) || empty($module[$field])) {
                    $missing[] = $field;
                }
            }

            // Validate credits field if module uses AI/expensive operations
            if (isset($module['requires']['credits']) && $module['requires']['credits'] === true) {
                if (!isset($module['credits']) || empty($module['credits'])) {
                    $this->addIssue('warning', $slug, 'module.json',
                        'Module requires credits but no costs defined',
                        'Add "credits" object with operation costs');
                }
            }

            // Validate routes_prefix if present
            if (isset($module['routes_prefix'])) {
                if (!str_starts_with($module['routes_prefix'], '/')) {
                    $invalid[] = 'routes_prefix should start with /';
                }
            }

            // Validate version format
            if (isset($module['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $module['version'])) {
                $invalid[] = 'version should follow semver (x.y.z)';
            }

            $isValid = empty($missing) && empty($invalid);

            $this->addResult('compliance', $slug, 'module.json',
                $isValid,
                empty($missing) ? 'All fields present' : 'Missing: ' . implode(', ', $missing)
            );

            if (!empty($missing)) {
                $this->addIssue('critical', $slug, 'module.json',
                    'Missing required fields: ' . implode(', ', $missing),
                    'Add missing fields');
            }

            $this->results['compliance'][$slug]['module_json'] = [
                'valid' => $isValid,
                'missing' => $missing,
                'invalid' => $invalid
            ];
        }
    }

    // 7. Folder Structure Check
    private function testFolderStructure(): void {
        $this->printSection("7. FOLDER STRUCTURE CHECK");

        $requiredItems = [
            'module.json' => 'file',
            'routes.php' => 'file',
            'controllers' => 'dir',
            'models' => 'dir',
            'views' => 'dir'
        ];

        foreach ($this->modules as $slug => $module) {
            $missing = [];
            $present = [];

            foreach ($requiredItems as $item => $type) {
                $path = $module['path'] . '/' . $item;
                $exists = $type === 'file' ? file_exists($path) : is_dir($path);

                if ($exists) {
                    $present[] = $item;
                } else {
                    $missing[] = $item;
                    $this->addIssue('warning', $slug, $module['path'],
                        "Missing required $type: $item",
                        "Create $item");
                }
            }

            $percentage = round((count($present) / count($requiredItems)) * 100);

            $this->addResult('compliance', $slug, 'Folder Structure',
                $percentage >= 80,
                "$percentage% complete (" . count($present) . "/" . count($requiredItems) . ")"
            );

            $this->results['compliance'][$slug]['structure'] = [
                'present' => $present,
                'missing' => $missing,
                'percentage' => $percentage
            ];
        }
    }

    // 8. Database Conventions Check
    private function testDatabaseConventions(): void {
        $this->printSection("8. DATABASE CONVENTIONS CHECK");

        if (!$this->db) {
            $this->addResult('compliance', 'core', 'Database Conventions', false, 'No DB connection');
            return;
        }

        $prefixes = [
            'internal-links' => 'il_',
            'ai-content' => 'aic_',
            'seo-audit' => 'sa_',
            'seo-tracking' => 'st_'
        ];

        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($this->modules as $slug => $module) {
            $prefix = $prefixes[$slug] ?? '';
            $moduleTables = array_filter($tables, fn($t) => str_starts_with($t, $prefix));

            if (empty($moduleTables)) {
                $this->addResult('compliance', $slug, 'Database Conventions', true, 'No tables to check');
                continue;
            }

            $timestampIssues = 0;
            $fkCascadeIssues = 0;
            $prefixIssues = 0;

            foreach ($moduleTables as $table) {
                $columns = $this->db->query("DESCRIBE `$table`")->fetchAll();
                $columnNames = array_column($columns, 'Field');

                // Check for created_at, updated_at
                if (!in_array('created_at', $columnNames)) {
                    $timestampIssues++;
                    $this->addIssue('warning', $slug, $table,
                        'Missing created_at column',
                        'Add created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
                }

                // Check FK cascade
                $fks = $this->db->query("
                    SELECT rc.CONSTRAINT_NAME, rc.DELETE_RULE
                    FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                    JOIN information_schema.KEY_COLUMN_USAGE kcu
                    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    WHERE kcu.TABLE_SCHEMA = DATABASE()
                    AND kcu.TABLE_NAME = '$table'
                ")->fetchAll();

                foreach ($fks as $fk) {
                    if ($fk['DELETE_RULE'] !== 'CASCADE') {
                        $fkCascadeIssues++;
                    }
                }

                // Check prefix
                if (!str_starts_with($table, $prefix)) {
                    $prefixIssues++;
                    $this->addIssue('warning', $slug, $table,
                        "Table doesn't use module prefix '$prefix'",
                        "Rename table to {$prefix}tablename");
                }
            }

            $issues = $timestampIssues + $fkCascadeIssues + $prefixIssues;

            $this->addResult('compliance', $slug, 'Database Conventions',
                $issues < count($moduleTables),
                "Tables: " . count($moduleTables) . ", Timestamp issues: $timestampIssues, FK cascade: $fkCascadeIssues"
            );

            $this->results['compliance'][$slug]['db_conventions'] = [
                'tables' => count($moduleTables),
                'timestamp_issues' => $timestampIssues,
                'fk_cascade_issues' => $fkCascadeIssues,
                'prefix_issues' => $prefixIssues
            ];
        }
    }

    // ============================================
    // PART 3: SHARED SERVICES
    // ============================================

    public function runServicesTests(): void {
        $this->printHeader("PART 3: SHARED SERVICES");

        $this->testServicesUsage();
        $this->testCreditsIntegration();
        $this->testAuthMiddleware();
    }

    // 9. Services Usage Check
    private function testServicesUsage(): void {
        $this->printSection("9. SERVICES USAGE CHECK");

        $servicePatterns = [
            'AiService' => [
                'correct' => '/new\s+\\\\?Services\\\\AiService|use\s+Services\\\\AiService/',
                'wrong' => '/api\.anthropic\.com|claude.*api/i',
                'description' => 'AI Service'
            ],
            'ScraperService' => [
                'correct' => '/new\s+\\\\?Services\\\\ScraperService|use\s+Services\\\\ScraperService/',
                'wrong' => '/new\s+(?:GuzzleHttp|Guzzle)(?!.*ScraperService)|curl_init\s*\([^)]*https?:/i',
                'description' => 'Scraper Service'
            ],
            'ExportService' => [
                'correct' => '/new\s+\\\\?Services\\\\ExportService|use\s+Services\\\\ExportService/',
                'wrong' => '/fputcsv.*(?!ExportService)|text\/csv.*(?!ExportService)/i',
                'description' => 'Export Service'
            ],
            'CsvImportService' => [
                'correct' => '/new\s+\\\\?Services\\\\CsvImportService|use\s+Services\\\\CsvImportService/',
                'wrong' => '/fgetcsv\s*\((?!.*CsvImportService)/i',
                'description' => 'CSV Import Service'
            ],
            'SitemapService' => [
                'correct' => '/new\s+\\\\?Services\\\\SitemapService|use\s+Services\\\\SitemapService/',
                'wrong' => '/simplexml_load_string.*sitemap(?!.*SitemapService)/i',
                'description' => 'Sitemap Service'
            ]
        ];

        foreach ($this->modules as $slug => $module) {
            $serviceUsage = [];
            $allFiles = array_merge(
                glob($module['path'] . '/controllers/*.php') ?: [],
                glob($module['path'] . '/models/*.php') ?: [],
                glob($module['path'] . '/services/*.php') ?: [],
                [$module['path'] . '/routes.php']
            );

            foreach ($servicePatterns as $service => $patterns) {
                $usesCorrect = false;
                $usesWrong = false;

                foreach ($allFiles as $file) {
                    if (!file_exists($file)) continue;
                    $content = file_get_contents($file);

                    if (preg_match($patterns['correct'], $content)) {
                        $usesCorrect = true;
                    }

                    // Only check for wrong usage if module is supposed to use this service
                    if (isset($module['requires']['services']) &&
                        in_array(strtolower(str_replace('Service', '', $service)), $module['requires']['services'])) {
                        if (preg_match($patterns['wrong'], $content)) {
                            $usesWrong = true;
                            $this->addIssue('warning', $slug, basename($file),
                                "Direct implementation instead of {$patterns['description']}",
                                "Use Services\\$service");
                        }
                    }
                }

                $serviceUsage[$service] = [
                    'uses' => $usesCorrect,
                    'direct' => $usesWrong,
                    'compliant' => $usesCorrect && !$usesWrong
                ];
            }

            $compliantCount = count(array_filter($serviceUsage, fn($s) => $s['compliant'] || !$s['uses']));

            $this->addResult('services', $slug, 'Services Usage',
                $compliantCount >= 3,
                "Compliant: $compliantCount/" . count($servicePatterns)
            );

            $this->results['services'][$slug]['services'] = $serviceUsage;
        }
    }

    // 10. Credits Integration Check
    private function testCreditsIntegration(): void {
        $this->printSection("10. CREDITS INTEGRATION CHECK");

        foreach ($this->modules as $slug => $module) {
            // Skip if module doesn't require credits
            if (!isset($module['requires']['credits']) || !$module['requires']['credits']) {
                $this->addResult('services', $slug, 'Credits Integration', true, 'Credits not required');
                $this->results['services'][$slug]['credits'] = ['required' => false];
                continue;
            }

            $allFiles = array_merge(
                glob($module['path'] . '/controllers/*.php') ?: [],
                glob($module['path'] . '/services/*.php') ?: [],
                [$module['path'] . '/routes.php']
            );

            $hasConsume = false;
            $hasBalance = false;
            $declaredCosts = $module['credits'] ?? [];
            $foundCosts = [];

            foreach ($allFiles as $file) {
                if (!file_exists($file)) continue;
                $content = file_get_contents($file);

                // Check for Credits::consume()
                if (preg_match('/Credits::consume\s*\(/', $content)) {
                    $hasConsume = true;
                }

                // Check for Credits::getBalance() or Credits::hasEnough()
                if (preg_match('/Credits::(getBalance|hasEnough)\s*\(/', $content)) {
                    $hasBalance = true;
                }

                // Find cost references
                preg_match_all('/Credits::getCost\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches);
                foreach ($matches[1] as $costKey) {
                    $foundCosts[$costKey] = true;
                }
            }

            $isCompliant = $hasConsume && $hasBalance;

            if (!$hasConsume) {
                $this->addIssue('warning', $slug, 'Credits',
                    'Module requires credits but no Credits::consume() calls found',
                    'Add credit consumption for costly operations');
            }

            if (!$hasBalance) {
                $this->addIssue('warning', $slug, 'Credits',
                    'No balance check before consuming credits',
                    'Add Credits::hasEnough() or Credits::getBalance() check');
            }

            $this->addResult('services', $slug, 'Credits Integration',
                $isCompliant,
                "Consume: " . ($hasConsume ? 'Yes' : 'No') . ", Balance check: " . ($hasBalance ? 'Yes' : 'No')
            );

            $this->results['services'][$slug]['credits'] = [
                'required' => true,
                'has_consume' => $hasConsume,
                'has_balance_check' => $hasBalance,
                'declared_costs' => array_keys($declaredCosts),
                'found_costs' => array_keys($foundCosts)
            ];
        }
    }

    // 11. Auth/Middleware Check
    private function testAuthMiddleware(): void {
        $this->printSection("11. AUTH/MIDDLEWARE CHECK");

        foreach ($this->modules as $slug => $module) {
            $routesFile = $module['path'] . '/routes.php';
            if (!file_exists($routesFile)) {
                $this->addResult('services', $slug, 'Auth/Middleware', false, 'No routes.php');
                continue;
            }

            $content = file_get_contents($routesFile);

            // Count routes
            preg_match_all('/Router::(get|post)\s*\(\s*[\'"][^\'"]+[\'"]/', $content, $routeMatches);
            $totalRoutes = count($routeMatches[0]);

            // Count auth middleware
            preg_match_all('/Middleware::auth\s*\(\s*\)/', $content, $authMatches);
            $authCount = count($authMatches[0]);

            // Count CSRF middleware for POST routes
            preg_match_all('/Router::post/', $content, $postMatches);
            $postRoutes = count($postMatches[0]);

            preg_match_all('/Middleware::csrf\s*\(\s*\)/', $content, $csrfMatches);
            $csrfCount = count($csrfMatches[0]);

            // Check views for CSRF token in forms
            $viewsPath = $module['path'] . '/views';
            $formWithoutCsrf = 0;

            if (is_dir($viewsPath)) {
                foreach ($this->getPhpFiles($viewsPath) as $viewFile) {
                    $viewContent = file_get_contents($viewFile);

                    // Check if view has form with POST
                    if (preg_match('/<form[^>]*method\s*=\s*["\']post["\']/i', $viewContent)) {
                        // Check for CSRF token
                        if (!preg_match('/csrf|_token|csrfToken/', $viewContent)) {
                            $formWithoutCsrf++;
                            $this->addIssue('critical', $slug, basename($viewFile),
                                'POST form without CSRF token',
                                'Add <?= csrfInput() ?> or hidden csrf_token field');
                        }
                    }
                }
            }

            // Check for input sanitization in controllers
            $sanitizationIssues = 0;
            $controllersPath = $module['path'] . '/controllers';
            if (is_dir($controllersPath)) {
                foreach (glob($controllersPath . '/*.php') as $file) {
                    $ctrlContent = file_get_contents($file);

                    // Check for raw $_POST/$_GET usage without sanitization
                    if (preg_match('/\$_(POST|GET)\s*\[\s*[\'"][^\'"]+[\'"]\s*\](?!\s*\?\?)/', $ctrlContent) &&
                        !preg_match('/htmlspecialchars|filter_input|filter_var|sanitize/i', $ctrlContent)) {
                        // Only warn if there's echo or direct DB insert
                        if (preg_match('/echo\s+\$_|INSERT.*\$_/i', $ctrlContent)) {
                            $sanitizationIssues++;
                        }
                    }
                }
            }

            $authProtected = $totalRoutes > 0 ? round(($authCount / $totalRoutes) * 100) : 100;
            $csrfProtected = $postRoutes > 0 ? round(($csrfCount / $postRoutes) * 100) : 100;

            $issues = [];
            if ($authProtected < 90) {
                $issues[] = "Auth: $authProtected%";
                $this->addIssue('warning', $slug, 'routes.php',
                    "Only $authProtected% of routes have auth middleware",
                    'Add Middleware::auth() to all protected routes');
            }
            if ($csrfProtected < 90) {
                $issues[] = "CSRF: $csrfProtected%";
            }
            if ($formWithoutCsrf > 0) {
                $issues[] = "Forms w/o CSRF: $formWithoutCsrf";
            }

            $isCompliant = $authProtected >= 90 && $csrfProtected >= 90 && $formWithoutCsrf === 0;

            $this->addResult('services', $slug, 'Auth/Middleware',
                $isCompliant,
                "Auth: $authProtected%, CSRF: $csrfProtected%, Form issues: $formWithoutCsrf"
            );

            $this->results['services'][$slug]['auth'] = [
                'total_routes' => $totalRoutes,
                'auth_protected' => $authCount,
                'post_routes' => $postRoutes,
                'csrf_protected' => $csrfCount,
                'forms_without_csrf' => $formWithoutCsrf,
                'sanitization_issues' => $sanitizationIssues
            ];
        }
    }

    // ============================================
    // HELPERS & OUTPUT
    // ============================================

    private function addResult(string $category, string $module, string $test, bool $passed, string $details): void {
        $this->stats['total']++;
        if ($passed) {
            $this->stats['passed']++;
        } else {
            $this->stats['failed']++;
        }

        if (!isset($this->results[$category][$module])) {
            $this->results[$category][$module] = [];
        }

        $this->results[$category][$module][$test] = [
            'passed' => $passed,
            'details' => $details
        ];

        $status = $passed ? Colors::GREEN . "PASS" : Colors::RED . "FAIL";
        echo "  [$module] $test: $status" . Colors::RESET . " - $details\n";
    }

    private function addIssue(string $severity, string $module, string $location, string $issue, string $fix): void {
        if ($severity === 'critical') {
            $this->stats['critical']++;
        } else {
            $this->stats['warnings']++;
        }

        $this->results['issues'][] = [
            'severity' => $severity,
            'module' => $module,
            'location' => $location,
            'issue' => $issue,
            'fix' => $fix
        ];
    }

    private function printHeader(string $text): void {
        echo "\n" . Colors::BOLD . Colors::CYAN;
        echo str_repeat("=", 70) . "\n";
        echo " $text\n";
        echo str_repeat("=", 70) . "\n";
        echo Colors::RESET;
    }

    private function printSection(string $text): void {
        echo "\n" . Colors::BOLD . Colors::BLUE . "--- $text ---" . Colors::RESET . "\n\n";
    }

    private function toKebabCase(string $string): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    // ============================================
    // REPORTS GENERATION
    // ============================================

    public function generateReports(): void {
        $this->printHeader("TEST REPORTS");

        $this->printReport1();
        $this->printReport2();
        $this->printReport3();
        $this->printReport4();
        $this->printSummary();
    }

    // Report 1: Functional Tests
    private function printReport1(): void {
        echo "\n" . Colors::BOLD . "REPORT 1: Test Funzionali" . Colors::RESET . "\n";
        echo str_repeat("-", 90) . "\n";
        printf("| %-20s | %-20s | %-10s | %-10s | %-12s |\n",
            "Modulo", "Test", "Passati", "Falliti", "Tempo (ms)");
        echo str_repeat("-", 90) . "\n";

        foreach ($this->modules as $slug => $module) {
            $funcData = $this->results['functional'][$slug] ?? [];

            // Routes
            $routes = $funcData['routes'] ?? ['passed' => 0, 'failed' => 0, 'time' => 0];
            printf("| %-20s | %-20s | %-10d | %-10d | %-12s |\n",
                $slug, "HTTP Routes", $routes['passed'], $routes['failed'], round($routes['time'], 2));

            // Views
            $views = $funcData['views'] ?? ['passed' => 0, 'syntax_errors' => 0, 'var_issues' => 0];
            printf("| %-20s | %-20s | %-10d | %-10d | %-12s |\n",
                "", "Views", $views['passed'], $views['syntax_errors'], "-");

            // Database
            $db = $funcData['database'] ?? [];
            $dbTables = count($db);
            printf("| %-20s | %-20s | %-10d | %-10d | %-12s |\n",
                "", "Database", $dbTables, 0, "-");

            // Classes
            $classes = $funcData['classes'] ?? ['controllers' => 0, 'models' => 0, 'issues' => 0];
            printf("| %-20s | %-20s | %-10d | %-10d | %-12s |\n",
                "", "Classes", $classes['controllers'] + $classes['models'], $classes['issues'], "-");
        }
        echo str_repeat("-", 90) . "\n";
    }

    // Report 2: Standards Compliance
    private function printReport2(): void {
        echo "\n" . Colors::BOLD . "REPORT 2: Conformita Standard" . Colors::RESET . "\n";
        echo str_repeat("-", 100) . "\n";
        printf("| %-20s | %-12s | %-15s | %-12s | %-12s | %-10s |\n",
            "Modulo", "Naming", "module.json", "Struttura", "DB Conv.", "Score %");
        echo str_repeat("-", 100) . "\n";

        foreach ($this->modules as $slug => $module) {
            $compData = $this->results['compliance'][$slug] ?? [];

            $naming = ($compData['naming']['percentage'] ?? 0) . '%';
            $moduleJson = isset($compData['module_json']['valid']) && $compData['module_json']['valid'] ? 'OK' : 'FAIL';
            $structure = ($compData['structure']['percentage'] ?? 0) . '%';
            $dbConv = isset($compData['db_conventions']) ?
                (($compData['db_conventions']['timestamp_issues'] ?? 0) === 0 ? 'OK' : 'ISSUES') : 'N/A';

            $scores = [
                $compData['naming']['percentage'] ?? 0,
                isset($compData['module_json']['valid']) && $compData['module_json']['valid'] ? 100 : 0,
                $compData['structure']['percentage'] ?? 0,
                ($compData['db_conventions']['timestamp_issues'] ?? 0) === 0 ? 100 : 50
            ];
            $avgScore = round(array_sum($scores) / count($scores));

            printf("| %-20s | %-12s | %-15s | %-12s | %-12s | %-10s |\n",
                $slug, $naming, $moduleJson, $structure, $dbConv, $avgScore . '%');
        }
        echo str_repeat("-", 100) . "\n";
    }

    // Report 3: Shared Services
    private function printReport3(): void {
        echo "\n" . Colors::BOLD . "REPORT 3: Servizi Condivisi" . Colors::RESET . "\n";
        echo str_repeat("-", 95) . "\n";
        printf("| %-20s | %-12s | %-12s | %-12s | %-12s | %-12s |\n",
            "Modulo", "AiService", "Scraper", "Export", "Credits", "Compliant");
        echo str_repeat("-", 95) . "\n";

        foreach ($this->modules as $slug => $module) {
            $svcData = $this->results['services'][$slug] ?? [];
            $services = $svcData['services'] ?? [];

            $ai = isset($services['AiService']) ?
                ($services['AiService']['compliant'] ? 'OK' : ($services['AiService']['uses'] ? 'DIRECT' : '-')) : '-';
            $scraper = isset($services['ScraperService']) ?
                ($services['ScraperService']['compliant'] ? 'OK' : ($services['ScraperService']['uses'] ? 'DIRECT' : '-')) : '-';
            $export = isset($services['ExportService']) ?
                ($services['ExportService']['compliant'] ? 'OK' : ($services['ExportService']['uses'] ? 'DIRECT' : '-')) : '-';

            $credits = 'N/A';
            if (isset($svcData['credits'])) {
                if (!$svcData['credits']['required']) {
                    $credits = '-';
                } else {
                    $credits = ($svcData['credits']['has_consume'] && $svcData['credits']['has_balance_check']) ? 'OK' : 'MISSING';
                }
            }

            $compliant = ($ai !== 'DIRECT' && $scraper !== 'DIRECT' && $credits !== 'MISSING') ? 'YES' : 'NO';

            printf("| %-20s | %-12s | %-12s | %-12s | %-12s | %-12s |\n",
                $slug, $ai, $scraper, $export, $credits, $compliant);
        }
        echo str_repeat("-", 95) . "\n";
    }

    // Report 4: Issues Found
    private function printReport4(): void {
        echo "\n" . Colors::BOLD . "REPORT 4: Issues Trovate" . Colors::RESET . "\n";
        echo str_repeat("-", 140) . "\n";
        printf("| %-10s | %-18s | %-35s | %-35s | %-30s |\n",
            "Severity", "Modulo", "File:Linea", "Issue", "Fix Suggerito");
        echo str_repeat("-", 140) . "\n";

        // Sort by severity (critical first)
        usort($this->results['issues'], function($a, $b) {
            return $a['severity'] === 'critical' ? -1 : 1;
        });

        $displayed = 0;
        foreach ($this->results['issues'] as $issue) {
            if ($displayed >= 50) {
                echo "... and " . (count($this->results['issues']) - 50) . " more issues\n";
                break;
            }

            $sevColor = $issue['severity'] === 'critical' ? Colors::RED : Colors::YELLOW;

            printf("| %s%-10s%s | %-18s | %-35s | %-35s | %-30s |\n",
                $sevColor,
                strtoupper($issue['severity']),
                Colors::RESET,
                substr($issue['module'], 0, 18),
                substr($issue['location'], 0, 35),
                substr($issue['issue'], 0, 35),
                substr($issue['fix'], 0, 30)
            );
            $displayed++;
        }
        echo str_repeat("-", 140) . "\n";
    }

    // Final Summary
    private function printSummary(): void {
        $elapsed = round(microtime(true) - $this->startTime, 2);
        $passRate = $this->stats['total'] > 0 ?
            round(($this->stats['passed'] / $this->stats['total']) * 100, 1) : 0;

        echo "\n";
        echo Colors::BOLD . str_repeat("=", 70) . Colors::RESET . "\n";
        echo Colors::BOLD . " RIEPILOGO FINALE" . Colors::RESET . "\n";
        echo str_repeat("=", 70) . "\n\n";

        echo "  Tempo esecuzione:  $elapsed secondi\n";
        echo "  Moduli testati:    " . count($this->modules) . "\n\n";

        echo "  " . Colors::BOLD . "Totale test:     " . Colors::RESET . $this->stats['total'] . "\n";
        echo "  " . Colors::GREEN . "Passati:         " . $this->stats['passed'] . " ($passRate%)" . Colors::RESET . "\n";
        echo "  " . Colors::RED . "Falliti:         " . $this->stats['failed'] . Colors::RESET . "\n";
        echo "  " . Colors::RED . "Critical:        " . $this->stats['critical'] . Colors::RESET . "\n";
        echo "  " . Colors::YELLOW . "Warnings:        " . $this->stats['warnings'] . Colors::RESET . "\n";

        echo "\n" . str_repeat("=", 70) . "\n";

        // Overall status
        if ($this->stats['critical'] === 0 && $passRate >= 80) {
            echo Colors::GREEN . Colors::BOLD . "  STATUS: PASS - Il progetto supera i test di audit" . Colors::RESET . "\n";
        } elseif ($this->stats['critical'] > 0) {
            echo Colors::RED . Colors::BOLD . "  STATUS: FAIL - Ci sono {$this->stats['critical']} errori critici da risolvere" . Colors::RESET . "\n";
        } else {
            echo Colors::YELLOW . Colors::BOLD . "  STATUS: WARNING - Il progetto necessita attenzione" . Colors::RESET . "\n";
        }

        echo str_repeat("=", 70) . "\n\n";
    }

    // ============================================
    // MAIN EXECUTION
    // ============================================

    public function run(): void {
        echo Colors::BOLD . Colors::CYAN;
        echo "\n";
        echo "  ╔══════════════════════════════════════════════════════════════╗\n";
        echo "  ║           SEO TOOLKIT - FULL AUDIT TEST SUITE                ║\n";
        echo "  ║                                                              ║\n";
        echo "  ║  Testing: Functional | Compliance | Services                 ║\n";
        echo "  ╚══════════════════════════════════════════════════════════════╝\n";
        echo Colors::RESET;

        echo "\n  Modules found: " . implode(', ', array_keys($this->modules)) . "\n";
        echo "  Database: " . ($this->db ? "Connected" : "Not connected") . "\n";
        echo "  Base URL: " . BASE_URL . "\n";

        $this->runFunctionalTests();
        $this->runComplianceTests();
        $this->runServicesTests();
        $this->generateReports();

        // Save results to JSON
        $jsonReport = ROOT_PATH . '/storage/audit-report-' . date('Y-m-d-His') . '.json';
        if (is_dir(ROOT_PATH . '/storage')) {
            file_put_contents($jsonReport, json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'stats' => $this->stats,
                'results' => $this->results
            ], JSON_PRETTY_PRINT));
            echo "\n  Report JSON salvato in: $jsonReport\n";
        }
    }
}

// Run the audit
$audit = new FullAuditTest();
$audit->run();
