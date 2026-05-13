<?php

namespace Editorial\Lib;

/**
 * MockLemonSqueezyClient
 *
 * Implementazione fake del client Lemon Squeezy per dev/test prima che il vero
 * account LS sia configurato (task M1.2 a carico del cliente umano).
 *
 * Riconosce 3 license key di test:
 *   - TEST-STARTER-XXXX-1111   tier=starter,  max_instances=1
 *   - TEST-PRO-XXXX-2222       tier=pro,      max_instances=3
 *   - TEST-BUSINESS-XXXX-3333  tier=business, max_instances=10
 *
 * Stato persistito in storage/editorial_mock_ls/instances.json.
 * Struttura del file:
 * {
 *   "TEST-STARTER-XXXX-1111": {
 *     "instances": {
 *       "ls_inst_abc123": { "name": "https://test1.com", "created_at": 1715600000 }
 *     }
 *   },
 *   ...
 * }
 */
class MockLemonSqueezyClient implements LemonSqueezyClientInterface
{
    /**
     * Catalogo licenze hardcoded.
     */
    private const LICENSES = [
        'TEST-STARTER-XXXX-1111' => [
            'tier' => 'starter',
            'max_instances' => 1,
            'status' => 'active',
            'customer_id' => 'cust_starter_001',
            'customer_email' => 'test-starter@example.com',
            'subscription_id' => 'sub_starter_001',
            'renews_at' => '2027-01-01T00:00:00Z',
        ],
        'TEST-PRO-XXXX-2222' => [
            'tier' => 'pro',
            'max_instances' => 3,
            'status' => 'active',
            'customer_id' => 'cust_pro_002',
            'customer_email' => 'test-pro@example.com',
            'subscription_id' => 'sub_pro_002',
            'renews_at' => '2027-01-01T00:00:00Z',
        ],
        'TEST-BUSINESS-XXXX-3333' => [
            'tier' => 'business',
            'max_instances' => 10,
            'status' => 'active',
            'customer_id' => 'cust_business_003',
            'customer_email' => 'test-business@example.com',
            'subscription_id' => 'sub_business_003',
            'renews_at' => '2027-01-01T00:00:00Z',
        ],
    ];

    public function validateLicense(string $key): array
    {
        if (!isset(self::LICENSES[$key])) {
            return ['valid' => false, 'error' => 'License key not found'];
        }

        $cat = self::LICENSES[$key];
        $state = $this->loadState();
        $usage = isset($state[$key]['instances']) ? count($state[$key]['instances']) : 0;

        return [
            'valid' => true,
            'status' => $cat['status'],
            'tier' => $cat['tier'],
            'max_instances' => $cat['max_instances'],
            'activation_usage' => $usage,
            'customer_id' => $cat['customer_id'],
            'customer_email' => $cat['customer_email'],
            'subscription_id' => $cat['subscription_id'],
            'renews_at' => $cat['renews_at'],
        ];
    }

    public function activateInstance(string $key, string $instanceName): array
    {
        if (!isset(self::LICENSES[$key])) {
            return ['activated' => false, 'error' => 'License key not found'];
        }

        $cat = self::LICENSES[$key];

        return $this->withLockedState(function (array &$state) use ($key, $cat, $instanceName) {
            if (!isset($state[$key])) {
                $state[$key] = ['instances' => []];
            }

            // Idempotente: se esiste gia' un'instance con lo stesso name, ritorna quella
            foreach ($state[$key]['instances'] as $instId => $inst) {
                if (($inst['name'] ?? '') === $instanceName) {
                    return [
                        'activated' => true,
                        'instance_id' => $instId,
                        'instance_name' => $instanceName,
                        'activation_usage' => count($state[$key]['instances']),
                        'activation_limit' => $cat['max_instances'],
                    ];
                }
            }

            $usage = count($state[$key]['instances']);
            if ($usage >= $cat['max_instances']) {
                return [
                    'activated' => false,
                    'error' => 'Activation limit reached',
                    'activation_usage' => $usage,
                    'activation_limit' => $cat['max_instances'],
                ];
            }

            $instId = 'ls_inst_' . bin2hex(random_bytes(8));
            $state[$key]['instances'][$instId] = [
                'name' => $instanceName,
                'created_at' => time(),
            ];

            return [
                'activated' => true,
                'instance_id' => $instId,
                'instance_name' => $instanceName,
                'activation_usage' => count($state[$key]['instances']),
                'activation_limit' => $cat['max_instances'],
            ];
        });
    }

    public function deactivateInstance(string $key, string $instanceId): bool
    {
        if (!isset(self::LICENSES[$key])) {
            return false;
        }

        return $this->withLockedState(function (array &$state) use ($key, $instanceId) {
            if (!isset($state[$key]['instances'][$instanceId])) {
                return false;
            }
            unset($state[$key]['instances'][$instanceId]);
            return true;
        });
    }

    public function getLicenseInfo(string $key): array
    {
        return $this->validateLicense($key);
    }

    public function getCustomer(string $customerId): array
    {
        foreach (self::LICENSES as $cat) {
            if ($cat['customer_id'] === $customerId) {
                return [
                    'id' => $customerId,
                    'email' => $cat['customer_email'],
                    'name' => 'Mock Customer (' . $cat['tier'] . ')',
                    'status' => 'subscribed',
                    'city' => null,
                    'country' => null,
                ];
            }
        }
        throw new LemonSqueezyException("Customer {$customerId} not found (mock)");
    }

    // -------------------- State helpers --------------------

    private function stateDir(): string
    {
        return dirname(__DIR__, 3) . '/storage/editorial_mock_ls';
    }

    private function stateFile(): string
    {
        return $this->stateDir() . '/instances.json';
    }

    /**
     * Carica lo stato senza lock (read-only).
     */
    private function loadState(): array
    {
        $file = $this->stateFile();
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Esegue $callback con lo stato passato per riferimento, scrive su disco
     * dopo il return e usa file lock per evitare race condition.
     *
     * @template T
     * @param callable(array&):mixed $callback
     */
    private function withLockedState(callable $callback)
    {
        $dir = $this->stateDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $this->stateFile();

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            throw new LemonSqueezyException("Cannot open mock state file: {$file}");
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new LemonSqueezyException("Cannot lock mock state file: {$file}");
        }

        try {
            $contents = stream_get_contents($fp);
            $state = [];
            if ($contents !== false && $contents !== '') {
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) {
                    $state = $decoded;
                }
            }

            $result = $callback($state);

            // riscrive sempre (mock semplice, no diff)
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            fflush($fp);

            return $result;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
