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
    private $base_url = 'https://api.pagstar.com.br/v1';
    private $crt_path;
    private $key_path;

    public function __construct() {
        $this->client_id = get_option('pagstar_client_id');
        $this->client_secret = get_option('pagstar_client_secret');
        $this->crt_path = get_option('pagstar_crt');
        $this->key_path = get_option('pagstar_key');
    }

    private function make_request($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ),
            'timeout' => 30,
            'sslverify' => true
        );

        if ($data) {
            $args['body'] = json_encode($data);
        }

        // Adicionar certificados se existirem
        if ($this->crt_path && file_exists($this->crt_path)) {
            $args['sslcert'] = $this->crt_path;
        }
        if ($this->key_path && file_exists($this->key_path)) {
            $args['sslkey'] = $this->key_path;
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->show_error_toast('Erro na requisição', $error_message);
            return array(
                'code' => 500,
                'message' => $error_message,
                'data' => null
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Erro desconhecido';
            $this->show_error_toast('Erro na API', $error_message);
        }

        return array(
            'code' => $response_code,
            'message' => isset($response_data['message']) ? $response_data['message'] : '',
            'data' => $response_data
        );
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

    public function configure_webhook($webhook_url) {
        try {
            $response = $this->make_request('/webhooks', 'POST', array(
                'url' => $webhook_url,
                'events' => array('payment.created', 'payment.updated')
            ));

            if ($response['code'] === 200) {
                $this->show_success_toast('Webhook Configurado', 'Webhook configurado com sucesso');
            }

            return $response;
        } catch (Exception $e) {
            $this->show_error_toast('Erro na Configuração', $e->getMessage());
            return array(
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            );
        }
    }

    public function create_payment($order_id, $amount, $pix_key) {
        try {
            $response = $this->make_request('/payments', 'POST', array(
                'order_id' => $order_id,
                'amount' => $amount,
                'pix_key' => $pix_key,
                'payment_method' => 'pix'
            ));

            if ($response['code'] === 200) {
                $this->show_success_toast('Pagamento Criado', 'Pagamento criado com sucesso');
            }

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

    public function get_payment_status($payment_id) {
        try {
            $response = $this->make_request('/payments/' . $payment_id);

            if ($response['code'] === 200) {
                return $response['data'];
            }

            return null;
        } catch (Exception $e) {
            $this->show_error_toast('Erro na Consulta', $e->getMessage());
            return null;
        }
    }

    public function validate_credentials() {
        try {
            $response = $this->make_request('/auth/validate');

            if ($response['code'] === 200) {
                $this->show_success_toast('Credenciais Válidas', 'Credenciais validadas com sucesso');
                return true;
            }

            $this->show_error_toast('Credenciais Inválidas', 'Verifique suas credenciais');
            return false;
        } catch (Exception $e) {
            $this->show_error_toast('Erro na Validação', $e->getMessage());
            return false;
        }
    }
} 