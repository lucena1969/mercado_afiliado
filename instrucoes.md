Autenticação de Aplicativo
A API da Hotmart usa o Oauth 2.0 como forma de autenticação e access token para tráfego da autorização do acesso aos nossos recursos.

A API da Hotmart usa o Oauth 2.0 como forma de autenticação e access token para tráfego da autorização do acesso aos nossos recursos. A seguir, serão mostrados os passos necessários para a criação das credenciais de acesso e como gerar o access token tanto para uso do nosso ambiente de produção quanto para o sandbox , nosso ambiente de teste.

Você já sabe mas não custa lembrar, guarde suas credenciais e token de maneira bem segura. A exposição das suas credenciais pode permitir que pessoas indevidas acessem suas informações. Na dúvida se seus dados foram expostos, você pode apagar e gerar novas credenciais sempre que precisar.

Gerar Credenciais
Em nossa plataforma, acesse Ferramentas > Credenciais Developers 
Clique no botão Criar Credencial e dê um nome para sua credencial. Esse nome é apenas para melhor organização das suas credenciais.
Se você for usar essa Credencial para o nosso ambiente de teste sandbox, marque a opção sandbox no campo Tipo. Caso a Credencial seja para o ambiente de produção basta deixar a caixa em branco e clicar no botão Confirmar. Uma vez criada, você não poderá alterar o tipo de uma credencial, devendo criar uma nova com o tipo desejado.
Se tudo correr bem, serão geradas três informações: client_id, client_secret e o token do tipo Basic.
Agora que você tem as credenciais, o próximo passo é obter o seu access_token. Para isso, é necessário realizar a seguinte requisição REST:

Parâmetros da requisição
client_id

Id do cliente gerado na ferramenta de credenciais.

client_secret

Chave gerada na ferramenta de credenciais.

POST
Request (“Authorization” Basic)
CURL
curl --location --request POST 'https://api-sec-vlc.hotmart.com/security/oauth/token?grant_type=client_credentials&client_id=:client_id&client_secret=:client_secret' \
	--header 'Content-Type: application/json' \
	--header 'Authorization: Basic :basic'
Caso a requisição seja feita com sucesso, você receberá o access_token conforme payload abaixo:

Retorno
expires_in

Indica o tempo necessário até que o token expire. Após esse período, toda requisição feita à API da Hotmart com esse mesmo token retornará o código de erro 401 .

Nossa recomendação é que sua aplicação trate esse retorno de erro e refaça a geração do access token. Um ponto de atenção é que apenas o access token expira. As credenciais, Client ID, Client Secret e Basic, seguem as mesmas.

Response
{
  "access_token": "wxyz",
  "token_type": "bearer",
  "expires_in": 172799,
  "scope": "read write",
  "jti": "da2eff63-754d-4v76-9b3a-19bdb5cc8f36"
}