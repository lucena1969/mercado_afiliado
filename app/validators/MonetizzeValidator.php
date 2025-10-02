<?php
/**
 * Validador de Postback da Monetizze
 * Garante integridade dos dados recebidos
 */

class MonetizzeValidator {

    /**
     * Validar estrutura completa do postback
     *
     * @param array $payload - Dados recebidos do postback
     * @throws Exception - Se validação falhar
     * @return bool - True se válido
     */
    public static function validate($payload) {
        // 1. Validar campos obrigatórios no nível raiz
        self::validateRootFields($payload);

        // 2. Validar objeto 'venda'
        self::validateVenda($payload);

        // 3. Validar objeto 'produto'
        self::validateProduto($payload);

        // 4. Validar objeto 'comprador'
        self::validateComprador($payload);

        // 5. Validar tipos de dados
        self::validateDataTypes($payload);

        return true;
    }

    /**
     * Validar campos obrigatórios no nível raiz
     */
    private static function validateRootFields($payload) {
        $required = [
            'postback_evento' => 'Código do evento',
            'codigo_venda' => 'Código da venda',
            'codigo_produto' => 'Código do produto',
            'codigo_status' => 'Código do status'
        ];

        foreach ($required as $field => $description) {
            if (!isset($payload[$field])) {
                throw new Exception("Campo obrigatório ausente: {$field} ({$description})");
            }

            if (empty($payload[$field]) && $payload[$field] !== '0' && $payload[$field] !== 0) {
                throw new Exception("Campo obrigatório vazio: {$field} ({$description})");
            }
        }
    }

    /**
     * Validar objeto 'venda'
     */
    private static function validateVenda($payload) {
        if (!isset($payload['venda']) || !is_array($payload['venda'])) {
            throw new Exception("Objeto 'venda' ausente ou inválido");
        }

        $venda = $payload['venda'];

        $required = [
            'codigo' => 'Código da venda',
            'valor' => 'Valor da venda',
            'status' => 'Status da venda'
        ];

        foreach ($required as $field => $description) {
            if (!isset($venda[$field])) {
                throw new Exception("Campo obrigatório ausente em 'venda': {$field} ({$description})");
            }
        }

        // Validar valor numérico
        if (isset($venda['valor']) && !is_numeric($venda['valor'])) {
            throw new Exception("Campo 'venda.valor' deve ser numérico, recebido: " . gettype($venda['valor']));
        }

        // Validar que valor não é negativo
        if (isset($venda['valor']) && (float)$venda['valor'] < 0) {
            throw new Exception("Campo 'venda.valor' não pode ser negativo");
        }
    }

    /**
     * Validar objeto 'produto'
     */
    private static function validateProduto($payload) {
        if (!isset($payload['produto']) || !is_array($payload['produto'])) {
            throw new Exception("Objeto 'produto' ausente ou inválido");
        }

        $produto = $payload['produto'];

        $required = [
            'codigo' => 'Código do produto',
            'nome' => 'Nome do produto'
        ];

        foreach ($required as $field => $description) {
            if (!isset($produto[$field])) {
                throw new Exception("Campo obrigatório ausente em 'produto': {$field} ({$description})");
            }
        }
    }

    /**
     * Validar objeto 'comprador'
     */
    private static function validateComprador($payload) {
        if (!isset($payload['comprador']) || !is_array($payload['comprador'])) {
            throw new Exception("Objeto 'comprador' ausente ou inválido");
        }

        $comprador = $payload['comprador'];

        $required = [
            'nome' => 'Nome do comprador',
            'email' => 'Email do comprador'
        ];

        foreach ($required as $field => $description) {
            if (!isset($comprador[$field]) || empty($comprador[$field])) {
                throw new Exception("Campo obrigatório ausente em 'comprador': {$field} ({$description})");
            }
        }

        // Validar formato de email
        if (isset($comprador['email']) && !filter_var($comprador['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email do comprador inválido: {$comprador['email']}");
        }

        // Validar CPF/CNPJ se presente
        if (isset($comprador['cnpj_cpf']) && !empty($comprador['cnpj_cpf'])) {
            self::validateDocument($comprador['cnpj_cpf']);
        }
    }

    /**
     * Validar tipos de dados
     */
    private static function validateDataTypes($payload) {
        // postback_evento deve ser numérico
        if (!is_numeric($payload['postback_evento'])) {
            throw new Exception("Campo 'postback_evento' deve ser numérico");
        }

        $event_code = (int) $payload['postback_evento'];

        // Validar códigos de evento válidos
        $valid_events = [1, 2, 3, 4, 5, 6, 7, 70, 98, 99, 101, 102, 103, 104, 105, 106, 120];
        if (!in_array($event_code, $valid_events)) {
            throw new Exception("Código de evento inválido: {$event_code}");
        }

        // codigo_status deve ser numérico
        if (!is_numeric($payload['codigo_status'])) {
            throw new Exception("Campo 'codigo_status' deve ser numérico");
        }
    }

    /**
     * Validar CPF ou CNPJ
     */
    private static function validateDocument($document) {
        // Remover formatação
        $document = preg_replace('/[^0-9]/', '', $document);

        // Verificar se tem 11 (CPF) ou 14 (CNPJ) dígitos
        $length = strlen($document);

        if ($length !== 11 && $length !== 14) {
            throw new Exception("CPF/CNPJ deve ter 11 ou 14 dígitos, recebido: {$length} dígitos");
        }

        // Verificar se não são todos dígitos iguais
        if (preg_match('/^(\d)\1+$/', $document)) {
            throw new Exception("CPF/CNPJ inválido (todos os dígitos são iguais)");
        }

        return true;
    }

    /**
     * Sanitizar dados do payload
     * Remove tags HTML, scripts e normaliza espaços
     */
    public static function sanitize($payload) {
        return self::sanitizeArray($payload);
    }

    /**
     * Sanitizar array recursivamente
     */
    private static function sanitizeArray($data) {
        if (!is_array($data)) {
            return self::sanitizeString($data);
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizar string
     */
    private static function sanitizeString($value) {
        if (!is_string($value)) {
            return $value;
        }

        // Remover tags HTML/XML
        $value = strip_tags($value);

        // Remover espaços extras
        $value = trim($value);

        // Normalizar espaços múltiplos
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Validar formato de data
     */
    public static function validateDate($date, $fieldName = 'data') {
        if (empty($date)) {
            return true; // Campos de data podem ser opcionais
        }

        // Tentar parsear a data
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            throw new Exception("Formato de data inválido em '{$fieldName}': {$date}");
        }

        // Validar que a data não é muito antiga (> 10 anos) ou futura (> 1 dia)
        $ten_years_ago = strtotime('-10 years');
        $tomorrow = strtotime('+1 day');

        if ($timestamp < $ten_years_ago) {
            throw new Exception("Data muito antiga em '{$fieldName}': {$date}");
        }

        if ($timestamp > $tomorrow) {
            throw new Exception("Data no futuro em '{$fieldName}': {$date}");
        }

        return true;
    }
}
