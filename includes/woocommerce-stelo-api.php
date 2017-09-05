<?php
class Stelo_API {
	private $settings;
	private $idUnico;

	public function __construct($settings) {
		$this->settings = $settings;
	}

	public function get_url() {
		$url = !empty($this->settings['environment']) ? 'https://apic1.hml.stelo.com.br' : 'https://api.stelo.com.br';

		return $url;
	}

	public function call($endpoint, $data = array(), $method = 'POST') {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->get_url() . $endpoint);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $this->settings['client_id'] . ":" . $this->settings['client_secret']);

		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$response = json_decode(curl_exec($ch), true);

		curl_close($ch);

		return $response;
	}

	public function register_transaction($order_id, $stelo_id) {
		global $wpdb;

		$sql = sprintf("INSERT INTO %sstelo_gateway SET order_id = '%s', stelo_id = '%s', data_inserido = NOW()", $wpdb->prefix, $order_id, $stelo_id);

		$wpdb->query( $sql );
	}

	public function get_unique_id() {
		$this->idUnico = base64_encode(sprintf('%s:%s', $this->settings['client_id'], uniqid()));

		return $this->idUnico;
	}

	private function get_method($method) {
		if ($method == 'bankslip') {
			$method = 'bankSlip';
		} else {
			$method = 'credit';
		}

		return $method;
	}

	private function only_numbers($string) {
		return preg_replace('/[^0-9]/', '', $string);
	}

	protected function dateFormat($date){
		$date = date_create_from_format('d/m/Y', $date);

		return date_format($date, 'Y-m-d');
	}

	public function get_invoice_data(WC_Order $order, $posted = array(), $methodType = 'transparent') {
		$shipping_cost = 0;

		$payment = array (
			'amount' => $order->get_total(),
			'freight' => $shipping_cost,
			'currency' => get_woocommerce_currency(),
		);

		if ($methodType == 'transparent') {
			$payment['paymentType'] = $this->get_method($posted['stelo_payment_method']);

			if ($payment['paymentType'] == 'credit') {
				$payment['installment'] = $posted['stelo_card_installments'];
				$payment['cardData'] = array (
					'token' => $posted['stelo_card_token']
				);
			}
		}

		if ($methodType == 'wallet') {
			$payment['maxInstallment'] = 12;
		}

		$shipping_cost = $order->get_shipping_total();

		if ( 0 < $shipping_cost) {
			$payment['freight'] = number_format($shipping_cost, 2, '.', '');
		}

		$cart = array();

		foreach ( $order->get_items() as $order_item ) {
			if ( $order_item['qty'] ) {
				$item_total = $order->get_item_total( $order_item, false );

				if ( 0 > $item_total ) {
					continue;
				}

				$item_name = $order_item['name'];
				$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

				if ( $meta = $item_meta->display( true, true ) ) {
					$item_name .= ' - ' . $meta;
				}

				$cart[] = array(
					'productName' => $item_name,
					'productQuantity' => $order_item['qty'],
					'productPrice' => $item_total,
				);
			}
		}

		if ( 0 < sizeof( $order->get_taxes() ) ) {
			foreach ( $order->get_taxes() as $tax ) {
				$tax_total = number_format( $tax['tax_amount'] + $tax['shipping_tax_amount'], 2, '.', '' );

				if ( 0 > $tax_total ) {
					continue;
				}

				$cart[] = array(
					'productName' =>  $tax['label'],
					'productQuantity' => 1,
					'productPrice' => $tax_total,
				);
			}
		}

		if ( 0 < sizeof( $order->get_fees() ) ) {
			foreach ( $order->get_fees() as $fee ) {
				$fee_total = number_format( $fee['line_total'], 2, ',', '' );

				if ( 0 > $fee_total ) {
					continue;
				}

				$cart[] = array(
					'productName' =>  $fee['name'],
					'productQuantity' => 1,
					'productPrice' => $fee_total,
				);
			}
		}

		$payment['cartData'] = $cart;

		// Discount.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.3', '<' ) ) {
			if ( 0 < $order->get_order_discount() ) {
				$payment['amount'] -= $order->get_order_discount();
			}
		}

		$phones[] = array(
			'type' => 'CELL',
			'areaCode' => substr($this->only_numbers( $order->billing_phone ), 0, 2),
			'number' => substr($this->only_numbers( $order->billing_phone ), 2, strlen($this->only_numbers ( $order->billing_phone )))
		);

		$birthdate = '';

		if ($methodType == 'transparent') {
			$birthdate = ($payment['paymentType'] == 'bankSlip') ? $this->dateFormat($posted['stelo_bankslip_birth_date']) : $this->dateFormat($posted['stelo_card_holder_birth_date']);
		}

		if ($methodType == 'wallet') {
			$birthdate = $this->dateFormat($posted['stelo_wallet_birth_date']);
		}

		$customer = array(
			'customerIdentity' => $this->only_numbers(($order->billing_persontype == 2) ? $order->billing_cnpj : $order->billing_cpf),
			'customerName' => $order->billing_first_name . ' ' . $order->billing_last_name,
			'customerEmail' => $order->billing_email,
			'birthDate' => $birthdate,
			'phoneData' => $phones,
			'billingAddress' => array(
				'street' => $order->billing_address_1,
				'number' => $order->billing_number,
				'complement' => $order->billing_address_2,
				'neighborhood' => $order->billing_neighborhood,
				'zipCode' => $this->only_numbers( $order->billing_postcode ),
				'city' => $order->billing_city,
				'state' => $order->billing_state,
				'country' => isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country
			),
			'shippingAddress' => array(
				'street' => $order->billing_address_1,
				'number' => $order->billing_number,
				'complement' => $order->billing_address_2,
				'neighborhood' => $order->billing_neighborhood,
				'zipCode' => $this->only_numbers( $order->billing_postcode ),
				'city' => $order->billing_city,
				'state' => $order->billing_state,
				'country' => isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country
			)
		);

		$orderData = array(
			'shippingBehavior' => "default",
			'orderId' => $order->get_order_number()
		);

		if ($methodType == 'transparent') {
			$orderData['secureCode'] = $posted['stelo_card_idUnico'];
		}

		if ($methodType == 'wallet') {
			$orderData['platformId'] = 'WooCommerce';
			$orderData['transactionType'] = 'w';
			$orderData['country'] = 'BR';
		}

		$data = array (
			'orderData'		=>	$orderData,
			'paymentData'	=>	$payment,
			'customerData'	=>	$customer,
		);

		$data = apply_filters ( 'stelo_woocommerce_invoice_data', $data );

		return $data;
	}

	public function update_order_status($order_id, $stelo_status) {
		$order = new WC_Order ( $order_id );
		$stelo_status_code = strtolower ( $stelo_status['statusCode'] );
		$order_status = $order->get_status();
		$order_updated = false;

		switch ($stelo_status_code) {
			case 'e' :
				if (! in_array ( $order_status, array (
					'on-hold',
					'processing',
					'completed'
				) )) {

					$order->update_status ( 'on-hold', __ ( 'O pagamento foi processado, aguardando confirmação do banco.', 'woocommerce-stelo' ) );

					$order_updated = true;
				}
				break;
			case 'cp' :
				if (! in_array ( $order_status, array (
					'on-hold',
					'processing',
					'completed'
				) )) {

					$order->update_status ( 'on-hold', __ ( 'O pagamento foi aprovado parcialmente.', 'woocommerce-stelo' ) );

					$order_updated = true;
				}
				break;
			case 'a' :
				if (! in_array ( $order_status, array (
					'processing',
					'completed'
				) )) {
					$order->add_order_note ( __ ( 'O pagamento foi confirmado com sucesso.', 'woocommerce-stelo' ) );

					// Changing the order for processing and reduces the stock.
					$order->payment_complete ();
					$order_updated = true;
				}
				break;

			case 'n' :
				$order->update_status ( 'cancelled', __ ( 'O pagamento não foi aprovado.', 'woocommerce-stelo' ) );
				$order_updated = true;

				break;
			case 'ni' :
				$order->update_status ( 'cancelled', __ ( 'O pagamento não foi aprovado pelo emissor do cartão.', 'woocommerce-stelo' ) );
				$order_updated = true;
				break;

			case 'c' :
				$order->update_status ( 'cancelled', __ ( 'O pagamento foi cancelado, não houve cobrança.', 'woocommerce-stelo' ) );
				$order_updated = true;
				break;

			case 's' :
				$order->update_status ( 'refunded', __ ( 'O pagamento foi reembolsado.', 'woocommerce-stelo' ) );
				$this->send_email ( sprintf ( __ ( 'Invoice for order %s was refunded', 'woocommerce-stelo-tc' ), $order->get_order_number () ), __ ( 'Invoice refunded', 'woocommerce-stelo-tc' ), sprintf ( __ ( 'Order %s has been marked as refunded by Stelo.', 'woocommerce-stelo-tc' ), $order->get_order_number () ) );
				$order_updated = true;

				break;
			case 'sp' :
				$order->update_status ( 'refunded', __ ( 'O pagamento foi reembolsado.', 'woocommerce-stelo' ) );
				$this->send_email ( sprintf ( __ ( 'Invoice for order %s was refunded', 'woocommerce-stelo-tc' ), $order->get_order_number () ), __ ( 'Invoice refunded', 'woocommerce-stelo-tc' ), sprintf ( __ ( 'Order %s has been marked as refunded by Stelo.', 'woocommerce-stelo-tc' ), $order->get_order_number () ) );
				$order_updated = true;

				break;

			default :

				// No action xD.
				break;
		}

		return $order_updated;
	}
}