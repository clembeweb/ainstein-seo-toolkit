<?php

namespace Modules\AdsAnalyzer\Services;

class ScriptGeneratorService
{
    /**
     * Genera lo script Google Ads completo con token e configurazione embedded
     */
    public static function generate(string $token, string $endpointUrl, array $config): string
    {
        $enableSearchTerms = ($config['enable_search_terms'] ?? true) ? 'true' : 'false';
        $enableCampaignPerf = ($config['enable_campaign_performance'] ?? true) ? 'true' : 'false';
        $dateRange = $config['date_range'] ?? 'LAST_30_DAYS';
        $campaignFilter = addslashes($config['campaign_filter'] ?? '');

        return <<<SCRIPT
/**
 * Ainstein SEO Toolkit - Google Ads Script
 * Invia automaticamente dati search terms e campagne al tuo progetto Ainstein.
 *
 * ISTRUZIONI:
 * 1. Copia questo script in Google Ads > Strumenti > Script
 * 2. Autorizza l'accesso all'account
 * 3. Esegui un test per verificare
 * 4. Imposta la frequenza desiderata (giornaliero, settimanale)
 *
 * NON modificare il token o l'endpoint.
 * Versione: 1.0
 */

// === CONFIGURAZIONE ===
var CONFIG = {
  TOKEN: '{$token}',
  ENDPOINT: '{$endpointUrl}',
  ENABLE_SEARCH_TERMS: {$enableSearchTerms},
  ENABLE_CAMPAIGN_PERFORMANCE: {$enableCampaignPerf},
  DATE_RANGE: '{$dateRange}',
  CAMPAIGN_FILTER: '{$campaignFilter}',
  SCRIPT_VERSION: '1.0',
  MAX_ITEMS: 5000
};

function main() {
  Logger.log('Ainstein Script - Avvio...');

  var payload = {
    token: CONFIG.TOKEN,
    type: getRunType(),
    script_version: CONFIG.SCRIPT_VERSION,
    date_range: getDateRange()
  };

  // Raccogli search terms
  if (CONFIG.ENABLE_SEARCH_TERMS) {
    Logger.log('Raccolta termini di ricerca...');
    payload.search_terms = collectSearchTerms();
    Logger.log('Termini raccolti: ' + payload.search_terms.length);
  }

  // Raccogli dati campagne
  if (CONFIG.ENABLE_CAMPAIGN_PERFORMANCE) {
    Logger.log('Raccolta dati campagne...');
    var campaignData = collectCampaignData();
    payload.campaigns = campaignData.campaigns;
    payload.ads = campaignData.ads;
    payload.extensions = campaignData.extensions;
    Logger.log('Campagne: ' + payload.campaigns.length +
               ', Annunci: ' + payload.ads.length +
               ', Estensioni: ' + payload.extensions.length);
  }

  // Invia dati
  Logger.log('Invio dati ad Ainstein...');
  var result = sendData(payload);

  if (result.success) {
    Logger.log('Completato! Run ID: ' + result.run_id +
               ', Items: ' + result.items_processed);
  } else {
    Logger.log('ERRORE: ' + result.error);
  }
}

function getRunType() {
  if (CONFIG.ENABLE_SEARCH_TERMS && CONFIG.ENABLE_CAMPAIGN_PERFORMANCE) return 'both';
  if (CONFIG.ENABLE_SEARCH_TERMS) return 'search_terms';
  return 'campaign_performance';
}

function getDateRange() {
  var today = new Date();
  var start = new Date();

  switch (CONFIG.DATE_RANGE) {
    case 'LAST_7_DAYS':
      start.setDate(today.getDate() - 7);
      break;
    case 'LAST_14_DAYS':
      start.setDate(today.getDate() - 14);
      break;
    case 'LAST_30_DAYS':
      start.setDate(today.getDate() - 30);
      break;
    case 'LAST_90_DAYS':
      start.setDate(today.getDate() - 90);
      break;
    case 'ALL_TIME':
      start.setFullYear(2020, 0, 1);
      break;
    default:
      start.setDate(today.getDate() - 30);
  }

  return {
    start: formatDate(start),
    end: formatDate(today)
  };
}

function formatDate(d) {
  return d.getFullYear() + '-' +
         pad(d.getMonth() + 1) + '-' +
         pad(d.getDate());
}

function pad(n) { return n < 10 ? '0' + n : '' + n; }

// === SEARCH TERMS ===
function collectSearchTerms() {
  var terms = [];
  var campaignIterator = getCampaigns();

  while (campaignIterator.hasNext() && terms.length < CONFIG.MAX_ITEMS) {
    var campaign = campaignIterator.next();
    var report = AdsApp.report(
      'SELECT Query, AdGroupName, Clicks, Impressions, Ctr, Cost, Conversions, ConversionValue ' +
      'FROM SEARCH_QUERY_PERFORMANCE_REPORT ' +
      'WHERE CampaignId = ' + campaign.getId() + ' ' +
      'DURING ' + CONFIG.DATE_RANGE
    );

    var rows = report.rows();
    while (rows.hasNext() && terms.length < CONFIG.MAX_ITEMS) {
      var row = rows.next();
      terms.push({
        ad_group: row['AdGroupName'],
        term: row['Query'],
        clicks: parseInt(row['Clicks']) || 0,
        impressions: parseInt(row['Impressions']) || 0,
        ctr: parseFloat(String(row['Ctr']).replace('%', '')) / 100 || 0,
        cost: parseFloat(String(row['Cost']).replace(/[^0-9.]/g, '')) || 0,
        conversions: parseInt(row['Conversions']) || 0,
        conversion_value: parseFloat(String(row['ConversionValue']).replace(/[^0-9.]/g, '')) || 0
      });
    }
  }

  return terms;
}

// === CAMPAIGN PERFORMANCE ===
function collectCampaignData() {
  var campaigns = [];
  var ads = [];
  var extensions = [];

  var campaignIterator = getCampaigns();

  while (campaignIterator.hasNext()) {
    var campaign = campaignIterator.next();
    var stats = campaign.getStatsFor(CONFIG.DATE_RANGE);

    campaigns.push({
      campaign_id: String(campaign.getId()),
      campaign_name: campaign.getName(),
      status: campaign.isEnabled() ? 'ENABLED' : 'PAUSED',
      type: campaign.getAdvertisingChannelType ? campaign.getAdvertisingChannelType() : 'SEARCH',
      bidding_strategy: campaign.getBiddingStrategyType(),
      budget: campaign.getBudget().getAmount(),
      budget_type: 'DAILY',
      clicks: stats.getClicks(),
      impressions: stats.getImpressions(),
      ctr: stats.getCtr(),
      avg_cpc: stats.getAverageCpc(),
      cost: stats.getCost(),
      conversions: stats.getConversions(),
      conversion_value: stats.getConversionValue ? stats.getConversionValue() : 0,
      conv_rate: stats.getConversionRate ? stats.getConversionRate() : 0
    });

    // Annunci per ogni Ad Group
    var adGroupIterator = campaign.adGroups().get();
    while (adGroupIterator.hasNext() && ads.length < CONFIG.MAX_ITEMS) {
      var adGroup = adGroupIterator.next();
      var adIterator = adGroup.ads().get();

      while (adIterator.hasNext() && ads.length < CONFIG.MAX_ITEMS) {
        var ad = adIterator.next();
        var adStats = ad.getStatsFor(CONFIG.DATE_RANGE);
        var adData = {
          campaign_id: String(campaign.getId()),
          campaign_name: campaign.getName(),
          ad_group_id: String(adGroup.getId()),
          ad_group_name: adGroup.getName(),
          type: ad.getType(),
          headlines: [],
          descriptions: [],
          final_url: '',
          path1: '',
          path2: '',
          status: ad.isEnabled() ? 'ENABLED' : 'PAUSED',
          clicks: adStats.getClicks(),
          impressions: adStats.getImpressions(),
          ctr: adStats.getCtr(),
          avg_cpc: adStats.getAverageCpc(),
          cost: adStats.getCost(),
          conversions: adStats.getConversions(),
          quality_score: null
        };

        // Estrai copy in base al tipo
        if (ad.isType().responsiveSearchAd()) {
          var rsa = ad.asType().responsiveSearchAd();
          adData.headlines = rsa.getHeadlines().map(function(h) { return h.text; }).slice(0, 3);
          adData.descriptions = rsa.getDescriptions().map(function(d) { return d.text; }).slice(0, 2);
        } else if (ad.isType().expandedTextAd()) {
          var eta = ad.asType().expandedTextAd();
          adData.headlines = [eta.getHeadlinePart1(), eta.getHeadlinePart2()];
          if (eta.getHeadlinePart3) adData.headlines.push(eta.getHeadlinePart3());
          adData.descriptions = [eta.getDescription1(), eta.getDescription2()];
        }

        adData.final_url = ad.urls().getFinalUrl() || '';
        adData.path1 = ad.isType().responsiveSearchAd() ? (ad.asType().responsiveSearchAd().getPath1() || '') : '';
        adData.path2 = ad.isType().responsiveSearchAd() ? (ad.asType().responsiveSearchAd().getPath2() || '') : '';

        // Quality Score a livello di keyword
        var kwIterator = adGroup.keywords().get();
        if (kwIterator.hasNext()) {
          var kw = kwIterator.next();
          adData.quality_score = kw.getQualityScore();
        }

        ads.push(adData);
      }
    }
  }

  // Estensioni sitelink
  try {
    var sitelinkIterator = AdsApp.extensions().sitelinks().get();
    while (sitelinkIterator.hasNext() && extensions.length < 500) {
      var sitelink = sitelinkIterator.next();
      var slStats = sitelink.getStatsFor(CONFIG.DATE_RANGE);
      extensions.push({
        campaign_id: null,
        type: 'SITELINK',
        text: sitelink.getLinkText() + ' - ' + (sitelink.getDescription1() || ''),
        status: sitelink.isEnabled ? (sitelink.isEnabled() ? 'ENABLED' : 'PAUSED') : 'UNKNOWN',
        clicks: slStats.getClicks(),
        impressions: slStats.getImpressions()
      });
    }
  } catch (e) {
    Logger.log('Nota: estensioni sitelink non disponibili - ' + e.message);
  }

  // Estensioni callout
  try {
    var calloutIterator = AdsApp.extensions().callouts().get();
    while (calloutIterator.hasNext() && extensions.length < 500) {
      var callout = calloutIterator.next();
      extensions.push({
        campaign_id: null,
        type: 'CALLOUT',
        text: callout.getText(),
        status: 'ENABLED',
        clicks: 0,
        impressions: 0
      });
    }
  } catch (e) {
    Logger.log('Nota: estensioni callout non disponibili - ' + e.message);
  }

  // Estensioni snippet strutturati
  try {
    var snippetIterator = AdsApp.extensions().snippets().get();
    while (snippetIterator.hasNext() && extensions.length < 500) {
      var snippet = snippetIterator.next();
      extensions.push({
        campaign_id: null,
        type: 'STRUCTURED_SNIPPET',
        text: snippet.getHeader() + ': ' + snippet.getValues().join(', '),
        status: 'ENABLED',
        clicks: 0,
        impressions: 0
      });
    }
  } catch (e) {
    Logger.log('Nota: snippet strutturati non disponibili - ' + e.message);
  }

  return { campaigns: campaigns, ads: ads, extensions: extensions };
}

// === UTILITY ===
function getCampaigns() {
  var selector = AdsApp.campaigns()
    .withCondition('Status = ENABLED');

  if (CONFIG.CAMPAIGN_FILTER) {
    selector = selector.withCondition("Name REGEXP_MATCH '" + CONFIG.CAMPAIGN_FILTER + "'");
  }

  return selector.get();
}

function sendData(payload) {
  var options = {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };

  try {
    var response = UrlFetchApp.fetch(CONFIG.ENDPOINT, options);
    var code = response.getResponseCode();
    var body = JSON.parse(response.getContentText());

    if (code !== 200) {
      return { success: false, error: body.error || ('HTTP ' + code) };
    }

    return body;
  } catch (e) {
    return { success: false, error: 'Errore connessione: ' + e.message };
  }
}
SCRIPT;
    }
}
