<?php

// Configuração de headers para resposta JSON
header('Content-Type: application/json');

// Inclui o arquivo de funções utilitárias
require_once(__DIR__ . '/utils.php');

// Função para limpar e fazer backup dos logs
function rotateLogs() {
    $logFile = __DIR__ . '/webhook_errors.log';
    $backupDir = __DIR__ . '/logs_backup';
    
    // Cria diretório de backup se não existir
    if (!file_exists($backupDir)) {
        wp_mkdir_p($backupDir);
    }
    
    // Verifica se o arquivo de log existe e tem conteúdo
    if (file_exists($logFile) && filesize($logFile) > 0) {
        // Gera nome do arquivo de backup com data
        $backupFile = $backupDir . '/webhook_errors_' . date('Y-m-d') . '.log';
        
        // Move o arquivo atual para backup
        rename($logFile, $backupFile);
        
        // Cria novo arquivo de log vazio
        touch($logFile);
        chmod($logFile, 0644);
        
        // Remove backups antigos (mais de 30 dias)
        $files = glob($backupDir . '/webhook_errors_*.log');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 30 * 24 * 60 * 60) { // 30 dias
                    unlink($file);
                }
            }
        }
    }
}

// Função para registrar logs
function logError($message, $data = null) {
    $logFile = __DIR__ . '/webhook_errors.log';
    
    // Verifica se é um novo dia para rotacionar os logs
    $lastRotationFile = __DIR__ . '/.last_rotation';
    $today = date('Y-m-d');
    
    if (!file_exists($lastRotationFile) || 
        file_get_contents($lastRotationFile) !== $today) {
        rotateLogs();
        file_put_contents($lastRotationFile, $today);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($data) {
        $logMessage .= "\nDados recebidos: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    $logMessage .= "\n" . str_repeat('-', 80) . "\n";
    
    error_log($logMessage, 3, $logFile);
}

// Função para sanitizar dados
function sanitizeData($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeData($value);
        }
    } else if (is_string($data)) {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verifica rate limit
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!pagstar_check_rate_limit($ip)) {
            $error = "Limite de requisições excedido. Tente novamente mais tarde.";
            logError($error, ['ip' => $ip]);
            throw new Exception($error);
        }

        // Recupera e sanitiza os dados
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        $data = sanitizeData($data);

        // Verifica se o JSON foi decodificado corretamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Erro ao decodificar JSON: ' . json_last_error_msg();
            logError($error, $payload);
            throw new Exception($error);
        }

        // Validação dos campos obrigatórios
        $requiredFields = ['txid', 'valor', 'horario', 'pagador', 'endToEndId'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $error = "Campo obrigatório não encontrado: {$field}";
                logError($error, $data);
                throw new Exception($error);
            }
        }

        // Validação dos campos do pagador
        if (!isset($data['pagador']['cpf']) || !isset($data['pagador']['nome'])) {
            $error = "Campos do pagador incompletos";
            logError($error, $data);
            throw new Exception($error);
        }

        // Validação do CPF
        if (!pagstar_validate_cpf($data['pagador']['cpf'])) {
            $error = "CPF inválido: " . $data['pagador']['cpf'];
            logError($error, $data);
            throw new Exception($error);
        }

        // Aqui você pode adicionar a lógica de processamento do pagamento
        // Por exemplo, atualizar o status do pagamento no banco de dados
        
        // Resposta de sucesso
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Webhook recebido com sucesso',
            'data' => [
                'txid' => $data['txid'],
                'endToEndId' => $data['endToEndId']
            ]
        ]);

    } catch (Exception $e) {
        // Resposta de erro com status 200
        http_response_code(200);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Responde com erro com status 200
    http_response_code(200);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método não permitido. Apenas POST é aceito.'
    ]);
}
