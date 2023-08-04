<?php
// Dados da requisição
ini_set('display_errors', 0); // Desativar a exibição de erros no ambiente de produção
error_reporting(0);

// Validar e sanitizar dados da requisição
$transaction_id = isset($_POST['tx']) ? htmlspecialchars($_POST['tx']) : '';
$refer = isset($_POST['refer']) ? htmlspecialchars($_POST['refer']) : '';

// Verificar se os dados estão presentes e não são vazios
if (empty($transaction_id) || empty($refer)) {
    die('Dados inválidos.');
}

// URL da API (certifique-se de usar HTTPS para garantir a segurança)
$url = 'https://api.pagstar.com/api/v2/wallet/partner/transactions/' . $transaction_id;

// Token Bearer
$token = $refer;

// User-Agent
$userAgent = 'String Empresa X (contato@empresa.com)';

// Inicializar o cURL
$ch = curl_init($url);

// Definir as opções do cURL para uma requisição GET
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'User-Agent: ' . $userAgent,
));

// Executar a requisição
$response = curl_exec($ch);

// Verificar erros na comunicação com a API
if ($response === false) {
    die('Erro na comunicação com a API.');
}

// Verificar o código de resposta da API
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code !== 200) {
    die('Erro na API: Código ' . $http_code);
}

// Fechar a conexão cURL
curl_close($ch);

// Decodificar a resposta JSON
$data = json_decode($response, true);

// Verificar se a resposta foi decodificada com sucesso
if ($data === null) {
    die('Erro ao processar a resposta da API.');
}

// Exibir a resposta JSON (se necessário)
// echo json_encode($data);

// Exibir a chave da transação 
echo $data['data']['status'];