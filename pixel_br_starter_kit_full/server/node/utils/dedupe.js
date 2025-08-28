export function ensureEventId(evt){
  if (evt && evt.event_id) return evt.event_id;
  const r = Math.random().toString(16).slice(2);
  return `evt_${Date.now()}_${r}`;
}
