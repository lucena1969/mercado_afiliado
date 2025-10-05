Webhook
O Webhook da Eduzz permite que você receba requisições HTTP em sua aplicação sempre que um novo evento acontecer em sua conta. Você pode configurar uma integração para receber diversos eventos de diversas aplicações diferentes da Eduzz.

Eventos e payloads
Formato padrão do JSON:

{
  "id": "z154l2pvk6jltotg0xy86glx" // id do evento,
  "event": "nutror.lesson_started" // nome do evento,
  "data": {}, // dados do evento,
  "sentDate": "2023-08-31T18:34:23.023Z" // data do evento
}
Autenticação de Usuário
A autenticação da API Eduzz é feita via OAuth2, um protocolo padrão na indústria que simplifica o uso para os desenvolvedores. Esta especificação inclui alguns endpoints-chave para autenticação:

URL de Autorização: https://accounts.eduzz.com/oauth/authorize
URL de Geração de Chave de Acesso: https://accounts-api.eduzz.com/oauth/token
Endereço Base da API: https://api.eduzz.com
Para autenticar um usuário, direcione primeiro o cliente para a página de autorização e assegure-se de incluir a URL de retorno que é descrita na documentação do endpoint, no parâmetro redirectTo.

A URL de retorno que é informada no parâmetro redirectTo deve estar registrada na tela de gerenciamento de aplicativos no Console para funcionar, caso contrário, um erro será exibido.

Exemplo de URL de autenticação:

https://accounts.eduzz.com/oauth/authorize?client_id=ID_DO_SEU_APLICATIVO&responseType=code&redirectTo=https://myappurl.com.br/oauth2

Ao acessar a URL de autenticação, será exibida uma tela onde o cliente do seu aplicativo deve realizar o login com a Eduzz para permitir que o seu aplicativo tenha acesso aos dados da conta.

Após o usuário realizar o login, será solicitado o acesso aos dados necessários para executar o aplicativo, ou seja, serão exibidas quais permissões foram requisitadas na tela de cadastro do aplicativo, na seção de permissões na DevHub.

Após a autorização, o usuário será redirecionado para a URL de retorno (redirectTo) juntamente com o código de acesso:

Caso a URL não exista, será exibido o erro **This site cant be reached** no navegador. No entanto, o código de acesso (code) será gerado com sucesso.

O código de acesso fornecido poderá então ser utilizado para acessar a API pública utilizando o endpoint para obter token do usuário. Veja um exemplo em JavaScript de como a autenticação é feita:

const options = {
	method: 'POST',
	headers: { accept: 'application/json' },
	body: JSON.stringify({
        "client_id": "22edfacb-9abd-4dfd-a9be-31a18266aeef",
        "client_secret": "ebf0cc8f59be0570952782d95dacb486188a9e7b2c9dea0f38a0b9aa97a8cfeb",
        "code": "xruWGHBwDdlR2JHjv3HWwUOf61FBODcVADsioMkdz2g",
        "redirect_uri": "https://h3llow0rld.com.br/callback",
        "grant_type": "authorization_code"
    })
};

fetch('https://accounts-api.eduzz.com/oauth/token', options)
	.then(response => response.json())
	.then(response => console.log(response))
	.catch(err => console.error(err))

Ao executar esse código, a seguinte resposta será exibida no seu terminal:

{
  "refresh_token": null,
  "id": "16305ca0-e8c1-4eb9-81ec-2c1edeb17200",
  "authenticated_userid": "0b19bf3d-b1f6-47cf-8a84-9fb22a9ae63b ,98239281",
  "credential": {
    "id": "71674a7e-3e45-4d14-8dbc-d6c35682f4c0"
  },
  "access_token": "qzrUZcm4dISz/ayXgq7g9+GmusYbXHmIpJ7fbLYDIjUPtNwAN1rrsRZbeJ6e6tAlSUSy3w==",
  "expires_in": 0,
  "scope": "webhook_read webhook_write",
  "ttl": null,
  "created_at": 1709822571,
  "service": null,
  "token_type": "bearer",
  "user": {
    "id": "0b19bf3d-b1f6-47cf-8a84-9fb22a9ae63b ",
    "eduzzId": 98239281,
    "nutrorId": 837462,
    "eduzz_id": 98239281,
    "nutror_id": 837462,
    "name": "QA Eduzz",
    "email": "qa@eduzz.com",
    "picture": "//cdn.eduzzcdn.com/myeduzz/upload/72/a0/72a099bb921a4277917448d6e38c92c0"
  }
}
Salve os dados do usuário, incluindo o valor do campo "access_token", que será usado para autenticar seu usuário na nossa API Pública. Para mais informações sobre os campos retornados, consulte o endpoint para obter token do usuário.

A partir desse momento, será possível utilizar o token de acesso para acessar nossos endpoints.

Testar o acesso
Para testar se a autenticação occoreu sem erros recomendamos utilizar o endpoint que retorna quais os dados da conta que realizou a autenticação, ou seja, a qual conta aquele token pertence:

const options = {
	method: 'GET',
	headers: { 
        accept: 'application/json' 
        // Este token é o mesmo gerado no passo anterior. Não esqueça do bearer
        authorization: 'bearer qzrUZcm4dISz/ayXgq7g9+GmusYbXHmIpJ7fbLYDIjUPtNwAN1rrsRZbeJ6e6tAlSUSy3w=='
    }
};

fetch('https://api.eduzz.com/accounts/v1/me', options)
    .then(response => response.json())
    .then(response => console.log(response))
    .catch(err => console.error(err))
O resultado será então os dados do usuário que fez o login e permitiu o acesso em sua conta:

{
    "id": "00000000-0000-0000-0000-000000000000",
    "name": "Fulano da Silva",
    "email": "fulanodasilva@gmail.com"
}

Padrões de respostas
Alguns status HTTP mais utilizados nas respostas da nossa API:

Status	Descrição
200	Requisição bem sucedida.
201	Requisição bem sucedida.
204	Requisição bem sucedida, porém sem conteúdo.
400	Requisição mal formada.
401	Requisição não autorizada.
403	Requisição não permitida.
404	Requisição não encontrada.
405	Método não permitido.
409	Conflito de dados.
422	Dados inválidos.
500	Erro interno do servidor.
503	Serviço indisponível.
Item único:

// com resultados
{
  "id": "1",
  "name": "Fulano da Silva",
  "birthdate": "1995-12-01"
}
// sem resultados
404 - Recurso não encontrado
Lista sem paginação:

// com resultados
{
  "items": [
    {
      "id": "1",
      "name": "Fulano da Silva",
      "birthdate": "1995-12-01"
    },
    {
      "id": "2",
      "name": "Ciclano Beltrano",
      "birthdate": "1989-08-13"
    }
  ]
}
// sem resultados
{
  "items": []
}
Lista com paginação:

// com resultados
// /v1/items?page=1&itemsPerPage=10
{
  "pages": 100,
  "page": 1,
  "itemsPerPage": 10,
  "totalItems": 101,
  "items": [
    {
      "id": "1",
      "name": "Fulano da Silva",
      "birthdate": "1995-12-01"
    },
    {
      "id": "2",
      "name": "Ciclano Beltrano",
      "birthdate": "1989-08-13"
    }
  ]
}
// sem resultados
{
  "pages": 1,
  "page": 1,
  "itemsPerPage": 10,
  "totalItems": 0,
  "items": []
}
Erro genérico:

{
  "message": "Some error",
  "code": "ERR_XPTO",
  "link": "https://documentacao-api.eduzz.com/suporte?error=ERR_XPTO",
  "debug": {}
}
Erro de validação:

{
  "message": "Saldo insuficiente para transferência",
  "code": "TRF_001",
  "link": "https://documentacao-api.eduzz.com/suporte?error=TRF_001",
  "errors": [
    {
        "field": "amount",
        "message": "saldo insuficiente",
        "data": {
          "requested": 12000.00,
          "available": 10500.00
        }
    }
  ],
  "debug": {}
}
Dados do Usuário
Retorna dados do usuário logado.

GET
https://api.eduzz.com/accounts/v1/me
Response params (200)
idstring
Id do usuário

namestring
Nome do usuário

emailstring
Email do usuário

Status codes
Status	Descrição
200	Success

Exemplos
Shell
JavaScript
const options = {
	method: 'GET',
	headers: {
        'Accept': 'application/json'
    }
};

fetch('https://api.eduzz.com/accounts/v1/me', options)
	.then(response => response.json())
	.then(response => console.log(response))
	.catch(err => console.error(err))
Response
{
  "id": "dcc44cb7-30c8-46ef-b35b-ffacf8cc61d1",
  "name": "Fulano da Silva",
  "email": "fulano.silva@example.com"
}
Obter token de Usuário
Gera uma chave de acesso para o aplicativo utilizando o código de login fornecido ao autenticar o usuário.

POST
https://accounts-api.eduzz.com/oauth/token
Atenção!
Essa documentação tem como fim utilizar o código de acesso gerado na autenticação do usuário para obter um token de acesso a nossa API, para conseguir o código de acesso (code), primeiro autentique seu usuário.

Utilize esse endpoint para gerar uma chave de acesso a nossa API, após informados os dados do usuário e a request retornar sucesso, a chave de acesso será exibida no campo access_token. Essa chave será utilizada para realizar requsisições http para a API Pública da Eduzz.

Query params
client_idRequiredstring
ID da aplicação

client_secretRequiredstring
Secret da aplicação

codeRequiredstring
Código provido ao redirecionar após a autenticação

redirect_uriRequiredstring
URL de redirecionamento após autenticação

grant_typeRequiredenum
Tipo de transmissão de token (neste caso, authorization_code)

authorization_code
Response params (200)
refresh_tokenstring
Token de atualização (não utilizado)

idstring
Id do token

authenticated_useridstring
Id do usuário autenticado no formato (accountsId,eduzzId)

credentialobject
Credencial do usuário

credential.idstring
Id da credencial

access_tokenstring
Token de acesso

expires_innumber
Tempo de expiração do token

scopestring
Escopos requeridos pelo aplicativo

ttlstring
Tempo de vida do token

created_atnumber
Data de criação do token

servicestring
Serviço

token_typestring
Tipo do token

userobject
Informações do usuário

user.idstring
Id do usuário

user.eduzzIdnumber
Id do usuário na Eduzz

user.nutrorIdnumber
Id do usuário na Nutror

user.eduzz_idnumber
Id do usuário na Eduzz

user.nutror_idnumber
Id do usuário na Nutror

user.namestring
Nome do usuário

user.emailstring
Email do usuário

Status codes
Status	Descrição
200	Success
404	Token não encontrado
404	Aplicação não encontrada
404	Token já utilizado

const options = {
	method: 'POST',
	headers: {
        'Accept': 'application/json'
    },
	body: JSON.stringify({
      "client_id": "5187f574-8604-4f0f-9fda-b2132631a1ac ",
      "client_secret": "kx1q33rfotl2pmny35ga9knsmtv2f4uawokoy0617ia6sysa8o",
      "code": "rpdeb9of72u06usbw4jq02mw5",
      "redirect_uri": "https://app.com/callback",
      "grant_type": "authorization_code"
})
};

fetch('https://accounts-api.eduzz.com/oauth/token', options)
	.then(response => response.json())
	.then(response => console.log(response))
	.catch(err => console.error(err))

response
{
  "refresh_token": null,
  "id": "16305ca0-e8c1-4eb9-81ec-2c1edeb17200",
  "authenticated_userid": "0b19bf3d-b1f6-47cf-8a84-9fb22a9ae63b ,98239281",
  "credential": {
    "id": "71674a7e-3e45-4d14-8dbc-d6c35682f4c0"
  },
  "access_token": "qzrUZcm4dISz/ayXgq7g9+GmusYbXHmIpJ7fbLYDIjUPtNwAN1rrsRZbeJ6e6tAlSUSy3w==",
  "expires_in": 0,
  "scope": "webhook_read webhook_write",
  "ttl": null,
  "created_at": 1709822571,
  "service": null,
  "token_type": "bearer",
  "user": {
    "id": "0b19bf3d-b1f6-47cf-8a84-9fb22a9ae63b ",
    "eduzzId": 98239281,
    "nutrorId": 837462,
    "eduzz_id": 98239281,
    "nutror_id": 837462,
    "name": "QA Eduzz",
    "email": "qa@eduzz.com",
    "picture": "//cdn.eduzzcdn.com/myeduzz/upload/72/a0/72a099bb921a4277917448d6e38c92c0"
  }
}