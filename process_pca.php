<?php
/**
 * Processador de Ações do Módulo Planejamento (PCA)
 *
 * Este arquivo contém todas as ações relacionadas ao PCA:
 * - Importação de CSV/Excel
 * - Edição de contratações
 * - Reversão de importações
 * - Exportações e relatórios
 */

// Desabilitar exibição de erros para não contaminar JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once 'config.php';
require_once 'functions.php';

// Configurar e iniciar sessão
configurarSessaoSegura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$acao = $_POST['acao'] ?? $_POST['action'] ?? '';
$pdo = conectarDB();

switch ($acao) {

    case 'importar_pca':
        verificarLogin();

        // Verificar permissão (apenas DIPLAN pode importar PCA)
        if (!temPermissao('pca_importar')) {
            setMensagem('Você não tem permissão para importar PCA. Apenas usuários DIPLAN podem importar.', 'erro');
            header('Location: dashboard.php');
            exit;
        }

        try {
            // Validar ano
            $ano_pca = intval($_POST['ano_pca'] ?? date('Y'));

            // Verificar se arquivo foi enviado
            if (!isset($_FILES['arquivo_pca']) || $_FILES['arquivo_pca']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo foi enviado ou houve erro no upload.');
            }

            $arquivo = $_FILES['arquivo_pca'];
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            // Validar extensão
            if (!in_array($extensao, ['csv', 'xls', 'xlsx'])) {
                throw new Exception('Formato de arquivo não suportado. Use CSV, XLS ou XLSX.');
            }

            // Criar diretório de uploads se não existir
            $upload_dir = __DIR__ . '/uploads';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Mover arquivo para diretório temporário
            $temp_file = $upload_dir . '/pca_import_' . time() . '.' . $extensao;
            if (!move_uploaded_file($arquivo['tmp_name'], $temp_file)) {
                throw new Exception('Erro ao salvar arquivo temporário.');
            }

            // Processar arquivo baseado na extensão
            $dados_importados = [];

            if ($extensao === 'csv') {
                // Processar CSV
                $dados_importados = processarCSV($temp_file);
            } else {
                // Processar Excel (XLS/XLSX)
                // Requer biblioteca PhpSpreadsheet
                if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                    throw new Exception('Biblioteca PhpSpreadsheet não está instalada. Use arquivo CSV.');
                }

                $dados_importados = processarExcel($temp_file);
            }

            if (empty($dados_importados)) {
                throw new Exception('Arquivo vazio ou sem dados válidos.');
            }

            // Iniciar transação
            $pdo->beginTransaction();

            // Registrar importação
            $sql_importacao = "INSERT INTO pca_importacoes (ano, arquivo_original, total_registros, usuario_id, status)
                              VALUES (?, ?, ?, ?, 'EM_PROCESSAMENTO')";
            $stmt_imp = $pdo->prepare($sql_importacao);
            $stmt_imp->execute([$ano_pca, $arquivo['name'], count($dados_importados), $_SESSION['usuario_id']]);
            $importacao_id = $pdo->lastInsertId();

            // Inserir dados do PCA
            $sql_insert = "INSERT INTO pca_dados (
                ano, numero_contratacao, numero_dfd, titulo_contratacao,
                area_requisitante, codigo_classe_grupo, categoria_item,
                identificador_futura_contratacao, valor_total_estimado,
                data_desejada, pdm, situacao_execucao, importacao_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                titulo_contratacao = VALUES(titulo_contratacao),
                area_requisitante = VALUES(area_requisitante),
                codigo_classe_grupo = VALUES(codigo_classe_grupo),
                categoria_item = VALUES(categoria_item),
                identificador_futura_contratacao = VALUES(identificador_futura_contratacao),
                valor_total_estimado = VALUES(valor_total_estimado),
                data_desejada = VALUES(data_desejada),
                pdm = VALUES(pdm),
                situacao_execucao = VALUES(situacao_execucao),
                importacao_id = VALUES(importacao_id)";

            $stmt_insert = $pdo->prepare($sql_insert);

            $registros_inseridos = 0;
            $registros_atualizados = 0;
            $erros = [];

            foreach ($dados_importados as $index => $linha) {
                try {
                    // Validar campos obrigatórios
                    if (empty($linha['numero_contratacao'])) {
                        $erros[] = "Linha " . ($index + 2) . ": Número de contratação vazio";
                        continue;
                    }

                    $stmt_insert->execute([
                        $ano_pca,
                        $linha['numero_contratacao'] ?? '',
                        $linha['numero_dfd'] ?? '',
                        $linha['titulo_contratacao'] ?? '',
                        $linha['area_requisitante'] ?? '',
                        $linha['codigo_classe_grupo'] ?? '',
                        $linha['categoria_item'] ?? '',
                        $linha['identificador_futura_contratacao'] ?? '',
                        isset($linha['valor_total_estimado']) ? floatval(str_replace(',', '.', str_replace('.', '', $linha['valor_total_estimado']))) : 0,
                        !empty($linha['data_desejada']) ? date('Y-m-d', strtotime($linha['data_desejada'])) : null,
                        $linha['pdm'] ?? 'NÃO',
                        $linha['situacao_execucao'] ?? 'Não Iniciado',
                        $importacao_id
                    ]);

                    if ($stmt_insert->rowCount() > 0) {
                        $registros_inseridos++;
                    } else {
                        $registros_atualizados++;
                    }

                } catch (PDOException $e) {
                    $erros[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            // Atualizar status da importação
            $status = empty($erros) ? 'CONCLUIDA' : 'CONCLUIDA_COM_ERROS';
            $sql_update_imp = "UPDATE pca_importacoes SET
                              status = ?,
                              registros_inseridos = ?,
                              registros_atualizados = ?,
                              observacoes = ?
                              WHERE id = ?";
            $stmt_upd = $pdo->prepare($sql_update_imp);
            $stmt_upd->execute([
                $status,
                $registros_inseridos,
                $registros_atualizados,
                !empty($erros) ? implode("\n", array_slice($erros, 0, 10)) : null,
                $importacao_id
            ]);

            // Commit da transação
            $pdo->commit();

            // Remover arquivo temporário
            unlink($temp_file);

            // Registrar log
            registrarLog('IMPORTAR_PCA', "Importou PCA do ano $ano_pca: $registros_inseridos inseridos, $registros_atualizados atualizados", 'pca_importacoes', $importacao_id);

            // Mensagem de sucesso
            $mensagem = "Importação concluída com sucesso!<br>";
            $mensagem .= "Registros inseridos: $registros_inseridos<br>";
            $mensagem .= "Registros atualizados: $registros_atualizados";

            if (!empty($erros)) {
                $mensagem .= "<br>Avisos: " . count($erros) . " linha(s) com problemas.";
            }

            setMensagem($mensagem, 'success');
            header('Location: dashboard.php?ano=' . $ano_pca);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Remover arquivo temporário se existir
            if (isset($temp_file) && file_exists($temp_file)) {
                unlink($temp_file);
            }

            error_log("Erro na importação PCA: " . $e->getMessage());
            setMensagem('Erro na importação: ' . $e->getMessage(), 'erro');
            header('Location: dashboard.php?ano=' . $ano_pca);
            exit;
        }
        break;

    case 'editar_contratacao':
        verificarLogin();

        // Verificar permissão
        if (!temPermissao('pca_editar')) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para editar contratações PCA.'
            ]);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => false, 'message' => ''];

        try {
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID da contratação não fornecido.');
            }

            // Campos editáveis
            $sql = "UPDATE pca_dados SET
                    situacao_execucao = ?,
                    observacoes = ?,
                    data_desejada = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['situacao_execucao'] ?? 'Não Iniciado',
                limpar($_POST['observacoes'] ?? ''),
                !empty($_POST['data_desejada']) ? formatarDataDB($_POST['data_desejada']) : null,
                $id
            ]);

            registrarLog('EDITAR_PCA', "Editou contratação PCA ID: $id", 'pca_dados', $id);

            $response['success'] = true;
            $response['message'] = 'Contratação atualizada com sucesso!';

        } catch (Exception $e) {
            error_log("Erro ao editar PCA: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        break;

    case 'reverter_importacao':
        verificarLogin();

        // Verificar permissão (apenas Coordenador)
        if ($_SESSION['usuario_nivel'] != 1) {
            setMensagem('Apenas Coordenadores podem reverter importações.', 'erro');
            header('Location: dashboard.php');
            exit;
        }

        try {
            $importacao_id = intval($_POST['importacao_id'] ?? 0);

            if ($importacao_id <= 0) {
                throw new Exception('ID de importação inválido.');
            }

            // Iniciar transação
            $pdo->beginTransaction();

            // Excluir registros da importação
            $sql_delete = "DELETE FROM pca_dados WHERE importacao_id = ?";
            $stmt_del = $pdo->prepare($sql_delete);
            $stmt_del->execute([$importacao_id]);
            $registros_excluidos = $stmt_del->rowCount();

            // Marcar importação como revertida
            $sql_update = "UPDATE pca_importacoes SET status = 'REVERTIDA' WHERE id = ?";
            $stmt_upd = $pdo->prepare($sql_update);
            $stmt_upd->execute([$importacao_id]);

            $pdo->commit();

            registrarLog('REVERTER_IMPORTACAO', "Reverteu importação ID: $importacao_id ($registros_excluidos registros)", 'pca_importacoes', $importacao_id);

            setMensagem("Importação revertida com sucesso! $registros_excluidos registros removidos.", 'success');
            header('Location: dashboard.php');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Erro ao reverter importação: " . $e->getMessage());
            setMensagem('Erro ao reverter importação: ' . $e->getMessage(), 'erro');
            header('Location: dashboard.php');
            exit;
        }
        break;

    default:
        setMensagem('Ação não reconhecida para o módulo PCA.', 'erro');
        header('Location: dashboard.php');
        exit;
}

/**
 * Processar arquivo CSV
 */
function processarCSV($arquivo) {
    $dados = [];
    $handle = fopen($arquivo, 'r');

    if (!$handle) {
        throw new Exception('Não foi possível abrir o arquivo CSV.');
    }

    // Detectar encoding
    $primeira_linha = fgets($handle);
    $encoding = mb_detect_encoding($primeira_linha, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    rewind($handle);

    // Ler cabeçalho
    $header = fgetcsv($handle, 0, ';');

    if (!$header) {
        fclose($handle);
        throw new Exception('Arquivo CSV vazio ou formato inválido.');
    }

    // Converter encoding do cabeçalho
    if ($encoding !== 'UTF-8') {
        $header = array_map(function($item) use ($encoding) {
            return mb_convert_encoding($item, 'UTF-8', $encoding);
        }, $header);
    }

    // Normalizar nomes das colunas
    $header = array_map('trim', $header);
    $header = array_map('strtolower', $header);

    // Ler dados
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        // Converter encoding
        if ($encoding !== 'UTF-8') {
            $row = array_map(function($item) use ($encoding) {
                return mb_convert_encoding($item, 'UTF-8', $encoding);
            }, $row);
        }

        // Criar array associativo
        $linha = [];
        foreach ($header as $index => $coluna) {
            $valor = isset($row[$index]) ? trim($row[$index]) : '';

            // Mapear colunas (ajustar conforme estrutura real do CSV)
            $mapa_colunas = [
                'número da contratação' => 'numero_contratacao',
                'numero da contratacao' => 'numero_contratacao',
                'número dfd' => 'numero_dfd',
                'numero dfd' => 'numero_dfd',
                'título da contratação' => 'titulo_contratacao',
                'titulo da contratacao' => 'titulo_contratacao',
                'área requisitante' => 'area_requisitante',
                'area requisitante' => 'area_requisitante',
                'código classe/grupo' => 'codigo_classe_grupo',
                'codigo classe/grupo' => 'codigo_classe_grupo',
                'categoria do item' => 'categoria_item',
                'categoria item' => 'categoria_item',
                'identificador de futura contratação' => 'identificador_futura_contratacao',
                'valor total estimado' => 'valor_total_estimado',
                'data desejada' => 'data_desejada',
                'pdm' => 'pdm',
                'situação de execução' => 'situacao_execucao',
                'situacao de execucao' => 'situacao_execucao'
            ];

            $coluna_normalizada = $mapa_colunas[$coluna] ?? $coluna;
            $linha[$coluna_normalizada] = $valor;
        }

        if (!empty($linha['numero_contratacao'])) {
            $dados[] = $linha;
        }
    }

    fclose($handle);
    return $dados;
}

/**
 * Processar arquivo Excel (placeholder - requer PhpSpreadsheet)
 */
function processarExcel($arquivo) {
    // Esta função requer a biblioteca PhpSpreadsheet
    // composer require phpoffice/phpspreadsheet

    throw new Exception('Processamento de Excel não implementado. Use arquivo CSV.');
}
