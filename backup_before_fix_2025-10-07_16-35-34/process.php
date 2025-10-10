<?php
// process.php

// IMPORTANTE: Desabilitar exibição de erros para não contaminar JSON
// Erros serão registrados em error_log
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once 'config.php';
require_once 'functions.php';

// Configurar e iniciar sessão
configurarSessaoSegura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$acao = $_POST['acao'] ?? $_POST['action'] ?? '';

$pdo = conectarDB();

switch ($acao) {
    case 'login':
        $email = limpar($_POST['email']);
        $senha = $_POST['senha'];

        // Verificar se login está bloqueado
        if (isLoginBloqueado()) {
            setMensagem('Muitas tentativas de login. Tente novamente em alguns minutos.', 'erro');
            header('Location: index.php');
            exit;
        }

        try {
            $sql = "SELECT * FROM usuarios WHERE email = ? AND ativo = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
                $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'] ?? 3;
                $_SESSION['usuario_departamento'] = $usuario['departamento'] ?? 'CGLIC';
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                registrarTentativaLogin($email, true);

                header('Location: selecao_modulos.php');
                exit;
            } else {
                registrarTentativaLogin($email, false, 'Credenciais inválidas');
                setMensagem('Email ou senha incorretos', 'erro');
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            setMensagem('Erro no login', 'erro');
            header('Location: index.php');
            exit;
        }
        break;

    case 'registro':
        $nome = limpar($_POST['nome']);
        $email = limpar($_POST['email']);
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        $departamento = limpar($_POST['departamento'] ?? 'CGLIC');

        if (empty($nome) || empty($email) || empty($senha)) {
            setMensagem('Todos os campos são obrigatórios', 'erro');
            header('Location: index.php');
            exit;
        }

        if ($senha !== $confirmar_senha) {
            setMensagem('As senhas não coincidem', 'erro');
            header('Location: index.php');
            exit;
        }

        if (strlen($senha) < 6) {
            setMensagem('A senha deve ter pelo menos 6 caracteres', 'erro');
            header('Location: index.php');
            exit;
        }

        try {
            $sql_check = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$email]);

            if ($stmt_check->fetchColumn() > 0) {
                setMensagem('Este email já está cadastrado', 'erro');
                header('Location: index.php');
                exit;
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, nivel_acesso, departamento, ativo)
                    VALUES (?, ?, ?, 'usuario', 3, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $senha_hash, $departamento]);

            setMensagem('Cadastro realizado com sucesso! Faça login para continuar.', 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            setMensagem('Erro ao cadastrar usuário', 'erro');
            header('Location: index.php');
            exit;
        }
        break;

    case 'criar_licitacao':
        verificarLogin();

        // Verificar permissão para criar licitação
        if (!temPermissao('licitacao_criar')) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para criar licitações. Apenas usuários DIPLI podem criar.'
            ]);
            exit;
        }

        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        try {
            if (!validarNUP($_POST['nup'])) {
                throw new Exception('Formato do NUP inválido! Use: xxxxx.xxxxxx/xxxx-xx');
            }

            $sql_check_nup = "SELECT COUNT(*) FROM licitacoes WHERE nup = ?";
            $stmt_check_nup = $pdo->prepare($sql_check_nup);
            $stmt_check_nup->execute([limpar($_POST['nup'])]);

            if ($stmt_check_nup->fetchColumn() > 0) {
                throw new Exception('Este NUP já está cadastrado no sistema');
            }

            $pca_dados_id = null;
            if (!empty($_POST['numero_contratacao'])) {
                $stmt_pca = $pdo->prepare("SELECT id FROM pca_dados WHERE numero_contratacao = ? LIMIT 1");
                $stmt_pca->execute([trim($_POST['numero_contratacao'])]);
                $pca_achado = $stmt_pca->fetch();
                if ($pca_achado) {
                    $pca_dados_id = $pca_achado['id'];
                }
            }

            $nup = limpar($_POST['nup']);
            $data_entrada_dipli = formatarDataDB($_POST['data_entrada_dipli']);
            $resp_instrucao = limpar($_POST['resp_instrucao']);
            $area_demandante = limpar($_POST['area_demandante']);
            $pregoeiro = limpar($_POST['pregoeiro']);
            $modalidade = $_POST['modalidade'];
            $tipo = $_POST['tipo'];
            $numero = !empty($_POST['numero']) ? intval($_POST['numero']) : null;
            $ano = !empty($_POST['ano']) ? intval($_POST['ano']) : null;
            $valor_estimado = !empty($_POST['valor_estimado']) ? formatarValorDB($_POST['valor_estimado']) : null;
            $data_abertura = formatarDataDB($_POST['data_abertura']);
            $situacao = $_POST['situacao'];
            $objeto = limpar($_POST['objeto']);

            $data_homologacao = null;
            $qtd_homol = null;
            $valor_homologado = null;
            $economia = null;

            if ($situacao === 'HOMOLOGADO') {
                $data_homologacao = formatarDataDB($_POST['data_homologacao']);
                $qtd_homol = !empty($_POST['qtd_homol']) ? intval($_POST['qtd_homol']) : null;
                $valor_homologado = !empty($_POST['valor_homologado']) ? formatarValorDB($_POST['valor_homologado']) : null;
                $economia = !empty($_POST['economia']) ? formatarValorDB($_POST['economia']) : null;
            }

            $numero_contratacao = !empty($_POST['numero_contratacao']) ? trim($_POST['numero_contratacao']) : null;

            $sql = "INSERT INTO licitacoes (
                        nup, data_entrada_dipli, resp_instrucao, area_demandante, pregoeiro,
                        modalidade, tipo, numero, ano, valor_estimado, data_abertura,
                        situacao, objeto, data_homologacao, qtd_homol, valor_homologado,
                        economia, usuario_id, numero_contratacao, pca_dados_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nup, $data_entrada_dipli, $resp_instrucao, $area_demandante, $pregoeiro,
                $modalidade, $tipo, $numero, $ano, $valor_estimado, $data_abertura,
                $situacao, $objeto, $data_homologacao, $qtd_homol, $valor_homologado,
                $economia, $_SESSION['usuario_id'], $numero_contratacao, $pca_dados_id
            ]);

            registrarLog('CRIAR_LICITACAO', "Criou licitação NUP: $nup", 'licitacoes', $pdo->lastInsertId());

            $response['success'] = true;
            $response['message'] = 'Licitação criada com sucesso!';

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        break;

case 'atualizar_licitacao':
        // Alias para 'editar_licitacao' - para compatibilidade com código frontend antigo
        $_POST['acao'] = 'editar_licitacao';
        $acao = 'editar_licitacao';

        // Redirecionar para o case 'editar_licitacao'
        // NÃO usar break aqui para continuar para o próximo case

case 'editar':
        // Debug: Log para confirmar que chegou no case 'editar'
        error_log("🔄 [DEBUG] Case 'editar' acionado, redirecionando para 'editar_licitacao'");

        // Redirecionar acao 'editar' para 'editar_licitacao' para compatibilidade
        $_POST['acao'] = 'editar_licitacao';
        $acao = 'editar_licitacao'; // Atualizar variável também

        error_log("🔄 [DEBUG] Ação atualizada para: " . $acao);
        // Continuar para o case editar_licitacao

    case 'editar_licitacao':
        // Limpar qualquer output anterior que possa contaminar o JSON
        if (ob_get_level()) {
            ob_clean();
        }

        // Configurar header ANTES de qualquer output
        header('Content-Type: application/json; charset=utf-8');

        // Debug: Log para confirmar que chegou no case 'editar_licitacao'
        error_log("🎯 [DEBUG] Case 'editar_licitacao' acionado");
        error_log("🔍 [DEBUG] Verificando login. Session ID: " . session_id());
        error_log("🔍 [DEBUG] Session status: " . session_status());
        error_log("🔍 [DEBUG] Usuario ID na sessão: " . ($_SESSION['usuario_id'] ?? 'NÃO DEFINIDO'));

        if (!verificarLogin()) {
            error_log("❌ [DEBUG] verificarLogin() retornou FALSE");
            error_log("❌ [DEBUG SESSION] session_id: " . session_id());
            error_log("❌ [DEBUG SESSION] session_status: " . session_status());
            error_log("❌ [DEBUG SESSION] usuario_id definido: " . (isset($_SESSION['usuario_id']) ? 'SIM' : 'NÃO'));
            error_log("❌ [DEBUG SESSION] sessao completa: " . print_r($_SESSION, true));

            echo json_encode([
                'success' => false,
                'message' => 'Usuário não está logado',
                'debug' => [
                    'session_id' => session_id(),
                    'session_status' => session_status(),
                    'usuario_id' => $_SESSION['usuario_id'] ?? 'NÃO DEFINIDO',
                    'session_keys' => array_keys($_SESSION),
                    'session_data' => $_SESSION,
                    'acao_recebida' => $_POST['acao'] ?? 'UNDEFINED',
                    'acao_atual' => $acao
                ],
                'data' => null,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        error_log("✅ [DEBUG] verificarLogin() retornou TRUE");

        // Verificar permissão para editar licitação
        if (!temPermissao('licitacao_editar')) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para editar licitações.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $response = ['success' => false, 'message' => ''];

        try {

            $pdo = conectarDB();

            // Validar ID

            if (empty($_POST['id'])) {

                throw new Exception('ID da licitação não fornecido');

            }

            // Validar NUP

            if (!validarNUP($_POST['nup'])) {

                throw new Exception('Formato do NUP inválido! Use: xxxxx.xxxxxx/xxxx-xx');

            }

            // CORREÇÃO: Lógica para buscar o pca_dados_id a partir do numero_contratacao

            $pca_dados_id = null;

            if (!empty($_POST['numero_contratacao'])) {

                $stmt_pca = $pdo->prepare("SELECT id FROM pca_dados WHERE numero_contratacao = ? LIMIT 1");

                $stmt_pca->execute([trim($_POST['numero_contratacao'])]);

                $pca_achado = $stmt_pca->fetch();

                if ($pca_achado) {

                    $pca_dados_id = $pca_achado['id'];

                }

            }

            // Processar dados

            $id = intval($_POST['id']);

            $nup = limpar($_POST['nup'] ?? '');

            $data_entrada_dipli = formatarDataDB($_POST['data_entrada_dipli'] ?? null);

            $resp_instrucao = limpar($_POST['resp_instrucao'] ?? '');

            $area_demandante = limpar($_POST['area_demandante'] ?? '');

            $pregoeiro = limpar($_POST['pregoeiro'] ?? '');

            $modalidade = $_POST['modalidade'] ?? '';

            $tipo = $_POST['tipo'] ?? '';

            $numero = !empty($_POST['numero']) ? intval($_POST['numero']) : null;

            $ano = !empty($_POST['ano']) ? intval($_POST['ano']) : null;

            $valor_estimado = !empty($_POST['valor_estimado']) ? formatarValorDB($_POST['valor_estimado']) : null;

            $data_abertura = formatarDataDB($_POST['data_abertura'] ?? null);

            $situacao = $_POST['situacao'] ?? 'EM_ANDAMENTO';

            $objeto = limpar($_POST['objeto'] ?? '');

            // Campos de homologação

            $data_homologacao = null;

            $qtd_homol = null;

            $valor_homologado = null;

            $economia = null;

            if ($situacao === 'HOMOLOGADO') {

                $data_homologacao = formatarDataDB($_POST['data_homologacao']);

                $qtd_homol = !empty($_POST['qtd_homol']) ? intval($_POST['qtd_homol']) : null;

                $valor_homologado = !empty($_POST['valor_homologado']) ? formatarValorDB($_POST['valor_homologado']) : null;

                $economia = !empty($_POST['economia']) ? formatarValorDB($_POST['economia']) : null;

            }

            // CORREÇÃO: Adicionado numero_contratacao ao UPDATE
            $numero_contratacao = !empty($_POST['numero_contratacao']) ? trim($_POST['numero_contratacao']) : null;

            $sql = "UPDATE licitacoes SET
                    nup = ?, data_entrada_dipli = ?, resp_instrucao = ?, area_demandante = ?,
                    pregoeiro = ?, modalidade = ?, tipo = ?, numero = ?, ano = ?,
                    valor_estimado = ?, data_abertura = ?, situacao = ?, objeto = ?,
                    data_homologacao = ?, qtd_homol = ?, valor_homologado = ?, economia = ?,
                    numero_contratacao = ?, pca_dados_id = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nup, $data_entrada_dipli, $resp_instrucao, $area_demandante,
                $pregoeiro, $modalidade, $tipo, $numero, $ano,
                $valor_estimado, $data_abertura, $situacao, $objeto,
                $data_homologacao, $qtd_homol, $valor_homologado, $economia,
                $numero_contratacao, $pca_dados_id, // CORREÇÃO: Salvando numero_contratacao
                $id
            ]);

            registrarLog('EDITAR_LICITACAO', "Editou licitação ID: $id - NUP: $nup", 'licitacoes', $id);

            $response['success'] = true;

            $response['message'] = 'Licitação atualizada com sucesso!';

        } catch (PDOException $e) {
            // Erro de banco de dados
            error_log("❌ [ERRO PDO] editar_licitacao: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = 'Erro ao atualizar no banco de dados: ' . $e->getMessage();
            $response['debug_type'] = 'PDOException';

        } catch (Exception $e) {
            // Erro geral
            error_log("❌ [ERRO GERAL] editar_licitacao: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response['debug_type'] = 'Exception';
        }

        // Garantir que APENAS JSON seja enviado
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit; // ← CRÍTICO: exit para evitar qualquer output adicional

        break;

    case 'excluir_licitacao':
        verificarLogin();

        // Verificar permissão para excluir licitação (apenas DIPLI)
        if (!temPermissao('licitacao_excluir')) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para excluir licitações. Apenas usuários DIPLI podem excluir.'
            ]);
            exit;
        }

        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        try {
            $pdo = conectarDB();

            // Validar ID
            if (empty($_POST['id'])) {
                throw new Exception('ID da licitação não fornecido');
            }

            $id = intval($_POST['id']);

            // Verificar se a licitação existe e buscar dados para log
            $sql_verificar = "SELECT id, nup, objeto FROM licitacoes WHERE id = ?";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([$id]);
            $licitacao = $stmt_verificar->fetch();

            if (!$licitacao) {
                throw new Exception('Licitação não encontrada');
            }

            // Verificar se há dependências (ex: andamentos, etc.)
            // Para futuras implementações, verificar relacionamentos

            // Excluir a licitação
            $sql_excluir = "DELETE FROM licitacoes WHERE id = ?";
            $stmt_excluir = $pdo->prepare($sql_excluir);
            $resultado = $stmt_excluir->execute([$id]);

            if (!$resultado) {
                throw new Exception('Erro ao excluir licitação do banco de dados');
            }

            // Verificar se realmente foi excluída
            if ($stmt_excluir->rowCount() === 0) {
                throw new Exception('Nenhuma licitação foi excluída. Verifique se o ID está correto');
            }

            // Registrar no log
            registrarLog('EXCLUIR_LICITACAO', "Excluiu licitação ID: $id - NUP: {$licitacao['nup']} - Objeto: " . substr($licitacao['objeto'], 0, 50) . "...", 'licitacoes', $id);

            $response['success'] = true;
            $response['message'] = 'Licitação excluída com sucesso!';
            $response['nup'] = $licitacao['nup']; // Para feedback no frontend

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();

            // Log do erro
            error_log("Erro ao excluir licitação: " . $e->getMessage());
        }

        echo json_encode($response);
        break;

    case 'criar_qualificacao':
        verificarLogin();

        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        // Log de debug inicial
        error_log("=== CRIAR QUALIFICAÇÃO - INÍCIO ===");
        error_log("POST data: " . json_encode($_POST));
        error_log("Usuário ID: " . $_SESSION['usuario_id']);

        try {
            // Validar dados obrigatórios
            $nup = limpar($_POST['nup'] ?? '');
            $area_demandante = limpar($_POST['area_demandante'] ?? '');
            $responsavel = limpar($_POST['responsavel'] ?? '');
            $modalidade = limpar($_POST['modalidade'] ?? '');
            $objeto = limpar($_POST['objeto'] ?? '');
            $palavras_chave = limpar($_POST['palavras_chave'] ?? '');
            $valor_estimado = limpar($_POST['valor_estimado'] ?? '');
            $status = limpar($_POST['status'] ?? '');
            $observacoes = limpar($_POST['observacoes'] ?? '');
            $pca_dados_id = !empty($_POST['pca_dados_id']) ? intval($_POST['pca_dados_id']) : null;

            // Log dos dados capturados
            error_log("Dados capturados:");
            error_log("- NUP: '$nup'");
            error_log("- Área: '$area_demandante'");
            error_log("- Responsável: '$responsavel'");
            error_log("- Modalidade: '$modalidade'");
            error_log("- Valor estimado: '$valor_estimado'");
            error_log("- Status: '$status'");

            // Validações
            if (empty($nup)) {
                throw new Exception('NUP é obrigatório');
            }

            if (empty($area_demandante)) {
                throw new Exception('Área demandante é obrigatória');
            }

            if (empty($responsavel)) {
                throw new Exception('Responsável é obrigatório');
            }

            if (empty($modalidade)) {
                throw new Exception('Modalidade é obrigatória');
            }

            if (empty($objeto)) {
                throw new Exception('Objeto é obrigatório');
            }

            if (empty($status)) {
                throw new Exception('Status é obrigatório');
            }

            // Limpar e converter valor monetário
            $valor_numerico = 0.00;
            if (!empty($valor_estimado)) {
                // Remover formatação completa (R$, espaços, etc)
                $valor_limpo = trim($valor_estimado);
                $valor_limpo = preg_replace('/[^\d,.]/', '', $valor_limpo);

                // Se tem vírgula e ponto, assumir formato brasileiro (1.000,00)
                if (strpos($valor_limpo, '.') !== false && strpos($valor_limpo, ',') !== false) {
                    $valor_limpo = str_replace('.', '', $valor_limpo); // Remove separador de milhares
                    $valor_limpo = str_replace(',', '.', $valor_limpo); // Vírgula vira ponto decimal
                }
                // Se tem apenas vírgula, assumir que é decimal brasileiro (100,50)
                elseif (strpos($valor_limpo, ',') !== false && strpos($valor_limpo, '.') === false) {
                    $valor_limpo = str_replace(',', '.', $valor_limpo);
                }
                // Se tem apenas ponto, pode ser decimal americano (100.50) ou separador de milhares (1.000)
                elseif (strpos($valor_limpo, '.') !== false && strpos($valor_limpo, ',') === false) {
                    // Se há mais de um ponto ou ponto não está nos últimos 3 dígitos, é separador de milhares
                    if (substr_count($valor_limpo, '.') > 1 || !preg_match('/\.\d{2}$/', $valor_limpo)) {
                        $valor_limpo = str_replace('.', '', $valor_limpo);
                    }
                }

                $valor_numerico = floatval($valor_limpo);

                // Log para debug
                error_log("Valor original: '$valor_estimado' -> Valor limpo: '$valor_limpo' -> Valor numérico: $valor_numerico");

                // Validar se o valor é válido
                if ($valor_numerico <= 0) {
                    throw new Exception('Valor estimado deve ser maior que zero');
                }
            }

            // Verificar se NUP já existe
            $sql_check = "SELECT id FROM qualificacoes WHERE nup = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$nup]);

            if ($stmt_check->fetch()) {
                throw new Exception('Este NUP já está cadastrado no sistema');
            }

            // Inserir qualificação
            $sql = "INSERT INTO qualificacoes (
                nup,
                area_demandante,
                responsavel,
                modalidade,
                objeto,
                palavras_chave,
                valor_estimado,
                status,
                observacoes,
                usuario_id,
                pca_dados_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                $nup,
                $area_demandante,
                $responsavel,
                $modalidade,
                $objeto,
                $palavras_chave,
                $valor_numerico,
                $status,
                $observacoes,
                $_SESSION['usuario_id'],
                $pca_dados_id
            ]);

            if (!$resultado) {
                throw new Exception('Erro ao salvar qualificação no banco de dados');
            }

            $qualificacao_id = $pdo->lastInsertId();
            error_log("Qualificação inserida com sucesso! ID: $qualificacao_id");
            error_log("Linhas afetadas: " . $stmt->rowCount());

            // Registrar no log
            registrarLog('CRIAR_QUALIFICACAO', "Criou qualificação ID: $qualificacao_id - NUP: $nup - Área: $area_demandante", 'qualificacoes', $qualificacao_id);

            $response['success'] = true;
            $response['message'] = 'Qualificação cadastrada com sucesso!';
            $response['id'] = $qualificacao_id;
            $response['nup'] = $nup;

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();

            // Log do erro
            error_log("Erro ao criar qualificação: " . $e->getMessage());
        }

        echo json_encode($response);
        break;

    case 'buscar_qualificacao':
        verificarLogin();

        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        try {
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID da qualificação é obrigatório');
            }

            // Buscar qualificação
            $sql = "SELECT * FROM qualificacoes WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $qualificacao = $stmt->fetch();

            if (!$qualificacao) {
                throw new Exception('Qualificação não encontrada');
            }

            $response['success'] = true;
            $response['data'] = $qualificacao;

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        break;

    case 'excluir_qualificacao':
        verificarLogin();

        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        try {
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID da qualificação é obrigatório');
            }

            // Verificar se qualificação existe antes de excluir
            $sql_check = "SELECT nup FROM qualificacoes WHERE id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id]);
            $qualificacao = $stmt_check->fetch();

            if (!$qualificacao) {
                throw new Exception('Qualificação não encontrada');
            }

            // Excluir qualificação
            $sql_delete = "DELETE FROM qualificacoes WHERE id = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $resultado = $stmt_delete->execute([$id]);

            if (!$resultado) {
                throw new Exception('Erro ao excluir qualificação do banco de dados');
            }

            // Registrar no log
            registrarLog('EXCLUIR_QUALIFICACAO', "Excluiu qualificação ID: $id - NUP: {$qualificacao['nup']}", 'qualificacoes', $id);

            $response['success'] = true;
            $response['message'] = 'Qualificação excluída com sucesso!';

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();

            // Log do erro
            error_log("Erro ao excluir qualificação: " . $e->getMessage());
        }

        echo json_encode($response);
