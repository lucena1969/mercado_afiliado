<?php
/**
 * Validador de Webhook da Eduzz
 * Garante integridade dos dados recebidos
 */

class EduzzValidator {

    /**
     * Validar estrutura completa do webhook
     *
     * @param array $payload - Dados recebidos do webhook
     * @throws Exception - Se validação falhar
     * @return bool - True se válido
     */
    public static function validate($payload) {
        // 1. Validar campos obrigatórios no nível raiz
        self::validateRootFields($payload);

        // 2. Validar evento/status
        self::validateEventType($payload);

        // 3. Validar dados da venda se presentes
        if (isset($payload['sale']) || isset($payload['customer'])) {
            self::validateSaleData($payload);
        }

        return true;
    }

    /**
     * Validar campos obrigatórios no nível raiz
     */
    private static function validateRootFields($payload) {
        // Eduzz pode enviar 'event_type' ou 'status'
        if (!isset($payload['event_type']) && !isset($payload['status'])) {
            throw new Exception("Campo obrigatório ausente: 'event_type' ou 'status'");
        }
    }

    /**
     * Validar tipo de evento
     */
    private static function validateEventType($payload) {
        $event_type = $payload['event_type'] ?? $payload['status'];

        // Eventos conhecidos da Eduzz
        $known_events = [
            'sale_completed', 'payment_approved', 'approved',
            'sale_cancelled', 'payment_cancelled', 'cancelled',
            'sale_refunded', 'payment_refunded', 'refunded',
            'chargeback'
        ];

        if (!in_array($event_type, $known_events)) {
            error_log("Eduzz: Evento desconhecido recebido: {$event_type}");
            // Não vamos bloquear, apenas registrar
        }
    }

    /**
     * Validar dados da venda
     */
    private static function validateSaleData($payload) {
        $sale = $payload['sale'] ?? $payload;
        $customer = $payload['customer'] ?? $sale['customer'] ?? $sale;

        // Validar email se presente
        if (isset($customer['email'])) {
            if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido: {$customer['email']}");
            }
        }

        // Validar valor se presente
        if (isset($sale['value']) || isset($sale['amount']) || isset($sale['price'])) {
            $value = $sale['value'] ?? $sale['amount'] ?? $sale['price'];

            if (!is_numeric($value)) {
                throw new Exception("Valor da venda deve ser numérico");
            }

            if ((float)$value < 0) {
                throw new Exception("Valor da venda não pode ser negativo");
            }
        }

        // Validar ID da venda/transação
        if (!isset($sale['id']) && !isset($sale['sale_id']) && !isset($sale['transaction_id'])) {
            throw new Exception("ID da transação não encontrado");
        }
    }

    /**
     * Sanitizar payload completo
     *
     * @param array $payload - Dados a serem sanitizados
     * @return array - Dados sanitizados
     */
    public static function sanitize($payload) {
        return self::sanitizeRecursive($payload);
    }

    /**
     * Sanitização recursiva de arrays
     */
    private static function sanitizeRecursive($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = self::sanitizeRecursive($value);
            }
            return $sanitized;
        }

        if (is_string($data)) {
            // Remover tags HTML e scripts
            $data = strip_tags($data);

            // Remover caracteres de controle perigosos
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);

            // Trim
            $data = trim($data);

            return $data;
        }

        // Outros tipos retornam sem alteração
        return $data;
    }

    /**
     * Validar CPF/CNPJ
     */
    private static function isValidDocument($document) {
        if (empty($document)) {
            return true; // Opcional
        }

        // Remover formatação
        $document = preg_replace('/[^0-9]/', '', $document);

        // CPF = 11 dígitos, CNPJ = 14 dígitos
        return (strlen($document) === 11 || strlen($document) === 14);
    }
}
