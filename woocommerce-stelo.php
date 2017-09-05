<?php
/*
Plugin Name: Stelo para Woocommerce
Plugin URI: http://www.stelo.com.br/
Description: Esta extensão tem por objetivo integrar o método de pagamento do Woocommerce com a Stelo
Version: 1
Author: Stelo S/A
Author URI: http://www.stelo.com.br/
*/
if ( ! class_exists( 'WC_Stelo' ) ) :
	define('STELO_PATH', plugin_dir_path(__FILE__));
	define('STELO_URI', plugin_dir_url(__FILE__));

	class WC_Stelo {
		protected static $instance = null;

		public function __construct() {
			$this->includes();

			if (!class_exists('WC_Payment_Gateway') || !class_exists('Extra_Checkout_Fields_For_Brazil')) {
				add_action('admin_notices', array($this, 'dependecy_error'));

				return false;
			} else {
				include STELO_PATH . 'includes/woocommerce-stelo-gateway.php';
			}

			add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		private function includes() {
			include STELO_PATH . 'includes/functions.php';
			include STELO_PATH . 'includes/woocommerce-stelo-api.php';
		}

		public function dependecy_error() {
			stelo_get_template('dependency-error.php', array(
				'plugin_url' => admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce-extra-checkout-fields-for-brazil&TB_iframe=true&width=600&height=550')
			));
		}

		public function add_gateway($methods) {
			$methods[] = 'Stelo_Gateway';

			return $methods;
		}

		public static function install() {
			global $wpdb;

			$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "stelo_gateway` (
	  `stelo_gateway_id` INT NOT NULL AUTO_INCREMENT COMMENT '',
	  `order_id` INT NOT NULL COMMENT '',
	  `stelo_id` VARCHAR(120) NULL COMMENT '',
	  `data_inserido` DATETIME NULL COMMENT '',
	  PRIMARY KEY (`stelo_gateway_id`)  COMMENT '');
	";

			$wpdb->query( $sql );
		}

		public static function uninstall() {
			global $wpdb;

			$sql = "DROP TABLE `" . $wpdb->prefix . "stelo_gateway`";

			$wpdb->query( $sql );
		}
	}

	register_activation_hook(__FILE__, array('WC_Stelo', 'install'));
	register_deactivation_hook(__FILE__, array('WC_Stelo', 'uninstall'));

	add_action('plugins_loaded', array('WC_Stelo', 'get_instance'));
endif;