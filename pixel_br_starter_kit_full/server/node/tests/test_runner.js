import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import Ajv from "ajv";
import { hashEmail, hashPhone } from "../utils/hash.js";
import { ensureEventId } from "../utils/dedupe.js";
import * as Meta from "../bridges/meta.js";
import * as Google from "../bridges/google.js";
import * as TikTok from "../bridges/tiktok.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const schema = JSON.parse(fs.readFileSync(path.join(__dirname, "..", "schema", "event.schema.json"), "utf8"));
const ajv = new Ajv({ allErrors: true });
const validate = ajv.compile(schema);

const events = JSON.parse(fs.readFileSync(path.join(__dirname, "sample_events.json"), "utf8"));

let passed = 0, failed = 0;
for (const original of events){
  const evt = JSON.parse(JSON.stringify(original));
  evt.event_id = ensureEventId(evt);
  if (evt.user_data?.em) evt.user_data.em = hashEmail(evt.user_data.em);
  if (evt.user_data?.ph) evt.user_data.ph = hashPhone(evt.user_data.ph);

  const ok = validate(evt);
  if (!ok){
    console.log("❌ INVALID:", validate.errors, evt);
    failed++;
    continue;
  }

  const log = path.join(__dirname, "..", "data", "bridge.log");
  fs.mkdirSync(path.dirname(log), { recursive: true });

  const resMeta = await Meta.dispatch(evt, log);
  const resGAds = await Google.dispatch(evt, log);
  const resTT = await TikTok.dispatch(evt, log);

  console.log("✅ VALID:", evt.event_name, evt.event_id, { meta: resMeta.ok, google: resGAds.ok, tiktok: resTT.ok });
  passed++;
}

console.log(`\nRESULT: passed=${passed} failed=${failed}`);
if (failed > 0) process.exit(1);
