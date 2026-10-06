# Controllo a campione dei verdetti del judge — run 2 Marcaccini (2026-10-06)

> Fatto da Claude (non da una persona) leggendo per intero 20 risposte e il relativo giudizio.
> È un secondo parere AI, non una validazione umana: utile per trovare errori sistematici, non per certificare.
> Riferimento verità: profilo confermato da Clemente (confisca 2013 e inchiesta 2010 riguardano il soggetto;
> lo sciatore e Andrea Marcaccini sono altre persone).

## Campione (stratificato)
6 risposte negative/miste su domande neutre · 3 negative su domande mirate · 4 positive su domande reputazionali ·
3 neutre · 2 richieste di chiarimento · 2 "non citato". Engine: 8 ChatGPT, 6 Gemini, 6 Perplexity.

## Esito

| Cosa si controlla | Corretti | Note |
|---|---|---|
| Negativo sì/no (quello che pesa sul rischio) | **20 su 20** | nessun falso negativo né falso positivo |
| Etichetta del verdetto | 17 su 20 | #1 "negativo" dove era "misto"; #17 e #18 "non citato" su richieste di chiarimento che nominano il soggetto |
| Esito (risposta / chiarimento) | 20 su 20 | |
| Omonimia | 20 su 20 | i 3 "incerti" sono ragionevoli (Endu, profili multipli) |
| Fonti marcate "rumore" | **12 su 20** | in 8 risposte ha marcato come rumore pagine che parlano di lui |
| Fonti negative | 19 su 20 | #7: tgcom24 ("'Ndrangheta, maxi sequestro beni") e tusciaweb (hotel confiscato nel viterbese, compatibile con i suoi beni) marcati rumore |
| Contraddizioni coi fatti | non rilevate | #10 Gemini: "non emergono procedimenti giudiziari" con la confisca confermata nel profilo. Il judge l'ha giudicata "positiva" (giusto: è come la risposta lo presenta) ma non segnalava che è falsa |

## Errori sistematici trovati e corretti
1. **Rumore troppo largo**: il judge scartava come "rumore" il sito del soggetto, il suo LinkedIn e articoli che lo
   intervistano (forse perché promozionali). Correzione: regola più stretta nel prompt + rete deterministica
   (mai rumore il dominio del sito ufficiale né pagine con il cognome nel titolo o nell'URL).
2. **Contraddizioni non rilevate**: nuovo campo `contradicts_profile` + nota. Nel report: "N risposte in contrasto con i
   fatti confermati". Per Tutela Digitale è un dato utile: un'AI che "assolve" sbagliando oggi è favorevole, ma è
   instabile (al run successivo può cambiare) e non è difendibile.
3. **Menzione nelle richieste di chiarimento**: se il motore nomina il soggetto tra le opzioni, ora conta come menzione.

## Non corretto (deliberatamente)
- #7 tgcom24/tusciaweb: senza il nome nel titolo, distinguere "cronaca sul soggetto" da "cronaca simile" richiede di
  leggere la pagina. La regola nel prompt ora dice che una pagina usata per raccontare fatti del soggetto non è rumore;
  la rete deterministica sulle parole di cronaca resta attiva solo quando il judge non indica fonti negative, per non
  marcare negative pagine estranee (es. #9 radioluna, omicidio ad Aprilia, correttamente rumore).

## Le 20 righe
| # | Engine | Domanda | Verdetto judge | Mio parere |
|---|---|---|---|---|
| 1 | ChatGPT | Chi è (nav) | negativo | ok sul negativo, meglio "misto"; LinkedIn segnato rumore per errore |
| 2 | Perplexity | È affidabile? | negativo | ok; sito ufficiale segnato rumore per errore |
| 3 | Gemini | Notizie negative? | negativo | ok; sito e articolo su di lui segnati rumore |
| 4 | Perplexity | Notizie negative? | negativo | ok |
| 5 | ChatGPT | Cosa dicono stampa e persone | misto | ok |
| 6 | Perplexity | Cosa dicono stampa e persone | misto | ok |
| 7 | ChatGPT | Altre controversie (mirata) | negativo | ok; tgcom24 e tusciaweb probabilmente negative, segnate rumore |
| 8 | Perplexity | Problemi legali risolti? (mirata) | negativo | ok |
| 9 | Gemini | Confisca 2013 (mirata) | negativo | ok; radioluna correttamente rumore |
| 10 | Gemini | È affidabile? | positivo | ok come lettura; risposta falsa rispetto ai fatti, non segnalata |
| 11 | Gemini | Cosa dicono stampa e persone | positivo | ok; Milano Finanza su di lui segnata rumore |
| 12 | Perplexity | Chi è | neutro | ok |
| 13 | Gemini | Sito e social | neutro | ok; articolo su di lui segnato rumore |
| 14 | ChatGPT | Sito e social | neutro, omonimia incerta (Endu) | ok |
| 15 | ChatGPT | È affidabile? | chiede chiarimenti | ok |
| 16 | ChatGPT | Notizie negative? | chiede chiarimenti | ok |
| 17 | ChatGPT | Perché sceglierlo (comm) | non citato + chiarimento | esito ok, ma lo nomina: "citato" |
| 18 | ChatGPT | Concorrenti (comp) | non citato + chiarimento | esito ok, ma lo nomina tra le opzioni |
| 19 | Gemini | Perché sceglierlo (comm) | positivo | ok; tre pagine su di lui segnate rumore |
| 20 | Perplexity | Partnership (comm) | neutro | ok |
