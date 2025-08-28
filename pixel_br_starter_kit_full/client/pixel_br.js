/** Pixel BR – Starter (client-side) */
(function(window, document){
  var DEFAULT_COLLECTOR = (window.PIXELBR_COLLECTOR_URL || "http://localhost:8080/collect");
  var PixelBR = window.PixelBR || {};

  function nowSec(){ return Math.floor(Date.now()/1000); }
  function uuid4(){ return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16|0,v=c=='x'?r:(r&0x3|0x8);return v.toString(16);}); }
  function saveQueue(q){ try{ localStorage.setItem("pixelbr_queue", JSON.stringify(q)); }catch(e){} }
  function loadQueue(){ try{ return JSON.parse(localStorage.getItem("pixelbr_queue")||"[]"); }catch(e){return [];} }
  function setConsent(state){ try{ localStorage.setItem("pixelbr_consent", state); }catch(e){} }
  function getConsent(){ try{ return localStorage.getItem("pixelbr_consent") || "granted"; }catch(e){ return "granted"; } }

  // (Demo) hashing base64 — em produção hash SHA-256 no servidor
  function simpleHash(s){ try{ return btoa(unescape(encodeURIComponent(s))).replace(/=+$/,''); } catch(e){ return ""; } }
  function normalizeEmail(e){ return (e||"").trim().toLowerCase(); }
  function normalizePhone(p){ return (p||"").replace(/\D+/g,''); }

  var config = { userId: null, product: null, collector: DEFAULT_COLLECTOR };

  PixelBR.init = function(opts){
    opts = opts || {};
    config.userId = opts.userId || config.userId;
    config.product = opts.product || config.product;
    config.collector = opts.collector || config.collector;
  };

  PixelBR.consentGrant = function(){ setConsent("granted"); };
  PixelBR.consentDeny = function(){ setConsent("denied"); };

  PixelBR.track = function(eventName, props){
    props = props || {};
    var consent = getConsent();
    var evt = {
      event_name: eventName,
      event_time: nowSec(),
      event_id: props.event_id || uuid4(),
      user_id: config.userId || null,
      product_id: props.product_id || config.product || null,
      source_url: window.location.href,
      utm: {
        source: (new URLSearchParams(window.location.search)).get('utm_source') || null,
        medium: (new URLSearchParams(window.location.search)).get('utm_medium') || null,
        campaign: (new URLSearchParams(window.location.search)).get('utm_campaign') || null,
        content: (new URLSearchParams(window.location.search)).get('utm_content') || null,
        term: (new URLSearchParams(window.location.search)).get('utm_term') || null
      },
      user_data: {},
      custom_data: props.custom_data || {},
      consent: consent
    };

    if(consent === "granted"){
      if(props.email){ evt.user_data.em = simpleHash(normalizeEmail(props.email)); }
      if(props.phone){ evt.user_data.ph = simpleHash(normalizePhone(props.phone)); }
      if(props.ip){ evt.user_data.ip = props.ip; }
      if(navigator && navigator.userAgent){ evt.user_data.ua = navigator.userAgent; }
    }

    var q = loadQueue(); q.push(evt); saveQueue(q);
    trySend();
  };

  function trySend(){
    var q = loadQueue();
    if(!q.length) return;
    var evt = q[0];
    fetch(config.collector, {
      method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(evt)
    }).then(function(res){
      if(res.ok){ q.shift(); saveQueue(q); if(q.length) setTimeout(trySend, 100); }
    }).catch(function(){ /* offline: mantém para retry */ });
  }

  // Auto-init via <script src="...pixel_br.js?id=USER&product=PROD&collector=URL">
  (function autoInit(){
    try{
      var current = document.currentScript && document.currentScript.src;
      if(!current) return;
      var u = new URL(current);
      var id = u.searchParams.get("id");
      var prod = u.searchParams.get("product");
      var c = u.searchParams.get("collector");
      PixelBR.init({ userId: id, product: prod, collector: c || config.collector });
      PixelBR.track("page_view", {});
    }catch(e){}
  })();

  window.PixelBR = PixelBR;
})(window, document);
