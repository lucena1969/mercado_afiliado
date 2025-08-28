<?php
/**
 * Página de Teste do Pixel BR
 * Para testar o sistema sem precisar de login
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Pixel BR - Mercado Afiliado</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
            line-height: 1.6;
        }
        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 0.5rem 0.5rem 0.5rem 0;
            font-size: 0.9rem;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-success {
            background: #10b981;
        }
        .btn-warning {
            background: #f59e0b;
        }
        .btn-danger {
            background: #ef4444;
        }
        .log {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.8rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin: 0.5rem 0;
            font-weight: 600;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .form-group {
            margin: 1rem 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
    </style>
    
    <!-- Pixel BR - Configuração de teste -->
    <script>
        window.PIXELBR_COLLECTOR_URL = "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/mercado_afiliado/api/pixel/collect_simple.php";
    </script>
    <script src="/mercado_afiliado/public/assets/js/pixel/pixel_br.js?user_id=999&integration_id=1&debug=true"></script>
</head>
<body>
    <h1>🧪 Teste do Pixel BR</h1>
    <p>Esta página permite testar todas as funcionalidades do sistema Pixel BR.</p>

    <div class="card">
        <h3>📊 Status do Sistema</h3>
        <div id="system-status">
            <div class="status" id="pixel-status">⏳ Verificando pixel...</div>
            <div class="status" id="collector-status">⏳ Verificando coletor...</div>
            <div class="status" id="database-status">⏳ Verificando banco...</div>
        </div>
    </div>

    <div class="card">
        <h3>🎯 Eventos de Teste</h3>
        <p>Clique nos botões para disparar eventos e verificar o funcionamento:</p>
        
        <button class="btn" onclick="testPageView()">📄 Page View</button>
        <button class="btn btn-success" onclick="testLead()">🎯 Lead</button>
        <button class="btn btn-warning" onclick="testPurchase()">💰 Purchase</button>
        <button class="btn" onclick="testCustomEvent()">⚡ Custom Event</button>
        
        <h4>🧑‍💼 Lead com dados do usuário:</h4>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" id="test-email" value="teste@exemplo.com">
        </div>
        <div class="form-group">
            <label>Telefone:</label>
            <input type="tel" id="test-phone" value="+5511999999999">
        </div>
        <button class="btn btn-success" onclick="testLeadWithData()">🎯 Lead com Dados</button>
        
        <h4>💳 Purchase com valor:</h4>
        <div class="form-group">
            <label>Valor (R$):</label>
            <input type="number" id="test-value" value="197.00" step="0.01">
        </div>
        <div class="form-group">
            <label>ID do Pedido:</label>
            <input type="text" id="test-order-id" value="TEST-001">
        </div>
        <button class="btn btn-warning" onclick="testPurchaseWithData()">💰 Purchase com Dados</button>
    </div>

    <div class="card">
        <h3>🔒 Teste de Consentimento LGPD</h3>
        <button class="btn btn-success" onclick="grantConsent()">✅ Conceder Consentimento</button>
        <button class="btn btn-danger" onclick="denyConsent()">❌ Negar Consentimento</button>
        <button class="btn" onclick="checkConsent()">🔍 Verificar Status</button>
    </div>

    <div class="card">
        <h3>📡 Teste do Coletor API</h3>
        <button class="btn" onclick="testCollectorDirectly()">🧪 Testar API Diretamente</button>
        <button class="btn" onclick="checkEventHistory()">📋 Ver Eventos Salvos</button>
    </div>

    <div class="card">
        <h3>📝 Log de Atividades</h3>
        <button class="btn btn-danger" onclick="clearLog()">🗑️ Limpar Log</button>
        <div id="activity-log" class="log">
            <div>Sistema iniciado. Aguardando atividade...</div>
        </div>
    </div>

    <script>
        // Sobrescrever console.log para mostrar na página
        const originalLog = console.log;
        const originalError = console.error;
        
        function addToLog(message, type = 'info') {
            const log = document.getElementById('activity-log');
            const time = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'black';
            log.innerHTML += `<div style="color: ${color}">[${time}] ${message}</div>`;
            log.scrollTop = log.scrollHeight;
        }
        
        console.log = function(...args) {
            addToLog(args.join(' '));
            originalLog.apply(console, args);
        };
        
        console.error = function(...args) {
            addToLog(args.join(' '), 'error');
            originalError.apply(console, args);
        };

        // Verificar status do sistema
        function checkSystemStatus() {
            // Verificar se o pixel carregou
            if (typeof PixelBR !== 'undefined') {
                document.getElementById('pixel-status').innerHTML = '✅ Pixel carregado';
                document.getElementById('pixel-status').className = 'status success';
                addToLog('✅ Pixel BR carregado com sucesso', 'success');
            } else {
                document.getElementById('pixel-status').innerHTML = '❌ Pixel não carregado';
                document.getElementById('pixel-status').className = 'status error';
                addToLog('❌ Erro: Pixel BR não foi carregado', 'error');
            }
            
            // Testar coletor
            testCollectorConnection();
        }
        
        function testCollectorConnection() {
            fetch(window.PIXELBR_COLLECTOR_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_name: 'test_connection',
                    event_time: Math.floor(Date.now() / 1000),
                    event_id: 'test_' + Date.now(),
                    source_url: window.location.href,
                    consent: 'granted'
                })
            })
            .then(response => {
                if (response.ok) {
                    document.getElementById('collector-status').innerHTML = '✅ Coletor funcionando';
                    document.getElementById('collector-status').className = 'status success';
                    addToLog('✅ Coletor API respondeu corretamente', 'success');
                    return response.json();
                } else {
                    throw new Error('HTTP ' + response.status);
                }
            })
            .then(data => {
                addToLog('✅ Resposta do coletor: ' + JSON.stringify(data), 'success');
                document.getElementById('database-status').innerHTML = '✅ Banco de dados OK';
                document.getElementById('database-status').className = 'status success';
            })
            .catch(error => {
                document.getElementById('collector-status').innerHTML = '❌ Erro no coletor: ' + error.message;
                document.getElementById('collector-status').className = 'status error';
                addToLog('❌ Erro no coletor: ' + error.message, 'error');
            });
        }

        // Funções de teste de eventos
        function testPageView() {
            addToLog('🧪 Testando Page View...');
            PixelBR.track('page_view', { custom_data: { test: true, page: 'test_page' } });
        }
        
        function testLead() {
            addToLog('🧪 Testando Lead básico...');
            PixelBR.trackLead({ 
                custom_data: { 
                    test: true, 
                    source: 'test_button' 
                } 
            });
        }
        
        function testLeadWithData() {
            const email = document.getElementById('test-email').value;
            const phone = document.getElementById('test-phone').value;
            
            addToLog(`🧪 Testando Lead com dados: ${email}, ${phone}`);
            PixelBR.trackLead({
                email: email,
                phone: phone,
                custom_data: { 
                    test: true, 
                    source: 'test_form' 
                }
            });
        }
        
        function testPurchase() {
            addToLog('🧪 Testando Purchase básico...');
            PixelBR.trackPurchase({
                value: 99.90,
                currency: 'BRL',
                order_id: 'TEST_' + Date.now(),
                custom_data: { test: true }
            });
        }
        
        function testPurchaseWithData() {
            const value = parseFloat(document.getElementById('test-value').value);
            const orderId = document.getElementById('test-order-id').value;
            const email = document.getElementById('test-email').value;
            
            addToLog(`🧪 Testando Purchase com dados: R$ ${value}, ${orderId}`);
            PixelBR.trackPurchase({
                value: value,
                currency: 'BRL',
                order_id: orderId,
                email: email,
                product_name: 'Produto de Teste',
                payment_method: 'credit_card'
            });
        }
        
        function testCustomEvent() {
            addToLog('🧪 Testando Custom Event...');
            PixelBR.track('custom', {
                custom_data: {
                    event_type: 'button_click',
                    button_name: 'test_custom',
                    test: true,
                    timestamp: Date.now()
                }
            });
        }

        // Funções de consentimento
        function grantConsent() {
            PixelBR.consentGrant();
            addToLog('✅ Consentimento concedido', 'success');
        }
        
        function denyConsent() {
            PixelBR.consentDeny();
            addToLog('❌ Consentimento negado');
        }
        
        function checkConsent() {
            const consent = localStorage.getItem('pixelbr_consent') || 'granted';
            addToLog(`🔍 Status do consentimento: ${consent}`);
        }

        // Funções de teste direto
        function testCollectorDirectly() {
            addToLog('🧪 Testando coletor diretamente...');
            
            const testPayload = {
                event_name: 'test_direct',
                event_time: Math.floor(Date.now() / 1000),
                event_id: 'direct_test_' + Date.now(),
                user_id: 999,
                integration_id: 1,
                source_url: window.location.href,
                utm: {
                    source: 'test',
                    medium: 'direct',
                    campaign: 'pixel_test'
                },
                custom_data: {
                    test_type: 'direct_api',
                    timestamp: Date.now()
                },
                consent: 'granted'
            };
            
            fetch(window.PIXELBR_COLLECTOR_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(testPayload)
            })
            .then(response => response.json())
            .then(data => {
                addToLog('✅ Resposta do teste direto: ' + JSON.stringify(data), 'success');
            })
            .catch(error => {
                addToLog('❌ Erro no teste direto: ' + error.message, 'error');
            });
        }
        
        function checkEventHistory() {
            addToLog('📋 Verificando eventos salvos no localStorage...');
            const queue = localStorage.getItem('pixelbr_queue');
            if (queue) {
                const events = JSON.parse(queue);
                addToLog(`📊 ${events.length} eventos na fila local: ${JSON.stringify(events, null, 2)}`);
            } else {
                addToLog('📊 Nenhum evento na fila local');
            }
        }
        
        function clearLog() {
            document.getElementById('activity-log').innerHTML = '<div>Log limpo.</div>';
        }

        // Inicializar testes
        window.addEventListener('load', function() {
            addToLog('🚀 Página de teste carregada');
            setTimeout(checkSystemStatus, 1000);
        });
        
        // Interceptar eventos do PixelBR para log
        if (typeof PixelBR !== 'undefined') {
            const originalTrack = PixelBR.track;
            PixelBR.track = function(eventName, props) {
                addToLog(`📤 Enviando evento: ${eventName} - ${JSON.stringify(props || {})}`, 'success');
                return originalTrack.call(this, eventName, props);
            };
        }
    </script>
</body>
</html>