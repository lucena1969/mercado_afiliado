export function shouldAllow(evt){
  // Starter: se consent = denied, só aceita page_view sem PII
  if (evt.consent === "denied" && evt.event_name !== "page_view") return false;
  return true;
}
