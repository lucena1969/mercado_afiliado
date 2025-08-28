# Laravel – Coletor (Stub)
Use este controlador como base para receber eventos do Pixel BR em Laravel.
- Valide o payload usando `EventSchema.json` (JSON Schema) ou validações manuais equivalentes.
- Faça hashing SHA-256 de email/telefone no servidor.
- Registre eventos e então despache para os *bridges* (Meta/Google/TikTok).
