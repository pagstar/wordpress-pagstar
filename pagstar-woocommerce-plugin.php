<?php
/**
 * Plugin Name: Plugin de pix Pagstar
 * Description: Plugin pix para WooCommerce usando a API Pagstar.
 * Version: 1.0
 * Author: Pagstar
 * Plugin URI: https://pagstar.com/
 * Text Domain: plugin-pagstar
 */

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

// Include the main class file after WooCommerce is loaded
add_action('plugins_loaded', 'pagstar_init_gateway');

function pagstar_init_gateway()
{
    include_once(plugin_dir_path(__FILE__) . '/class-pagstar-gateway.php');
}


// Adicione a opção de pagamento Pagstar ao WooCommerce
function adicionar_gateway_fakepay($gateways)
{
    $gateways[] = 'WC_Gateway_FakePay';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'adicionar_gateway_fakepay');







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
        'manage_options',
        'pagstar-settings',
        'pagstar_settings_page'
    );
}

add_action('admin_menu', 'pagstar_admin_menu');

// Função para backup automático de configurações
function pagstar_backup_settings($settings) {
    $backup_dir = WP_CONTENT_DIR . '/pagstar_backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0700, true);
    }

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
    if (!is_writable($dir)) {
        return new WP_Error('directory_not_writable', 'O diretório não tem permissões de escrita');
    }
    
    $perms = substr(sprintf('%o', fileperms($dir)), -4);
    if ($perms !== '0700') {
        return new WP_Error('invalid_permissions', 'Permissões do diretório devem ser 0700');
    }
    
    return true;
}

// Função para sanitizar nome de arquivo
function pagstar_sanitize_filename($filename) {
    $filename = sanitize_file_name($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
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
        .pagstar-settings input[type="text"] {
            width: 100%;
            max-width: 400px;
        }
        .pagstar-settings .section-title {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2271b1;
        }
        .pagstar-settings .cert-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
        }
        .cert-valid {
            background: #d4edda;
            color: #155724;
        }
        .cert-invalid {
            background: #f8d7da;
            color: #721c24;
        }
        .pagstar-settings .help-text {
            color: #666;
            font-style: italic;
            margin-top: 5px;
            display: block;
        }
        .pagstar-settings .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }
    </style>
    <?php

    // Verificar status dos certificados
    $crt_path = get_option('pagstar_crt');
    $key_path = get_option('pagstar_key');
    $crt_exists = file_exists($crt_path);
    $key_exists = file_exists($key_path);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('pagstar_settings_nonce', 'pagstar_nonce')) {
        $errors = [];
        $success = true;

        // Validação dos campos
        if (empty($_POST['client_id'])) {
            $errors[] = 'Client ID é obrigatório';
            $success = false;
        }
        if (empty($_POST['client_secret'])) {
            $errors[] = 'Client Secret é obrigatório';
            $success = false;
        }
        if (empty($_POST['pix_key'])) {
            $errors[] = 'Chave PIX é obrigatória';
            $success = false;
        }
        if (empty($_POST['user_agent'])) {
            $errors[] = 'Empresa/Contato é obrigatório';
            $success = false;
        }
        if (empty($_POST['link_r']) || !filter_var($_POST['link_r'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL de redirecionamento inválida';
            $success = false;
        }

        if ($success) {
            // Preparar configurações para backup
            $settings = array(
                'client_id' => sanitize_text_field($_POST['client_id']),
                'client_secret' => sanitize_text_field($_POST['client_secret']),
                'pix_key' => sanitize_text_field($_POST['pix_key']),
                'user_agent' => sanitize_text_field($_POST['user_agent']),
                'link_r' => esc_url_raw($_POST['link_r']),
                'payment_info' => sanitize_textarea_field($_POST['payment_info']),
                'expiration_time' => intval($_POST['expiration_time'])
            );

            // Fazer backup antes de atualizar
            $backup_file = pagstar_backup_settings($settings);
            if (is_wp_error($backup_file)) {
                $errors[] = 'Erro ao criar backup das configurações';
                $success = false;
            }

            // Atualizar configurações
            foreach ($settings as $key => $value) {
                update_option('pagstar_' . $key, $value);
            }

            $upload_dir = ABSPATH . 'certificados_pagstar/';
            
            // Verificar permissões do diretório
            $dir_perms = pagstar_validate_directory_permissions($upload_dir);
            if (is_wp_error($dir_perms)) {
                $errors[] = $dir_perms->get_error_message();
                $success = false;
            }

            // Inicializar finfo para validação de MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            // Upload CRT
            if (!empty($_FILES['pagstar_crt']['tmp_name'])) {
                // Validação de tamanho máximo (5KB)
                if ($_FILES['pagstar_crt']['size'] > 5120) {
                    $errors[] = 'O arquivo CRT excede o tamanho máximo permitido de 5KB';
                    $success = false;
                } else {
                    $file_info = pathinfo($_FILES['pagstar_crt']['name']);
                    if (strtolower($file_info['extension']) !== 'crt') {
                        $errors[] = 'O arquivo CRT deve ter a extensão .crt';
                        $success = false;
                    } else {
                        $mime = $finfo->file($_FILES['pagstar_crt']['tmp_name']);
                        $allowed_crt_types = ['application/x-x509-ca-cert', 'application/pkix-cert', 'application/x-pem-file', 'text/plain'];
                        if (in_array($mime, $allowed_crt_types)) {
                            $safe_filename = pagstar_sanitize_filename($_FILES['pagstar_crt']['name']);
                            $crt_path = $upload_dir . $safe_filename;
                            move_uploaded_file($_FILES['pagstar_crt']['tmp_name'], $crt_path);
                            update_option('pagstar_crt', $crt_path);
                        } else {
                            $errors[] = 'Arquivo CRT inválido';
                            $success = false;
                        }
                    }
                }
            }

            // Upload KEY
            if (!empty($_FILES['pagstar_key']['tmp_name'])) {
                // Validação de tamanho máximo (5KB)
                if ($_FILES['pagstar_key']['size'] > 5120) {
                    $errors[] = 'O arquivo KEY excede o tamanho máximo permitido de 5KB';
                    $success = false;
                } else {
                    $file_info = pathinfo($_FILES['pagstar_key']['name']);
                    if (strtolower($file_info['extension']) !== 'key') {
                        $errors[] = 'O arquivo KEY deve ter a extensão .key';
                        $success = false;
                    } else {
                        $mime = $finfo->file($_FILES['pagstar_key']['tmp_name']);
                        $allowed_key_types = ['application/x-pem-file', 'text/plain'];
                        if (in_array($mime, $allowed_key_types)) {
                            $safe_filename = pagstar_sanitize_filename($_FILES['pagstar_key']['name']);
                            $key_path = $upload_dir . $safe_filename;
                            move_uploaded_file($_FILES['pagstar_key']['tmp_name'], $key_path);
                            update_option('pagstar_key', $key_path);
                        } else {
                            $errors[] = 'Arquivo KEY inválido';
                            $success = false;
                        }
                    }
                }
            }

            if ($success) {
                echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso! Backup criado em: ' . esc_html($backup_file) . '</p></div>';
            }
        }

        if (!empty($errors)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Erro:</strong> ' . implode('<br>', $errors) . '</p></div>';
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
                               value="<?php echo esc_attr(get_option('client_id')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">ID do cliente fornecido pela Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="client_secret" class="required-field">Client Secret:</label></th>
                    <td>
                        <input type="text" name="client_secret" id="client_secret" 
                               value="<?php echo esc_attr(get_option('client_secret')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Chave secreta do cliente fornecida pela Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="pix_key" class="required-field">Chave PIX:</label></th>
                    <td>
                        <input type="text" name="pix_key" id="pix_key" 
                               value="<?php echo esc_attr(get_option('pix_key')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Chave PIX cadastrada na Pagstar</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="user_agent" class="required-field">Empresa/Contato:</label></th>
                    <td>
                        <input type="text" name="user_agent" id="user_agent" 
                               value="<?php echo esc_attr(get_option('pagstar_user_agent')); ?>" 
                               class="regular-text" required>
                        <span class="help-text">Nome da empresa ou contato responsável</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="link_r" class="required-field">URL de Redirecionamento:</label></th>
                    <td>
                        <input type="url" name="link_r" id="link_r" 
                               value="<?php echo esc_attr(get_option('link_r')); ?>" 
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
                        <?php if ($crt_exists): ?>
                            <span class="cert-status cert-valid">Certificado instalado</span>
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
                    <th><label for="expiration_time">Tempo de Expiração:</label></th>
                    <td>
                        <input type="number" name="expiration_time" id="expiration_time" 
                               value="<?php echo esc_attr(get_option('pagstar_expiration_time', 3600)); ?>" 
                               min="300" max="86400" step="60" class="regular-text">
                        <span class="help-text">Tempo em segundos para expiração do QR Code (mínimo: 5 minutos, máximo: 24 horas)</span>
                    </td>
                </tr>
            </table>

            <?php submit_button('Salvar Configurações', 'primary', 'submit', true, array('id' => 'submit-pagstar-settings')); ?>
        </form>
    </div>
    <?php
}

