# Standard Piattaforma SEO Toolkit

Questo documento definisce gli standard obbligatori per lo sviluppo della piattaforma SEO Toolkit.

---

## Lingua

**TUTTA l'interfaccia utente DEVE essere in ITALIANO.**

Questo include:
- Titoli e intestazioni delle pagine
- Etichette dei pulsanti
- Messaggi di errore e successo
- Placeholder nei campi di input
- Testi descrittivi e istruzioni
- Tooltip e messaggi di aiuto
- Voci di menu e navigazione
- Messaggi di conferma
- Notifiche toast

### Eccezioni

I seguenti termini tecnici universali possono rimanere in inglese:
- URL
- CSV
- API
- SEO
- XML
- Sitemap
- HTTP/HTTPS
- ID
- HTML
- JSON
- Dashboard (opzionale, si puo usare "Pannello")

### Esempi di Traduzioni Standard

| Inglese | Italiano |
|---------|----------|
| Import | Importa |
| Export | Esporta |
| Save | Salva |
| Delete | Elimina |
| Cancel | Annulla |
| Confirm | Conferma |
| Back | Indietro |
| Next | Avanti |
| Search | Cerca |
| Filter | Filtra |
| Add | Aggiungi |
| Edit | Modifica |
| View | Visualizza |
| Settings | Impostazioni |
| Project | Progetto |
| Projects | Progetti |
| Upload | Carica |
| Download | Scarica |
| Error | Errore |
| Success | Successo |
| Warning | Attenzione |
| Loading | Caricamento |
| No results | Nessun risultato |
| Select | Seleziona |
| Selected | Selezionato/i |
| All | Tutti |
| None | Nessuno |

---

## Nuovi Moduli

Quando si creano nuovi moduli, assicurarsi che:

1. **Views**: Tutti i testi nelle view devono essere in italiano
2. **Messaggi di errore**: Tutti i messaggi di errore devono essere in italiano
3. **Placeholder**: Tutti i placeholder nei form devono essere in italiano
4. **Flash messages**: Messaggi di sessione in italiano
5. **Validazione**: Messaggi di validazione in italiano

### Esempio di Messaggio Flash

```php
// Corretto
$_SESSION['flash_success'] = 'URL importati con successo';
$_SESSION['flash_error'] = 'Errore durante l\'importazione';

// Sbagliato
$_SESSION['flash_success'] = 'URLs imported successfully';
$_SESSION['flash_error'] = 'Error during import';
```

### Esempio di Validazione

```php
// Corretto
if (empty($url)) {
    $errors[] = 'L\'URL e obbligatorio';
}

// Sbagliato
if (empty($url)) {
    $errors[] = 'URL is required';
}
```

---

## Componenti Condivisi

I componenti in `shared/views/components/` devono essere in italiano, inclusi:

- `import-tabs.php` - Interfaccia di importazione
- Altri componenti riutilizzabili

### Parametri Configurabili

Se un componente ha bisogno di testi personalizzabili, usare variabili PHP con valori di default in italiano:

```php
$backLabel = $backLabel ?? 'Indietro';
$submitLabel = $submitLabel ?? 'Salva';
$cancelLabel = $cancelLabel ?? 'Annulla';
```

---

## Risposte API

Le risposte API JSON possono contenere messaggi in italiano per il feedback utente:

```php
// Per messaggi mostrati all'utente
echo json_encode([
    'success' => true,
    'message' => 'Operazione completata con successo'
]);

// Per errori mostrati all'utente
echo json_encode([
    'success' => false,
    'error' => 'Progetto non trovato'
]);
```

---

## Convenzioni di Naming

### Codice PHP (Inglese)

Il codice sorgente rimane in inglese:
- Nomi di variabili: `$projectId`, `$urlCount`
- Nomi di funzioni: `importUrls()`, `validateProject()`
- Nomi di classi: `ProjectController`, `UrlModel`
- Commenti tecnici nel codice

### Interfaccia Utente (Italiano)

Solo cio che l'utente vede deve essere in italiano:
- Testi nelle view
- Messaggi di feedback
- Etichette e titoli

---

## Checklist per Review

Prima di fare merge di nuovo codice, verificare:

- [ ] Tutti i testi visibili all'utente sono in italiano
- [ ] I messaggi di errore sono in italiano
- [ ] I placeholder sono in italiano
- [ ] Le notifiche toast sono in italiano
- [ ] I titoli delle pagine sono in italiano
- [ ] I pulsanti hanno etichette in italiano

---

## Note Importanti

1. **Consistenza**: Usare sempre le stesse traduzioni per gli stessi termini
2. **Contesto**: Adattare la traduzione al contesto (es. "View" puo essere "Visualizza" o "Vista")
3. **Grammatica**: Rispettare la grammatica italiana (genere, numero, coniugazioni)
4. **Accenti**: Usare correttamente gli accenti (e, a, i, o, u)
