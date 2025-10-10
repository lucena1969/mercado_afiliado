# Mercado Afiliado - Documentação do Projeto

## Visão Geral
Sistema de painel unificado para afiliados que permite centralizar e monitorar campanhas de marketing digital de múltiplas redes de afiliação brasileiras (Hotmart, Monetizze, Eduzz e Braip).

## Arquitetura

### Stack Tecnológica
- **Backend**: PHP 8.x com PDO
- **Frontend**: HTML, CSS, JavaScript vanilla
- **Banco de Dados**: MySQL 8.x
- **Servidor Web**: Apache (XAMPP)

### Estrutura de Diretórios
```
mercado_afiliado/
├── api/                    # Endpoints da API
│   ├── auth.php           # Autenticação
│   └── webhooks.php       # Recebimento de webhooks
├── app/                   # Aplicação principal
│   ├── controllers/       # Controladores MVC
│   ├── models/           # Modelos de dados
│   ├── services/         # Serviços de integração
│   └── utils/            # Utilitários
├── config/               # Configurações
│   ├── app.php          # Configurações gerais
│   └── database.php     # Configuração do banco
├── database/             # Schema e migrações
├── public/              # Assets públicos
├── templates/           # Templates PHP
└── vendor/             # Dependências
```

## Funcionalidades Principais

### 1. Painel Unificado
- Centralização de métricas de vendas de múltiplas redes
- Visualização de CR (taxa de conversão) e receita por período
- Dashboards personalizáveis por usuário

### 2. IntegraSync (Sistema de Integrações)
Conecta com as principais redes de afiliação:
- **Hotmart**: API e webhooks para vendas e produtos
- **Monetizze**: Sincronização de dados de comissões
- **Eduzz**: Integração completa de métricas
- **Braip**: Monitoramento de campanhas

### 3. Link Maestro
- Geração de links de afiliado padronizados
- UTMs consistentes e rastreamento
- Sistema de links curtos
- Registro de cliques

### 4. Alerta Queda
- Monitoramento de performance em tempo real
- Notificações automáticas quando conversões caem
- Alertas via email, WhatsApp e Telegram

### 5. Pixel BR
- Sistema de tracking compatível com LGPD
- Coleta de eventos no domínio do usuário
- Preparado para auditorias de privacidade

### 6. CAPI Bridge
- Envio de eventos server-side
- Otimização para campanhas de Facebook Ads
- Melhor atribuição de conversões

## Modelos de Dados

### Principais Tabelas

#### users
- Cadastro e autenticação de usuários
- Informações de planos e assinaturas

#### integrations
- Configurações de integração por plataforma
- Status e tokens de API
- Última sincronização

#### products
- Produtos sincronizados das redes
- Metadados e informações de comissão

#### sales
- Vendas consolidadas de todas as redes
- UTMs e dados de atribuição
- Status de pagamento e reembolsos

#### sync_logs
- Logs de sincronização
- Controle de erros e performance

#### webhook_events
- Eventos recebidos em tempo real
- Processamento assíncrono

## Planos de Assinatura

### Starter (R$ 79/mês)
- 2 integrações
- Painel básico
- Alertas por email

### Pro (R$ 149/mês)
- 4 integrações
- Link Maestro avançado
- Pixel BR
- Alertas WhatsApp/Telegram

### Scale (R$ 299/mês)
- Integrações ilimitadas
- CAPI Bridge
- Gestão de equipe
- Suporte prioritário

## Configuração

### Variáveis de Ambiente
```php
// config/app.php
define('APP_NAME', 'Mercado Afiliado');
define('BASE_URL', 'http://localhost/mercado_afiliado/public');
define('MP_ACCESS_TOKEN', ''); // MercadoPago
define('MP_PUBLIC_KEY', '');
```

### Banco de Dados
```php
// config/database.php
private $host = 'localhost';
private $db_name = 'mercado_afiliado';
private $username = 'root';
private $password = '';
```

## APIs e Webhooks

### Endpoints Principais
- `POST /api/auth.php` - Autenticação
- `POST /api/webhooks.php` - Recebimento de webhooks
- Dashboard em `/templates/dashboard/index.php`

### Webhooks Suportados
- Hotmart: `PURCHASE_COMPLETE`, `PURCHASE_REFUNDED`
- Monetizze: `sale_approved`, `sale_refunded`
- Eduzz: `sale_completed`, `sale_cancelled`
- Braip: `purchase_approved`, `purchase_refunded`

## Segurança

### Medidas Implementadas
- Headers de segurança (XSS, CSRF, Clickjacking)
- PDO com prepared statements
- Validação de tokens de webhook
- Sessões seguras com timeout

### Conformidade LGPD
- Pixel de tracking próprio
- Auditoria de coleta de dados
- Consentimento explícito
- Direito ao esquecimento

## Próximos Passos
1. Implementação de testes automatizados
2. Cache Redis para performance
3. Monitoramento APM
4. API REST completa
5. Mobile app (React Native)

## Contato Técnico
Para dúvidas sobre integração ou desenvolvimento, consulte a documentação específica de cada serviço na pasta `/app/services/`.