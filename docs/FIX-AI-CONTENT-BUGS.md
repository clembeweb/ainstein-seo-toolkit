# FIX BUG AI-CONTENT - ESEGUI IN CLAUDE CODE

## CONTESTO
Modulo: `ai-content`
Directory: `C:\laragon\www\seo-toolkit\modules\ai-content\`

## REGOLE
- UN bug alla volta
- Verifica sintassi dopo ogni fix: `php -l [file]`
- Mostra diff prima/dopo
- Attendi conferma prima del bug successivo

---

## BUG #9 - CRITICO: Inserimento KW non funziona

**Problema:** Modal inserimento keyword funziona, ma dopo click "Aggiungi" la tabella rimane vuota (progetto 11).

### STEP 1: DEBUG COMPLETO
Esegui questi comandi e mostra output:

```bash
cd C:\laragon\www\seo-toolkit

echo "=== 1. KeywordController::store ==="
cat modules/ai-content/controllers/KeywordController.php | grep -n -A60 "function store"

echo "=== 2. Keyword Model create ==="
cat modules/ai-content/models/Keyword.php | grep -n -A30 "function create"

echo "=== 3. Keyword Model findByProject ==="
cat modules/ai-content/models/Keyword.php | grep -n -A20 "findByProject"

echo "=== 4. View AJAX/Form ==="
cat modules/ai-content/views/keywords/index.php | grep -n -B5 -A15 "fetch\|ajax\|submit\|addKeyword"

echo "=== 5. Verifica KW in DB progetto 11 ==="
mysql -u root seo_toolkit -e "SELECT id, project_id, keyword, status FROM aic_keywords WHERE project_id = 11 ORDER BY id DESC LIMIT 10;"

echo "=== 6. Route store keyword ==="
grep -n "keyword.*store\|store.*keyword\|keywords" modules/ai-content/routes.php
```

### STEP 2: ANALISI E FIX
Dopo l'output, analizza:
1. Il form/AJAX passa project_id correttamente?
2. La route è definita?
3. Il controller salva nel DB?
4. Le KW esistono nel DB ma la query non le trova?

**Applica il fix necessario, verifica sintassi, conferma.**

---

## BUG #1 e #2 - HIGH: Database::reconnect() mancante

**Problema:** Manca reconnect() dopo operazioni AI lunghe → "MySQL server has gone away"

### FIX
```bash
# Trova i punti dove aggiungere reconnect
grep -n "generateBrief\|generateArticle\|analyze" modules/ai-content/controllers/WizardController.php
```

**Dopo ogni chiamata AI lunga (brief, article), aggiungi:**
```php
// Reconnect dopo operazione AI lunga
\Core\Database::reconnect();
```

**Verifica:**
```bash
php -l modules/ai-content/controllers/WizardController.php
```

---

## BUG #7 - HIGH: Siti WP collegati automaticamente

**Problema:** Siti WP collegati automaticamente a nuovi progetti. Devono essere gestiti separatamente a livello utente.

### ANALISI
```bash
# Verifica tabelle
mysql -u root seo_toolkit -e "DESCRIBE aic_wp_sites;"
mysql -u root seo_toolkit -e "DESCRIBE aic_projects;"

# Verifica come viene creato un progetto
grep -n "wp_site\|WpSite" modules/ai-content/controllers/ProjectController.php
```

### FIX ARCHITETTURALE
1. **Separare gestione Siti WP** - creare sezione dedicata nel modulo
2. **Dropdown nel progetto** - selezione opzionale sito WP (non automatica)
3. **Aggiungere colonna** - `aic_projects.wp_site_id` (se non esiste)

**Attendi conferma prima di procedere con le modifiche.**

---

## BUG #8 - MEDIUM: Breadcrumbs mancanti

**Problema:** Progetto MANUALE non ha breadcrumbs.

### FIX
```bash
# Trova la view del dashboard progetto
ls modules/ai-content/views/
cat modules/ai-content/views/dashboard.php | head -50
```

**Aggiungi breadcrumbs all'inizio:**
```php
<nav class="text-sm text-slate-500 mb-4">
    <a href="<?= url('/ai-content') ?>" class="hover:text-primary-600">AI Content</a>
    <span class="mx-2">›</span>
    <span class="text-slate-900 font-medium"><?= e($project['name']) ?></span>
</nav>
```

---

## BUG #3 - MEDIUM: Route test espone session

### FIX
```bash
grep -n "test-ajax\|debug" modules/ai-content/routes.php
```

**Rimuovi o commenta route di test.**

---

## BUG #4 - MEDIUM: Data leak ArticleController

### FIX
```bash
grep -n -A15 "function progress" modules/ai-content/controllers/ArticleController.php
```

**Aggiungi verifica userId:**
```php
$article = $this->article->find($id);
if (!$article || $article['user_id'] !== $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}
```

---

## BUG #5 e #6 - LOW: Debug logging

### FIX
```bash
grep -rn "error_log" modules/ai-content/controllers/WizardController.php
grep -rn "error_log" modules/ai-content/controllers/SerpController.php
```

**Rimuovi o commenta le righe di debug.**

---

## ORDINE ESECUZIONE

1. ✅ BUG #9 - KW non si salva (BLOCCANTE)
2. ✅ BUG #1+#2 - reconnect()
3. ✅ BUG #7 - Siti WP
4. ✅ BUG #8 - Breadcrumbs
5. ✅ BUG #3+#4 - Sicurezza
6. ✅ BUG #5+#6 - Cleanup

---

## VERIFICA FINALE

```bash
# Sintassi tutti i file
find modules/ai-content -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

# Test manuale
# 1. Crea nuovo progetto MANUAL
# 2. Aggiungi keyword → DEVE apparire in tabella
# 3. Verifica breadcrumbs visibili
# 4. Verifica Siti WP NON pre-associati
```

---

**INIZIA CON BUG #9 - Esegui debug e mostra output.**
