<?php
/**
 * Funções utilitárias para o plugin Pagstar
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Valida um número de CPF
 * 
 * @param string $cpf Número do CPF a ser validado
 * @return bool True se o CPF for válido, False caso contrário
 */
function pagstar_validate_cpf($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validação do primeiro dígito verificador
    for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    if ($cpf[9] != ($resto < 2 ? 0 : 11 - $resto)) {
        return false;
    }
    
    // Validação do segundo dígito verificador
    for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    if ($cpf[10] != ($resto < 2 ? 0 : 11 - $resto)) {
        return false;
    }
    
    return true;
}

/**
 * Verifica se o IP excedeu o limite de requisições
 * 
 * @param string $ip IP do cliente
 * @return bool True se estiver dentro do limite, False se excedeu
 */
function pagstar_check_rate_limit($ip) {
    $rate_limit = get_option('pagstar_webhook_rate_limit', 100);
    $cache_file = WP_CONTENT_DIR . '/pagstar_rate_limit.json';
    
    // Carrega o cache existente
    $cache = [];
    if (file_exists($cache_file)) {
        $cache = json_decode(file_get_contents($cache_file), true);
        
        // Limpa requisições antigas (mais de 1 minuto)
        $current_time = time();
        foreach ($cache as $key => $value) {
            if ($current_time - $value['timestamp'] > 60) {
                unset($cache[$key]);
            }
        }
    }
    
    // Verifica o limite para o IP atual
    if (!isset($cache[$ip])) {
        $cache[$ip] = [
            'count' => 0,
            'timestamp' => time()
        ];
    }
    
    $cache[$ip]['count']++;
    
    // Salva o cache atualizado
    file_put_contents($cache_file, json_encode($cache));
    
    // Retorna true se estiver dentro do limite
    return $cache[$ip]['count'] <= $rate_limit;
} 