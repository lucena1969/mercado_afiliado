<?php
/**
 * Importação de Contratos via CSV
 * Alternativa simples sem dependências externas
 */

session_start();
require_once 'config.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Apenas administradores
if ($_SESSION['nivel_acesso'] > 1) {
    die('Acesso negado. Apenas coordenadores podem importar dados.');
}

// Processar importação
$mensagem = '';
$tipo_mensagem = '';
$stats = ['total' => 0, 'importados' => 0, 'duplicados' => 0, 'erros' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_importacao'])) {
    $arquivo_csv = 'relatorios/contratos_vigentes_2025.csv';

    if (!file_exists($arquivo_csv)) {
        $tipo_mensagem = 'error';
        $mensagem = "Arquivo CSV não encontrado. Execute primeiro: python3 converter_excel_para_csv.py";
    } else {
        try {
            $handle = fopen($arquivo_csv, 'r');

            if ($handle === false) {
                throw new Exception("Erro ao abrir arquivo CSV");
            }

            // Pular cabeçalho
            $header = fgetcsv($handle, 0, ',');

            $linha_num = 1;
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $linha_num++;
                $stats['total']++;

                try {
                    $resultado = importarContratoCSV($row, $conn, $_SESSION['user_id']);

                    if ($resultado === 'importado') {
                        $stats['importados']++;
                    } elseif ($resultado === 'duplicado') {
                        $stats['duplicados']++;
                    }
                } catch (Exception $e) {
                    $stats['erros']++;
                    error_log("Erro na linha $linha_num: " . $e->getMessage());
                }
            }

            fclose($handle);

            $tipo_mensagem = 'success';
            $mensagem = sprintf(
                "Importação concluída! Total: %d | Importados: %d | Duplicados: %d | Erros: %d",
                $stats['total'],
                $stats['importados'],
                $stats['duplicados'],
                $stats['erros']
            );

        } catch (Exception $e) {
            $tipo_mensagem = 'error';
            $mensagem = "Erro na importação: " . $e->getMessage();
        }
    }
}

/**
 * Importar um contrato do CSV
 */
function importarContratoCSV($row, $conn, $user_id) {
    // Mapeamento das colunas
    $dados = [
        'ano' => limparNumero($row[0] ?? null),
        'numero_contrato' => limparTexto($row[1] ?? null),
        'contratado_nome' => limparTexto($row[2] ?? null),
        'contratado_cnpj_cpf' => limparCNPJ($row[3] ?? null),
        'sei_processo' => limparTexto($row[4] ?? null),
        'objeto' => limparTexto($row[5] ?? null),
        'modalidade' => limparTexto($row[6] ?? null),
        'numero_modalidade' => limparTexto($row[7] ?? null),
        'valor_2020' => limparValor($row[8] ?? null),
        'valor_2021' => limparValor($row[9] ?? null),
        'valor_2022' => limparValor($row[10] ?? null),
        'valor_2023' => limparValor($row[11] ?? null),
        'valor_2025' => limparValor($row[12] ?? null),
        'valor_inicial' => limparValor($row[13] ?? null),
        'valor_atual' => limparValor($row[14] ?? null),
        'data_inicio' => limparData($row[15] ?? null),
        'data_fim' => limparData($row[16] ?? null),
        'data_assinatura' => limparData($row[17] ?? null),
        'area_gestora' => limparTexto($row[18] ?? null),
        'finalidade' => limparTexto($row[19] ?? null),
        'portaria_fiscalizacao' => limparTexto($row[20] ?? null),
        'fiscais_texto' => limparTexto($row[21] ?? null),
        'garantia_info' => limparTexto($row[22] ?? null),
        'mao_obra' => converterBoolean($row[23] ?? null),
        'base_legal' => limparTexto($row[24] ?? null),
        'link_sharepoint' => limparTexto($row[25] ?? null),
        'alerta_sei' => limparTexto($row[26] ?? null),
        'portaria_aprovacao' => limparTexto($row[27] ?? null),
        'situacao_atual' => limparTexto($row[28] ?? null),
        'dfd' => limparTexto($row[29] ?? null),
    ];

    // Validações
    if (empty($dados['sei_processo'])) {
        throw new Exception("SEI vazio");
    }

    // Verificar duplicidade
    $stmt = $conn->prepare("SELECT id FROM contratacoes WHERE sei_processo = ?");
    $stmt->bind_param("s", $dados['sei_processo']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return 'duplicado';
    }

    // Determinar tipo
    $contratado_tipo = (strlen($dados['contratado_cnpj_cpf']) == 14) ? 'PJ' : 'PF';

    // Inserir
    $sql = "INSERT INTO contratacoes (
        tipo_contratacao, status_contratacao, ano_contrato, numero_contrato, sei_processo,
        pca_dfd, numero_modalidade, modalidade, contratado_nome, contratado_cnpj_cpf,
        contratado_tipo, objeto, valor_inicial, valor_atual, valor_2020, valor_2021,
        valor_2022, valor_2023, valor_2025, data_assinatura, data_inicio_vigencia,
        data_fim_vigencia, area_gestora, finalidade, portaria_fiscalizacao, fiscais_texto,
        garantia_info, possui_mao_obra, base_legal_prorrogacao, alerta_vigencia_sei,
        link_sharepoint, portaria_aprovacao, situacao_atual, criado_por
    ) VALUES (
        'CONTRATO_TRADICIONAL', 'VIGENTE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssssssdddddddssssssssisssssi",
        $dados['ano'], $dados['numero_contrato'], $dados['sei_processo'], $dados['dfd'],
        $dados['numero_modalidade'], $dados['modalidade'], $dados['contratado_nome'],
        $dados['contratado_cnpj_cpf'], $contratado_tipo, $dados['objeto'],
        $dados['valor_inicial'], $dados['valor_atual'], $dados['valor_2020'],
        $dados['valor_2021'], $dados['valor_2022'], $dados['valor_2023'], $dados['valor_2025'],
        $dados['data_assinatura'], $dados['data_inicio'], $dados['data_fim'],
        $dados['area_gestora'], $dados['finalidade'], $dados['portaria_fiscalizacao'],
        $dados['fiscais_texto'], $dados['garantia_info'], $dados['mao_obra'],
        $dados['base_legal'], $dados['alerta_sei'], $dados['link_sharepoint'],
        $dados['portaria_aprovacao'], $dados['situacao_atual'], $user_id
    );

    return $stmt->execute() ? 'importado' : 'erro';
}

// Funções auxiliares
function limparTexto($v) { return empty($v) ? null : trim($v); }
function limparNumero($v) { return empty($v) ? null : (int)preg_replace('/[^0-9]/', '', $v); }
function limparValor($v) { return empty($v) ? null : (float)str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $v)); }
function limparCNPJ($v) { return empty($v) ? null : preg_replace('/[^0-9]/', '', $v); }
function limparData($v) {
    if (empty($v)) return null;
    try {
        return (new DateTime($v))->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}
function converterBoolean($v) {
    return in_array(strtolower(trim($v)), ['sim', 'yes', 's', 'y', '1', 'true']) ? 1 : 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Contratos (CSV) - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .card { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-info { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; color: white; }
        .code-block { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat { background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #3498db; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2><i data-lucide="file-spreadsheet"></i> Importar Contratos (via CSV)</h2>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                    <?php if ($tipo_mensagem === 'success'): ?>
                        <div class="stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value" style="color: #2ecc71;"><?php echo $stats['importados']; ?></div>
                                <div class="stat-label">Importados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value" style="color: #f39c12;"><?php echo $stats['duplicados']; ?></div>
                                <div class="stat-label">Duplicados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value" style="color: #e74c3c;"><?php echo $stats['erros']; ?></div>
                                <div class="stat-label">Erros</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <strong>📋 Processo de Importação:</strong>
                <ol>
                    <li>Converter planilha Excel para CSV usando Python</li>
                    <li>Importar o CSV gerado para o banco de dados</li>
                </ol>
            </div>

            <h3>Passo 1: Converter Excel para CSV</h3>
            <p>Execute este comando no terminal:</p>
            <div class="code-block">python3 converter_excel_para_csv.py</div>

            <h3>Passo 2: Importar CSV</h3>
            <form method="POST" onsubmit="return confirm('Deseja realmente importar os contratos?\n\nEsta operação pode demorar alguns minutos.');">
                <div class="alert alert-warning">
                    <strong>⚠️ ATENÇÃO:</strong>
                    <ul>
                        <li>Certifique-se de ter executado o Passo 1 primeiro</li>
                        <li>Contratos duplicados (mesmo SEI) serão ignorados</li>
                        <li>Recomenda-se fazer backup do banco antes</li>
                    </ul>
                </div>

                <button type="submit" name="confirmar_importacao" class="btn btn-primary">
                    <i data-lucide="upload"></i> Iniciar Importação
                </button>
                <a href="selecao_modulos.php" class="btn btn-secondary">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
