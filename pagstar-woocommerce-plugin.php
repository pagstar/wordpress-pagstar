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

// Página de configurações do Pagstar
function pagstar_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        update_option('client_id', sanitize_text_field($_POST['client_id']));
        update_option('client_secret', sanitize_text_field($_POST['client_secret']));
        update_option('pix_key', sanitize_text_field($_POST['pix_key']));
        update_option('pagstar_user_agent', sanitize_text_field($_POST['user_agent']));
        update_option('link_r', sanitize_text_field($_POST['link_r']));
        update_option('pagstar_mode', 'production');

        $upload_dir = ABSPATH . 'certificados_pagstar/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0700, true);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        // Upload CRT
        if (!empty($_FILES['pagstar_crt']['tmp_name'])) {
            $mime = $finfo->file($_FILES['pagstar_crt']['tmp_name']);
            $allowed_crt_types = ['application/x-x509-ca-cert', 'application/pkix-cert', 'application/x-pem-file', 'text/plain'];
            if (in_array($mime, $allowed_crt_types)) {
                $crt_path = $upload_dir . 'certificado.crt';
                move_uploaded_file($_FILES['pagstar_crt']['tmp_name'], $crt_path);
                update_option('pagstar_crt', $crt_path);
            } else {
                echo '<div class="notice notice-error"><p>Arquivo CRT inválido.</p></div>';
            }
        }

        // Upload KEY
        if (!empty($_FILES['pagstar_key']['tmp_name'])) {
            $mime = $finfo->file($_FILES['pagstar_key']['tmp_name']);
            $allowed_key_types = ['application/x-pem-file', 'text/plain'];
            if (in_array($mime, $allowed_key_types)) {
                $key_path = $upload_dir . 'chave.key';
                move_uploaded_file($_FILES['pagstar_key']['tmp_name'], $key_path);
                update_option('pagstar_key', $key_path);
            } else {
                echo '<div class="notice notice-error"><p>Arquivo KEY inválido.</p></div>';
            }
        }

        echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
    }
    ?>

    <div class="wrap">
        <h1>Pagstar Settings</h1>
        <form method="post" action="" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="client_id">Client ID:</label></th>
                    <td><input type="text" name="client_id" id="client_id" value="<?php echo esc_attr(get_option('client_id')); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="client_secret">Client Secret:</label></th>
                    <td><input type="text" name="client_secret" id="client_secret" value="<?php echo esc_attr(get_option('client_secret')); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="pix_key">Pix Key:</label></th>
                    <td><input type="text" name="pix_key" id="pix_key" value="<?php echo esc_attr(get_option('pix_key')); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="user_agent">Empresa/Contato:</label></th>
                    <td><input type="text" name="user_agent" id="user_agent" value="<?php echo esc_attr(get_option('pagstar_user_agent')); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="link_r">Link de redirecionamento após pagamento:</label></th>
                    <td><input type="text" name="link_r" id="link_r" value="<?php echo esc_attr(get_option('link_r')); ?>" class="regular-text" required></td>
                </tr>
                <h1>MTLS Certificates (Seguros)</h1>
                <tr>
                    <th><label for="pagstar_crt">Arquivo CRT (.crt):</label></th>
                    <td><input type="file" name="pagstar_crt" id="pagstar_crt" accept=".crt" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="pagstar_key">Arquivo KEY (.key):</label></th>
                    <td><input type="file" name="pagstar_key" id="pagstar_key" accept=".key" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

