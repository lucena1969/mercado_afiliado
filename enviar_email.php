<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar dados do formulário
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    
    // Validação básica
    if (empty($nome) || empty($email) || empty($mensagem)) {
        header('Location: contato.html?status=erro&msg=campos_obrigatorios');
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: contato.html?status=erro&msg=email_invalido');
        exit;
    }
    
    // Configurações de e-mail
    $para = 'contato@mercadoafiliado.com.br';
    $assunto = 'Novo contato via site - ' . $nome;
    
    // Corpo do e-mail
    $corpo = "Novo contato recebido via site:\n\n";
    $corpo .= "Nome: " . $nome . "\n";
    $corpo .= "E-mail: " . $email . "\n";
    $corpo .= "Mensagem:\n" . $mensagem . "\n\n";
    $corpo .= "Data: " . date('d/m/Y H:i:s') . "\n";
    
    // Cabeçalhos do e-mail
    $cabecalhos = "From: " . $email . "\r\n";
    $cabecalhos .= "Reply-To: " . $email . "\r\n";
    $cabecalhos .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $cabecalhos .= "X-Mailer: PHP/" . phpversion();
    
    // Tentar enviar o e-mail
    if (mail($para, $assunto, $corpo, $cabecalhos)) {
        header('Location: contato.html?status=sucesso');
        exit;
    } else {
        header('Location: contato.html?status=erro&msg=envio_falhou');
        exit;
    }
} else {
    // Se não for POST, redirecionar para o formulário
    header('Location: contato.html');
    exit;
}
?>