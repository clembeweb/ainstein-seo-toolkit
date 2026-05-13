# Survey + Email Automation — Validation

> 5 domande survey ottimizzate per estrarre signal massimo con friction minimo.
> Welcome email + sequenza nurture per i signup waitlist.
> Tool consigliato: **Tally.so** (gratis, bellissimo) o **Typeform** (free tier).
> Data: 2026-05-12

---

## Strategia survey

### Obiettivi
1. **Qualificare il lead** (è veramente target?)
2. **Validare willingness-to-pay** (€29? €69? €149?)
3. **Capire pain points reali** (cosa li fa scegliere noi?)
4. **Segmentare audience** (blogger vs PMI vs agency)

### Principi
- **Massimo 5 domande** (più = drop-off >40%)
- **3-4 domande chiuse + 1 aperta** (chiuse = data analizzabile, aperta = insight qualitativo)
- **Tempo completion <60 secondi** (mostrare progress bar)
- **Niente domande sensibili upfront** (no telefono, no nome azienda)
- **Mobile-first** (60%+ degli utenti completerà da mobile)

---

## Le 5 domande (versione finale)

### Domanda 1: Qualificazione (multiple choice singola)
```
Cosa descrive meglio la tua situazione?

○ Ho un blog/sito personale, lo gestisco da solo
○ Ho una piccola/media impresa con un blog aziendale  
○ Sono freelancer/consulente, gestisco siti per i miei clienti
○ Sono un'agenzia web, gestiamo siti per molti clienti
○ Altro
```

**Why**: segmentazione immediata. Determina se è blogger (40% target), PMI (30%), freelance (15%), agency (15%).

---

### Domanda 2: Volume attuale (multiple choice singola)
```
Quanti articoli pubblichi attualmente sul tuo blog ogni mese?

○ Nessuno o quasi (1-2 al massimo)
○ Pochi (3-5 al mese)
○ Regolarmente (6-15 al mese)
○ Tanti (16+ al mese)
○ Non lo so, varia troppo
```

**Why**: capire pain real. Chi pubblica "nessuno o quasi" = il nostro vero target. Chi pubblica "tanti" già ha workflow → potrebbe non aver bisogno.

---

### Domanda 3: Willingness to pay (multiple choice singola)
```
Per uno strumento che gestisce in autonomia tutto il content marketing 
SEO del tuo blog (keyword, articoli, link interni, tutto), quanto saresti
disposto a pagare al mese?

○ Niente, voglio gratis
○ Fino a €19/mese
○ €20-49/mese
○ €50-99/mese
○ €100-249/mese
○ Più di €250/mese (gestisco molti siti)
```

**Why**: **questa è LA domanda critica**. Risposta principale per la decision gate. 
- Se >50% risponde "niente o fino €19" → pricing va abbassato O target non è giusto
- Se >50% risponde "€50-99 o €100-249" → confermiamo pricing planned
- Se >20% "più di €250" → c'è mercato agency forte

---

### Domanda 4: Pain ranking (multiple choice multipla, max 2)
```
Cosa ti darebbe più sollievo, se fosse risolto?
(scegli al massimo 2 risposte)

☐ Non avere mai più il blocco del "cosa scrivo oggi?"
☐ Smettere di studiare la SEO tecnica  
☐ Non passare ore a scrivere articoli lunghi
☐ Avere finalmente più visite organiche da Google
☐ Risparmiare i soldi del SEO consultant
☐ Pubblicare contenuti professionali senza essere un copywriter
☐ Non perdere mai più tempo sui meta-tag e SEO on-page
```

**Why**: estrae IL pain principale, ranking-style. Top answers determineranno copy marketing primario.
- Se "non avere mai più blocco" vince → posizionarsi su "ispirazione + scrittura"
- Se "smettere di studiare SEO" vince → posizionarsi su "esperto al posto tuo"
- Se "risparmiare freelancer" vince → confermare positioning anti-consultant

---

### Domanda 5: Open feedback (textarea opzionale)
```
Una cosa qualunque che vorresti dirci? 
(suggerimenti, domande, dubbi, feature che desideri)

[textarea — facoltativa]
```

**Why**: qualitative gold. La risposta "voglio vedere se gestisce anche WooCommerce", o "ho paura che gli articoli sembrino fake", o "ma quanto costa Anthropic Claude?" → tutti insight di marketing/UX impossibili da ottenere chiusi.

---

### Domanda 6: Brand name preference (multiple choice singola) ⭐ AGGIUNTA ADR-022

```
Bonus question: stiamo scegliendo il nome del prodotto. 
Quale preferisci?

○ Scribo — "il blog si scrive da solo" (latino, evocativo)
○ Penna — "scrive per te, mentre tu vivi" (italiano diretto)
○ Inkly — "AI che scrive come scrivi TU" (modern tech feel)
○ Non ho preferenze, scegliete voi
```

**Why**: trasforma la validation in **decisione naming data-driven**. Permette di:
- Vincere risparmio €300 di domini pre-validation (compriamo solo il vincente)
- Coinvolgere gli iscritti nel processo (commitment psicologico → meno churn al lancio)
- Estrarre insight su quale brand "comunica meglio" il valore al target reale

**Decision rule**: il nome con > 40% preferenze diventa il finale. Se distribuzione frammentata (< 40% leader), valuteremo qualitativo + disponibilità domini.

---

## Email automation flow

### Email 1: Welcome (immediate, post-signup)

**Subject**: 🌟 Sei dentro. Ecco cosa succede ora.

**Body**:
```
Ciao [first_name|"a te"],

Sei ufficialmente in lista d'attesa per [BRAND]. 🎉

Posto riservato. Sei l'iscritto numero [N]. 

Ecco cosa succederà nei prossimi giorni:

📋 Adesso: ti chiediamo 60 secondi
   Vorremmo capire meglio il tuo blog e cosa ti serve. 
   5 domande, niente di personale. 
   👉 [Rispondi al questionario] (link Tally/Typeform)
   
📅 Le prossime settimane: ti teniamo aggiornato
   Riceverai un'email ogni 1-2 settimane con anteprime, 
   demo, scoop dello sviluppo. Niente spam, promesso.

🚀 Quando lanciamo: sei tra i primi
   Chi è in lista riceve un link prioritario + 30% di sconto 
   per i primi 6 mesi su qualsiasi piano.

Se hai domande veloci, rispondi pure a questa email — 
arriva direttamente a me.

A presto,
[Founder name]
[BRAND] · An Ainstein product

P.S. Conosci qualcuno con un blog che vorrebbe questa cosa?
Inoltragli questa email. Più siamo, prima lanciamo. 🙏
```

**Variabili**:
- `[first_name]` — se ConvertKit ha il dato
- `[N]` — numero iscritto (ConvertKit ha "subscribers count" disponibile)

**A/B test su subject**:
- A: "🌟 Sei dentro. Ecco cosa succede ora."
- B: "Benvenuto! Hai 60 secondi?"
- C: "[BRAND] – Confermato il tuo posto"

---

### Email 2: Follow-up survey (3 giorni dopo se non risposto)

**Subject**: Hai 60 secondi per aiutarmi?

**Body**:
```
Ciao [first_name|"a te"],

3 giorni fa ti sei iscritto a [BRAND]. Grazie ancora!

Ti avevo promesso un piccolo sondaggio di 5 domande. 
Vedo che non l'hai ancora compilato — capisco, la vita.

Ma il punto è: senza le tue risposte, costruiremo un prodotto
basato sulle nostre idee. E le nostre idee potrebbero essere
sbagliate.

Le tue risposte cambiano davvero quello che faremo. 

👉 [Rispondi adesso, 60 secondi] (link)

Se non vuoi rispondere, va bene comunque. Resti in lista e
riceverai gli aggiornamenti.

Grazie!
[Founder name]
```

---

### Email 3: Update settimanale (1° venerdì del mese)

**Subject**: 📰 Settimana 1 di [BRAND] — anteprima video

**Body** (esempio, varia ogni mese):
```
Ciao [first_name|"a te"],

Update breve da [BRAND]:

✅ Settimana 1: design UX completato
   👉 [vedi 3 screenshot] (link drive/notion)

🛠️ Settimana 2-3: stiamo costruendo il "Content Brain"
   La feature che fa "leggere il tuo sito" all'AI per imparare 
   il tuo tono. Insight: i siti più "scrivibili" sono quelli con
   almeno 10 articoli storici. Sotto è dura.

📊 La community: siamo a [N] iscritti. Grazie a chi ci ha riferito amici.

🎁 Ti piace l'idea? Manda il link a un amico:
   [BRAND].com — un caffè in meno per loro, un blog scritto da AI in più.

Alla prossima!
[Founder name]
```

---

### Email 4: Beta invite (quando MVP pronto, ~ M5-M6)

**Subject**: 🎁 Sei stato selezionato per la closed beta

**Body**:
```
[first_name|"Ciao"],

Eccoci. [BRAND] è pronto per la beta.

Tu sei tra i 30 primi utenti che possono provarlo gratis 
per 30 giorni, senza impegno.

Cosa serve da te:
✓ 5 minuti per installare il plugin (ti guidiamo)
✓ Provare 1-2 articoli generati
✓ Risponderci a una mini-survey la fine settimana

In cambio ricevi:
🎁 30 giorni gratuiti completi
🎁 50% di sconto life-time se decidi di continuare
🎁 Il tuo nome (se vuoi) tra i primi supporter sulla landing
🎁 Accesso diretto al founder per feedback

👉 [Accetta l'invito beta] (link form)

Posti limitati a 30, primo arrivato primo servito. Ti aspettiamo.

[Founder name]
```

---

### Email 5: Public launch announcement (quando vai live)

**Subject**: 🚀 [BRAND] è LIVE. Il tuo sconto è pronto.

**Body**:
```
[first_name|"Sì"],

È il giorno. [BRAND] è live.

Per ringraziarti di essere stato con noi dall'inizio, ecco
il tuo regalo:

🎁 -30% su qualsiasi piano per i primi 6 mesi
🎁 Codice sconto: WAITLIST30 (auto-applicato dal link sotto)

👉 [Scegli il tuo piano] (link checkout Lemon Squeezy)

Tier consigliato per la tua situazione (basato sulle tue risposte):
[piano dinamico in base a survey question 1+2+3]

Domande? Rispondi a questa email, ti rispondo io.

Grazie davvero per averci creduto.
[Founder name]
```

---

## Setup tecnico ConvertKit (free tier)

### Tags da creare
- `waitlist-active` — chiunque si iscrive
- `survey-completed` — chi compila il questionario
- `interest-blogger` — risposta D1: blogger
- `interest-smb` — risposta D1: PMI
- `interest-freelance` — risposta D1: freelancer
- `interest-agency` — risposta D1: agency
- `pricing-low` — risposta D3: fino €19
- `pricing-mid` — risposta D3: €20-99
- `pricing-high` — risposta D3: €100+
- `beta-invited` — invitato alla beta
- `beta-accepted` — ha accettato beta
- `customer` — ha pagato

### Sequenza automation
1. Trigger: form signup → tag `waitlist-active` → invia Email 1
2. Trigger: 3 giorni dopo + non ha tag `survey-completed` → invia Email 2
3. Trigger: prima settimana mese (manuale) → broadcast Email 3 a `waitlist-active`
4. Trigger: manuale (M5-M6) → invia Email 4 a 30 hand-picked
5. Trigger: launch day (manuale) → broadcast Email 5 a `waitlist-active`

### Integrazione survey → tag
Tally/Typeform può inviare webhook a ConvertKit:
- Risposta D1 → tag corrispondente
- Risposta D3 → tag pricing
- Form completed → tag `survey-completed`

---

## Metriche da monitorare (settimanale)

| Metrica | Target a 14 giorni | Soglia preoccupazione |
|---------|--------------------|-----------------------|
| Signup waitlist | ≥ 100 | < 50 → pivot |
| Survey completion rate | ≥ 50% | < 30% → email follow-up insufficiente |
| Email open rate | ≥ 40% | < 25% → subject line da rivedere |
| Email click rate (CTA) | ≥ 10% | < 5% → copy debole |
| Survey D3 (pricing): >€50 | ≥ 30% | < 15% → pricing troppo alto vs target |
| Bounce rate landing | ≤ 60% | > 75% → traffic non qualificato o landing slow |
| Time on landing | ≥ 60s | < 30s → contenuto non engaging |

---

## Decision gate dopo 14 giorni

```
TRAFFIC SOURCES (prevedi mix):
- Ads paid: ~40% signup
- LinkedIn organico: ~25%
- Gruppi FB italiani: ~20%
- Word of mouth + referral: ~15%
```

### Scenario A — GO 🟢
- ≥ 100 signup
- ≥ 30% accept pricing €50+
- ≥ 50% survey completion
- Pain principale rilevato (D4) coincide con positioning attuale

→ **Procedi M1 development**

### Scenario B — GO con riserva 🟡
- 50-99 signup
- 15-30% accept pricing €50+
- Pain rilevato leggermente diverso

→ **Procedi M1 ma riconsidera**:
- Pricing entry tier (forse €19 invece di €29)
- Messaggio principale (cambia headline landing)
- Feature priority (forse Content Brain meno centrale, altro più)

### Scenario C — STOP 🔴
- < 50 signup nonostante €200+ ads
- < 15% accept pricing €50+
- Pain dominante è "voglio gratis"

→ **STOP development**:
- Riconsiderare target (forse non blogger ma SEO pro?)
- Riconsiderare prodotto (forse SaaS standalone non plugin?)
- Riconsiderare pricing (forse one-time license €99 invece subscription?)
- Possibile pivot completo

---

## Output deliverable post-survey

Al termine validation (giorno 14):
1. **Excel/Sheets dump** di tutte le risposte con segmentazione per tag
2. **2-page summary** con:
   - Numeri (signup, breakdown segmenti, pricing distribuito)
   - Pain points top 3
   - Quote testuali interessanti dalla D5 aperta
   - Raccomandazione GO/GO-conditional/STOP con rationale
3. **Roadmap update** se serve modifica scope

---

*Fine survey + email automation. Prossimo: `04-ads-copy.md` per le ads di acquisizione.*
