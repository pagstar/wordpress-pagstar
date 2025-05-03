<?php
// Oculta erros de exibição
ini_set('display_errors', 0);
error_reporting(0);

// Coleta e sanitiza os dados do POST
$transaction_id = isset($_POST['tx']) ? htmlspecialchars($_POST['tx']) : '';
$refer = isset($_POST['refer']) ? htmlspecialchars($_POST['refer']) : '';

// Valida se os dados existem
if (empty($transaction_id) || empty($refer)) {
    die('Dados inválidos.');
}

// Inclui o arquivo da classe Pagstar_API
require_once __DIR__ . '/pagstar-api.php';

try {
    // Instancia a API passando o token (caso necessário no construtor)
    $api = new Pagstar_API($refer);

    // Consulta o status do pagamento via método
    $response = $api->get_payment_status($transaction_id);

    wp_send_json('Teste retorno ' . json_encode($response));

    // Espera-se que $response seja um array associativo com chave 'status'
    if (!is_array($response) || !isset($response['status'])) {
        die('Resposta inválida da API');
    }


    // Ajuste conforme os possíveis status da Pagstar
    if ($response['status'] === 'CONCLUIDA') {
        wp_send_json('1'); // Pagamento aprovado
    } else {
        wp_send_json('0'); // Ainda aguardando ou outro status
    }

} catch (Exception $e) {
    // Log do erro opcional
    error_log('Erro ao consultar status de pagamento Pagstar: ' . $e->getMessage());
    echo '0'; // Para o JS, considera como ainda não aprovado
}