<?php

class Tarlan_Payments_Gateway extends WC_Payment_Gateway
{
    const ID = 'tarlan_payments_gateway';
    public $merchant_id;
    public $project_id;
    public $secret_key;
    public $success_redirect_url;
    public $failure_redirect_url;
    public $is_test;
	public $test_merchant_id;
    public $test_project_id;
    public $test_secret_key;


    public function __construct()
    {
        $this->id = self::ID;
        $this->method_title = __('Tarlan Payments', 'tarlan-payments-gateway');
        $this->method_description = __('Tarlan Payments is your reliable partner for secure and convenient online payment processing. We guarantee fast transaction processing and high level of protection', 'tarlan-payments-gateway');
        $this->has_fields = false;
        $this->supports = [
            'products',
            'refunds',
        ];

        $this->title = $this->get_option('title');
        $this->merchant_id = $this->get_option('merchant_id');
        $this->project_id = $this->get_option('project_id');
        $this->secret_key = $this->get_option('secret_key');
        $this->success_redirect_url = $this->get_option('success_redirect_url');
        $this->failure_redirect_url = $this->get_option('failure_redirect_url');
		$this->test_merchant_id = $this->get_option('test_merchant_id');
        $this->test_project_id = $this->get_option('test_project_id');
        $this->test_secret_key = $this->get_option('test_secret_key');
        $this->is_test = $this->get_option('is_test') == 'yes';
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_payment-callback', array($this, 'callback_webhook'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'merchant_id' => array(
                'title' => __('Merchant ID', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('This ID is your merchant ID in the Tarlan Payments system', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'project_id' => array(
                'title' => __('Project ID', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('This ID is your project ID in the Tarlan Payments system', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('You can get this key during onboarding in Tarlan Payments', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'success_redirect_url' => array(
                'title' => __('Success redirect URL', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('The user will be automatically redirected to this Internet address upon successful payment', 'tarlan-payments-gateway'),
                'default' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]",
                'desc_tip' => true,
            ),
            'failure_redirect_url' => array(
                'title' => __('Failure redirect URL', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('The user will be automatically redirected to this Internet address if the payment is unsuccessful', 'tarlan-payments-gateway'),
                'default' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]",
                'desc_tip' => true,
            ),
			'test_merchant_id' => array(
                'title' => __('Test merchant ID', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('This ID is your merchant ID in the Tarlan Payments system', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'test_project_id' => array(
                'title' => __('Test project ID', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('This ID is your project ID in the Tarlan Payments system', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'test_secret_key' => array(
                'title' => __('Test secret Key', 'tarlan-payments-gateway'),
                'type' => 'text',
                'description' => __('You can get this key during onboarding in Tarlan Payments', 'tarlan-payments-gateway'),
                'default' => '',
                'desc_tip' => true
            ),
            'is_test' => array(
                'title' => __('Test mode', 'tarlan-payments-gateway'),
                'type' => 'checkbox',
                'label' => __('On/Off', 'tarlan-payments-gateway'),
                'default' => 'yes',
                'description' => __('In this mode, you can test the capabilities of the Tarlan Payments payment gateway', 'tarlan-payments-gateway')
            )
        );
    }

    public function getSign($array_data, $secret_key) {
        ksort($array_data);

        $sortedJson = json_encode($array_data, JSON_UNESCAPED_SLASHES);

        $base64EncodedData = base64_encode($sortedJson);

        $dataToSign = $base64EncodedData . $secret_key;

        $sign = hash("sha256", $dataToSign);

        return $sign;
    }

    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        try {
            $order = wc_get_order( $order_id );

            if ( ! $this->can_refund_order( $order ) ) {
                return new WP_Error( 'error', __( 'Refund failed', 'tarlan-payments-gateway' ) );
            }

            $array_data = [
                'amount' => floatval($amount),
                'transaction_id' => (int)$order->get_transaction_id()
            ];

			$secret_key = $this->secret_key;
			if ($this->is_test) {
				 $secret_key = $this->test_secret_key;
            }
			
            $sign = $this->getSign($array_data, $secret_key);

            $json_data = json_encode($array_data);

            $ctp_url = 'https://prapi.tarlanpayments.kz/refund/api/v1/system/refund/partial';

            if ($this->is_test) {
                $ctp_url = 'https://sandboxapi.tarlanpayments.kz/transaction/api/v1/system/refund/partial';
            }

            $ch = curl_init($ctp_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $sign
            ]);

            $response = curl_exec($ch);


            if (curl_errno($ch)) {
                return new WP_Error( 'error', __( 'Refund failed', 'tarlan-payments-gateway' ) );
            }

            curl_close($ch);
            $decoded_response = json_decode($response, true);
            if (isset($decoded_response['status']) && $decoded_response['status'] === true) {
                return true;
            } else {
                return new WP_Error( 'error', __( 'Refund failed', 'tarlan-payments-gateway' ) );
            }

            return false;
        } catch ( Exception $e ) {

            return new WP_Error( 'error', $e->getMessage() );
        }

        return false;
    }

    public function process_payment($order_id)
    {
        try {
            global $woocommerce;

			$order = new WC_Order($order_id);
            $project_reference_id = strval($order_id) . '_' . random_int(100000, 999999);;
			
            $amount_str = $order->get_total();
            $amount_fl = floatval($amount_str);

            $callback_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]?wc-api=payment-callback";

            $array_data = [
                'merchant_id' => intval($this->merchant_id),
                'project_id' => intval($this->project_id),
				'project_order_id' => strval($order_id),
                'success_redirect_url' => $this->success_redirect_url,
                'failure_redirect_url' => $this->failure_redirect_url,
                'callback_url' => $callback_url,
                'project_reference_id' => $project_reference_id,
                'description' => "wordpress woocommerce",
                'amount' => $amount_fl,
            ];
			
			$secret_key = $this->secret_key;
			if ($this->is_test) {
				 $array_data['merchant_id'] = intval($this->test_merchant_id);
				 $array_data['project_id'] = intval($this->test_project_id);
				 $secret_key = $this->test_secret_key;
            }
			
            $sign = $this->getSign($array_data, $secret_key);

            $json_data = json_encode($array_data);

            $order->update_status('pending', 'tarlan-payments-gateway');

			$ctp_url = 'https://prapi.tarlanpayments.kz/transaction/api/v1/transaction/primal/pay-in';

            if ($this->is_test) {
                $ctp_url = 'https://sandboxapi.tarlanpayments.kz/transaction/api/v1/transaction/primal/pay-in';
            }

            $ch = curl_init($ctp_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $sign
            ]);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_message = curl_error($ch);
                wc_add_notice($error_message, 'error' );
                return ['result' => 'failure', 'messages' => __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' )];
            }

            curl_close($ch);
            $decoded_response = json_decode($response, true);
            if (isset($decoded_response['status']) && $decoded_response['status'] === true) {
				//очищает корзину
				//$woocommerce->cart->empty_cart();
                return ['result' => 'success', 'redirect' => $decoded_response['result']];
            } else {
				var_dump($decoded_response);die;
				
                wc_add_notice( __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' ), 'error' );
                return ['result' => 'failure', 'messages' => __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' )];
            }
			

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } catch (Exception $e) {
            wc_add_notice( __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' ), 'error' );
            return ['result' => 'failure', 'messages' => __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' )];
        }

        wc_add_notice( __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' ), 'error' );
		
		return ['result' => 'failure', 'messages' => __( 'An error occurred when accepting payment', 'tarlan-payments-gateway' )];
    }

    public function callback_webhook() {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        if ($data && array_key_exists('project_reference_id', $data)) {
			$parts  = explode('_', $data['project_reference_id']);
			if (count($parts) != 2) {
				return;
			}
            $order = wc_get_order($parts[0]);
            if ($order && array_key_exists('status_code', $data)) {
                $statusCode = $data['status_code'];
                if ($statusCode == 'success') {
                    $order->set_transaction_id($data['transaction_id']);
                    $order->update_status('completed', 'tarlan-payments-gateway');
                } elseif ($statusCode == 'holded') {
                    $order->update_status('on-hold', 'tarlan-payments-gateway');
                } elseif ($statusCode == 'processed') {
                    $order->update_status('processing', 'tarlan-payments-gateway');
                } elseif ($statusCode == 'refund') {
                    $order->update_status('refunded', 'tarlan-payments-gateway');
                } elseif ($statusCode == 'failed') {
                    $order->update_status('failed', 'tarlan-payments-gateway');
                } elseif ($statusCode == 'canceled') {
                    $order->update_status('cancelled', 'tarlan-payments-gateway');
                } else {
                    $order->update_status('pending', 'tarlan-payments-gateway');
                }
            }
        }
    }
}

