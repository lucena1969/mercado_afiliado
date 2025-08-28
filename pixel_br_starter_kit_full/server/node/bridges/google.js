import fs from "fs";
export async function dispatch(evt, logFile){
  const payload = buildPayload(evt);
  fs.appendFileSync(logFile, JSON.stringify({ dst: "google", payload, at: Date.now() }) + "\n");
  return { ok: true, mocked: true };
}
export function buildPayload(evt){
  return {
    conversionAction: process.env.GOOGLE_CONVERSION_ACTION_ID || "customers/XXXX/conversionActions/YYYY",
    conversionDateTime: new Date(evt.event_time * 1000).toISOString(),
    conversionValue: evt.custom_data?.value || 0,
    currencyCode: evt.custom_data?.currency || "BRL",
    orderId: evt.custom_data?.order_id || evt.event_id,
    userIdentifiers: [
      evt.user_data?.em ? { hashedEmail: evt.user_data.em } : undefined,
      evt.user_data?.ph ? { hashedPhoneNumber: evt.user_data.ph } : undefined
    ].filter(Boolean)
  };
}
