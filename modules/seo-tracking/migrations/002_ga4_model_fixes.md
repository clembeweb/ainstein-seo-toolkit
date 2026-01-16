# GA4 Model Fixes

## 1. Ga4Connection.php - Aggiungere metodi

### Metodo getByProject() - ALIAS per findByProject()
```php
/**
 * Trova connessione per progetto (alias)
 */
public function getByProject(int $projectId): ?array
{
    return $this->findByProject($projectId);
}
```

### Metodo upsert()
```php
/**
 * Crea o aggiorna connessione
 */
public function upsert(int $projectId, array $data): void
{
    $existing = $this->findByProject($projectId);

    if ($existing) {
        Database::update($this->table, $data, 'project_id = ?', [$projectId]);
    } else {
        $data['project_id'] = $projectId;
        Database::insert($this->table, $data);
    }
}
```

### Metodo updateToken()
```php
/**
 * Aggiorna token cache
 */
public function updateToken(int $projectId, string $accessToken, string $expiresAt): void
{
    Database::update($this->table, [
        'access_token' => $accessToken,
        'token_expires_at' => $expiresAt,
    ], 'project_id = ?', [$projectId]);
}
```

---

## 2. Ga4Data.php - Aggiungere metodo

### Metodo getByDateRange()
```php
/**
 * Dati per range date
 */
public function getByDateRange(int $projectId, string $startDate, string $endDate): array
{
    return Database::fetchAll(
        "SELECT * FROM {$this->table}
         WHERE project_id = ? AND date BETWEEN ? AND ?
         ORDER BY date ASC, landing_page ASC",
        [$projectId, $startDate, $endDate]
    );
}
```

---

## 3. GscData.php - Aggiungere metodo

### Metodo getKeywordsByPage()
```php
/**
 * Keyword che hanno portato click a una specifica pagina in una data
 */
public function getKeywordsByPage(int $projectId, string $landingPage, string $date): array
{
    return Database::fetchAll(
        "SELECT query, clicks, impressions, position
         FROM {$this->table}
         WHERE project_id = ? AND page = ? AND date = ? AND clicks > 0
         ORDER BY clicks DESC",
        [$projectId, $landingPage, $date]
    );
}
```

---

## Riepilogo Fix

| Model | Metodo da Aggiungere | Priorità |
|-------|---------------------|----------|
| Ga4Connection | getByProject() | ALTA |
| Ga4Connection | upsert() | ALTA |
| Ga4Connection | updateToken() | ALTA |
| Ga4Data | getByDateRange() | MEDIA |
| GscData | getKeywordsByPage() | MEDIA |
