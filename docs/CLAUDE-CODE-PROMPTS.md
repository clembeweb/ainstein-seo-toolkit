# AINSTEIN - Prompt per Claude Code

**Prompt pronti all'uso per sviluppo e manutenzione**

---

## 🔄 SYNC GIORNALIERO

```
# SYNC GIORNALIERO AINSTEIN - [DATA]

OBIETTIVO: Analizzare modifiche, sincronizzare repo, aggiornare documentazione.

## FASE 1: Analisi Produzione (READ-ONLY)
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it

# File modificati ultime 24h
find ~/www/ainstein.it/public_html -type f -name "*.php" -mtime -1 | head -50

# Stato moduli
ls -la ~/www/ainstein.it/public_html/modules/

## FASE 2: Sync Git
cd /percorso/locale/seo-toolkit
git fetch origin
git pull origin main --rebase

## FASE 3: Documentazione
Aggiorna docs/DEVELOPMENT_STATUS.md con:
- Modifiche di oggi
- Bug risolti
- Bug aperti
- Prossimi step

## FASE 4: Commit
git add docs/
git commit -m "docs: stato sviluppo [DATA]"
# Push manuale dopo conferma
```

---

## 🆕 NUOVO MODULO

```
# CREA MODULO [NOME] per Ainstein

PRIMA DI INIZIARE:
1. Leggi /mnt/project/GOLDEN-RULES.md
2. Leggi /mnt/project/specs/[nome-modulo].md (se esiste)
3. Usa ai-content come reference

CONTESTO:
- Directory: C:\laragon\www\seo-toolkit\modules\[slug]\
- Prefisso DB: [XX]_
- Lingua UI: Italiano
- Icone: Heroicons SVG inline
- AI: AiService centralizzato

STRUTTURA BASE:
modules/[slug]/
├── module.json
├── routes.php
├── controllers/
├── models/
├── services/
└── views/

ORDINE IMPLEMENTAZIONE:
1. module.json + routes.php
2. Tabelle database (SQL)
3. Models CRUD
4. Controllers
5. Views (copia pattern da ai-content)
6. Integrazione AiService
7. Sistema crediti
8. Test funzionale

Procedi step by step. Fermati dopo ogni step per conferma.
```

---

## 🐛 FIX BUG

```
# FIX BUG in [MODULO]

BUG: [descrizione]
FILE: [path/file.php]
LINEA: [numero se noto]

PASSI:
1. Leggi il file incriminato
2. Identifica la causa root
3. Proponi fix con spiegazione
4. Attendi conferma prima di applicare
5. Verifica sintassi: php -l [file]
6. Test manuale

GOLDEN RULES DA RISPETTARE:
- Mai curl diretto per AI
- Prepared statements per SQL
- Testi in italiano
- Heroicons SVG
```

---

## 🔍 AUDIT MODULO

```
# AUDIT MODULO [NOME]

VERIFICA:

1. GOLDEN RULES
   - [ ] AiService centralizzato (no curl diretto)
   - [ ] Icone Heroicons (no Lucide/FA)
   - [ ] Testi UI in italiano
   - [ ] Prefisso DB corretto
   - [ ] Prepared statements
   - [ ] CSRF token su form

2. STRUTTURA
   - [ ] module.json completo
   - [ ] routes.php con pattern corretti
   - [ ] Controllers con metodi implementati
   - [ ] Models con CRUD base
   - [ ] Views con layout standard

3. FUNZIONALITÀ
   - [ ] CRUD funzionante
   - [ ] Integrazione crediti
   - [ ] Export dati
   - [ ] Error handling

OUTPUT:
- Lista bug trovati con severity
- Fix suggeriti
- Stima tempo fix
```

---

## 🚀 DEPLOY PRODUZIONE

```
# DEPLOY AINSTEIN

PREREQUISITI:
- [ ] Tutti i test locali passano
- [ ] Nessun file con credenziali hardcoded
- [ ] Git status pulito

PASSI:

1. PUSH A GITHUB
   git add .
   git commit -m "[tipo]: [descrizione]"
   git push origin main

2. PULL IN PRODUZIONE
   ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
   cd ~/www/ainstein.it/public_html
   git pull origin main

3. VERIFICA
   # Test endpoint critico
   curl -s -o /dev/null -w '%{http_code}' https://ainstein.it/ai-content
   
   # Verifica errori
   tail -20 ~/www/ainstein.it/logs/error.log

4. ROLLBACK (se necessario)
   git reset --hard HEAD~1
   git push --force origin main
```

---

## 📊 TEST OAUTH GSC

```
# TEST OAUTH GOOGLE SEARCH CONSOLE

PREREQUISITI:
1. Redirect URI configurato in Google Cloud Console:
   https://ainstein.it/oauth/google/callback

2. Credenziali salvate in Admin > Impostazioni:
   - google_client_id
   - google_client_secret

PASSI TEST:

1. Login https://ainstein.it come admin

2. Vai su SEO Tracking > Nuovo Progetto
   - Nome: "Test OAuth"
   - Dominio: [un tuo sito in GSC]
   - Salva

3. Clicca "Connetti Google Search Console"
   - Deve redirect a Google
   - Seleziona account Google
   - Autorizza accesso

4. Verifica callback
   - Deve tornare su Ainstein
   - Deve mostrare lista proprietà GSC
   - Seleziona proprietà

5. Verifica connessione
   - Dashboard deve mostrare dati GSC
   - Tabella st_gsc_connections deve avere record

DEBUG SE ERRORE:
- Controlla logs: tail -f ~/www/ainstein.it/logs/error.log
- Verifica redirect URI esatto (no trailing slash)
- Verifica scopes OAuth
```

---

## 🔧 MIGRARE ICONE LUCIDE → HEROICONS

```
# MIGRAZIONE ICONE [MODULO]

TROVA OCCORRENZE:
grep -rn "data-lucide" modules/[modulo]/views/

MAPPA CONVERSIONE:
| Lucide | Heroicons SVG |
|--------|---------------|
| check | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> |
| x | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> |
| plus | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> |
| trash | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> |
| edit | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> |
| search | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> |
| download | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> |
| upload | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> |
| refresh | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> |
| settings | <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg> |

PROCEDURA:
1. Apri file view
2. Trova <i data-lucide="nome">
3. Sostituisci con SVG corrispondente
4. Verifica sintassi PHP
5. Test visivo in browser
```

---

## 📝 TEMPLATE SPEC MODULO

```markdown
# [NOME MODULO] - Specifiche Tecniche

## Overview
[Descrizione breve del modulo e scopo]

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `nome-modulo` |
| **Prefisso DB** | `xx_` |
| **Stato** | ❌ Da implementare / 🔄 In corso / ✅ Completato |

---

## Database Schema

```sql
CREATE TABLE xx_tabella (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    -- campi
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Architettura

```
modules/nome-modulo/
├── module.json
├── routes.php
├── controllers/
├── models/
├── services/
└── views/
```

---

## Routes

```php
// CRUD Progetti
GET  /nome-modulo/projects
GET  /nome-modulo/projects/create
POST /nome-modulo/projects/store
GET  /nome-modulo/projects/{id}
// etc.
```

---

## Crediti

| Azione | Costo | Descrizione |
|--------|-------|-------------|
| azione_1 | 1 | Descrizione |
| azione_ai | 5 | Analisi AI |

---

## UI Mockup

[Descrizione interfaccia o ASCII art]

---

## Checklist Implementazione

- [ ] Database schema
- [ ] Models CRUD
- [ ] Controllers
- [ ] Views
- [ ] Integrazione AI
- [ ] Sistema crediti
- [ ] Export
- [ ] Test
```

---

*Prompt aggiornati - 2026-01-02*
