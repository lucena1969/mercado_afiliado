/** 
 * Pixel BR - Mercado Afiliado
 * Sistema de tracking compatível com LGPD
 */
(function(window, document){
  var DEFAULT_COLLECTOR = (window.PIXELBR_COLLECTOR_URL || window.location.origin + "/mercado_afiliado/api/pixel/collect.php");
  var PixelBR = window.PixelBR || {};

  function nowSec(){ return Math.floor(Date.now()/1000); }
  function uuid4(){ return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16|0,v=c=='x'?r:(r&0x3|0x8);return v.toString(16);}); }
  function saveQueue(q){ try{ localStorage.setItem("pixelbr_queue", JSON.stringify(q)); }catch(e){} }
  function loadQueue(){ try{ return JSON.parse(localStorage.getItem("pixelbr_queue")||"[]"); }catch(e){return [];} }
  function setConsent(state){ try{ localStorage.setItem("pixelbr_consent", state); }catch(e){} }
  function getConsent(){ try{ return localStorage.getItem("pixelbr_consent") || "granted"; }catch(e){ return "granted"; } }

  function simpleHash(s){ try{ return btoa(unescape(encodeURIComponent(s))).replace(/=+$/,''); } catch(e){ return ""; } }
  function normalizeEmail(e){ return (e||"").trim().toLowerCase(); }
  function normalizePhone(p){ return (p||"").replace(/\D+/g,''); }

  var config = { 
    userId: null, 
    integrationId: null,
    productId: null, 
    collector: DEFAULT_COLLECTOR,
    debug: false
  };

  PixelBR.init = function(opts){
    opts = opts || {};
    config.userId = opts.userId || config.userId;
    config.integrationId = opts.integrationId || config.integrationId;
    config.productId = opts.productId || config.productId;
    config.collector = opts.collector || config.collector;
    config.debug = opts.debug || config.debug;
    
    if(config.debug) console.log('[PixelBR] Initialized:', config);
  };

  PixelBR.consentGrant = function(){ 
    setConsent("granted"); 
    if(config.debug) console.log('[PixelBR] Consent granted');
  };
  
  PixelBR.consentDeny = function(){ 
    setConsent("denied"); 
    if(config.debug) console.log('[PixelBR] Consent denied');
  };

  PixelBR.track = function(eventName, props){
    props = props || {};
    var consent = getConsent();
    var urlParams = new URLSearchParams(window.location.search);
    
    var evt = {
      event_name: eventName,
      event_time: nowSec(),
      event_id: props.event_id || uuid4(),
      user_id: config.userId || null,
      integration_id: config.integrationId || null,
      product_id: props.product_id || config.productId || null,
      source_url: window.location.href,
      referrer_url: document.referrer || null,
      utm: {
        source: urlParams.get('utm_source') || null,
        medium: urlParams.get('utm_medium') || null,
        campaign: urlParams.get('utm_campaign') || null,
        content: urlParams.get('utm_content') || null,
        term: urlParams.get('utm_term') || null
      },
      user_data: {
        screen_resolution: screen.width + 'x' + screen.height,
        language: navigator.language || navigator.userLanguage || 'pt-BR',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null
      },
      custom_data: props.custom_data || {},
      consent: consent
    };

    if(consent === "granted"){
      if(props.email){ evt.user_data.em = simpleHash(normalizeEmail(props.email)); }
      if(props.phone){ evt.user_data.ph = simpleHash(normalizePhone(props.phone)); }
      if(navigator && navigator.userAgent){ evt.user_data.ua = navigator.userAgent; }
      
      try {
        var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if(connection) evt.user_data.connection_type = connection.effectiveType || connection.type;
      } catch(e) {}
    }

    var q = loadQueue(); 
    q.push(evt); 
    saveQueue(q);
    
    if(config.debug) console.log('[PixelBR] Event queued:', evt);
    trySend();
  };

  function trySend(){
    var q = loadQueue();
    if(!q.length) return;
    
    var evt = q[0];
    if(config.debug) console.log('[PixelBR] Sending event:', evt);
    
    fetch(config.collector, {
      method: "POST", 
      headers: { "Content-Type": "application/json" }, 
      body: JSON.stringify(evt)
    }).then(function(res){
      if(res.ok){ 
        q.shift(); 
        saveQueue(q); 
        if(config.debug) console.log('[PixelBR] Event sent successfully');
        if(q.length) setTimeout(trySend, 100); 
      } else {
        if(config.debug) console.error('[PixelBR] Failed to send event:', res.status);
      }
    }).catch(function(err){ 
      if(config.debug) console.error('[PixelBR] Network error:', err);
    });
  }

  PixelBR.trackPurchase = function(orderData){
    var customData = {
      value: orderData.value || 0,
      currency: orderData.currency || 'BRL',
      order_id: orderData.order_id || null,
      product_name: orderData.product_name || null,
      payment_method: orderData.payment_method || null
    };
    
    PixelBR.track('purchase', {
      email: orderData.email,
      phone: orderData.phone,
      product_id: orderData.product_id,
      custom_data: customData
    });
  };

  PixelBR.trackLead = function(leadData){
    PixelBR.track('lead', {
      email: leadData.email,
      phone: leadData.phone,
      product_id: leadData.product_id,
      custom_data: leadData.custom_data || {}
    });
  };

  (function autoInit(){
    try{
      var current = document.currentScript && document.currentScript.src;
      if(!current) return;
      
      var u = new URL(current);
      var userId = u.searchParams.get("user_id");
      var integrationId = u.searchParams.get("integration_id");
      var productId = u.searchParams.get("product_id");
      var collector = u.searchParams.get("collector");
      var debug = u.searchParams.get("debug") === "true";
      
      PixelBR.init({ 
        userId: userId, 
        integrationId: integrationId,
        productId: productId, 
        collector: collector || config.collector,
        debug: debug
      });
      
      PixelBR.track("page_view", {});
    }catch(e){
      console.error('[PixelBR] Auto-init failed:', e);
    }
  })();

  window.PixelBR = PixelBR;
})(window, document);