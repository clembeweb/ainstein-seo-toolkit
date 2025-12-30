=== SEO Toolkit Connector ===
Contributors: seotoolkit
Tags: seo, content, ai, automation, publishing
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connettore per piattaforma SEO Toolkit SaaS - Pubblica articoli generati con AI direttamente su WordPress.

== Description ==

**SEO Toolkit Connector** e il plugin WordPress ufficiale per collegare il tuo sito alla piattaforma SEO Toolkit.

Con questo plugin puoi:

* Pubblicare automaticamente articoli generati con AI dalla piattaforma SEO Toolkit
* Sincronizzare categorie e tag tra la piattaforma e WordPress
* Gestire i contenuti direttamente dalla piattaforma senza accedere a WP Admin
* Impostare automaticamente meta description per Yoast SEO, RankMath e All In One SEO

= Requisiti =

* WordPress 5.6 o superiore
* PHP 7.4 o superiore
* REST API WordPress attiva
* Account sulla piattaforma SEO Toolkit

= Come funziona =

1. Installa e attiva il plugin
2. Vai su Impostazioni > SEO Toolkit
3. Copia l'API Key generata automaticamente
4. Nella piattaforma SEO Toolkit, aggiungi il tuo sito WordPress inserendo URL e API Key
5. Inizia a pubblicare articoli direttamente dalla piattaforma!

= Sicurezza =

* L'API Key e generata con crittografia sicura (48 caratteri esadecimali)
* Ogni richiesta viene autenticata tramite header `X-SEO-Toolkit-Key`
* Puoi rigenerare l'API Key in qualsiasi momento
* Nessun dato sensibile viene trasmesso

= Compatibilita Plugin SEO =

Il plugin imposta automaticamente la meta description per:

* Yoast SEO
* RankMath SEO
* All In One SEO

== Installation ==

= Installazione automatica =

1. Scarica il file ZIP del plugin dalla piattaforma SEO Toolkit
2. Vai su WordPress Admin > Plugin > Aggiungi nuovo > Carica plugin
3. Seleziona il file ZIP scaricato
4. Clicca "Installa ora"
5. Attiva il plugin

= Installazione manuale =

1. Scarica e decomprimi il file ZIP
2. Carica la cartella `seo-toolkit-connector` in `/wp-content/plugins/`
3. Attiva il plugin dal menu Plugin in WordPress

= Configurazione =

1. Dopo l'attivazione, vai su Impostazioni > SEO Toolkit
2. Copia l'URL del sito e l'API Key
3. Inserisci questi dati nella piattaforma SEO Toolkit per collegare il sito

== Frequently Asked Questions ==

= E sicuro usare questo plugin? =

Si, il plugin utilizza le API REST native di WordPress con autenticazione tramite API Key.
Nessuna credenziale utente viene mai trasmessa.

= Posso usare il plugin su piu siti? =

Si, puoi installare il plugin su tutti i siti WordPress che vuoi.
Ogni sito avra una propria API Key unica.

= Cosa succede se rigenero l'API Key? =

Dovrai aggiornare la chiave nella piattaforma SEO Toolkit.
La vecchia chiave smettera immediatamente di funzionare.

= Il plugin funziona con i custom post type? =

Attualmente il plugin supporta solo i post standard di WordPress.
Il supporto per custom post type sara aggiunto in futuro.

= Posso usare il plugin senza la piattaforma SEO Toolkit? =

No, il plugin e progettato esclusivamente per funzionare con la piattaforma SEO Toolkit.

== Changelog ==

= 1.0.0 =
* Prima release pubblica
* Autenticazione via API Key
* Endpoint per creare/aggiornare post
* Endpoint per sincronizzare categorie e tag
* Supporto meta description per Yoast, RankMath, AIOSEO
* Endpoint per upload media
* Pagina impostazioni con visualizzazione API Key

== Upgrade Notice ==

= 1.0.0 =
Prima release - installa per collegare il tuo sito alla piattaforma SEO Toolkit.

== Screenshots ==

1. Pagina impostazioni del plugin con API Key
2. Collegamento del sito nella piattaforma SEO Toolkit

== API Endpoints ==

Il plugin registra i seguenti endpoint REST:

* `GET /wp-json/seo-toolkit/v1/ping` - Verifica connessione
* `GET /wp-json/seo-toolkit/v1/categories` - Lista categorie
* `GET /wp-json/seo-toolkit/v1/tags` - Lista tag
* `POST /wp-json/seo-toolkit/v1/posts` - Crea nuovo post
* `PUT /wp-json/seo-toolkit/v1/posts/{id}` - Aggiorna post esistente
* `GET /wp-json/seo-toolkit/v1/posts` - Lista post
* `POST /wp-json/seo-toolkit/v1/media` - Upload immagine

Tutti gli endpoint richiedono autenticazione tramite header `X-SEO-Toolkit-Key`.
