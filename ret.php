<?php

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera os dados enviados no corpo da requisição
    $data = json_decode(file_get_contents('php://input'), true);

    // Verifica se os dados foram recebidos corretamente
    if ($data && isset($data['status']) && $data['status'] === 'approved') {
      
        http_response_code(200);
    } else {
        // Dados inválidos ou não foram enviados corretamente
        http_response_code(400);
    }
} else {
    // Responde com erro para outros métodos de requisição diferentes de POST
    http_response_code(405);
}
