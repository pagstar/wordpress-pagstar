<?php
/**
 * Plugin Name: Pagstar
 * Plugin URI: https://pagstar.com.br
 * Description: Plugin de integração com a Pagstar para WordPress
 * Version: 1.0.6
 * Author: Pagstar
 * Author URI: https://pagstar.com.br
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pagstar
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * HPOS: true
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Declarar compatibilidade com HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Verificar se o WooCommerce está ativo
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', 'pagstar_woocommerce_not_active_notice');

    function pagstar_woocommerce_not_active_notice()
    {
        echo '<div class="error"><p>O Plugin de Pagamento Pagstar requer que o WooCommerce esteja ativo. Por favor, ative o WooCommerce.</p></div>';
    }

    return;
}

// Adiciona o campo "end2end" nos detalhes do pedido no admin
add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
    $end2end = $order->get_meta('_pagstar_end2end');
    $txid = $order->get_meta('_pagstar_txid');
    
    if ($end2end) {
        echo '<div class="form-field form-field-wide" style="margin-top: 15px;">
                <label>' . __('End2End ID', 'pagstar') . ':</label>
                <input type="text" readonly value="' . esc_html($end2end) . '" style="width: calc(100% - 12px); background-color: #f3f3f3; border: 1px solid #ddd; border-radius: 3px; padding: 8px;" />
              </div>';
    }

    if ($txid) {
        echo '<div class="form-field form-field-wide" style="margin-top: 15px;">
                <label>' . __('Txid', 'pagstar') . ':</label>
                <input type="text" readonly value="' . esc_html($txid) . '" style="width: calc(100% - 12px); background-color: #f3f3f3; border: 1px solid #ddd; border-radius: 3px; padding: 8px;" />
              </div>';
    }
});


require_once plugin_dir_path(__FILE__) . 'pagstar-api.php';

// Verificar versão do WooCommerce
function pagstar_check_wc_version() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    $wc_version = WC()->version;
    $required_version = '8.0.0';

    if (version_compare($wc_version, $required_version, '<')) {
        add_action('admin_notices', 'pagstar_wc_version_notice');
    }
}

function pagstar_wc_version_notice() {
    echo '<div class="error"><p>O Plugin de Pagamento Pagstar requer WooCommerce versão 8.0.0 ou superior. Por favor, atualize o WooCommerce.</p></div>';
}

add_action('plugins_loaded', 'pagstar_check_wc_version');

// Include the main class file after WooCommerce is loaded
add_action('plugins_loaded', 'pagstar_init_gateway', 0);

function pagstar_init_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once(plugin_dir_path(__FILE__) . '/class-pagstar-gateway.php');

    // Registra o método de pagamento
    add_filter('woocommerce_payment_gateways', function($gateways) {
        $gateways[] = 'WC_Pagstar_Gateway';
        return $gateways;
    });
}

add_action('rest_api_init', function () {
    register_rest_route('pagstar/v1', '/webhook', [
        'methods'  => 'POST',
        'callback' => 'pagstar_handle_webhook',
        'permission_callback' => '__return_true',
    ]);
});



// Adicionar link de configuração rápida
function pagstar_add_action_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=pagstar') . '">' . __('Configurações', 'pagstar') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pagstar_add_action_links');

// Adicionar a página de Extrato ao menu do admin
add_action('admin_menu', 'pagstar_add_extrato_page');

function pagstar_add_extrato_page() {
    add_menu_page(
        'Extrato Pagstar',     // Título da página
        'Extrato Pagstar',     // Nome do menu
        'manage_options',      // Capacidade necessária para acessar a página
        'pagstar-extrato',     // Slug da página
        'pagstar_render_extrato_page', // Função de callback para renderizar a página
        'dashicons-chart-area', // Ícone do menu (opcional)
        25                     // Posição do menu no painel (opcional)
    );
}

function pagstar_handle_webhook(WP_REST_Request $request) {
    $body = $request->get_json_params();

    // Verifica se os dados básicos estão presentes
    if (!isset($body['txid'])) {
        return new WP_REST_Response(['error' => 'Dados incompletos'], 400);
    }

    $txid = sanitize_text_field($body['txid']);

    // Encontra o pedido no WooCommerce
    // $order = wc_get_order($txid);

    global $wpdb;
    $table_name = $wpdb->prefix . 'webhook_transactions'; // Replace 'webhook_transactions' with your table name

    $transaction = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE transaction_id = %s",
            $txid
        )
    );

    if (!$transaction) {
        return new WP_REST_Response(['error' => 'Pedido não encontrado'], 404);
    }

    $order = wc_get_order($transaction->order_id);

    if (!$order) {
        return new WP_REST_Response(['error' => 'Pedido não encontrado'], 404);
    }

    $api = new Pagstar_API();

    $response = $api->get_payment_status($txid);

    if (!is_array($response) || !isset($response['status'])) {
        return new WP_REST_Response([
            'error' => 'Resposta inválida da API'
        ], 400);
    }


    // Ajuste conforme os possíveis status da Pagstar
    if ($response['status'] === 'CONCLUIDA') {

        $order->update_status('processing');
    
        $order->update_meta_data('_pagstar_end2end', $response['pix'][0]['endToEndId']);
        $order->update_meta_data('_pagstar_txid', $response['txid']);
        $order->save();

        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_transactions'; // Replace 'webhook_transactions' with your table name
        $wpdb->update(
        $table_name,
        [
            'status' => 'processing'
        ],
        [
            'transaction_id' => $txid
        ]
        );

        return new WP_REST_Response([
            'success' => 'Sucesso',
        ], 200);
    }
}

function pagstar_render_extrato_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'webhook_transactions';

    // Query para obter os dados do banco de dados
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    // Renderizar a página
    ?>
    <div class="wrap">
        <h1>Extrato Pagstar</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Id Pagstar</th>
                    <th>Pedido</th>
                    <th>Valor</th>
                    <th>Data</th>
                     <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->transaction_id; ?></td>
                        <td><?php echo $row->order_id; ?></td>
                        <td><?php echo 'R$ '.$row->order_value; ?></td>
                        <td><?php echo date('d/m/Y H:m:s', strtotime($row->created_at)); ?></td>
                        <td><?php echo$row->status; ?></td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function create_webhook_transactions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'webhook_transactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(255) NOT NULL,
        order_id INT NOT NULL,
        order_value DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'create_webhook_transactions_table' );

// Adicionar submenu no menu WooCommerce
function pagstar_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'Configurações do Pagstar',
        'Pagstar',
        'manage_woocommerce',
        'pagstar-settings',
        'pagstar_settings_page'
    );
}

add_action('admin_menu', 'pagstar_admin_menu');

add_action('http_api_curl', function($handle, $r, $url) {
    if (strpos($url, 'api.pagstar.com') === false) {
        return;
    }

    $crt = get_option('pagstar_cert_crt_path');
    $key = get_option('pagstar_cert_key_path');

    if ($crt && file_exists($crt)) {
        curl_setopt($handle, CURLOPT_SSLCERT, $crt);
    }

    if ($key && file_exists($key)) {
        curl_setopt($handle, CURLOPT_SSLKEY, $key);
    }
}, 10, 3);

// Função para backup automático de configurações
function pagstar_backup_settings($settings) {
    $backup_dir = WP_CONTENT_DIR . '/pagstar_backups/';
    wp_mkdir_p($backup_dir);

    $backup_file = $backup_dir . 'settings_backup_' . date('Y-m-d_H-i-s') . '.json';
    $backup_data = array(
        'timestamp' => current_time('mysql'),
        'settings' => $settings
    );

    file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
    return $backup_file;
}

// Função para validar permissões de diretório
function pagstar_validate_directory_permissions($dir) {
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    
    if (!is_writable($dir)) {
        chmod($dir, 0755);
        if (!is_writable($dir)) {
            return new WP_Error('directory_not_writable', 'O diretório não tem permissões de escrita');
        }
    }
    
    return true;
}

// Função para sanitizar nome de arquivo
function pagstar_sanitize_filename($filename) {
    $filename = sanitize_file_name($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

// Função para verificar a integridade do arquivo
function pagstar_verify_file_integrity($file_path) {
    if (!file_exists($file_path)) {
        return true; // Retorna true se o arquivo não existir ainda
    }
    return true; // Sempre retorna true, removendo a verificação de integridade
}

function pagstar_settings_page()
{
    // Verificação de capacidade mais específica
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'plugin-pagstar'));
    }

    // Adicionar estilos CSS
    ?>
    <style>
        /* Reset e estilos base */
        .pagstar-settings {
            max-width: 800px;
            margin: 20px auto;
        }

        .pagstar-settings .form-table {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pagstar-settings .form-table th {
            width: 200px;
            padding: 15px 10px;
        }

        .pagstar-settings .form-table td {
            padding: 15px 10px;
        }

        .pagstar-settings input[type="text"],
        .pagstar-settings input[type="url"],
        .pagstar-settings input[type="email"],
        .pagstar-settings input[type="number"],
        .pagstar-settings textarea {
            width: 100%;
            max-width: 400px;
            margin-bottom: 5px;
        }

        .pagstar-settings .help-text {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            line-height: 1.4;
        }

        .pagstar-settings .section-title {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2271b1;
        }

        /* Estilos do Toast/Snackbar moderno */
        .pagstar-toast {
            visibility: hidden;
            min-width: 350px;
            max-width: 450px;
            background-color: #fff;
            color: #333;
            text-align: left;
            border-radius: 12px;
            padding: 16px 24px;
            position: fixed;
            z-index: 999999;
            right: 30px;
            top: 80px;
            font-size: 14px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-left: 4px solid #2271b1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pagstar-toast .toast-header {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 15px;
        }

        .pagstar-toast .toast-body {
            color: #666;
            line-height: 1.4;
            font-size: 14px;
        }

        .pagstar-toast.success {
            border-left-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }

        .pagstar-toast.error {
            border-left-color: #f44336;
            background-color: rgba(244, 67, 54, 0.1);
        }

        .pagstar-toast.warning {
            border-left-color: #ff9800;
            background-color: rgba(255, 152, 0, 0.1);
        }

        .pagstar-toast.info {
            border-left-color: #2196F3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .pagstar-toast.show {
            visibility: visible;
            transform: translateX(0);
        }

        .pagstar-toast .icon {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .pagstar-toast.success .icon,
        .pagstar-toast.success .title {
            color: #4CAF50;
        }

        .pagstar-toast.error .icon,
        .pagstar-toast.error .title {
            color: #f44336;
        }

        .pagstar-toast.warning .icon,
        .pagstar-toast.warning .title {
            color: #ff9800;
        }

        .pagstar-toast.info .icon,
        .pagstar-toast.info .title {
            color: #2196F3;
        }

        /* Animações */
        @keyframes slideIn {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(120%);
                opacity: 0;
            }
        }

        /* Responsividade */
        @media screen and (max-width: 782px) {
            .pagstar-toast {
                right: 20px;
                top: 60px;
                min-width: 300px;
                max-width: calc(100% - 40px);
            }
        }

        /* Estilos do status dos certificados */
        .cert-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin: 4px 0;
            text-align: center;
            min-width: 120px;
        }

        .cert-status.valid {
            background-color: #e6f4ea;
            color: #1e7e34;
            border: 1px solid #1e7e34;
        }

        .cert-status.invalid {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #721c24;
        }

        .cert-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Estilo do grupo de botões */
        .button-group {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .button-group .button {
            margin: 0;
        }

        /* Estilo do botão de limpar */
        #clear-pagstar-settings {
            background-color: #dc3545 !important;
            color: white !important;
            border-color: #dc3545 !important;
            margin-left: 10px !important;
        }

        #clear-pagstar-settings:hover {
            background-color: #c82333 !important;
            border-color: #bd2130 !important;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Função para mostrar toast
        function showToast(title, message, type) {
            // Remover toasts existentes
            $('.pagstar-toast').remove();
            
            var icon = '';
            switch(type) {
                case 'success':
                    icon = 'dashicons-yes-alt';
                    break;
                case 'error':
                    icon = 'dashicons-warning';
                    break;
                case 'warning':
                    icon = 'dashicons-info';
                    break;
                case 'info':
                    icon = 'dashicons-info';
                    break;
            }

            var toast = $('<div class="pagstar-toast ' + type + '">' +
                '<div class="toast-header">' +
                '<span class="dashicons ' + icon + ' icon"></span>' +
                '<span class="title">' + title + '</span>' +
                '</div>' +
                '<div class="toast-body">' + message + '</div>' +
                '</div>');
            
            $('body').append(toast);
            
            setTimeout(function() {
                toast.addClass('show');
            }, 100);

            setTimeout(function() {
                toast.removeClass('show');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 5000);
        }

        // Verificar se há informações carregadas
        var hasClientId = $('#client_id').val().length > 0;
        var hasClientSecret = $('#client_secret').val().length > 0;
        var hasPixKey = $('#pix_key').val().length > 0;
        var hasLinkR = $('#link_r').val().length > 0;

        // Só mostrar toast de carregamento se não for uma submissão de formulário
        var isSubmit = window.location.href.includes('action=submit') || 
                      window.location.href.includes('_wpnonce=') ||
                      window.location.href.includes('pagstar_nonce=');

        //if (!isSubmit && (hasClientId || hasClientSecret || hasPixKey || hasLinkR || hasWebhookUrl)) {
        //    showToast('Sucesso', 'Configurações carregadas com sucesso', 'success');
        //}

        // Tratamento do formulário
        $('form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('input[type="submit"]');
            var formData = new FormData(this);
            
            submitButton.prop('disabled', true);
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        // Verificar se a resposta é HTML
                        if (response.trim().startsWith('<!DOCTYPE') || response.trim().startsWith('<html')) {
                            showToast('Erro', 'Erro ao processar resposta do servidor: Resposta inválida', 'error');
                            return;
                        }

                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        // Verificar se houve erro na configuração do webhook
                        if (data.success && data.webhook_error) {
                            showToast('Erro', 'Erro ao configurar webhook: ' + data.webhook_error, 'error');
                            return;
                        }

                        if (data.success) {
                            showToast('Sucesso', 'Configurações salvas com sucesso', 'success');
                            // Recarregar a página após 2 segundos
                            setTimeout(function() {
                                window.location.href = window.location.href.split('?')[0];
                            }, 2000);
                        } else {
                            showToast('Erro', data.data || 'Erro ao salvar configurações', 'error');
                        }
                    } catch (e) {
                        showToast('Erro', 'Erro ao processar resposta do servidor: ' + e.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'Erro ao salvar configurações';
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMessage = response.data || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText;
                        }
                    }
                    showToast('Erro', errorMessage, 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });
        });

        // Botão para limpar configurações
        $('#clear-pagstar-settings').on('click', function() {
            if (confirm('Tem certeza que deseja limpar todas as configurações? Esta ação não pode ser desfeita.')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pagstar_clear_settings',
                        nonce: '<?php echo wp_create_nonce("pagstar_clear_settings"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Sucesso', 'Configurações limpas com sucesso', 'success');
                            // Limpar campos do formulário
                            $('#client_id, #client_secret, #pix_key, #link_r').val('');
                            // Recarregar a página após 2 segundos
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showToast('Erro', response.data || 'Erro ao limpar configurações', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('Erro', 'Erro ao limpar configurações: ' + error, 'error');
                    }
                });
            }
        });
    });
    </script>
    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('pagstar_settings_nonce', 'pagstar_nonce')) {
        $errors = [];
        $success = true;

        try {
            // Validação dos campos
            if (empty($_POST['client_id'])) {
                throw new Exception('Client ID é obrigatório');
            }
            if (empty($_POST['client_secret'])) {
                throw new Exception('Client Secret é obrigatório');
            }
            if (empty($_POST['pix_key'])) {
                throw new Exception('Chave PIX é obrigatória');
            }
            if (empty($_POST['link_r']) || !filter_var($_POST['link_r'], FILTER_VALIDATE_URL)) {
                throw new Exception('URL de redirecionamento inválida');
            }

            $upload_dir = wp_upload_dir()['basedir'];

            // Upload do certificado .crt
            if (!empty($_FILES['pagstar_crt']['tmp_name'])) {
                $crt_path = $upload_dir . '/pagstar_cert.crt';
                if (move_uploaded_file($_FILES['pagstar_crt']['tmp_name'], $crt_path)) {
                    update_option('pagstar_cert_crt_path', $crt_path);
                } else {
                    echo '<div class="notice notice-error"><p>Erro ao salvar o certificado CRT.</p></div>';
                }
            }

            // Upload da chave .key
            if (!empty($_FILES['pagstar_key']['tmp_name'])) {
                $key_path = $upload_dir . '/pagstar_key.key';
                if (move_uploaded_file($_FILES['pagstar_key']['tmp_name'], $key_path)) {
                    update_option('pagstar_cert_key_path', $key_path);
                } else {
                    echo '<div class="notice notice-error"><p>Erro ao salvar a chave privada KEY.</p></div>';
                }
            }

            // Preparar configurações
            $settings = array(
                'pagstar_client_id' => sanitize_text_field($_POST['client_id']),
                'pagstar_client_secret' => sanitize_text_field($_POST['client_secret']),
                'pagstar_pix_key' => sanitize_text_field($_POST['pix_key']),
                'pagstar_link_r' => esc_url_raw($_POST['link_r']),
                'pagstar_company_name' => sanitize_text_field($_POST['company_name']),
                'pagstar_company_email' => sanitize_text_field($_POST['company_email'])
            );

            // Atualizar configurações
            foreach ($settings as $key => $value) {
                update_option($key, $value);
            }

            $api = new Pagstar_API();

            $api->configure_webhook(rest_url('pagstar/v1/webhook'));
            // Sempre retornar JSON e encerrar a execução
            wp_send_json_success('Configurações salvas com sucesso');
            exit;

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
            exit;
        }
    }
    ?>

    <div class="wrap pagstar-settings">
        <h1>Configurações Pagstar</h1>
        
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('pagstar_settings_nonce', 'pagstar_nonce'); ?>
            
            <div class="section-title">
                <h2>Credenciais da API (QR Codes)</h2>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="client_id" class="required-field">Client ID:</label></th>
                    <td>
                        <input type="text" name="client_id" id="client_id" 
                               value="<?php echo esc_attr(get_option('pagstar_client_id')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">ID do cliente fornecido pela Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="client_secret" class="required-field">Client Secret:</label></th>
                    <td>
                        <input type="text" name="client_secret" id="client_secret" 
                               value="<?php echo esc_attr(get_option('pagstar_client_secret')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Chave secreta do cliente fornecida pela Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="pix_key" class="required-field">Chave PIX:</label></th>
                    <td>
                        <input type="text" name="pix_key" id="pix_key" 
                               value="<?php echo esc_attr(get_option('pagstar_pix_key')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Chave PIX cadastrada na Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="link_r" class="required-field">URL de Redirecionamento:</label></th>
                    <td>
                        <input type="url" name="link_r" id="link_r" 
                               value="<?php echo esc_attr(get_option('pagstar_link_r')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">URL para onde o cliente será redirecionado após o pagamento</span>
                    </td>
                </tr>
            </table>

            <div class="section-title">
                <h2>Certificados MTLS</h2>
            </div>

            <table class="form-table">
                <tr>
                    <th><label for="pagstar_crt">Certificado CRT:</label></th>
                    <td>
                        <input type="file" name="pagstar_crt" id="pagstar_crt" accept=".crt">
                        <?php
                        // Verificar status dos certificados
                        $crt_path = get_option('pagstar_cert_crt_path');
                        $key_path = get_option('pagstar_cert_key_path');
                        
                        $crt_exists = !empty($crt_path) && file_exists($crt_path);
                        $key_exists = !empty($key_path) && file_exists($key_path);
                        
                        $crt_filename = $crt_exists ? basename($crt_path) : '';
                        $key_filename = $key_exists ? basename($key_path) : '';
                        ?>
                        <?php if ($crt_exists): ?>
                            <span class="cert-status cert-valid">Certificado instalado</span>
                            <div class="cert-info">Arquivo: <?php echo esc_html($crt_filename); ?></div>
                        <?php else: ?>
                            <span class="cert-status cert-invalid">Certificado não encontrado</span>
                        <?php endif; ?>
                        <span class="help-text">Arquivo de certificado (.crt) para autenticação MTLS</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="pagstar_key">Chave Privada:</label></th>
                    <td>
                        <input type="file" name="pagstar_key" id="pagstar_key" accept=".key">
                        <?php if ($key_exists): ?>
                            <span class="cert-status cert-valid">Chave instalada</span>
                            <div class="cert-info">Arquivo: <?php echo esc_html($key_filename); ?></div>
                        <?php else: ?>
                            <span class="cert-status cert-invalid">Chave não encontrada</span>
                        <?php endif; ?>
                        <span class="help-text">Arquivo de chave privada (.key) para autenticação MTLS</span>
                    </td>
                </tr>
            </table>

            <div class="section-title">
                <h2>Configurações Extras</h2>
            </div>

            <table class="form-table">
                <tr>
                    <th><label for="payment_info">Informações de Pagamento:</label></th>
                    <td>
                        <textarea name="payment_info" id="payment_info" rows="4" class="regular-text"><?php echo esc_textarea(get_option('pagstar_payment_info', 'Para realizar o pagamento via PIX:

            1. Abra o aplicativo do seu banco
            2. Escaneie o QR Code ou copie o código PIX
            3. Confirme os dados e finalize o pagamento
            4. O status do pedido será atualizado automaticamente')); ?></textarea>
                        <span class="help-text">Informações adicionais que serão exibidas durante o processo de pagamento</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="company_name" class="required-field">Nome da Empresa:</label></th>
                    <td>
                        <input type="text" name="company_name" id="company_name" 
                               value="<?php echo esc_attr(get_option('pagstar_company_name')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Nome da empresa que será exibido nas informações de pagamento</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="company_email" class="required-field">Email da Empresa:</label></th>
                    <td>
                        <input type="email" name="company_email" id="company_email" 
                               value="<?php echo esc_attr(get_option('pagstar_company_email')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Email da empresa para contato</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="expiration_time">Tempo de Expiração:</label></th>
                    <td>
                        <input type="number" name="expiration_time" id="expiration_time" 
                               value="<?php echo esc_attr(get_option('pagstar_expiration_time', 3600)); ?>" 
                               min="300" max="86400" step="60" class="regular-text">
                        <span class="help-text">Tempo em segundos para expiração do QR Code (mínimo: 5 minutos, máximo: 24 horas)</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="webhook_rate_limit">Limite de Requisições por Minuto:</label></th>
                    <td>
                        <input type="number" name="webhook_rate_limit" id="webhook_rate_limit" 
                               value="<?php echo esc_attr(get_option('pagstar_webhook_rate_limit', 100)); ?>" 
                               min="1" max="20000" class="regular-text">
                        <span class="help-text">Número máximo de requisições permitidas por minuto no webhook (padrão: 100, máximo: 20.000)</span>
                    </td>
                </tr>
            </table>

            <div class="button-group">
                <?php submit_button('Salvar Configurações', 'primary', 'submit', false, array('id' => 'submit-pagstar-settings')); ?>
                <button type="button" id="clear-pagstar-settings" class="button button-secondary" style="background-color: #dc3545; color: white; border-color: #dc3545; margin-left: 10px;">Limpar Configurações</button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Obtém a versão atual do plugin
 */
function pagstar_get_version() {
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
    return $plugin_data['Version'];
}

/**
 * Atualiza a versão do plugin
 */
function pagstar_update_version($new_version) {
    $plugin_file = __FILE__;
    $plugin_data = file_get_contents($plugin_file);
    
    // Atualiza a versão no cabeçalho do plugin
    $plugin_data = preg_replace(
        '/Version:\s*[\d\.]+/',
        'Version: ' . $new_version,
        $plugin_data
    );
    
    file_put_contents($plugin_file, $plugin_data);
    
    // Atualiza a versão nas opções do WordPress
    update_option('pagstar_version', $new_version);
}

/**
 * Atualiza o CHANGELOG.md com uma nova versão
 */
function pagstar_update_changelog($version, $changes) {
    $changelog_file = plugin_dir_path(__FILE__) . 'CHANGELOG.md';
    $current_content = file_get_contents($changelog_file);
    
    // Prepara o novo conteúdo
    $date = date('Y-m-d');
    $new_content = "## [$version] - $date\n\n";
    
    foreach ($changes as $type => $items) {
        if (!empty($items)) {
            $new_content .= "### $type\n";
            foreach ($items as $item) {
                $new_content .= "- $item\n";
            }
            $new_content .= "\n";
        }
    }
    
    // Insere o novo conteúdo após o cabeçalho
    $new_content .= $current_content;
    file_put_contents($changelog_file, $new_content);
}

/**
 * Adiciona informações de versão na página de configurações
 */
function pagstar_add_version_info() {
    $version = pagstar_get_version();
    ?>
    <div class="pagstar-version-info">
        <p>Versão do Plugin: <strong><?php echo esc_html($version); ?></strong></p>
        <p>Última atualização: <strong><?php echo esc_html(get_option('pagstar_last_update', 'N/A')); ?></strong></p>
    </div>
    <style>
        .pagstar-version-info {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #2271b1;
        }
        .pagstar-version-info p {
            margin: 5px 0;
        }
    </style>
    <?php
}

// Adiciona o hook para exibir a versão na página de configurações
add_action('pagstar_settings_before_form', 'pagstar_add_version_info');

require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/pagstar-api.php');

// Adicionar ação AJAX para atualizar o status do gateway
add_action('wp_ajax_pagstar_update_gateway_status', 'pagstar_update_gateway_status');
function pagstar_update_gateway_status() {
    check_ajax_referer('pagstar_update_gateway_status', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Permissão negada');
    }

    $enabled = sanitize_text_field($_POST['enabled'] ?? 'no');
    update_option('woocommerce_pagstar_settings', array('enabled' => $enabled));
    
    // Limpar cache do WooCommerce
    if (function_exists('wc_get_container')) {
        wc_delete_product_transients();;
    }
    
    wp_send_json_success('Status atualizado com sucesso');
}

// Adicionar ação AJAX para limpar configurações
add_action('wp_ajax_pagstar_clear_settings', 'pagstar_clear_settings');
function pagstar_clear_settings() {
    check_ajax_referer('pagstar_clear_settings', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Permissão negada');
    }

    // Lista de opções para remover
    $options = [
        'pagstar_client_id',
        'pagstar_client_secret',
        'pagstar_pix_key',
        'pagstar_link_r',
        'pagstar_webhook_url',
        'pagstar_cert_crt_path',
        'pagstar_cert_key_path'
    ];

    // Remover cada opção
    foreach ($options as $option) {
        delete_option($option);
    }

    // Limpar transients
    delete_transient('pagstar_access_token');

    wp_send_json_success('Configurações limpas com sucesso');
}

