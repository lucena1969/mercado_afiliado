<?php
/**
 * Validador de Webhook da Hotmart
 * Garante integridade dos dados recebidos
 */

class HotmartValidator {

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

        // 2. Validar objeto 'data' (dados principais)
        self::validateDataObject($payload);

        // 3. Validar buyer (comprador)
        if (isset($payload['data']['buyer'])) {
            self::validateBuyer($payload['data']);
        }

        // 4. Validar purchase (compra)
        if (isset($payload['data']['purchase'])) {
            self::validatePurchase($payload['data']);
        }

        // 5. Validar product (produto)
        if (isset($payload['data']['product'])) {
            self::validateProduct($payload['data']);
        }

        return true;
    }

    /**
     * Validar campos obrigatórios no nível raiz
     */
    private static function validateRootFields($payload) {
        $required = [
            'event' => 'Tipo do evento'
        ];

        foreach ($required as $field => $description) {
            if (!isset($payload[$field])) {
                throw new Exception("Campo obrigatório ausente: {$field} ({$description})");
            }

            if (empty($payload[$field]) && $payload[$field] !== '0' && $payload[$field] !== 0) {
                throw new Exception("Campo obrigatório vazio: {$field} ({$description})");
            }
        }

        // Validar eventos conhecidos
        $known_events = [
            'PURCHASE_COMPLETE', 'PURCHASE_APPROVED', 'PURCHASE_REFUNDED',
            'PURCHASE_CHARGEBACK', 'PURCHASE_CANCELED', 'PURCHASE_EXPIRED',
            'PURCHASE_BILLET_PRINTED', 'PURCHASE_PROTEST', 'PURCHASE_DELAYED',
            'PURCHASE_OUT_OF_SHOPPING_CART', 'SUBSCRIPTION_CANCELLATION'
        ];

        if (!in_array($payload['event'], $known_events)) {
            error_log("Hotmart: Evento desconhecido recebido: {$payload['event']}");
            // Não vamos bloquear, apenas registrar
        }
    }

    /**
     * Validar objeto 'data'
     */
    private static function validateDataObject($payload) {
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            throw new Exception("Objeto 'data' ausente ou inválido");
        }
    }

    /**
     * Validar comprador
     */
    private static function validateBuyer($data) {
        $buyer = $data['buyer'];

        $required = [
            'email' => 'Email do comprador'
        ];

        foreach ($required as $field => $description) {
            if (!isset($buyer[$field])) {
                throw new Exception("Campo obrigatório ausente em 'buyer': {$field} ({$description})");
            }
        }

        // Validar formato de email
        if (isset($buyer['email']) && !filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido: {$buyer['email']}");
        }
    }

    /**
     * Validar compra
     */
    private static function validatePurchase($data) {
        $purchase = $data['purchase'];

        // Validar valor se presente
        if (isset($purchase['price']['value'])) {
            if (!is_numeric($purchase['price']['value'])) {
                throw new Exception("Campo 'purchase.price.value' deve ser numérico");
            }

            if ((float)$purchase['price']['value'] < 0) {
                throw new Exception("Campo 'purchase.price.value' não pode ser negativo");
            }
        }
    }

    /**
     * Validar produto
     */
    private static function validateProduct($data) {
        $product = $data['product'];

        $required = [
            'id' => 'ID do produto'
        ];

        foreach ($required as $field => $description) {
            if (!isset($product[$field])) {
                throw new Exception("Campo obrigatório ausente em 'product': {$field} ({$description})");
            }
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
