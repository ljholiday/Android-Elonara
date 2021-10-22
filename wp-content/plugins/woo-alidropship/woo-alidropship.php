<?php
/**
 * Plugin Name: Dropshipping and Fulfillment for AliExpress and WooCommerce
 * Plugin URI: https://villatheme.com/extensions/aliexpress-dropshipping-and-fulfillment-for-woocommerce/
 * Description: Transfer data from AliExpress products to WooCommerce effortlessly and fulfill WooCommerce orders to AliExpress automatically.
 * Version: 1.0.7.1
 * Author: VillaTheme(villatheme.com)
 * Author URI: http://villatheme.com
 * Text Domain: woo-alidropship
 * Copyright 2019-2020 VillaTheme.com. All rights reserved.
 * Tested up to: 5.8
 * WC tested up to: 5.5
 * Requires PHP: 7.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VI_WOO_ALIDROPSHIP_VERSION', '1.0.7.1' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce-alidropship/woocommerce-alidropship.php' ) ) {
	return;
}
define( 'VI_WOO_ALIDROPSHIP_DIR', plugin_dir_path( __FILE__ ) );
define( 'VI_WOO_ALIDROPSHIP_INCLUDES', VI_WOO_ALIDROPSHIP_DIR . "includes" . DIRECTORY_SEPARATOR );
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-ali-orders-info-table.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-ali-orders-info-table.php";
}
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	$init_file = VI_WOO_ALIDROPSHIP_INCLUDES . "define.php";
	require_once $init_file;
}

/**
 * Class VI_WOO_ALIDROPSHIP
 */
class VI_WOO_ALIDROPSHIP {
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action( 'admin_notices', array( $this, 'global_note' ) );
	}

	function global_note() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			?>
            <div id="message" class="error">
                <p><?php _e( 'Please install and activate WooCommerce to use Dropshipping and Fulfillment for AliExpress and WooCommerce plugin.', 'woo-alidropship' ); ?></p>
            </div>
			<?php
		}
	}


	/**
	 * When active plugin Function will be call
	 */
	public function install() {
		global $wp_version;
		if ( version_compare( $wp_version, "2.9", "<" ) ) {
			deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
			wp_die( "This plugin requires WordPress version 2.9 or higher." );
		}
		VI_WOO_ALIDROPSHIP_Ali_Orders_Info_Table::create_table();
		$check_active = get_option( 'wooaliexpressdropship_params' );
		if ( ! $check_active ) {
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$settings             = VI_WOO_ALIDROPSHIP_DATA::get_instance();
				$params               = $settings->get_params();
				$params['secret_key'] = md5( time() );
				update_option( 'wooaliexpressdropship_params', $params );
				add_action( 'activated_plugin', array( $this, 'after_activated' ) );
			}
		} elseif ( wp_next_scheduled( 'vi_wad_update_aff_urls' ) ) {
			wp_unschedule_hook( 'vi_wad_update_aff_urls' );
		}
	}

	public function after_activated( $plugin ) {
		if ( $plugin === plugin_basename( __FILE__ ) ) {
			$url = admin_url( '?vi_wad_setup_wizard=true' );
			$url = add_query_arg( '_wpnonce', wp_create_nonce( 'vi_wad_setup' ), $url );
			exit( wp_redirect( $url ) );
		}
	}
}

new VI_WOO_ALIDROPSHIP();