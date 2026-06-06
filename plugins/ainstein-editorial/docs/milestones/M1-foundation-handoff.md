# M1 Foundation — Handoff & verifica live

> Stato: i task "codice" di M1 sono completi e committati. Questo documento elenca i
> passi che richiedono il **tuo** ambiente reale (DB MySQL, WordPress, account Lemon Squeezy)
> — non eseguibili nel container di sviluppo Claude.
> Ultimo aggiornamento: 2026-06-06

---

## ✅ Cosa è già fatto (in repo)

| Task | Output | Verifica fatta |
|------|--------|----------------|
| M1.1 | `database/migrations/2026-05-15-aied-schema.sql` (+ rollback) | schema scritto, 10 tabelle aied_* + test data |
| M1.3 | `api/editorial/` (Router/Request/Response, middleware, routes) | `php -l` OK, 22 rotte, matching parametri |
| M1.4 | `JwtService`, `LemonSqueezyClient`, `LicenseManager`, `ActivationController`, `SubscriptionController` | JWT issue/verify/tamper OK |
| M1.5 | `WebhookController` (5 eventi + firma HMAC + idempotency) | `php -l` OK |
| M1.6 | `plugins/ainstein-editorial/src/` (plugin shell + pagina License) | `php -l` OK, autoloader risolve namespace |
| M1.7 | `build.sh` → `dist/ainstein-editorial-v0.1.0.zip` (~28KB) | build eseguita, zip valido |

---

## 🔲 1. Esegui la migration DB (M1.1.d-f)

```bash
# Locale (Laragon)
mysql seo_toolkit < database/migrations/2026-05-15-aied-schema.sql
mysql seo_toolkit -e "SHOW TABLES LIKE 'aied_%';"          # → 10 tabelle
mysql seo_toolkit -e "SHOW INDEX FROM aied_users;"         # → idx_license presente

# Produzione (Hetzner)
mysql -u ainstein -p'***' ainstein_seo < database/migrations/2026-05-15-aied-schema.sql
```

Rollback se serve: `mysql seo_toolkit < database/migrations/rollback/2026-05-15-aied-schema-rollback.sql`

## 🔲 2. Configura le variabili env

In `.env` (o `.env.local`, gitignored):

```
AIED_JWT_SECRET=<genera con: php -r "echo bin2hex(random_bytes(32));">
LEMONSQUEEZY_API_KEY=...
LEMONSQUEEZY_STORE_ID=...
LEMONSQUEEZY_WEBHOOK_SECRET=...
```

## 🔲 3. Account Lemon Squeezy (M1.2 — manuale, ~2-3h)

Segui §2.4 di `M1-foundation.md`. Punti chiave per coerenza col codice:
- **Activation Limit** per i 4 prodotti subscription: Starter=1, Pro=3, Business=10, Agency=50.
  → Il backend deriva il **tier dall'activation_limit** (ADR-024), quindi questi numeri devono essere esatti.
- Abilita "License Keys" su ogni prodotto subscription.
- Webhook URL: `https://ainstein.it/api/editorial/v1/webhooks/lemonsqueezy` (prod) o ngrok in dev.
  Eventi: `subscription_created/updated/cancelled`, `subscription_payment_failed`, `order_created`, `license_key_created`.
- Salva il signing secret in `LEMONSQUEEZY_WEBHOOK_SECRET`.
- Genera 3 test license key (Starter/Pro/Business).

## 🔲 4. Smoke test backend via curl (M1.3.h, M1.4.f)

```bash
BASE=http://localhost/seo-toolkit/api/editorial/v1

# Health
curl -s $BASE/ping
# → {"status":"ok",...}

# Stub 501
curl -s $BASE/articles/generate -X POST
# → {"error":"...","milestone":"M3",...}  (HTTP 501)

# Attivazione (license key di test LS)
curl -s -X POST $BASE/activate -H 'Content-Type: application/json' \
  -d '{"license_key":"<TEST_KEY_STARTER>","domain":"http://localhost/wp-test","wp_version":"6.5","plugin_version":"0.1.0","admin_email":"test@example.com"}'
# → {"api_token":"...","tier":"starter","sites_remaining":0,...}

# Verifica DB
mysql seo_toolkit -e "SELECT id,email,tier FROM aied_users; SELECT id,domain,status FROM aied_sites;"

# Status (con token ottenuto sopra)
curl -s $BASE/subscription/status \
  -H "X-Api-Token: <TOKEN>" -H "X-License-Key: <TEST_KEY_STARTER>" -H "X-Site-Domain: http://localhost/wp-test"

# Limite siti: riattiva stesso license su 2° dominio (Starter limit 1) → 422 site_limit_reached
# Refresh: POST $BASE/refresh-token -d '{"license_key":"...","domain":"..."}'
# Deactivate: POST $BASE/deactivate -d '{"license_key":"...","site_id":N}'
```

## 🔲 5. Test plugin su WP (M1.6.l, M1.7.e, M1.8)

1. `./build.sh` → installa `dist/ainstein-editorial-v0.1.0.zip` da WP Admin → Plugin → Carica.
2. In `wp-config.php` (dev): `define('AIED_API_BASE', 'http://localhost/seo-toolkit/api/editorial/v1');`
3. Attiva il plugin → redirect a pagina "Ainstein Editorial".
4. Incolla la test license Starter → "✓ Attivato. Piano: Starter".
5. Verifica `wp_options` (`aied_api_token`, `aied_tier`) e record DB.
6. "Disattiva su questo sito" → slot liberato, riattivabile altrove.
7. Webhook: usa il test webhook builder LS → verifica update in `aied_users` + log in `aied_api_logs`.
8. Compila i risultati + screenshot in `M1-foundation-test-report.md` (DoD M1.8).

---

## Note / possibili aggiustamenti dopo test live

- **Formato risposta LS `validateLicense`**: il codice si aspetta `valid:true` + `license_key.activation_limit` + `meta.{customer_id,subscription_id}`. Se il payload reale differisce, adeguare `LicenseManager::activate()` e `LemonSqueezyClient`.
- **Header firma webhook**: assunto `X-Signature` (hex HMAC-SHA256). Confermare col payload reale LS.
- **`getallheaders()`**: su alcuni setup Apache/CGI può mancare; è previsto un fallback da `$_SERVER`.
