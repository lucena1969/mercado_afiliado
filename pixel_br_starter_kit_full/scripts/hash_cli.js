import crypto from "crypto";
function normEmail(s){ return (s||"").trim().toLowerCase(); }
function normPhone(s){ return (s||"").replace(/\D+/g,""); }
function sha256(s){ return crypto.createHash("sha256").update(s,"utf8").digest("hex"); }
const type = process.argv[2]; // em|ph
const value = process.argv[3] || "";
if (!type || !value){
  console.log("Uso: node scripts/hash_cli.js em email@exemplo.com");
  console.log("     node scripts/hash_cli.js ph +55 11 99999-0000");
  process.exit(1);
}
if (type === "em") console.log(sha256(normEmail(value)));
else if (type === "ph") console.log(sha256(normPhone(value)));
else console.log("Tipo inválido. Use em|ph");
