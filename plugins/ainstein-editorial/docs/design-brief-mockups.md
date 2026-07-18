# Brief per mockup — Claude Design

> Documento da passare a **Claude Design** per realizzare i mockup UI/UX del plugin.
> UI/UX del prodotto NON si progettano qui: si parte dai mockup di Claude Design, poi
> lo sviluppo traduce i mockup approvati in codice, schermata per schermata.
> Stile empirico: persona reale + contenuti d'esempio, pochi vincoli tecnici.
> Creato: 2026-07-18

---

## In una riga
Un plugin WordPress che pianifica e scrive da solo gli articoli SEO del blog, al posto dell'utente.

## Persona di riferimento (usala nei mockup)
**Maria, 45 anni**, ha un e-commerce di vino artigianale "Cantina Rossi". Non è tecnica. Pubblica ~4 articoli al mese ma non ha tempo. Vuole risultati senza imparare la SEO.

## Dove vive
Dentro la bacheca di WordPress (area admin). C'è già la barra laterale di WP a sinistra; noi disegniamo **l'area centrale**. Lingua UI: **italiano**. Utente **non tecnico**.

## Sensazione complessiva da trasmettere
Ordine, calma, "ci pensa lui per me". Premium ma semplicissimo — come un'app consumer ben fatta, non un pannello tecnico. *"Deve sembrare un prodotto da $200/mese anche se costa €19."*

---

## Le schermate, raccontate per come Maria le vive

### 1. Attivazione
Maria ha appena installato il plugin. Vede una schermata sobria: un campo "Incolla qui la tua chiave" e un bottone "Attiva". Dopo il clic la schermata diventa verde: *"Fatto! Sei collegata. Piano: Starter — 4 articoli al mese."* Niente di più.

### 2. Onboarding — 5 domande, una per schermata, con avanzamento "1 di 5"
- *"Di cosa parla il tuo sito?"* → Maria scrive: "Vino artigianale e cantina di famiglia in Toscana."
- *"Chi sono i tuoi clienti?"* → "Appassionati di vino, 35-65 anni, italiani, amano la tradizione."
- *"Che tono usi?"* → 5 scelte da toccare, ognuna con una frase-esempio: **Professionale** ("Il Sangiovese esprime tannini eleganti."), **Amichevole** ("Ti raccontiamo il nostro Sangiovese, lo amerai!"), **Divertente**, **Autorevole**, **Diretto**. Maria sceglie *Amichevole*.
- *"Quanti articoli al mese?"* → uno slider; mentre lo trascina compare *"circa 1 a settimana"*; sotto un promemoria: *"Il tuo piano Starter ne include 4."*
- *"Cosa non vuoi mai sul tuo blog?"* → "Niente politica, niente recensioni negative di altre cantine."
- Bottone finale grande: **"Inizia la magia ✨"**.

### 3. "Sto leggendo il tuo sito…" (primo momento-wow)
Dopo il clic parte un'attesa "viva": una barra che avanza e messaggi che si susseguono, uno alla volta:
*"Sto aprendo i tuoi articoli… (3 di 18)"* → *"Sto capendo come scrivi…"* → *"Quasi fatto…"*. Dura ~30-60 secondi e deve far sembrare che stia succedendo qualcosa di intelligente, non un cerchietto che gira.

### 4. "Ecco cosa ho imparato su di te"
Una scheda-riassunto con dati concreti d'esempio:
- *Tono rilevato*: **Amichevole ed esperto**
- *Parole che usi spesso*: "vendemmia", "tradizione di famiglia", "abbinamento"
- *Argomenti tipici*: degustazioni, ricette, storia della cantina
- *Un tuo paragrafo tipico* (citato): "Ogni bottiglia nasce dalle nostre colline…"
Ogni cosa è **modificabile con un clic** (Maria può correggere il tono o togliere una parola). Bottone: "Va bene così, continua".

### 5. Dashboard (la home del plugin)
Maria vede a colpo d'occhio numeri reali d'esempio:
- "**3** articoli scritti questo mese · **2** pubblicati · **1** in bozza che ti aspetta"
- "Prossimo articolo in programma: *'Quali vini abbinare alla cacciagione'* — lunedì"
- un bottone grande **"Genera un articolo ora"**.
Se è tutto vuoto (primo accesso): uno stato vuoto simpatico, tipo *"Ancora tutto silenzioso qui… vuoi che scriva il primo articolo?"*.

### 6. Generazione di un articolo (momento-wow principale)
Maria clicca "Genera ora", scrive una keyword ("vino rosso per arrosto") e parte una finestra dove l'articolo **appare scrivendosi da solo**, riga dopo riga, come quando chatti con un'AI. Prima compaiono dei passaggi spuntati ("Ho letto i primi risultati Google ✓", "Ho studiato i concorrenti ✓"), poi il testo che scorre. Alla fine: un'anteprima con titolo, descrizione Google e un punteggio di qualità. Bottoni: "Pubblica come bozza" / "Rigenera".

---

## Note per lo sviluppo (dopo l'approvazione dei mockup)
- Priorità mockup = ordine sopra (primo viaggio dell'utente: attivazione → onboarding → dashboard → generazione).
- Contesto tecnico già pronto (non vincola il design, serve solo per la traduzione in codice): plugin dentro WP admin, il backend/licenze esiste già (M1).
- Riferimento funzionale completo dei flussi: `docs/design.md` §7 (flussi) e §12 (idee "wow moment"). Il design è libero di discostarsene.
