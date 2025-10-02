<?php
/**
 * Validador de Webhook da Braip
 * Garante integridade dos dados recebidos
 */

class BraipValidator {

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

        // 3. Validar dados da compra/venda se presentes
        if (isset($payload['purchase']) || isset($payload['sale'])) {
            self::validatePurchaseData($payload);
        }

        return true;
    }

    /**
     * Validar campos obrigatórios no nível raiz
     */
    private static function validateRootFields($payload) {
        // Braip pode enviar 'event' ou 'status'
        if (!isset($payload['event']) && !isset($payload['status'])) {
            throw new Exception("Campo obrigatório ausente: 'event' ou 'status'");
        }
    }

    /**
     * Validar tipo de evento
     */
    private static function validateEventType($payload) {
        $event_type = $payload['event'] ?? $payload['status'];

        // Eventos conhecidos da Braip
        $known_events = [
            'purchase_approved', 'sale_approved', 'approved',
            'purchase_cancelled', 'sale_cancelled', 'cancelled',
            'purchase_refunded', 'sale_refunded', 'refunded',
            'chargeback'
        ];

        if (!in_array($event_type, $known_events)) {
            error_log("Braip: Evento desconhecido recebido: {$event_type}");
            // Não vamos bloquear, apenas registrar
        }
    }

    /**
     * Validar dados da compra
     */
    private static function validatePurchaseData($payload) {
        $purchase = $payload['purchase'] ?? $payload['sale'] ?? $payload;
        $customer = $purchase['customer'] ?? $purchase['buyer'] ?? $purchase;

        // Validar email se presente
        if (isset($customer['email'])) {
            if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido: {$customer['email']}");
            }
        } elseif (isset($customer['buyer_email'])) {
            if (!filter_var($customer['buyer_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido: {$customer['buyer_email']}");
            }
        }

        // Validar valor se presente
        if (isset($purchase['amount']) || isset($purchase['value']) || isset($purchase['price'])) {
            $value = $purchase['amount'] ?? $purchase['value'] ?? $purchase['price'];

            if (!is_numeric($value)) {
                throw new Exception("Valor da compra deve ser numérico");
            }

            if ((float)$value < 0) {
                throw new Exception("Valor da compra não pode ser negativo");
            }
        }

        // Validar ID da transação
        if (!isset($purchase['id']) && !isset($purchase['transaction_id']) && !isset($purchase['purchase_id'])) {
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
