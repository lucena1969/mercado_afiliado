# Introdução
O Webhook usa o padrão de códigos de resposta **HTTP** para indicar o sucesso ou falha de cada requisição, exceto quando o motivo do erro com um serviço não pôde ser determinado, retornando o status **-1**.

No geral, um código de status pode ser rapidamente identificado por seu primeiro dígito:

- **1xx**: Informativo  
- **2xx**: Sucesso  
- **3xx**: Redirecionamento  
- **4xx**: Erro do cliente  
- **5xx**: Erro de servidor  

---

# Códigos de resposta HTTP
Operações que resultam em um erro que ocorreu por conta do cliente (por exemplo: **token de acesso inválido**) vão retornar um código no intervalo **4xx**, indicando que a requisição está inválida.  
Se você receber um erro **4xx**, recomendamos que leia o nosso glossário de erros para ajudá-lo a solucionar o problema.

Os códigos no intervalo **5xx** sugerem um problema no serviço configurado no webhook. Em caso de dúvidas, procure a pessoa responsável pelo serviço em sua empresa.

## Exemplos de Status
| Status | Descrição |
|--------|-----------|
| 2XX    | Tudo certo. |
| 400    | A requisição enviada está de alguma forma inválida. |
| 500    | Ocorreu algum erro interno não esperado e não foi possível completar a requisição. |

---

# Sugestões de solução
Em caso de respostas de erro, confira algumas dicas do que você pode fazer para solucionar.

| Status | Descrição |
|--------|-----------|
| 400    | Algum parâmetro obrigatório não foi enviado ou é inválido. |
| 401    | O serviço exige autenticação. Verifique se está validando o **hottok** ou outra chave. |
| 404    | A URL configurada no seu serviço não existe. |
| 408    | A aplicação não respondeu dentro do tempo esperado. |
| 5XX    | O evento foi enviado, mas não houve resposta dentro do tempo esperado. |
| -1     | A conexão foi encerrada antes do tempo esperado sem informar o motivo. |

---

# Evento de cancelamento de assinatura
Você receberá dados gerais sobre cancelamento, como informações do assinante, data de cancelamento e mais.  

## Parâmetros
- **hottok (string)**: Token único da conta. Garantia de segurança.  
- **id (string)**: Código único de identificação do evento.  
- **creation_date (long)**: Data de criação do evento (em milissegundos desde 1970-01-01 00:00:00 UTC).  
- **event (integer)**: Nome do evento (`SUBSCRIPTION_CANCELLATION`).  
- **version (integer)**: Versão do evento (sempre `2.0.0`).  
- **data (object)**: Dados do cancelamento, incluindo:  
  - **actual_recurrence_value (double)**: Valor pago na última recorrência.  
  - **cancellation_date (long)**: Data do cancelamento.  
  - **date_next_charge (long)**: Próxima data de cobrança caso haja reativação.  
  - **product (object)**: Dados do produto cancelado.  
  - **subscriber (object)**: Dados do assinante (nome, email, telefone).  
  - **subscription (object)**: Dados da assinatura e do plano.  

## Exemplo de Payload (JSON)
```json
{
  "id": "0d7aa966-b887-4617-8c56-9e865bfc8ce4",
  "creation_date": 1632411406874,
  "event": "SUBSCRIPTION_CANCELLATION",
  "version": "2.0.0",
  "data": {
    "date_next_charge": 1580667200000,
    "product": {
      "name": "Product Name",
      "id": 3526906
    },
    "actual_recurrence_value": 50.10,
    "subscriber": {
      "code": "QO4THU04",
      "name": "Subscriber Name",
      "email": "subscriber@email.com",
      "phone": {
        "dddPhone": "31",
        "phone": "33334444",
        "dddCell": "31",
        "cell": "999999999"
      }
    },
    "subscription": {
      "id": 471681,
      "plan": {
        "name": "Plan Name",
        "id": 460805
      }
    },
    "cancellation_date": 1633410850832
  }
}

Evento de troca de plano
Definição

Evento SWITCH_PLAN que indica a troca de plano de assinatura.

Parâmetros

hottok (string): Token único da conta.

id (string): Código único do evento.

creation_date (long): Data de criação.

event (integer): Nome do evento (SWITCH_PLAN).

version (integer): Versão (2.0.0).

data (object):

switch_plan_date (long): Data da troca.

subscription (object): Dados da assinatura.

plans (array<object>): Lista de planos (com id, name, offer.key, current).

##Exemplo de Payload (JSON)##
{
  "id": "93069d0e-f35b-443e-9146-75b552321a7e",
  "creation_date": 1633003064000,
  "event": "SWITCH_PLAN",
  "version": "2.0.0",
  "data": {
    "switch_plan_date": 1629926054000,
    "subscription": {
      "subscriber_code": "AT3IV3RX",
      "status": "ACTIVE",
      "date_next_charge": 1736337600000,
      "product": {
        "id": 4116023,
        "name": "Product Name"
      },
      "user": {
        "email": "email@hotmart.com"
      }
    },
    "plans": [
      {
        "id": 707635,
        "name": "Plan Test 1",
        "offer": { "key": "py01ycdp" },
        "current": true
      },
      {
        "id": 631288,
        "name": "Plan Test 2",
        "offer": { "key": "2nyk0xc3" },
        "current": false
      }
    ]
  }
}

Evento de abandono de carrinho

Você receberá informações sobre leads que abandonaram o checkout.

Exemplo de Payload (JSON)

{
  "id": "0d7aa966-b887-4617-8c56-9e865bfc8ce4",
  "creation_date": 1632411406874,
  "event": "PURCHASE_OUT_OF_SHOPPING_CART",
  "version": "2.0.0",
  "data": {
    "affiliate": true,
    "product": {
      "id": 3526906,
      "name": "Product Name"
    },    
    "buyer": {
      "name": "Buyer name",
      "email": "buyer@email.com.br",
      "phone": "31999999999"
    },
    "offer": { "code": "n82b9jqz" },
    "checkout_country": {
      "name": "Brasil",
      "iso": "BR"
    }
  }
}

Eventos de pedidos

Você receberá informações sobre compras, como dados do comprador, produto e pagamento.

Os eventos possíveis incluem:
PURCHASE_CANCELED, PURCHASE_COMPLETE, PURCHASE_BILLET_PRINTED, PURCHASE_APPROVED, PURCHASE_PROTEST, PURCHASE_REFUNDED, PURCHASE_CHARGEBACK, PURCHASE_EXPIRED, PURCHASE_DELAYED.

Exemplo de Payload (JSON)

{
  "id": "1234567890123456789",
  "creation_date": 12345678,
  "event": "PURCHASE_APPROVED",
  "version": "2.0.0",
  "data": {
    "product": {
      "id": 213344,
      "ucode": "2e9c43a9-0aeb-48ed-9464-630f845c23af",
      "name": "Product Name",
      "warranty_date": "2017-12-27T00:00:00Z"
    },
    "affiliates": [
      { "affiliate_code": "Q58388177J", "name": "Affiliate name" }
    ],
    "buyer": {
      "email": "buyer@email.com",
      "name": "Buyer Name"
    },
    "producer": {
      "name": "Producer Name",
      "legal_nature": "Pessoa Física",
      "document": "12345678965"
    },
    "purchase": {
      "status": "STARTED",
      "transaction": "HP02316330308193",
      "payment": {
        "type": "PICPAY"
      }
    },
    "subscription": {
      "status": "ACTIVE",
      "plan": {
        "id": 711459,
        "name": "plan name"
      },
      "subscriber": {
        "code": "12133421"
      }
    }
  }
}



