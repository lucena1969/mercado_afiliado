#!/usr/bin/env php
<?php
/**
 * Script de Teste - Webhook Braip
 * Testa a integração Braip sem necessidade de conta na plataforma
 *
 * USO: php test_braip_webhook.php [URL_WEBHOOK] [TOKEN]
 *
 * Exemplo:
 * php test_braip_webhook.php https://seu-dominio.com/api/webhooks/braip SEU_TOKEN_AQUI
 */

// Cores para output no terminal
class Colors {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color
}

echo Colors::$BLUE . "==========================================\n";
echo "  TESTE DE WEBHOOK BRAIP\n";
echo "==========================================" . Colors::$NC . "\n\n";

// Parâmetros
$webhook_url = $argv[1] ?? null;
$token = $argv[2] ?? null;

if (!$webhook_url || !$token) {
    echo Colors::$RED . "❌ Erro: Parâmetros obrigatórios faltando\n" . Colors::$NC;
    echo "\nUso: php test_braip_webhook.php [URL_BASE] [TOKEN]\n";
    echo "Exemplo: php test_braip_webhook.php https://seu-dominio.com/api/webhooks/braip abc123\n\n";
    exit(1);
}

// Construir URL completa
$url = rtrim($webhook_url, '/') . '/' . $token;

echo "🔗 URL do Webhook: " . Colors::$YELLOW . $url . Colors::$NC . "\n\n";

// Payloads de teste
$test_cases = [
    [
        'name' => '✅ Pagamento Aprovado (Cartão de Crédito)',
        'payload' => [
            'trans_id' => 'TEST_' . uniqid(),
            'trans_status' => 'approved',
            'trans_value' => '297.00',
            'trans_currency' => 'BRL',
            'trans_payment_method' => 'credit_card',
            'trans_installments' => '3',
            'trans_date' => date('Y-m-d H:i:s'),
            'client_name' => 'João da Silva Teste',
            'client_email' => 'joao.teste@exemplo.com',
            'client_document' => '12345678900',
            'client_cel' => '11987654321',
            'prod_id' => 'PROD_TEST_001',
            'prod_name' => 'Curso de Marketing Digital - TESTE',
            'prod_value' => '297.00',
            'commission_percentage' => '40',
            'commission_value' => '118.80',
            'aff_id' => 'AFF_TEST_001',
            'aff_name' => 'Maria Afiliada Teste',
            'aff_email' => 'maria.afiliada@teste.com',
            'utm_source' => 'facebook',
            'utm_campaign' => 'teste_webhook',
            'utm_medium' => 'cpc',
            'utm_content' => 'anuncio_01',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ],
    [
        'name' => '💳 Pagamento via PIX',
        'payload' => [
            'trans_id' => 'TEST_' . uniqid(),
            'trans_status' => 'approved',
            'trans_value' => '147.00',
            'trans_currency' => 'BRL',
            'trans_payment_method' => 'pix',
            'trans_installments' => '1',
            'trans_date' => date('Y-m-d H:i:s'),
            'client_name' => 'Ana Costa Teste',
            'client_email' => 'ana.teste@exemplo.com',
            'client_document' => '98765432100',
            'client_cel' => '21999887766',
            'prod_id' => 'PROD_TEST_002',
            'prod_name' => 'E-book Vendas Online - TESTE',
            'prod_value' => '147.00',
            'commission_percentage' => '50',
            'commission_value' => '73.50',
            'utm_source' => 'instagram',
            'utm_campaign' => 'lancamento_ebook',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ],
    [
        'name' => '❌ Venda Cancelada',
        'payload' => [
            'trans_id' => 'TEST_' . uniqid(),
            'trans_status' => 'cancelada',
            'trans_value' => '197.00',
            'trans_currency' => 'BRL',
            'client_name' => 'Pedro Santos Teste',
            'client_email' => 'pedro.teste@exemplo.com',
            'client_document' => '11122233344',
            'prod_id' => 'PROD_TEST_003',
            'prod_name' => 'Mentoria Individual - TESTE',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ],
    [
        'name' => '💰 Chargeback',
        'payload' => [
            'trans_id' => 'TEST_' . uniqid(),
            'trans_status' => 'chargeback',
            'trans_value' => '397.00',
            'trans_currency' => 'BRL',
            'client_name' => 'Carlos Oliveira Teste',
            'client_email' => 'carlos.teste@exemplo.com',
            'client_document' => '55566677788',
            'prod_id' => 'PROD_TEST_004',
            'prod_name' => 'Curso Avançado - TESTE',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ],
    [
        'name' => '🔄 Assinatura Ativa (Recorrente)',
        'payload' => [
            'subscription_id' => 'SUB_TEST_' . uniqid(),
            'subscription_status' => 'ativa',
            'subscription_plan' => 'monthly',
            'subscription_next_charge' => date('Y-m-d', strtotime('+1 month')),
            'trans_value' => '97.00',
            'trans_currency' => 'BRL',
            'client_name' => 'Fernanda Lima Teste',
            'client_email' => 'fernanda.teste@exemplo.com',
            'client_document' => '99988877766',
            'client_cel' => '31988776655',
            'prod_id' => 'PROD_TEST_005',
            'prod_name' => 'Assinatura Premium - TESTE',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ],
    [
        'name' => '🚫 Assinatura Cancelada',
        'payload' => [
            'subscription_id' => 'SUB_TEST_' . uniqid(),
            'subscription_status' => 'cancelada pelo cliente',
            'subscription_plan' => 'monthly',
            'trans_value' => '97.00',
            'trans_currency' => 'BRL',
            'client_name' => 'Roberto Silva Teste',
            'client_email' => 'roberto.teste@exemplo.com',
            'client_document' => '44455566677',
            'prod_id' => 'PROD_TEST_006',
            'prod_name' => 'Assinatura Premium - TESTE',
            'basic_authentication' => 'test_auth_key_12345'
        ]
    ]
];

// Função para enviar webhook
function sendWebhook($url, $payload, $test_name) {
    echo Colors::$BLUE . "\n📤 Testando: " . Colors::$NC . $test_name . "\n";
    echo str_repeat("-", 50) . "\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: BraipWebhookTest/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Mostrar resultado
    echo "📊 Status HTTP: ";
    if ($http_code >= 200 && $http_code < 300) {
        echo Colors::$GREEN . $http_code . " ✓" . Colors::$NC . "\n";
    } else {
        echo Colors::$RED . $http_code . " ✗" . Colors::$NC . "\n";
    }

    if ($error) {
        echo Colors::$RED . "❌ Erro cURL: " . $error . Colors::$NC . "\n";
    }

    echo "📝 Resposta:\n";
    if ($response) {
        $json = json_decode($response, true);
        if ($json) {
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo $response . "\n";
        }
    } else {
        echo Colors::$YELLOW . "(vazio)" . Colors::$NC . "\n";
    }

    return [
        'http_code' => $http_code,
        'success' => ($http_code >= 200 && $http_code < 300),
        'response' => $response
    ];
}

// Executar todos os testes
$results = [];
foreach ($test_cases as $test) {
    $result = sendWebhook($url, $test['payload'], $test['name']);
    $results[] = [
        'name' => $test['name'],
        'success' => $result['success']
    ];

    // Aguardar 1 segundo entre testes
    sleep(1);
}

// Resumo final
echo "\n" . Colors::$BLUE . "==========================================\n";
echo "  RESUMO DOS TESTES\n";
echo "==========================================" . Colors::$NC . "\n\n";

$total = count($results);
$passed = 0;
$failed = 0;

foreach ($results as $result) {
    if ($result['success']) {
        echo Colors::$GREEN . "✓ " . Colors::$NC;
        $passed++;
    } else {
        echo Colors::$RED . "✗ " . Colors::$NC;
        $failed++;
    }
    echo $result['name'] . "\n";
}

echo "\n";
echo "Total de testes: " . $total . "\n";
echo Colors::$GREEN . "Aprovados: " . $passed . Colors::$NC . "\n";
echo Colors::$RED . "Falhados: " . $failed . Colors::$NC . "\n";

if ($failed === 0) {
    echo "\n" . Colors::$GREEN . "🎉 Todos os testes passaram com sucesso!" . Colors::$NC . "\n\n";
    exit(0);
} else {
    echo "\n" . Colors::$YELLOW . "⚠️  Alguns testes falharam. Verifique os logs acima." . Colors::$NC . "\n\n";
    exit(1);
}
