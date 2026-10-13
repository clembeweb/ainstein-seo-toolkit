# Landing Ainstein in stile Semrush + controllo AI gratuito — design

> 2026-10-13. Decisioni di Clemente: stile Semrush con i colori di Ainstein (A), focus forte sui tool AI, niente
> storia personale (ispirarsi ai competitor), casella in alto con un **controllo vero e gratuito** "le AI leggono il tuo
> sito?" (A); al visitatore gli **highlight**, i **dettagli dopo l'iscrizione**. Mockup approvato:
> `_INBOX/2026-10-13-mockup-landing-semrush.html` ("mi piace, procedi con la versione vera").
> La landing fatta con Claude Design è scartata.

## Pagina (`public/landing5.php`, servita su `/` al posto di `landing4.php`, che resta per tornare indietro)
CSS proprio (come landing4/pricing), DM Sans, colori `#006e96 / #004d69 / #00a3d9`, header e footer condivisi
(`public/includes/site-header.php`, `site-footer.php`). Sezioni, dall'alto:
1. Barra novità AI GEO Audit.
2. Hero: "Fatti trovare su Google. Fatti consigliare dalle AI." + casella "Controlla gratis".
3. Risultato in pagina: voto N/5 + highlight (titolo + esito), dettagli sfocati, "Vedi i dettagli" → iscrizione.
4. Numeri veri: 5 tool AI, 8 lettori AI controllati, 2 AI interrogate, bonus iscrizione (`Credits::signupBonus()`), 0 carte.
5. Strumenti per obiettivo a linguette (GEO, Audit, Keywords, Writer, Rank + Punto della situazione), icone Heroicons in
   tasselli colorati, anteprime HTML/CSS animate leggere, link alle pagine docs dei moduli.
6. Catena in 6 passi.
7. Caso vero anonimo 79% / 2% in tabella ("un'attività turistica").
8. Per chi è (blog, e-commerce, attività locale, freelance/agenzie).
9. Prezzi in breve (bonus + Starter/Pro/Agency letti da `plans`, "Contattami" → info@ainstein.it).
10. FAQ, chiusura con seconda casella.
Esempi solo inventati (niente dati dei clienti nella pagina pubblica, tranne il caso anonimo).

## Controllo gratuito
- **POST `/check-ai`** (pubblico, JSON, CSRF della sessione, pattern AJAX lungo GR 15/17/23): valida l'URL, esegue
  `GeoTechService::check()` (già esistente, nessuna AI, 5-30 s), salva in `public_ai_checks`, risponde con
  `{token, host, score, total, highlights:[{level,title}]}`. I `detail` NON escono dall'endpoint.
- **Protezioni**:
  - SSRF: solo http/https, porte 80/443, l'host deve risolvere solo a IP pubblici (niente privati, loopback,
    link-local, riservati, metadati cloud); stesso controllo sull'URL finale dopo i redirect.
  - Limiti: per IP (hash) 3 all'ora e 10 al giorno; tetto globale 300 al giorno; stesso host già controllato da meno
    di 6 ore → si riusa il risultato (niente nuove richieste al sito).
  - Campo trappola anti-bot nascosto.
- **Voto** su 5 punti: le AI entrano (crawler di risposta non bloccati), testo leggibile senza JS, dati strutturati su
  chi sei, dati di contatto/societari visibili, sitemap. Calcolato da `GeoTechService::findings()`.
- **GET `/check/{token}`** (login richiesto: `Middleware::auth` salva l'URL voluto): mostra tutti i findings con i
  dettagli e "Crea il progetto per questo sito" (→ `/projects/create` col dominio). Al primo accesso il controllo si
  lega all'utente (`user_id`). La registrazione rispetta `_intended_url` (oggi solo il login lo fa).
- Tabella `public_ai_checks`: id, token (32 hex, UK), url, host, result (JSON), score, ip_hash, user_id NULL,
  created_at. Pulizia: `cron/cleanup-data.php` toglie i controlli anonimi oltre 30 giorni.

## Test
- Script CLI senza rete: validazione URL/SSRF (IP privati, porte, schemi), calcolo voto, filtro highlight senza dettagli.
- Browser: landing desktop/mobile, controllo vero su un sito, limite superato, gate → registrazione → dettagli.
