import express from "express";
import cors from "cors";
import fs from "fs";
import path from "path";
import morgan from "morgan";
import dotenv from "dotenv";
import { fileURLToPath } from "url";
import Ajv from "ajv";

import { hashEmail, hashPhone } from "./utils/hash.js";
import { shouldAllow } from "./utils/consent.js";
import { ensureEventId } from "./utils/dedupe.js";

import * as Meta from "./bridges/meta.js";
import * as Google from "./bridges/google.js";
import * as TikTok from "./bridges/tiktok.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config();
const app = express();
app.use(express.json({ limit: "1mb" }));
app.use(cors({ origin: (_o, cb)=>cb(null,true) }));
app.use(morgan("dev"));

const ajv = new Ajv({ allErrors: true, removeAdditional: "failing" });
const schema = JSON.parse(fs.readFileSync(path.join(__dirname, "schema", "event.schema.json"), "utf8"));
const validate = ajv.compile(schema);

const dataDir = path.join(__dirname, "data");
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir);
const eventsLog = process.env.EVENTS_LOG || path.join(__dirname, "data", "events.log");
const bridgeLog = process.env.BRIDGE_LOG || path.join(__dirname, "data", "bridge.log");

function appendJSONL(file, obj){ fs.appendFileSync(file, JSON.stringify(obj) + "\n", "utf8"); }

app.get("/health", (_req, res) => res.json({ ok: true }));

app.post("/collect", async (req, res) => {
  let evt = req.body || {};
  evt.event_id = ensureEventId(evt);
  evt.event_time = evt.event_time || Math.floor(Date.now()/1000);
  evt.user_data = evt.user_data || {};

  if (evt.user_data.em && !/^[a-f0-9]{64}$/.test(evt.user_data.em)) evt.user_data.em = hashEmail(evt.user_data.em);
  if (evt.user_data.ph && !/^[a-f0-9]{64}$/.test(evt.user_data.ph)) evt.user_data.ph = hashPhone(evt.user_data.ph);

  if (!shouldAllow(evt)) return res.status(202).json({ accepted: false, reason: "no_consent" });

  const ok = validate(evt);
  if (!ok) {
    appendJSONL(eventsLog, { type: "invalid", errors: validate.errors, evt, at: Date.now() });
    return res.status(400).json({ ok: false, errors: validate.errors });
  }

  appendJSONL(eventsLog, { type: "event", evt, at: Date.now() });

  const dispatchResult = {
    meta: await Meta.dispatch(evt, bridgeLog),
    google: await Google.dispatch(evt, bridgeLog),
    tiktok: await TikTok.dispatch(evt, bridgeLog)
  };

  res.json({ ok: true, dispatch: dispatchResult });
});

const PORT = process.env.PORT || 8080;
app.listen(PORT, () => console.log("Pixel BR Collector listening on port", PORT));
