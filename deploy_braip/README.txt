================================================================================
INTEGRAÇÃO BRAIP - ARQUIVOS PARA DEPLOY
================================================================================

Data: 2025-01-05
Versão: 1.0

================================================================================
CONTEÚDO DO PACOTE
================================================================================

1. DOCUMENTAÇÃO
   - INTEGRACAO_BRAIP.md ............ Documentação técnica completa
   - DEPLOY_BRAIP.md ................ Instruções detalhadas de deploy
   - README.txt ..................... Este arquivo

2. ARQUIVOS NOVOS (criar no servidor)
   - app/models/BraipIntegration.php

3. ARQUIVOS ATUALIZADOS (substituir no servidor)
   - app/services/BraipService.php
   - app/controllers/WebhookController.php

================================================================================
INSTRUÇÕES RÁPIDAS DE INSTALAÇÃO
================================================================================

1. FAZER BACKUP
   Antes de qualquer alteração, faça backup dos arquivos existentes:
   - app/services/BraipService.php
   - app/controllers/WebhookController.php

2. UPLOAD DOS ARQUIVOS
   Extraia este ZIP e envie os arquivos para o servidor na mesma estrutura:

   /seu-servidor/
   ├── INTEGRACAO_BRAIP.md (opcional, documentação)
   └── app/
       ├── controllers/
       │   └── WebhookController.php         [SUBSTITUIR]
       ├── models/
       │   └── BraipIntegration.php          [NOVO]
       └── services/
           └── BraipService.php               [SUBSTITUIR]

3. AJUSTAR PERMISSÕES
   chmod 644 app/models/BraipIntegration.php
   chmod 644 app/services/BraipService.php
   chmod 644 app/controllers/WebhookController.php

4. TESTAR
   Acesse: https://seu-dominio.com/integrations/add
   Selecione: Braip
   Configure API Key e Auth Key

5. CONFIGURAR WEBHOOK NA BRAIP
   a) Acesse: https://ev.braip.com/login
   b) Vá em: Ferramentas > API > Novo token
   c) Vá em: Ferramentas > Postback > Nova documentação
   d) Cole a URL: https://seu-dominio.com/api/webhooks/braip/{seu-token}
   e) Selecione eventos: Pagamento Aprovado, Cancelada, Chargeback, etc
   f) Método HTTP: POST
   g) Copie a "Chave Única" e cole como API Secret na integração

================================================================================
FUNCIONALIDADES IMPLEMENTADAS
================================================================================

✓ Webhooks de pagamento (aprovado, cancelado, chargeback, reembolso)
✓ Webhooks de assinatura (ativa, atrasada, cancelada, vencida)
✓ Autenticação via chave única (basic_authentication)
✓ Suporte a UTMs completo
✓ Dados de afiliados e comissões
✓ Múltiplos métodos de pagamento
✓ Tratamento de erros robusto
✓ Logs detalhados de eventos

================================================================================
ARQUIVOS DETALHADOS
================================================================================

>> app/models/BraipIntegration.php (NOVO - 8KB)
   - Model específico da integração Braip
   - Validação de credenciais
   - Processamento de dados do webhook
   - Mapeamento de status

>> app/services/BraipService.php (ATUALIZADO - 17KB)
   - Service completo de processamento
   - Suporte a pagamentos e assinaturas
   - Validação de autenticação
   - Mapeamento de eventos

>> app/controllers/WebhookController.php (ATUALIZADO - 16KB)
   - Adicionado suporte à auth_key da Braip
   - Payload de teste completo
   - Integração com BraipService

================================================================================
SUPORTE E TROUBLESHOOTING
================================================================================

Problema: Class 'BraipService' not found
Solução: Verificar se o arquivo foi enviado para app/services/

Problema: Webhook não recebe dados
Solução: Verificar URL configurada na Braip e token correto

Problema: Erro de autenticação
Solução: Verificar se a Chave Única foi configurada corretamente

Para mais detalhes, consulte DEPLOY_BRAIP.md

================================================================================
LINKS ÚTEIS
================================================================================

Painel Braip: https://ev.braip.com/login
Documentação: Ver INTEGRACAO_BRAIP.md
Deploy: Ver DEPLOY_BRAIP.md

================================================================================
