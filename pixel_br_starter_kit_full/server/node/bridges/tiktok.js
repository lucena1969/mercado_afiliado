import fs from "fs";
export async function dispatch(evt, logFile){
  const payload = buildPayload(evt);
  fs.appendFileSync(logFile, JSON.stringify({ dst: "tiktok", payload, at: Date.now() }) + "\n");
  return { ok: true, mocked: true };
}
export function buildPayload(evt){
  return {
    pixel_code: process.env.TIKTOK_PIXEL_CODE || "TT_PIXEL_CODE",
    event: mapEventName(evt.event_name),
    event_id: evt.event_id,
    timestamp: evt.event_time,
    context: {
      page: { url: evt.source_url },
      user: {
        email: evt.user_data?.em,
        phone_number: evt.user_data?.ph,
        ip: evt.user_data?.ip,
        user_agent: evt.user_data?.ua
      }
    },
    properties: {
      value: evt.custom_data?.value || 0,
      currency: evt.custom_data?.currency || "BRL",
      order_id: evt.custom_data?.order_id || evt.event_id
    }
  };
}
function mapEventName(name){
  if (name === "purchase") return "Purchase";
  if (name === "lead") return "CompleteRegistration";
  if (name === "page_view") return "ViewContent";
  if (name === "click") return "ClickButton";
  return "Custom";
}
