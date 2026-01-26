# Deploy Guide - Ainstein.it

## SSH
```bash
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
```

## DB Produzione
- Host: localhost
- Name: dbj0xoiwysdlk1
- User: u6iaaermphtha

## Path Produzione
```
~/www/ainstein.it/public_html/
```

## Comandi Utili

### Upload file
```bash
scp -i siteground_key -P 18765 file.sql u1608-ykgnd3z1twn4@ssh.ainstein.it:~/www/ainstein.it/public_html/
```

### Import DB
```bash
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < database_full_dump.sql
```

### Pull aggiornamenti
```bash
cd ~/www/ainstein.it/public_html
git pull origin main
```

### Permessi
```bash
chmod 755 -R .
chmod 777 -R storage/
```

---

## Migrazioni Database

### File di Migrazione Pendenti

Eseguire in ordine i seguenti file SQL:

| Modulo | File | Descrizione |
|--------|------|-------------|
| seo-tracking | `003_add_search_volume.sql` | Aggiunge colonna search volume |
| seo-tracking | `004_rank_check.sql` | Tabelle per rank checking |
| seo-tracking | `005_keyword_volumes_cache.sql` | Cache volumi keyword |
| seo-tracking | `006_create_locations.sql` | Tabella locations |
| seo-audit | `003_create_action_plans.sql` | Tabella action plans AI |
| ai-content | `003_add_wp_site_to_projects.sql` | Colonna WP site nei progetti |

### Comando Migrazione

```bash
# Esempio per singola migrazione
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-tracking/migrations/004_rank_check.sql

# Tutti i file seo-tracking
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-tracking/migrations/003_add_search_volume.sql
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-tracking/migrations/004_rank_check.sql
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-tracking/migrations/005_keyword_volumes_cache.sql
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-tracking/migrations/006_create_locations.sql

# seo-audit
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/seo-audit/migrations/003_create_action_plans.sql

# ai-content
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < modules/ai-content/migrations/003_add_wp_site_to_projects.sql
```

### Nota SiteGround - Rate Limiting

> **Attenzione**: SiteGround potrebbe restituire l'errore **"Database is limited"** se si eseguono troppe query in rapida successione.
>
> **Soluzione**: Attendere 30-60 secondi e riprovare. Se persiste, eseguire le migrazioni una alla volta con pause tra una e l'altra.
