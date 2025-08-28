import crypto from "crypto";
export function normalizeEmail(email){ return (email || "").trim().toLowerCase(); }
export function normalizePhone(phone){ return (phone || "").replace(/\D+/g,""); }
export function sha256Hex(str){ return crypto.createHash("sha256").update(str, "utf8").digest("hex"); }
export function hashEmail(email){ return sha256Hex(normalizeEmail(email)); }
export function hashPhone(phone){ return sha256Hex(normalizePhone(phone)); }
