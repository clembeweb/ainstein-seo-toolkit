# Ainstein SEO Toolkit

Piattaforma SaaS modulare per tool SEO con integrazione AI.

## Panoramica

Suite completa di strumenti SEO professionali:
- Generazione contenuti AI-powered
- Analisi link interni
- Audit SEO completo con Google Search Console
- Tracking posizionamento keyword con GA4

## Moduli

| Modulo | Descrizione | Stato |
|--------|-------------|-------|
| AI Content Generator | Wizard 4-step per contenuti SEO | 98% |
| Internal Links Analyzer | Analisi struttura link interni | 85% |
| SEO Audit | Audit tecnico con GSC | 90% |
| SEO Position Tracking | Monitoraggio keyword + GA4 | 70% |

## Stack Tecnologico

- **Backend:** PHP 8.x (MVC custom)
- **Frontend:** Tailwind CSS, Alpine.js, HTMX
- **Database:** MySQL
- **AI:** Claude API (Anthropic)
- **Integrazioni:** SerpAPI, Google Search Console, GA4, WordPress

## Installazione

```bash
# 1. Clona repository
git clone https://github.com/clembeweb/ainstein-seo-toolkit.git
cd ainstein-seo-toolkit

# 2. Copia e configura environment
cp .env.example .env

# 3. Modifica .env con le tue credenziali
nano .env

# 4. Importa database
mysql -u root -p seo_toolkit < database/schema.sql

# 5. Configura webserver per puntare a public/
```

## Struttura Progetto

```
ainstein-seo-toolkit/
├── config/           # Configurazioni (app, database, modules)
├── core/             # Classi core (Router, Auth, Database, View)
├── modules/          # Moduli applicazione
│   ├── ai-content/       # Generazione contenuti AI
│   ├── internal-links/   # Analisi link interni
│   ├── seo-audit/        # Audit SEO tecnico
│   └── seo-tracking/     # Tracking posizionamento
├── services/         # Servizi condivisi (AiService, Export, etc.)
├── shared/views/     # Layout e componenti condivisi
├── public/           # Document root (index.php, .htaccess)
├── storage/          # File temporanei, cache, logs
├── docs/             # Documentazione
└── admin/            # Pannello amministrazione
```

## Documentazione

- [PLATFORM_STANDARDS.md](docs/PLATFORM_STANDARDS.md) - Convenzioni e standard
- [DEVELOPMENT_STATUS.md](docs/DEVELOPMENT_STATUS.md) - Stato sviluppo moduli
- [DEPLOY.md](docs/DEPLOY.md) - Guida al deploy
- [CLAUDE_CODE_GUIDE.md](docs/CLAUDE_CODE_GUIDE.md) - Regole per sviluppo con AI
- [specs/](docs/specs/) - Specifiche tecniche moduli

## Configurazione

Tutte le configurazioni sensibili sono gestite via `.env`:

```env
# Applicazione
APP_URL=https://tuodominio.com
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_NAME=seo_toolkit
DB_USER=your_user
DB_PASS=your_password

# API Keys
CLAUDE_API_KEY=sk-ant-...
SERPAPI_KEY=...
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

## Requisiti

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Estensioni PHP: pdo_mysql, curl, json, mbstring
- Composer (opzionale, no dipendenze esterne attualmente)

## Sviluppo

```bash
# Avvia con Laragon/XAMPP/MAMP
# Accedi a http://localhost/seo-toolkit

# Per sviluppo con Claude Code, leggi:
docs/CLAUDE_CODE_GUIDE.md
```

## License

Proprietario - Tutti i diritti riservati.

---

Sviluppato con Claude Code by Anthropic
