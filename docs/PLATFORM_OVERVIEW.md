# SEO TOOLKIT - Piattaforma Modulare SaaS

Crea una piattaforma modulare PHP per tool SEO in C:\laragon\www\seo-toolkit

## ARCHITETTURA
```
seo-toolkit/
├── public/
│   └── index.php
├── core/
│   ├── Router.php
│   ├── ModuleLoader.php
│   ├── Database.php
│   ├── View.php
│   ├── Auth.php              # Autenticazione
│   ├── Credits.php           # Gestione crediti
│   └── Middleware.php        # Auth + ruoli + crediti
├── services/
│   ├── AiService.php         # Claude API (consuma crediti)
│   ├── ScraperService.php    # Scraping (consuma crediti)
│   └── ExportService.php
├── modules/
│   ├── ai-content/
│   ├── seo-audit/
│   ├── ads-analyzer/
│   ├── internal-links/
│   ├── seo-tracking/
│   ├── keyword-research/
│   ├── content-creator/    # Content Creator (HTML body)
│   └── _template/
├── admin/                    # Area admin
│   ├── controllers/
│   ├── views/
│   └── routes.php
├── shared/
│   ├── views/
│   │   ├── layout.php
│   │   ├── sidebar.php
│   │   └── components/
│   └── assets/
├── config/
│   ├── app.php
│   ├── database.php
│   └── modules.php
└── storage/
```

## DATABASE

### Core Auth
```sql
-- Utenti
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  role ENUM('admin', 'user') DEFAULT 'user',
  credits INT DEFAULT 50,           -- Free tier iniziale
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP
);

-- Piani abbonamento
CREATE TABLE plans (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),                -- Es: Starter, Pro, Agency
  credits_monthly INT,              -- Crediti inclusi/mese
  price_monthly DECIMAL(10,2),
  price_yearly DECIMAL(10,2),
  is_active BOOLEAN DEFAULT TRUE,
  features JSON                     -- Tool abilitati, limiti
);

-- Abbonamenti utenti
CREATE TABLE subscriptions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  plan_id INT,
  status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
  credits_remaining INT,            -- Crediti periodo corrente
  current_period_start DATE,
  current_period_end DATE,
  stripe_subscription_id VARCHAR(255),
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Ricariche manuali admin
CREATE TABLE credit_transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  amount INT,                       -- Positivo = aggiunta, negativo = consumo
  type ENUM('purchase', 'subscription', 'manual', 'usage', 'bonus'),
  description VARCHAR(255),
  admin_id INT NULL,                -- Se manual, chi ha fatto l'operazione
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Log utilizzo dettagliato
CREATE TABLE usage_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  module_slug VARCHAR(100),
  action VARCHAR(100),              -- Es: ai_analysis, scrape_url
  credits_used DECIMAL(5,2),
  metadata JSON,                    -- Dettagli operazione
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Core Moduli
```sql
-- Moduli installati
CREATE TABLE modules (
  id INT PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(100) UNIQUE,
  name VARCHAR(255),
  version VARCHAR(20),
  is_active BOOLEAN DEFAULT TRUE,
  settings JSON
);

-- Progetti (multi-tenant)
CREATE TABLE projects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  module_slug VARCHAR(100),         -- A quale modulo appartiene
  name VARCHAR(255),
  settings JSON,                    -- Config specifiche progetto
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Settings globali (API keys, config)
CREATE TABLE settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  key_name VARCHAR(100) UNIQUE,
  value TEXT,
  is_secret BOOLEAN DEFAULT FALSE,  -- Se true, mascherato in UI
  updated_by INT,
  updated_at TIMESTAMP
);
```

## SISTEMA CREDITI

### Costi operazioni (configurabili da admin)

| Operazione | Crediti | Note |
|------------|---------|------|
| scrape_url | 0.1 | Fetch singola pagina |
| ai_analysis_small | 1 | < 1000 token |
| ai_analysis_medium | 2 | 1000-5000 token |
| ai_analysis_large | 5 | > 5000 token |
| export_csv | 0 | Gratuito |
| export_excel | 0.5 | |

### Logica

1. Prima di ogni operazione: verifica crediti sufficienti
2. Se insufficienti: blocca e mostra messaggio upgrade
3. Dopo operazione: scala crediti e logga in usage_log
4. Admin può impostare costi in settings

### Reset/Accumulo

- Subscription: crediti resettano a inizio periodo
- Crediti bonus/manuali: non scadono, usati per primi
- Logica: prima consuma bonus, poi subscription

## AUTENTICAZIONE

### Pagine pubbliche
- /login
- /register
- /forgot-password
- /reset-password

### Middleware
```php
// Verifica auth
Middleware::auth();

// Verifica ruolo admin
Middleware::role('admin');

// Verifica crediti sufficienti
Middleware::hasCredits(5);
```

### Sessione

- PHP native sessions
- Remember me con token sicuro
- Logout invalida sessione

## ADMIN PANEL (/admin)

### Dashboard Admin
- Utenti totali, attivi oggi
- Crediti consumati oggi/mese
- Revenue (se Stripe attivo)
- Grafici utilizzo

### Gestione Utenti (/admin/users)
- Lista con filtri (ruolo, stato, piano)
- Azioni: attiva/disattiva, cambia ruolo, reset password
- Dettaglio utente: storico crediti, usage, progetti

### Gestione Crediti (/admin/users/{id}/credits)
- Aggiungi/rimuovi crediti manualmente
- Note obbligatorie per audit
- Storico transazioni

### Gestione Piani (/admin/plans)
- CRUD piani abbonamento
- Imposta crediti, prezzo, feature

### Gestione Moduli (/admin/modules)
- Abilita/disabilita moduli globalmente
- Imposta quali piani possono usare quali moduli

### Settings (/admin/settings)
- API Keys (Claude, altri)
- Costi crediti per operazione
- Config SMTP
- Stripe keys
- Free tier credits (default 50)

## STRIPE (PREDISPOSIZIONE)

### Config
```php
// config/app.php
'stripe' => [
    'enabled' => false,  // Attivare quando pronto
    'public_key' => '',
    'secret_key' => '',
    'webhook_secret' => '',
]
```

### Webhook endpoint
- /webhook/stripe
- Gestisce: checkout.session.completed, invoice.paid, subscription.updated/deleted

### Flow acquisto
1. User clicca upgrade
2. Redirect a Stripe Checkout
3. Webhook conferma pagamento
4. Sistema attiva subscription e crediti

## UI/UX

### Layout User
- Sidebar: moduli abilitati per il piano
- Header: nome, crediti rimanenti, upgrade button
- Footer: link supporto

### Layout Admin
- Sidebar separata con menu admin
- Badge notifiche (es. utenti in attesa)

### Componenti
- Tailwind CSS
- Dark mode
- Toast notifiche
- Modal conferme
- Progress bar operazioni
- Lingua: italiano

## ROUTING
```
# Pubbliche
/login
/register

# User
/dashboard                    # Home user
/{modulo}/...                 # Rotte modulo

# Admin
/admin                        # Dashboard admin
/admin/users                  # Gestione utenti
/admin/plans                  # Gestione piani
/admin/modules                # Gestione moduli
/admin/settings               # Impostazioni
```

## PRIMO STEP IMPLEMENTAZIONE

1. Crea struttura cartelle
2. Database: tutte le tabelle core
3. Auth: login, register, middleware
4. Layout base con sidebar
5. Admin panel base (users, settings)
6. Sistema crediti funzionante
7. Homepage user con crediti visibili
8. Template modulo in modules/_template/

**NON creare moduli funzionanti, solo la shell SaaS pronta.**

## CONFIG INIZIALE
```php
// config/database.php
return [
    'host' => 'localhost',
    'dbname' => 'seo_toolkit',
    'username' => 'root',
    'password' => '',
];
```

Crea database MySQL: seo_toolkit