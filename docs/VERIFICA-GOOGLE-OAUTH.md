# Verifica app Google OAuth di Ainstein (2026-10-16)

## Dove sta
- Progetto Google Cloud **Ainstein** (`ainstein-511817`), account bewebsolution@gmail.com. Solo Ainstein, niente altro.
- Client OAuth web **"Ainstein web"** (JSON scaricato da Clemente in `Desktop\_INBOX`). Redirect:
  `https://ainstein.it/oauth/google/callback`, `http://localhost/seo-toolkit/oauth/google/callback`.
- API attive: Search Console, Analytics Data, Analytics Admin.
- Pubblico: Esterno, **In produzione** dal 2026-10-16.
- Scope: `openid`, `userinfo.email`, `userinfo.profile`, `webmasters.readonly` (non sensibili) + **`analytics.readonly`** (unico sensibile → l'unico da verificare).
- **Google Ads NON è qui**: resta sul vecchio progetto "Beweb" (`beweb-189710`), legato al developer token.
  In Admin → Integrazioni → Google Ads API: `gads_oauth_client_id/secret` = client Beweb (commit `cda3798`).

Perché un progetto nuovo: il progetto "Beweb" ha 8 client (Apps Script, Amevista, ...) e decine di scope Gmail/Drive
(con restrizioni → serve una verifica di sicurezza a pagamento). Con lo stesso progetto la verifica non passava.

## Ordine
1. Deploy + SQL copia client Beweb in `gads_oauth_*` → poi Clemente incolla il client nuovo in Admin → Google OAuth.
2. Ricollegare GSC e GA4 sui progetti AI Rank (i token vecchi sono del client vecchio).
3. Branding: logo `public/assets/images/logo-ainstein-google-120.png` (copia in `Desktop\_INBOX`) → Salva → verifica branding.
4. Accesso ai dati: motivazione scope + link video YouTube (non in elenco) → invia la verifica.

## Motivazione scope `analytics.readonly` (da incollare, inglese)

Ainstein is an SEO toolkit. In the AI Rank module a user can optionally connect their own Google Analytics 4 property to a project. We use analytics.readonly only to list the GA4 accounts and properties the user can access, so they can pick one, and to read report data for that property: sessions, users, channels (including the "AI Assistant" channel), landing pages, conversions and revenue. The data is shown only to that user in the "Punto della situazione" (site status) report, compared with the previous period and the previous year, with an AI-written summary of those numbers. We never change Analytics settings, so read-only access is the narrowest scope that works. Data is not sold, not used for advertising and not used to train AI models.

## Scaletta video (2-3 minuti, YouTube "Non in elenco")
Requisiti Google: si vede l'URL, la schermata di consenso con il nome dell'app e lo scope, il `client_id` nella barra
degli indirizzi, e come l'app usa i dati. Schermata di consenso in **inglese** (aggiungere `&hl=en` all'URL di Google).

1. Browser su `https://ainstein.it` (URL visibile). Scorri la home, clicca **Privacy** nel footer e mostra la sezione 5 "Dati ricevuti da Google".
2. Accedi (anche con "Accedi con Google": si vede la schermata con "Ainstein").
3. AI Rank → un progetto → **Punto della situazione** → **+ Collega Analytics**.
4. Pagina Google: clicca nella barra degli indirizzi e mostra `client_id=961366436399-...` (stesso ID del client "Ainstein web").
   Aggiungi `&hl=en` e premi Invio. Scegli l'account.
5. Se compare "Google hasn't verified this app": mostrala, **Advanced → Go to ainstein.it**.
6. Schermata di consenso: si legge **Ainstein** e "See and download your Google Analytics data". Clicca Continue.
7. Torni su Ainstein: scegli la proprietà GA4.
8. Genera il **Punto della situazione**: mostra visite, canali, "Visite dalle AI", pagine di atterraggio, e il commento AI.
9. Mostra come scollegare (× accanto ad "Analytics collegato").

## Stato
- [x] Progetto, API, branding testi, scope, client, In produzione (2026-10-16)
- [x] Privacy con sezione "Dati ricevuti da Google" + frase Limited Use (`944d506`)
- [x] Logo caricato (2026-10-16)
- [x] Deploy + client nuovo in Admin (2026-10-16 sera). La SQL `gads_oauth_*` era partita dopo l'incolla e aveva
      copiato il client nuovo: rimesso il client Beweb dal DB locale + cache settings svuotata.
- [ ] **MCC Google Ads da ricollegare** (Admin → Integrazioni → Keyword Planner → Ricollega): il token del 9/10 era nato
      con l'app Beweb in Test (scadenza 7 giorni) ed è scaduto il 16/10 ~18:34. Senza, niente volumi Keyword Planner.
- [ ] Ricollegare GSC e GA4 sui progetti AI Rank (token del client vecchio)
- [x] Search Console: proprietà `https://ainstein.it/` VERIFICATA per bewebsolution (2026-10-16 sera, file HTML
      `public/google671e942f079f888e.html`, `897f817`: NON cancellarlo).
      Primo controllo branding (2026-10-16) bocciato: "home page non registrata a tuo nome".
- [ ] Branding: **dal 2026-10-17 sera** (24 h dopo la verifica GSC) → Branding → Stato di verifica → "Ho risolto i problemi" → Procedi
- [ ] Video + invio verifica scope
