<?php
/*
 * Plugin Name: WooCommerce Stelo
 * Plugin URI: https://github.com/jarysardinha/woocommerce-stelo
 * Description: Adds the Stelo payment method to your WooCommerce store.
 * Version: 1.0.0
 * Author: Jary Fernandes Sardinha Jr
 * Author URI: https://github.com/jarysardinha/woocommerce-stelo
 * License:     GPLv2 or later
 * Text Domain: woocommerce-stelo
 * Domain Path: /languages/
 *
 * WooCommerce Stelo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WooCommerce Stelo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Correios. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Stelo' ) ) :
	define('STELO_PATH', plugin_dir_path(__FILE__));
	define('STELO_URI', plugin_dir_url(__FILE__));

	class WC_Stelo {
		protected static $instance = null;

		public function __construct() {
			add_action( 'init', array( $this, 'load_plugin_textdomain' ), -1 );

			$this->includes();

			if (!class_exists('WC_Payment_Gateway') || !class_exists('Extra_Checkout_Fields_For_Brazil')) {
				add_action('admin_notices', array($this, 'dependecy_error'));

				return false;
			} else {
				include STELO_PATH . 'includes/woocommerce-stelo-gateway.php';
			}

			add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
		}

		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-stelo' );
			$default_textdomain = STELO_PATH . 'languages/woocommerce-stelo-' . $locale . '.mo';
			$updated_textdomain = WP_LANG_DIR . '/woocommerce-stelo-' . $locale . '.mo';

			// Make sure that the right language file is being loaded if available, if not get default from plugins directory
			if ( !file_exists( $updated_textdomain ) && file_exists( $default_textdomain ) ) {
				load_textdomain( 'woocommerce-stelo', $default_textdomain );
			} elseif ( file_exists( $updated_textdomain ) ) {
				load_textdomain( 'woocommerce-stelo', $updated_textdomain );
			}

			load_plugin_textdomain( 'woocommerce-stelo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
				'message' => sprintf( __( 'The plugin <b>WooCommerce Stelo</b> uses <b>WooCommerce Extra Checkout Fields for Brazil</b> to work properly. Please install it on this link <a href="%s">WooCommerce Extra Checkout Fields for Brazil</a>' ), admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce-extra-checkout-fields-for-brazil&TB_iframe=true&width=600&height=550' ) )
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