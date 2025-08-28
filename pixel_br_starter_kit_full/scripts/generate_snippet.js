// Uso: node scripts/generate_snippet.js USER123 PROD567
const userId = process.argv[2] || "USER123";
const product = process.argv[3] || null;
const collector = "http://localhost:8080/collect";
const u = new URL("client/pixel_br.js", "https://pixel.mercadoafiliado.com.br/");
if (userId) u.searchParams.set("id", userId);
if (product) u.searchParams.set("product", product);
u.searchParams.set("collector", collector);
console.log(`<script src="${u.toString()}"></script>`);
