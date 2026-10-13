# Check UI/UX dei moduli e delle aree admin — 2026-10-13

> Richiesto da Clemente il 2026-10-13 (prima del post): coerenza grafica, layout, funzioni, testi e istruzioni tra i
> moduli, partendo dalle loro dashboard; aree admin allineate agli ultimi sviluppi, funzionanti, senza cose inutili o
> fuori posto. Giro fatto in locale (stessi file di produzione) su: pagina iniziale dei 5 moduli accesi, una dashboard
> interna per modulo, dashboard utente, Prezzi, Docs, 11 pagine admin. **Legenda:** 🔴 da sistemare prima del post ·
> 🟡 da sistemare, non blocca · ⚪ scelta di Clemente.

## Trovato e già sistemato durante il giro (online)
- 🔴 **Avviso PHP in produzione** nel crawler del SEO Audit (`CrawlerService::extractSchemaTypes`): un `@type` a
  elenco nel JSON-LD produceva "Array to string conversion" (5 volte oggi, progetto 18). Corretto (`338f222`).
- 🔴 **Position Tracking in locale: pagine progetto bianche (500)** per la colonna `st_keywords.keyword_intent`
  presente solo in produzione. Aggiunta la migrazione `013_keyword_intent.sql` e applicata in locale.
- 🟡 Testo stale nelle impostazioni admin del GEO Audit ("il judge paga i crediti AI standard"): corretto.

## Fatto la sera stessa (online)
- Prezzi in pagina dai costi veri: landing AI Content (20 ad articolo, copertina inclusa) e riquadro costi del
  progetto (brief e copertina), landing Keyword Research (3/5/5 dalle impostazioni), dashboard utente (tutte le card).
- Dashboard utente: card GEO Audit; i progetti con solo GEO Audit non risultano più "0 moduli attivi".
- Admin: via il riquadro "Come creare un nuovo modulo"; moduli accesi prima; menu dei modelli AI solo Claude 5.5
  (globale e per modulo); schede Jobs solo dei moduli accesi.
- **Nomi unici (decisione Clemente 2026-10-13 sera): AI Writer · AI Keywords · AI Audit · AI Rank · AI GEO Audit**, applicati
  in 94 file (menu dal DB, titoli, dashboard, docs utente, tour, admin) e nel DB di locale e produzione.
- Restano: GEO Audit hero/sezione lunga/docs, sottotitoli, bottone
  "Nuovo progetto" uniforme, piano Free (decisione), /docs e /pricing, tour.

## 1. Pagina iniziale dei moduli (la "dashboard" di ogni tool)

| | AI Content | Keyword Research | SEO Audit | Position Tracking | GEO Audit |
|---|---|---|---|---|---|
| Nome nel menu (DB prod) | AI SEO Content Generator | AI Keyword Research | AI SEO Audit | AI SEO Position Tracking | GEO Audit |
| Titolo della pagina (h1) | AI Content Generator | Keyword Research | SEO Audit | SEO Position Tracking | GEO Audit |
| Nome in dashboard utente / docs / prezzi | AI Content / AI Content Generator | Keyword Research | SEO Audit | Position Tracking / SEO Tracking | AI Reputation Radar (!) / assente / assente |
| Bottone principale | Nuovo Progetto | Nuovo Progetto | **Nuovo Audit** | Nuovo Progetto | Nuovo Progetto |
| "Attiva su un progetto" | ✅ (oggi) | ✅ | ✅ | ✅ | ✅ |
| Hero "Come funziona" (3 step + CTA) | ✅ | ✅ | ✅ | ✅ | ✅ (2026-10-13) |
| Sezione lunga "Scopri cosa puoi fare" | ✅ | ✅ | ✅ | ✅ | ✅ (2026-10-13) |
| Tour guidato | ✅ (stale) | ✅ | ✅ (stale) | ✅ (stale) | **❌** |
| Costi dichiarati in pagina | 19 cr/articolo | 3 / 10 / 10 / Gratis | nessuno | nessuno | nessuno |
| Docs utente (/docs) | ✅ | ✅ | ✅ | ✅ | **❌** |

**Cosa non torna**
1. 🔴 **Il nome di ogni modulo cambia da un posto all'altro** (menu, titolo, dashboard utente, docs, prezzi). In
   dashboard utente il GEO Audit si chiama ancora "AI Reputation Radar" (nome preso dal DB locale: in produzione è
   "GEO Audit", ma le altre 4 voci del menu in produzione hanno il prefisso "AI SEO …" che le pagine non usano).
   ⚪ Proposta di nome unico, da usare ovunque: **AI Content · Keyword Research · SEO Audit · Position Tracking · GEO Audit**.
2. 🔴 **Il GEO Audit è l'unico senza hero "Come funziona", senza sezione "Scopri cosa puoi fare", senza tour e senza
   pagina docs**: è il gancio del post ed è il modulo più "nudo". Da fare: hero con 3 passi (Chi sei → Domande → Run e
   piano) + CTA, sezione lunga secondo il pattern landing del CLAUDE.md (colore indigo), docs `ai-reputation.php`.
3. 🔴 **Costi dichiarati diversi dai costi veri**:
   - AI Content: card "19 cr/articolo" ma un articolo con 1 URL costa 3 SERP + 1 scraping + 3 brief + 10 articolo + 3
     copertina = **20**; dashboard utente dice "Articoli da 3 crediti" e "Meta tag da 1 credito" (è 2); la dashboard
     interna del progetto elenca "3 SERP, 1 scraping, 10 generazione, 0 pubblica" senza brief e copertina.
   - Keyword Research: landing "Architettura 10 cr, Piano editoriale 10 cr", dashboard utente "da 5 crediti", e il
     piano di Stabia è costato 5. **Da verificare qual è il prezzo vero** (config `editorial_plan` = 10).
   - SEO Audit: dashboard utente "Audit da 2 crediti": è gratis.
   - Position Tracking: dashboard utente "Report AI settimanale 1 credito": il report è spento.
   - GEO Audit: nessun prezzo in pagina (ora 1 credito a risposta, stima prima del run).
   Regola da adottare: **i prezzi in pagina si leggono da `Credits::getCost()`**, mai scritti a mano (Troubleshooting
   del CLAUDE.md lo dice già).
4. 🟡 Bottone principale: "Nuovo Audit" nel SEO Audit, "Nuovo Progetto" altrove. Uniformare a "Nuovo progetto" (l'hero
   può restare diverso: "Avvia il tuo primo audit").
5. 🟡 Sottotitoli: "Audit SEO completo dei tuoi siti web", "Monitora posizioni, traffico e revenue dei tuoi siti web"
   (revenue: GA4 non collegato), "Ricerca keyword potenziata da AI con clustering semantico e architettura sito"
   (gergo), "Genera articoli SEO e contenuti automatici ottimizzati con intelligenza artificiale". Riscriverli come
   promesse concrete e brevi, nello stile del GEO Audit ("Cosa dicono ChatGPT, Gemini e le altre AI…").
6. 🟡 Le pagine iniziali mostrano i progetti con card diverse (SEO Audit: health score e contatori; Tracking: GSC,
   tracciate, verificate; GEO: domande, run, ultimo run; AI Content: progetti recenti per tipo). Va bene che i numeri
   cambino, ma la card dovrebbe avere la stessa forma (titolo, dominio, 3 KPI, data, bottone "Apri").

## 2. Dashboard interne dei progetti
- Navigazione a schede sotto l'intestazione in tutti (ok). AI Content: 5 schede; SEO Audit: 9; Tracking: 8; GEO: 4.
- 🟡 SEO Audit ha schede "Report AI" e "Action Plan"; GEO ha "Controllo tecnico"; Tracking ha "Quick Wins" e "Analisi
  pagina". I nomi sono sensati; da allineare solo il caso ("Action Plan" → "Piano d'azione", "Quick Wins" → va bene).
- 🟡 Position Tracking: progetto "standalone" mostra "Progetto standalone. Crea un Progetto…" (il banner arancione dei
  progetti orfani) — ok, ma in locale ha "Vai alla sezione Overview" e la scheda si chiama "Panoramica": allineare.
- 🟡 AI Content progetto: "Costi Crediti" incompleti (vedi sopra). SEO Audit progetto: cross-sell "Vai a Position
  Tracking" ok. GEO: ok (riquadro "Chi stai monitorando", audit, stima).
- 🟡 Stato vuoto: SEO Audit "Nessuna scansione", Tracking "Non hai ancora effettuato nessuna verifica", GEO "Nessuna
  domanda per l'audit…". Toni diversi; va bene, purché ognuno dica il prossimo passo (lo fanno).

## 3. Dashboard utente (/dashboard)
- 🔴 "I tuoi strumenti": in produzione elenca solo i moduli accesi (ok), ma la card del GEO Audit nella sezione "Scopri
  cosa puoi fare" **manca** (le card sono scritte a mano in `shared/views/dashboard.php`, righe 43-104: Keyword
  Research, AI Content, SEO Audit, SEO Tracking, Google Ads, Internal Links). Da fare: card GEO Audit; togliere Google
  Ads e Internal Links se spenti (o legare le card ai moduli attivi); prezzi da `Credits::getCost()`.
- 🔴 Card progetto "Federico Marcaccini: 0 moduli attivi — Attiva SEO Audit" mentre ha il GEO Audit attivo: il
  conteggio dei moduli nella dashboard non vede il GEO Audit (da verificare in `GlobalProject::allWithDashboardData`).

## 4. Pagine pubbliche
- 🔴 **/pricing**: "Tutti i 7 moduli", "30 crediti/mese" (ora 100 di benvenuto), elenca Google Ads Analyzer e Content
  Creator (spenti), "Report AI settimanali" come vantaggio (spento), "Prova gratis 7 giorni" (non esiste una prova a
  tempo: è il piano Free), header pubblico anche da loggati. Già in lista; va rifatta insieme alla landing.
- 🔴 **/docs**: elenca Internal Links, Google Ads Analyzer, Content Creator (spenti) e non il GEO Audit.

## 5. Aree admin (tieni / sistema / togli)

| Pagina | Stato | Note |
|---|---|---|
| /admin (dashboard) | tieni | Numeri e scorciatoie corretti. |
| /admin/users, /admin/users/{id} | tieni | Ok. |
| /admin/plans | ⚪ sistema | Free "30 crediti/mese" contro la decisione "100 crediti ai nuovi iscritti": decidere se il piano Free diventa 100/mese o se i 100 sono solo di benvenuto (e allora scriverlo). Nota Stripe ok. |
| /admin/finance | tieni | Ok. |
| /admin/modules | 🟡 sistema | Nomi dei moduli diversi dalle pagine (vedi §1). Mettere prima i moduli accesi. **Togliere** il riquadro "Come creare un nuovo modulo" (istruzioni da sviluppatore: vanno in docs/, non in admin). |
| /admin/modules/{id}/settings | 🟡 sistema | GEO Audit ok (34 impostazioni, judge_model/judge_effort nel gruppo AI). Da controllare gli altri 4 moduli accesi: impostazioni di funzioni spente (es. report settimanale in Tracking, GA4). |
| /admin/settings | 🟡 sistema | Modelli AI: elenco con Claude Sonnet 4, Opus 4, 3.5 Sonnet, 3.5 Haiku accanto ai 5.5 → lasciare solo la famiglia 5.5 (+ GPT) per non sbagliare modello. Scheda Integrazioni: Google Ads API e Keyword Planner (Keyword Planner serve, Ads API no se il modulo è spento), Stripe, SMTP: ok. Scheda Avanzate "Costi Operazioni (Crediti)": è la tabella dei prezzi globale: va bene, ma i moduli hanno i loro override e qui non si vede (confonde): aggiungere una riga "override nei moduli: …" o un link. |
| /admin/email-templates | tieni | 8 template; per il GEO Audit si usa "Notifica generica": ok. |
| /admin/ai-logs, /admin/api-logs | tieni | Verificati oggi per i costi. |
| /admin/jobs | 🟡 sistema | Schede "Content Creator" e "SEO Onpage" (moduli spenti): nascondere le schede dei moduli non attivi. Manca il GEO Audit (i run non sono "job"; va bene, ma dirlo). |
| /admin/cache | tieni | Elenca anche i moduli spenti: innocuo. |

## 6. Tour guidati (config/onboarding.php)
- Esistono per: welcome, AI Content, SEO Audit, Position Tracking, Keyword Research (+ Internal Links e Ads Analyzer,
  spenti). **Manca il GEO Audit.**
- Stale: AI Content "Costi operazioni" (prezzi cambiati), SEO Audit passo "Google Search Console" (non c'entra con
  l'audit), Position Tracking "Alert e Report AI" (report spento), welcome "Sistema crediti" (30 → 100).
- Da riscrivere dopo le correzioni di §1-3, altrimenti descrive cose che cambiano.

## Ordine di lavoro proposto (un giro solo, poi tour)
1. Nomi unici dei moduli (DB `modules.name` in prod + h1 + dashboard utente + docs + prezzi).
2. Prezzi in pagina da `Credits::getCost()` (AI Content, KR, dashboard utente) + verifica del prezzo vero del piano editoriale.
3. GEO Audit: hero "Come funziona" + sezione "Scopri cosa puoi fare" + docs `ai-reputation.php` + card in dashboard utente.
4. Dashboard utente: card legate ai moduli accesi; conteggio moduli per progetto che veda il GEO Audit.
5. Admin: riquadro sviluppatore via, moduli accesi prima, modelli AI solo 5.5, schede Jobs dei moduli spenti via, piano Free (decisione).
6. /docs e /pricing allineati ai 5 moduli (prezzi con la landing nuova).
7. Tour guidati riscritti (GEO compreso).
