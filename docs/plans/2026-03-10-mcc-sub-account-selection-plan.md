# MCC Sub-Account Selection — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users to expand MCC accounts and select their sub-accounts instead of selecting the MCC directly (which has no campaign data).

**Architecture:** Modify the `selectAccount()` controller method to build a hierarchical account structure (MCC → sub-accounts). MCC rows become non-selectable expandable containers (Alpine.js accordion). Sub-accounts appear indented with radio buttons. One level deep only. `saveSelectedAccount()` validates that selected account is not an MCC and stores the MCC parent as `login_customer_id`.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS, Google Ads API v20 REST (GAQL `customer_client`)

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `modules/ads-analyzer/controllers/ProjectController.php` | Modify (lines 316-393, 428-495) | Build hierarchical account list; validate no-MCC selection; pass `mcc_parent_id` |
| `modules/ads-analyzer/views/campaigns/connect.php` | Modify (lines 84-135) | Alpine.js accordion for MCC expansion; indented sub-accounts; hidden `mcc_parent_id` field |

No new files. No DB migrations.

---

### Task 1: Backend — Build hierarchical account list in `selectAccount()` GET

**Files:**
- Modify: `modules/ads-analyzer/controllers/ProjectController.php:316-393`

**Context:** Currently the controller fetches all accessible customer IDs, queries MCC for names, and builds a flat `$accounts` array. We need to:
1. Identify which accounts are MCC (`is_manager === true`)
2. For each MCC, query its sub-accounts via `customer_client` GAQL
3. Deduplicate: sub-accounts that appear at top level should be moved under their MCC parent
4. Structure: MCC accounts get a `sub_accounts` array; non-MCC accounts stay flat

- [ ] **Step 1: Replace the account list building logic (lines 377-389)**

Replace the current flat list builder (section "3. Costruisci lista account") with hierarchical logic. The new code goes after the existing `$accountNames` population (line 375), replacing lines 377-389.

```php
// 3. Fetch sub-accounts for each MCC
$mccSubAccounts = []; // mccId => [subAccount, ...]
$subAccountParent = []; // subAccountId => mccId (for deduplication)

foreach ($accountNames as $accId => $accInfo) {
    if (!empty($accInfo['is_manager'])) {
        try {
            $mccGadsLocal = new GoogleAdsService($user['id'], $accId);
            $subResult = $mccGadsLocal->search(
                "SELECT customer_client.id, customer_client.descriptive_name, " .
                "customer_client.manager, customer_client.currency_code, customer_client.status " .
                "FROM customer_client WHERE customer_client.level <= 1"
            );
            $subs = [];
            foreach (($subResult['results'] ?? []) as $subRow) {
                $sc = $subRow['customerClient'] ?? [];
                $scId = (string)($sc['id'] ?? '');
                // Skip the MCC itself (it appears in its own customer_client results)
                if (empty($scId) || $scId === $accId) continue;
                // Skip sub-MCC accounts (non-selezionabili, livello 1 only)
                if (!empty($sc['manager'])) continue;
                $scDisplayId = substr($scId, 0, 3) . '-' . substr($scId, 3, 3) . '-' . substr($scId, 6);
                $subs[] = [
                    'customer_id' => $scId,
                    'display_id' => $scDisplayId,
                    'name' => ($sc['descriptiveName'] ?? '') ?: 'Account ' . $scDisplayId,
                    'is_manager' => false,
                    'currency' => $sc['currencyCode'] ?? '',
                    'status' => $sc['status'] ?? '',
                    'mcc_parent_id' => $accId,
                ];
                $subAccountParent[$scId] = $accId;
            }
            $mccSubAccounts[$accId] = $subs;
        } catch (\Exception $e) {
            // MCC sub-account query failed — show MCC with error
            $mccSubAccounts[$accId] = null; // null = error state
        }
    }
}

// 4. Build hierarchical account list
foreach ($customerIds as $customerId) {
    // Skip accounts that appear as sub-accounts of an MCC (deduplication)
    if (isset($subAccountParent[$customerId])) continue;

    $info = $accountNames[$customerId] ?? [];
    $displayId = substr($customerId, 0, 3) . '-' . substr($customerId, 3, 3) . '-' . substr($customerId, 6);
    $account = [
        'customer_id' => $customerId,
        'display_id' => $displayId,
        'name' => ($info['name'] ?? '') ?: 'Account ' . $displayId,
        'is_manager' => $info['is_manager'] ?? false,
        'currency' => $info['currency'] ?? '',
        'status' => $info['status'] ?? '',
    ];

    if (!empty($account['is_manager'])) {
        $account['sub_accounts'] = $mccSubAccounts[$customerId] ?? [];
        $account['sub_accounts_error'] = !isset($mccSubAccounts[$customerId]);
    }

    $accounts[] = $account;
}
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/controllers/ProjectController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/controllers/ProjectController.php
git commit -m "feat(ads-analyzer): build hierarchical account list with MCC sub-accounts"
```

---

### Task 2: Backend — Validate no-MCC selection in `saveSelectedAccount()` POST

**Files:**
- Modify: `modules/ads-analyzer/controllers/ProjectController.php:428-495`

**Context:** Currently the POST handler validates that the customer_id is in the accessible list and determines `login_customer_id` from the global MCC setting. We need to:
1. Accept new `mcc_parent_id` form field
2. Reject if selected account is an MCC
3. Use `mcc_parent_id` (from form) as `login_customer_id` instead of global MCC setting

- [ ] **Step 1: Update `saveSelectedAccount()` method**

Replace the entire method body (lines 428-495) with:

```php
private function saveSelectedAccount(array $user, array $project, int $projectId): null
{
    $customerId = trim($_POST['customer_id'] ?? '');
    $accountName = trim($_POST['account_name'] ?? '');
    $mccParentId = trim($_POST['mcc_parent_id'] ?? '');

    if (empty($customerId)) {
        $_SESSION['_flash']['error'] = 'Seleziona un account Google Ads';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
        exit;
    }

    $cleanCustomerId = preg_replace('/[^0-9]/', '', $customerId);
    $cleanMccParentId = !empty($mccParentId) ? preg_replace('/[^0-9]/', '', $mccParentId) : '';

    // Verify the customer_id is accessible
    try {
        $gads = new GoogleAdsService($user['id'], '0');
        $result = $gads->listAccessibleCustomers();
        $resourceNames = $result['resourceNames'] ?? [];

        $validIds = array_map(function ($rn) {
            return str_replace('customers/', '', $rn);
        }, $resourceNames);

        // If selected via MCC sub-account, validate via MCC's customer_client
        if (!empty($cleanMccParentId)) {
            // The MCC itself must be in the accessible list
            if (!in_array($cleanMccParentId, $validIds, true)) {
                $_SESSION['_flash']['error'] = 'Account MCC non accessibile';
                header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
                exit;
            }
            // Verify the sub-account is under this MCC
            $mccGads = new GoogleAdsService($user['id'], $cleanMccParentId);
            $clientsResult = $mccGads->search(
                "SELECT customer_client.id, customer_client.manager FROM customer_client WHERE customer_client.level <= 1"
            );
            $validSubIds = [];
            foreach (($clientsResult['results'] ?? []) as $row) {
                $cc = $row['customerClient'] ?? [];
                $ccId = (string)($cc['id'] ?? '');
                if (!empty($ccId) && empty($cc['manager'])) {
                    $validSubIds[] = $ccId;
                }
            }
            if (!in_array($cleanCustomerId, $validSubIds, true)) {
                $_SESSION['_flash']['error'] = 'Account non trovato sotto questo MCC';
                header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
                exit;
            }
        } else {
            // Direct account selection — must be in accessible list and NOT an MCC
            if (!in_array($cleanCustomerId, $validIds, true)) {
                $_SESSION['_flash']['error'] = 'Account non valido o non accessibile';
                header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
                exit;
            }
            // Check it's not an MCC
            try {
                $directGads = new GoogleAdsService($user['id'], $cleanCustomerId, '');
                $directResult = $directGads->search(
                    "SELECT customer.manager FROM customer LIMIT 1"
                );
                $isManager = ($directResult['results'][0]['customer']['manager'] ?? false);
                if ($isManager) {
                    $_SESSION['_flash']['error'] = 'Non puoi selezionare un account MCC. Espandilo per scegliere un sub-account.';
                    header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
                    exit;
                }
            } catch (\Exception $e) {
                // Can't verify manager status — allow selection
            }
        }
    } catch (\Exception $e) {
        $_SESSION['_flash']['error'] = 'Errore nella verifica dell\'account: ' . $e->getMessage();
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/connect"));
        exit;
    }

    // login_customer_id = MCC parent if sub-account, null otherwise
    $loginCustomerId = !empty($cleanMccParentId) ? $cleanMccParentId : null;

    // Update project
    Project::update($projectId, [
        'google_ads_customer_id' => $cleanCustomerId,
        'google_ads_account_name' => $accountName ?: $cleanCustomerId,
        'login_customer_id' => $loginCustomerId,
    ]);

    $_SESSION['_flash']['success'] = 'Account Google Ads collegato con successo';
    header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaign-dashboard"));
    exit;
}
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/controllers/ProjectController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/controllers/ProjectController.php
git commit -m "feat(ads-analyzer): validate MCC not selectable, use mcc_parent_id for login_customer_id"
```

---

### Task 3: Frontend — Alpine.js MCC accordion in connect.php

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/connect.php:84-135`

**Context:** Currently the account selection form (lines 99-134) renders a flat list of radio buttons. We need to:
1. Wrap the form in an Alpine.js `x-data` component with `expandedMcc` state
2. MCC rows: no radio button, clickable to expand/collapse, chevron icon
3. Sub-account rows: indented (`pl-10`), radio button, hidden `mcc_parent_id` set on selection
4. Non-MCC rows: unchanged (radio button, no indentation)
5. Auto-expand MCC if its sub-account is the currently connected account

- [ ] **Step 1: Replace the account selection section (lines 84-135)**

Replace the `<?php elseif (!$isConnected && !empty($accounts)): ?>` block (lines 84-135) with:

```php
<?php elseif (!$isConnected && !empty($accounts)): ?>
<!-- State: Connected but needs account selection -->
<div class="p-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Seleziona account</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Account Google collegato. Scegli l'account Google Ads da utilizzare.</p>
        </div>
    </div>

    <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" method="POST" class="space-y-4"
          x-data="{
              expandedMcc: {},
              selectedCustomerId: '',
              selectedAccountName: '',
              selectedMccParentId: '',
              selectAccount(customerId, accountName, mccParentId) {
                  this.selectedCustomerId = customerId;
                  this.selectedAccountName = accountName;
                  this.selectedMccParentId = mccParentId || '';
              },
              toggleMcc(mccId) {
                  this.expandedMcc[mccId] = !this.expandedMcc[mccId];
              }
          }">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="customer_id" :value="selectedCustomerId">
        <input type="hidden" name="account_name" :value="selectedAccountName">
        <input type="hidden" name="mcc_parent_id" :value="selectedMccParentId">

        <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Account Google Ads</label>
            <?php foreach ($accounts as $account): ?>

                <?php if (!empty($account['is_manager'])): ?>
                <!-- MCC Account — expandable container, NOT selectable -->
                <div class="rounded-lg border border-slate-200 dark:border-slate-600 overflow-hidden">
                    <!-- MCC Header (click to expand) -->
                    <div @click="toggleMcc('<?= e($account['customer_id']) ?>')"
                         class="flex items-center gap-4 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex-shrink-0 text-slate-400">
                            <svg class="h-5 w-5 transition-transform duration-200" :class="expandedMcc['<?= e($account['customer_id']) ?>'] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900 dark:text-white"><?= e($account['name']) ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">MCC</span>
                            </div>
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($account['display_id']) ?><?= !empty($account['currency']) ? ' · ' . e($account['currency']) : '' ?></span>
                        </div>
                        <span class="text-xs text-slate-400 dark:text-slate-500" x-text="expandedMcc['<?= e($account['customer_id']) ?>'] ? 'Chiudi' : '<?= count($account['sub_accounts'] ?? []) ?> account'"></span>
                    </div>

                    <!-- Sub-accounts (expandable) -->
                    <div x-show="expandedMcc['<?= e($account['customer_id']) ?>']" x-collapse>
                        <div class="border-t border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50">
                            <?php if (isset($account['sub_accounts_error']) && $account['sub_accounts_error']): ?>
                            <div class="p-4 pl-14">
                                <p class="text-sm text-red-500 dark:text-red-400">Impossibile caricare i sub-account di questo MCC.</p>
                            </div>
                            <?php elseif (empty($account['sub_accounts'])): ?>
                            <div class="p-4 pl-14">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Nessun sub-account trovato in questo MCC.</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($account['sub_accounts'] as $sub): ?>
                                <label @click.stop
                                       class="flex items-center gap-4 p-3 pl-14 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/30 transition-colors"
                                       :class="selectedCustomerId === '<?= e($sub['customer_id']) ?>' ? 'bg-rose-50 dark:bg-rose-900/20' : ''">
                                    <input type="radio" name="customer_id_radio"
                                           value="<?= e($sub['customer_id']) ?>"
                                           @change="selectAccount('<?= e($sub['customer_id']) ?>', '<?= e(addslashes($sub['name'])) ?>', '<?= e($account['customer_id']) ?>')"
                                           :checked="selectedCustomerId === '<?= e($sub['customer_id']) ?>'"
                                           class="text-rose-600 focus:ring-rose-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-slate-900 dark:text-white text-sm"><?= e($sub['name']) ?></span>
                                            <?php if (!empty($sub['status']) && $sub['status'] !== 'ENABLED'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"><?= e($sub['status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($sub['display_id']) ?><?= !empty($sub['currency']) ? ' · ' . e($sub['currency']) : '' ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- Regular Account — selectable -->
                <label class="flex items-center gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-600 hover:border-rose-300 dark:hover:border-rose-600 cursor-pointer transition-colors"
                       :class="selectedCustomerId === '<?= e($account['customer_id']) ?>' ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/20' : ''">
                    <input type="radio" name="customer_id_radio"
                           value="<?= e($account['customer_id']) ?>"
                           @change="selectAccount('<?= e($account['customer_id']) ?>', '<?= e(addslashes($account['name'])) ?>', '')"
                           :checked="selectedCustomerId === '<?= e($account['customer_id']) ?>'"
                           class="text-rose-600 focus:ring-rose-500">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900 dark:text-white"><?= e($account['name']) ?></span>
                            <?php if (!empty($account['status']) && $account['status'] !== 'ENABLED'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"><?= e($account['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($account['display_id']) ?><?= !empty($account['currency']) ? ' · ' . e($account['currency']) : '' ?></span>
                    </div>
                </label>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>

        <button type="submit"
                :disabled="!selectedCustomerId"
                :class="selectedCustomerId ? 'bg-rose-600 hover:bg-rose-700' : 'bg-slate-400 cursor-not-allowed'"
                class="inline-flex items-center px-6 py-3 rounded-lg text-white font-medium transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Conferma selezione
        </button>
    </form>
</div>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l modules/ads-analyzer/views/campaigns/connect.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/connect.php
git commit -m "feat(ads-analyzer): MCC accordion UI with sub-account selection"
```

---

### Task 4: Test end-to-end on production

- [ ] **Step 1: Deploy to production**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && git pull origin main"
```

- [ ] **Step 2: Test on production page**

Navigate to `https://ainstein.it/ads-analyzer/projects/11/connect` and verify:
1. MCC accounts (Beweb Agency, Madcrumbs) show chevron, no radio button
2. Clicking MCC expands to show sub-accounts indented
3. Sub-accounts have radio buttons and are selectable
4. Non-MCC accounts (Linkiller, AMEVISTA, etc.) have radio buttons as before
5. Selecting a sub-account and clicking "Conferma selezione" works
6. `login_customer_id` is saved correctly (= MCC parent ID)
7. Subsequent sync works with the selected sub-account

- [ ] **Step 3: Test edge cases**

1. MCC with no sub-accounts → shows "Nessun sub-account trovato"
2. Accounts that were both top-level AND under MCC → deduplicated (shown only under MCC)
3. SUSPENDED/CANCELED sub-accounts → shown with badge
4. Submit without selection → button disabled
