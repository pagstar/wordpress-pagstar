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
    private $client_id;
    private $client_secret;
    private $pix_key;
    private $link_r;
    private $webhook_url;
    private $base_url = 'https://api.pix.pagstar.com';
    private $cert_path;
    private $key_path;
    private $access_token;
    private $token_expires_at;

    public function __construct() {
        $this->client_id = get_option('pagstar_client_id');
        $this->client_secret = get_option('pagstar_client_secret');
        $this->pix_key = get_option('pagstar_pix_key');
        $this->link_r = get_option('pagstar_link_r');
        $this->webhook_url = get_option('pagstar_webhook_url');
        $this->cert_path = get_option('pagstar_cert_crt_path');
        $this->key_path = get_option('pagstar_cert_key_path');
    }

    private function get_access_token()
    {
        // Tentar obter o token do transient
        $cached_token = get_transient('pagstar_access_token');
        if ($cached_token) {
            $this->access_token = $cached_token;
            return $this->access_token;
        }

        $url = $this->base_url . '/oauth/token';
        $ch = curl_init();

        // Configurações básicas do cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // Configurar certificados MTLS
        if ($this->cert_path && file_exists($this->cert_path)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->cert_path);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        }
        if ($this->key_path && file_exists($this->key_path)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        }

        // Configurar headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Configurar dados
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Executar requisição
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Tratar resposta
        if ($error) {
            throw new Exception('Erro na requisição de token: ' . $error);
        }

        $response_data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar resposta JSON do token');
        }

        if ($http_code !== 201) {
            throw new Exception($response_data['message'] ?? 'Erro ao obter token de acesso' . $http_code);
        }

        // Salvar token e tempo de expiração
        $this->access_token = $response_data['access_token'];
        
        // Armazenar token no transient por 4 minutos
        set_transient('pagstar_access_token', $this->access_token, 4 * MINUTE_IN_SECONDS);

        return $this->access_token;
    }

    private function make_request($endpoint, $method = 'POST', $data = [])
    {
        try {
            // Obter token de acesso
            $token = $this->get_access_token();

            $url = $this->base_url . $endpoint;
            $ch = curl_init();

            // Configurações básicas do cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            // Configurar certificados MTLS
            if ($this->cert_path && file_exists($this->cert_path)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $this->cert_path);
            }
            if ($this->key_path && file_exists($this->key_path)) {
                curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
            }

            // Configurar headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Configurar método e dados
            if ($method === 'POST' || $method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($method === 'GET') {
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
            }

            // Executar requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Tratar resposta
            if ($error) {
                return [
                    'code' => 500,
                    'message' => 'Erro na requisição: ' . $error,
                    'data' => null
                ];
            }

            $response_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'code' => 500,
                    'message' => 'Erro ao decodificar resposta JSON',
                    'data' => null
                ];
            }

            if ($http_code < 200 || $http_code >= 300) {
                return [
                    'code' => $http_code,
                    'message' => json_encode($response_data),
                    'data' => $response_data
                ];
            }

            return [
                'code' => $http_code,
                'message' => json_encode($response_data),
                'data' => $response_data
            ];
        } catch (Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function show_error_toast($title, $message) {
        add_action('admin_footer', function() use ($title, $message) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                showToast('<?php echo esc_js($title); ?>', '<?php echo esc_js($message); ?>', 'error');
            });
            </script>
            <?php
        });
    }

    private function show_success_toast($title, $message) {
        add_action('admin_footer', function() use ($title, $message) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                showToast('<?php echo esc_js($title); ?>', '<?php echo esc_js($message); ?>', 'success');
            });
            </script>
            <?php
        });
    }

    public function configure_webhook($webhook_url)
    {
        try {
            // Verificar se temos a chave PIX
            if (empty($this->pix_key)) {
                throw new Exception('Chave PIX não configurada');
            }

            $data = [
                'webhookUrl' => $webhook_url
            ];

            // Usar o endpoint correto com a chave PIX
            $response = $this->make_request('/webhook/' . $this->pix_key, 'PUT', $data);

            if ($response['code'] !== 200) {
                throw new Exception($response['message'] ?? 'Erro ao configurar webhook');
            }

            // $this->show_success_toast('Webhook Configurado', 'Webhook configurado com sucesso');
            $this->show_success_toast('Webhook Configurado', $response['code']);
            return $response;
        } catch (Exception $e) {
            $this->show_error_toast('Erro na Configuração', $e->getMessage());
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function create_payment($data) {
        try {
            $response = $this->make_request('/cob', 'POST', $data);

            if ($response['code'] < 200 || $response['code'] >= 300) {
                return [
                    'code' => $response['code'],
                    'message' => $response['message'] . $response['code'],
                    'data' => $response
                ];
            }

            $this->show_success_toast('Pagamento Criado', 'Pagamento criado com sucesso');
            return $response;
        } catch (Exception $e) {
            $this->show_error_toast('Erro no Pagamento', $e->getMessage());
            return array(
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            );
        }
    }

    public function get_payment_status($txid) {
        try {
            $response = $this->make_request('/cob/' . $txid, 'GET');

            if ($response['code'] !== 200) {
                throw new Exception($response['message']);
            }

            return $response['data'];
        } catch (Exception $e) {
            $this->show_error_toast('Erro na Consulta', $e->getMessage());
            return null;
        }
    }

    public function validate_credentials() {
        try {
            $response = $this->make_request('/validate', 'GET');

            if ($response['code'] !== 200) {
                throw new Exception($response['message']);
            }

            $this->show_success_toast('Credenciais Válidas', 'Credenciais validadas com sucesso');
            return true;
        } catch (Exception $e) {
            $this->show_error_toast('Credenciais Inválidas', 'Verifique suas credenciais');
            return false;
        }
    }
} 