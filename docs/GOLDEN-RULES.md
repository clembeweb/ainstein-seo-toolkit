# AINSTEIN - Golden Rules

**10 regole INVIOLABILI per lo sviluppo**

Queste regole garantiscono consistenza, manutenibilità e qualità del codice.

---

## 1️⃣ AiService SEMPRE Centralizzato

```php
// ✅ CORRETTO
$ai = new AiService('nome-modulo');
$response = $ai->analyze($userId, $systemPrompt, $userPrompt, 'nome-modulo');

// ❌ VIETATO - Mai curl diretto
$ch = curl_init('https://api.anthropic.com/...');
```

**Perché:** Logging automatico, gestione crediti, fallback provider, metriche unificate.

---

## 2️⃣ Icone SOLO Heroicons SVG

```html
<!-- ✅ CORRETTO - SVG inline -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- ❌ VIETATO -->
<i data-lucide="check"></i>
<i class="fa fa-check"></i>
```

**Perché:** Nessuna dipendenza JS, performance, consistenza UI.

---

## 3️⃣ Lingua UI: ITALIANO

Tutti i testi visibili all'utente DEVONO essere in italiano.

```php
// ✅ CORRETTO
$_SESSION['flash_success'] = 'Progetto creato con successo';
$_SESSION['flash_error'] = 'Crediti insufficienti';

// ❌ VIETATO
$_SESSION['flash_success'] = 'Project created successfully';
```

**Eccezioni:** Termini tecnici universali (URL, CSV, API, SEO, OAuth, etc.)

---

## 4️⃣ Prefisso DB per Modulo

Ogni modulo usa un prefisso univoco di 2-3 lettere:

| Modulo | Prefisso |
|--------|----------|
| ai-content | `aic_` |
| seo-audit | `sa_` |
| seo-tracking | `st_` |
| internal-links | `il_` |
| ads-analyzer | `ga_` |
| content-creator | `cc_` |

```sql
-- ✅ CORRETTO
CREATE TABLE sa_projects (...);
CREATE TABLE sa_pages (...);

-- ❌ VIETATO
CREATE TABLE projects (...);
CREATE TABLE seo_audit_projects (...);
```

---

## 5️⃣ API Keys in Database, MAI in .env

```php
// ✅ CORRETTO - Recupera da tabella settings
$apiKey = Settings::get('anthropic_api_key');
$serpApiKey = Settings::get('serpapi_key');

// ❌ VIETATO
$apiKey = $_ENV['ANTHROPIC_API_KEY'];
$apiKey = getenv('ANTHROPIC_API_KEY');
```

**Perché:** Gestione centralizzata da Admin Panel, nessun file sensibile nel repo.

---

## 6️⃣ Pattern Routes Standard

```php
// ✅ CORRETTO - Pattern uniforme
/modulo/projects                     // Lista progetti
/modulo/projects/create              // Form creazione
/modulo/projects/{id}                // Dettaglio progetto
/modulo/projects/{id}/sezione        // Sotto-sezione
/modulo/projects/{id}/sezione/{subId} // Dettaglio sotto-elemento

// ❌ VIETATO - Pattern inconsistenti
/modulo/keywords/{id}/add            // Manca projects
/modulo/{id}/gsc/connect             // Manca projects
```

---

## 7️⃣ Prepared Statements SEMPRE

```php
// ✅ CORRETTO
$stmt = $pdo->prepare("SELECT * FROM sa_projects WHERE user_id = ? AND id = ?");
$stmt->execute([$userId, $projectId]);

// ❌ VIETATO - SQL injection risk
$pdo->query("SELECT * FROM sa_projects WHERE user_id = $userId");
```

---

## 8️⃣ CSRF Token su Tutti i Form POST

```html
<!-- ✅ CORRETTO -->
<form method="POST" action="/modulo/action">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <!-- campi -->
</form>
```

```php
// Nel controller
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('Token CSRF non valido');
}
```

---

## 9️⃣ ai-content è il Modulo Reference

Quando implementi un nuovo modulo, **copia i pattern da ai-content**:

- Struttura controller
- Integrazione AiService
- Pattern wizard multi-step
- Gestione crediti
- Flash messages
- Views layout

```bash
# Prima di iniziare un nuovo modulo
cat modules/ai-content/controllers/WizardController.php
cat modules/ai-content/services/ArticleGeneratorService.php
```

---

## 🔟 Database::reconnect() per Operazioni Lunghe

```php
// ✅ CORRETTO - Prima di operazioni post-API call
$response = $aiService->analyze(...); // Può durare 30-60s

Database::reconnect(); // Riconnetti prima di salvare
$model->save($data);

// ❌ VIETATO - Rischio "MySQL server has gone away"
$response = $aiService->analyze(...);
$model->save($data); // Può fallire se connessione scaduta
```

---

## 📋 CHECKLIST PRE-COMMIT

Prima di ogni commit, verifica:

- [ ] Nessun `curl` diretto per API AI
- [ ] Nessuna icona Lucide/FontAwesome
- [ ] Tutti i testi UI in italiano
- [ ] Tabelle con prefisso modulo corretto
- [ ] Nessuna API key in file
- [ ] Routes seguono pattern standard
- [ ] Query SQL con prepared statements
- [ ] CSRF token su form POST
- [ ] `Database::reconnect()` dopo chiamate lunghe

---

## 🚫 VIOLAZIONI COMUNI

| Violazione | Dove cercare | Fix |
|------------|--------------|-----|
| Lucide icons | `grep -r "data-lucide" modules/` | Sostituire con SVG Heroicons |
| Curl diretto | `grep -r "curl_init.*anthropic" modules/` | Usare AiService |
| Testi inglese | Review manuale views | Tradurre in italiano |
| SQL injection | `grep -r "query\(.*\$" modules/` | Usare prepare() |

---

*Documento di riferimento - Non modificare senza approvazione*
