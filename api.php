<?php
// Oculta erros de exibição
ini_set('display_errors', 0);
error_reporting(0);

// Caminho absoluto até o wp-load.php
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');

// Verifica se o caminho é válido e inclui o wp-load.php
if ($wp_load_path && file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    // Loga erro caso não encontre o arquivo
    error_log('wp-load.php não encontrado em: ' . $wp_load_path);
    http_response_code(500); // Resposta HTTP 500 (Erro interno)
    exit('Erro interno');
}

// Coleta e sanitiza os dados do POST
$transaction_id = isset($_POST['tx']) ? htmlspecialchars($_POST['tx']) : '';
$refer = isset($_POST['refer']) ? htmlspecialchars($_POST['refer']) : '';

// Valida se os dados existem
if (empty($transaction_id) || empty($refer)) {
    die('Dados inválidos.');
}

try {
    // Inclui o arquivo da classe Pagstar_API
    require_once __DIR__ . '/pagstar-api.php';

    // Instancia a API passando o token (caso necessário no construtor)
    $api = new Pagstar_API($refer);

    // Consulta o status do pagamento via método
    $response = $api->get_payment_status($transaction_id);

    // Teste de retorno para debug
    wp_send_json('Teste retorno ' . json_encode($response));

    // Espera-se que $response seja um array associativo com chave 'status'
    if (!is_array($response) || !isset($response['status'])) {
        die('Resposta inválida da API');
    }

    // Ajuste conforme os possíveis status da Pagstar
    if ($response['status'] === 'CONCLUIDA') {
        echo 1; // Pagamento aprovado
    } else {
        echo 0; // Ainda aguardando ou outro status
    }

} catch (Exception $e) {
    // Log do erro opcional
    error_log('Erro ao consultar status de pagamento Pagstar: ' . $e->getMessage());
    echo 0; // Para o JS, considera como ainda não aprovado
}

exit(); // Finaliza a execução do script após a resposta

