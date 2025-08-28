import fs from "fs";
export async function dispatch(evt, logFile){
  const payload = buildPayload(evt);
  fs.appendFileSync(logFile, JSON.stringify({ dst: "meta", payload, at: Date.now() }) + "\n");
  return { ok: true, mocked: true };
}
export function buildPayload(evt){
  return {
    data: [{
      event_name: mapEventName(evt.event_name),
      event_time: evt.event_time,
      action_source: "website",
      event_source_url: evt.source_url,
      event_id: evt.event_id,
      user_data: {
        em: evt.user_data?.em,
        ph: evt.user_data?.ph,
        client_ip_address: evt.user_data?.ip,
        client_user_agent: evt.user_data?.ua
      },
      custom_data: evt.custom_data || {}
    }]
  };
}
function mapEventName(name){
  if (name === "purchase") return "Purchase";
  if (name === "lead") return "Lead";
  if (name === "page_view") return "PageView";
  if (name === "click") return "ClickButton";
  return "CustomEvent";
}
