<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Stelo_Gateway extends WC_Payment_Gateway {
	private $api;

	public function __construct() {
		$this->id = 'stelo';
		$this->method_title = __( 'Stelo', 'woocommerce-stelo' );
		$this->method_description = __( 'Integrate WooCommerce with Stelo payment method', 'woocommerce-stelo' );
		$this->title = __( 'Stelo', 'woocommerce-stelo' );
		$this->icon = STELO_URI . 'assets/images/icon.png';
		$this->has_fields = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->api = new Stelo_API( $this->settings );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'woocommerce_api_wc_stelo_gateway', array( $this, 'ipn_handler' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		add_filter( 'woocommerce_form_field_multiselect', array( $this, 'multiselect' ), 10, 4 );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	}

	public function ipn_handler() {
		$postData = file_get_contents( "php://input" );

		if ( !empty( $postData ) ) {
			global $wpdb;

			$postdata = json_decode( $postData );

			$stelo_id = sanitize_text_field( $postdata->steloId );
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM " . $wpdb->prefix . "stelo_gateway WHERE stelo_id = '%s'", $stelo_id ) );
			$order_id = intval( $order_id );

			if ( $order_id ) {
				$response = $this->api->call( '/ec/V1/orders/transactions/' . $stelo_id, array(), 'GET' );

				if ( $response ) {
					$this->api->update_order_status( $order_id, $response['steloStatus'] );

				}
			}

			wp_die( esc_html__( 'Stelo Request Authorized', 'woocommerce-stelo' ), esc_html__( 'Stelo Request Authorized', 'woocommerce-stelo' ), array(
				'response' => 200
			) );
		}

		wp_die( esc_html__( 'Stelo Request Unauthorized', 'woocommerce-stelo' ), esc_html__( 'Stelo Request Unauthorized', 'woocommerce-stelo' ), array(
			'response' => 401
		) );
	}

	public function multiselect( $field, $key, $args, $value ) {
		if ( ! empty( $args['options'] ) ) {
			$field = stelo_get_template( 'multiselect.php', array(
				'field' => $field,
				'key' => $key,
				'args' => $args,
				'value' => $value
			), TRUE );
		}

		return $field;
	}

	public function load_scripts() {
		wp_register_style( 'stelo-gateway-css', STELO_URI . 'assets/css/stelo-gateway.css', array(), time() );

		if ( $this->get_option('type') == 'transparent' ) {
			wp_register_script( 'stelo-register-card', 'https://carteirac1.hml.stelo.com.br/static/js/component/register-card-post.js', array('jquery'), time(), true );

			wp_enqueue_script( 'stelo-register-card' );
		}

		if ( $this->get_option( 'type' ) == 'wallet' ) {
			wp_register_style( 'stelo-lytebox-css', STELO_URI . 'assets/js/fancybox/source/jquery.fancybox.css', array(), time() );
			wp_register_script( 'stelo-lytebox-js', STELO_URI . 'assets/js/fancybox/source/jquery.fancybox.js', array('jquery'), time() );

			wp_enqueue_style( 'stelo-lytebox-css' );
			wp_enqueue_script( 'stelo-lytebox-js' );
		}

		wp_register_script( 'mask-js', STELO_URI . 'assets/js/jquery.mask.min.js', array('jquery'), time(), true );
		wp_register_script( 'stelo-gateway-js', STELO_URI . 'assets/js/stelo-gateway.js', array('jquery'), time(), true );

		wp_enqueue_script( 'mask-js' );
		wp_enqueue_style( 'stelo-gateway-css' );
		wp_enqueue_script( 'stelo-gateway-js' );
	}

	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( $this->get_option( 'type' ) == 'transparent' ) {
			$response = $this->api->call( '/ec/V1/subacquirer/transactions/', $this->api->get_invoice_data( $order, $_POST ) );
		} elseif ( $this->get_option( 'type' ) == 'wallet' ) {
			$response = $this->api->call( '/ec/V1/wallet/transactions/', $this->api->get_invoice_data( $order, $_POST, 'wallet' ) );
		}

		if ( !empty( $response['orderData'] ) ) {
			$this->api->register_transaction( $order_id, $response['orderData']['orderId'] );

			$order->add_order_note( sprintf( "Transação de pagamento iniciada com código de transação: %s e pedido número: %s. Aguardando processamento.", $response['orderData']['tid'], $response['orderData']['orderId'] ), false );

			if ( !empty( $response['bankSlipURL'] ) ) {
				update_post_meta( $order_id, '_bankSlipUrl', $response['bankSlipURL'] );

				$order->add_order_note( sprintf( "A transação gerou um boleto para pagamento, o mesmo será processado após pagamento do boleto no seguinte link: %s", $response['bankSlipURL'] ), true );
			}

			if ( !empty( $response['urlWallet'] ) ) {
				update_post_meta( $order_id, '_urlWallet', $response['urlWallet'] );

				$order->add_order_note( sprintf( "A transação gerou um link para pagamento pelo Carteira Digital Stelo no seguinte link: %s", $response['urlWallet'] ), true );
			}

			return array(
				'result' => 'success',
				'redirect' => ( $this->get_option( 'type' ) == 'transparent' ) ? $this->get_return_url( $order ) : $order->get_checkout_payment_url( true )
			);
		}

		return array(
			'result' => 'fail',
			'redirect' => ''
		);
	}

	public function thankyou_page( $order_id ) {
		$bankSlipURL = get_post_meta( $order_id, '_bankSlipUrl', true );

		if ( !empty( $bankSlipURL ) ) {
			stelo_get_template( 'thankyou.php', array(
				'bankSlipURL' => $bankSlipURL
			) );
		}
	}

	public function receipt_page( $order_id ) {
		$order = new WC_Order( $order_id );

		wp_localize_script( 'stelo-gateway-js', 'stelo_params', array(
			'methodType' => 'wallet',
			'checkout_url' => $order->get_checkout_order_received_url(),
			'payment_url' => get_post_meta( $order_id, '_urlWallet', true )
		));

		stelo_get_template( 'receipt_page.php', array(
			'payment_url' => get_post_meta( $order_id, '_urlWallet', true ),
			'cancel_order_url' => $order->get_cancel_order_url(),
		));
	}

	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		if ( 'transparent' == $this->get_option('type') ) {
			wp_enqueue_script( 'wc-credit-card-form' );

			$idUnico = $this->api->get_unique_id();

			wp_localize_script( 'stelo-gateway-js', 'stelo_params', array(
				'idUnico' => $idUnico,
				'client_id' => $this->get_option( 'client_id' ),
				'token_url' => $this->api->get_url() . '/security/v1/cards/tokens',
				'transfer_block' => stelo_get_template( 'transfer-block.php', array(
					'idUnico' => $idUnico
				), TRUE ),
				'registerCardError' => stelo_register_card_error(),
				'methodType' => 'transparent'
			) );

			stelo_get_template( 'transparent-checkout.php', array(
				'idUnico' => $idUnico,
				'methods' => $this->get_option( 'methods' ),
				'boleto_brand' => STELO_URI . 'assets/images/bandeiras-boleto-stelo.png',
				'credit_card_brand' => STELO_URI . 'assets/images/bandeiras-cartao-stelo.png'
			) );
		}

		if ( 'wallet' == $this->get_option('type') ) {
			wp_localize_script( 'stelo-gateway-js', 'stelo_params', array(
				'methodType' => 'wallet'
			) );

			stelo_get_template( 'wallet-checkout.php' );
		}
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'notification_url' => array(
				'title'     => __( 'Notification URL', 'woocommerce-stelo' ),
				'label'     => __( 'Notification URL', 'woocommerce-stelo' ),
				'type'      => 'text',
				'desc_tip'  => __( 'Copy this URL and paste it in the Stelo admin panel in order to receive automatic updates on the payment status', 'woocommerce-stelo' ),
				'default'   => home_url( '/' ) . '?wc-api=wc_stelo_gateway',
				'custom_attributes' => array(
					'readonly' => 'readonly'
				)
			),
			'enabled' => array(
				'title'		=> __( 'Enabled / Disabled', 'woocommerce-stelo' ),
				'label'		=> __( 'Enable this payment method', 'woocommerce-stelo' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'woocommerce-stelo' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'The title your customer will see on checkout.', 'woocommerce-stelo' ),
				'default'	=> __( 'Stelo', 'woocommerce-stelo' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'woocommerce-stelo' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'The description the customer will see during checkout.', 'woocommerce-stelo' ),
				'default'	=> __( 'Pay with confidence using the Stelo payment system.', 'woocommerce-stelo' ),
				'css'		=> 'max-width:350px;'
			),
			'client_id' => array(
				'title'		=> __( 'Client ID', 'woocommerce-stelo' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the "Client ID" received from Stelo and will be necessary to create the payment transaction.', 'woocommerce-stelo' ),
			),
			'client_secret' => array(
				'title'		=> __( 'Client Secret', 'woocommerce-stelo' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the "Client Secret" received from Stelo and will be necessary to create the payment transaction', 'woocommerce-stelo' ),
			),
			'environment' => array(
				'title'		=> __( 'Enviroment', 'woocommerce-stelo' ),
				'label'		=> __( 'Sandbox', 'woocommerce-stelo' ),
				'type'		=> 'checkbox',
				'description' => __( 'Put the extenstion in "Sandbox" mode for testing purposes.', 'woocommerce-stelo' ),
				'default'	=> 'no',
			),
			'type' => array(
				'title' => __( 'Checkout method', 'woocommerce-stelo' ),
				'type' => 'select',
				'options' => array(
					'transparent' => __( 'Transparent Checkout', 'woocommerce-stelo' ),
					'wallet' => __( 'Carteira Digital', 'woocommerce-stelo' )
				),
				'desc_tip' => __( 'Choose how you would like to have the customer checkout.', 'woocommerce-stelo' ),
				'default' => 'transparent'
			),
			'methods' => array(
				'title' => __( 'Payment methods', 'woocommerce-stelo' ),
				'type' => 'multiselect',
				'options' => array(
					'credit_card' => __( 'Credit Card', 'woocommerce-stelo' ),
					'bankslip' => __( 'Bankslip', 'woocommerce-stelo' )
				),
				'desc_tip' => __( 'Select the payment methods that will be used for the transparent checkout method.', 'woocommerce-stelo' ),
			),
			'bankslip_status' => array(
				'title' => __('Status for bankslip', 'woocommerce-stelo'),
				'type' => 'select',
				'options' =>wc_get_order_statuses(),
				'desc_tip' => __( 'Select what status the order should go to when the customer selects the bankslip payment method', 'woocommerce-stelo' ),
				'default' => 'wc-processing'
			)
		);
	}
}