<?php
/**
 * Arquivo de integração com a API da Pagstar
 *
 * @package Pagstar
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe para interagir com a API da Pagstar
 */
class Pagstar_API {
    /**
     * URL base da API
     */
    private const API_BASE_URL = 'https://api.pix.pagstar.com';

    /**
     * Obtém os certificados MTLS
     *
     * @return array|WP_Error Array com os caminhos dos certificados ou erro
     */
    private static function get_certificates() {
        $crt_path = get_option('pagstar_crt');
        $key_path = get_option('pagstar_key');

        if (empty($crt_path) || empty($key_path)) {
            return new WP_Error('missing_certificates', 'Certificados MTLS não configurados');
        }

        if (!file_exists($crt_path) || !file_exists($key_path)) {
            return new WP_Error('certificates_not_found', 'Arquivos de certificado não encontrados');
        }

        return [
            'crt' => $crt_path,
            'key' => $key_path
        ];
    }

    /**
     * Obtém o token de acesso da API
     *
     * @return string|WP_Error Token de acesso ou erro
     */
    public static function get_access_token() {
        // Tenta obter o token do cache
        $cached_token = get_transient('pagstar_access_token');
        if ($cached_token !== false) {
            return $cached_token;
        }

        $client_id = get_option('pagstar_client_id');
        $client_secret = get_option('pagstar_client_secret');
        $certificates = self::get_certificates();

        if (is_wp_error($certificates)) {
            return $certificates;
        }

        if (empty($client_id) || empty($client_secret)) {
            return new WP_Error('missing_credentials', 'Credenciais da API não configuradas');
        }

        $response = wp_remote_post(self::API_BASE_URL . '/oauth/token', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ]),
            'sslverify' => false,
            'sslcertificates' => $certificates['crt'],
            'sslkey' => $certificates['key'],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token'])) {
            return new WP_Error('invalid_response', 'Resposta inválida da API');
        }

        // Armazena o token no cache por 4 minutos
        set_transient('pagstar_access_token', $body['access_token'], 4 * MINUTE_IN_SECONDS);

        return $body['access_token'];
    }

    /**
     * Verifica se o token está válido e obtém um novo se necessário
     *
     * @return string|WP_Error Token de acesso ou erro
     */
    private static function ensure_valid_token() {
        $token = get_transient('pagstar_access_token');
        if ($token === false) {
            return self::get_access_token();
        }
        return $token;
    }

    /**
     * Cria uma cobrança PIX
     *
     * @param array $data Dados da cobrança
     * @return array|WP_Error Dados da cobrança ou erro
     */
    public static function create_cob($data) {
        $token = self::ensure_valid_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $certificates = self::get_certificates();
        if (is_wp_error($certificates)) {
            return $certificates;
        }

        $response = wp_remote_post(self::API_BASE_URL . '/cob', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode($data),
            'sslverify' => false,
            'sslcertificates' => $certificates['crt'],
            'sslkey' => $certificates['key'],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Consulta uma cobrança PIX
     *
     * @param string $txid ID da transação
     * @return array|WP_Error Dados da cobrança ou erro
     */
    public static function get_cob($txid) {
        $token = self::ensure_valid_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $certificates = self::get_certificates();
        if (is_wp_error($certificates)) {
            return $certificates;
        }

        $response = wp_remote_get(self::API_BASE_URL . '/cob/' . $txid, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'sslverify' => false,
            'sslcertificates' => $certificates['crt'],
            'sslkey' => $certificates['key'],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Configura o webhook
     *
     * @param string $webhook_id ID do webhook
     * @param string $webhook_url URL do webhook
     * @return array|WP_Error Resposta da API ou erro
     */
    public static function configure_webhook($webhook_id, $webhook_url) {
        $token = self::ensure_valid_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $certificates = self::get_certificates();
        if (is_wp_error($certificates)) {
            return $certificates;
        }

        $response = wp_remote_request(self::API_BASE_URL . '/webhook/' . $webhook_id, [
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode([
                'webhookUrl' => $webhook_url,
            ]),
            'sslverify' => false,
            'sslcertificates' => $certificates['crt'],
            'sslkey' => $certificates['key'],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
} 