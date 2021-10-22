<?php
/**
 * Plugin Name: Storefront Extension - Align Menu Right
 * Version: 1.0.0
 * Author: PootlePress
 *
 * Text Domain: sfx-align-menu-right
 * Domain Path: /languages/
 *
 * @package SFX_Align_Menu_Right
 * @category Core
 * @author Alan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

require_once 'class-pootlepress-updater.php';

add_action( 'init', 'pp_sfx_amr_updater' );
function pp_sfx_amr_updater() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		include( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$data                          = get_plugin_data( __FILE__ );
	$wptuts_plugin_current_version = $data['Version'];
	$wptuts_plugin_remote_path     = 'http://www.pootlepress.com/?updater=1';
	$wptuts_plugin_slug            = plugin_basename( __FILE__ );
	new Pootlepress_Updater ( $wptuts_plugin_current_version, $wptuts_plugin_remote_path, $wptuts_plugin_slug );
}


/**
 * Returns the main instance of SFX_Align_Menu_Right to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object SFX_Align_Menu_Right
 */
function SFX_Align_Menu_Right() {
	return SFX_Align_Menu_Right::instance();
} // End SFX_Align_Menu_Right()

SFX_Align_Menu_Right();

/**
 * Main SFX_Align_Menu_Right Class
 *
 * @class SFX_Align_Menu_Right
 * @version  1.0.0
 * @since 1.0.0
 * @package  SFX_Align_Menu_Right
 * @author Alan
 */
final class SFX_Align_Menu_Right {
	/**
	 * SFX_Align_Menu_Right The single instance of SFX_Align_Menu_Right.
	 * @var  object
	 * @access  private
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End

	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();
	// Post Types - End


	private $enableAlignMenuRight;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token       = 'sfx-align-menu-right';
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->version     = '1.0.0';

		// Admin - Start
//		require_once( 'classes/class-sfx-align-menu-right-settings.php' );
//			$this->settings = SFX_Align_Menu_Right_Settings::instance();
//
//		if ( is_admin() ) {
//			require_once( 'classes/class-sfx-align-menu-right-admin.php' );
//			$this->admin = SFX_Align_Menu_Right_Admin::instance();
//		}
		// Admin - End

		// Post Types - End
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// needs to be hooked to 'wp_loaded', since this is the action that when executed, will filter get_option from customizer
		add_action( 'wp_loaded', array( $this, 'get_options' ), 100 );

		add_action( 'wp_loaded', array( $this, 'align_menu_right' ), 120 );

		add_action( 'wp_head', array( $this, 'option_css' ), 150 );

		add_action( 'customize_register', array( $this, 'customize_register' ) );

	} // End __construct()

	/**
	 * Main SFX_Align_Menu_Right Instance
	 *
	 * Ensures only one instance of SFX_Align_Menu_Right is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see SFX_Align_Menu_Right()
	 * @return Main SFX_Align_Menu_Right instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function get_options() {
		$arr = get_option( 'sfx-align-menu-right', array( 'checked' => false ) );
		if ( is_array( $arr ) && isset( $arr['checked'] ) ) {
			$this->enableAlignMenuRight = $arr['checked'] == true;
		} else {
			$this->enableAlignMenuRight = false;
		}
	}

	public function align_menu_right() {

		if ( $this->enableAlignMenuRight ) {
			remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
			remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
			add_action( 'storefront_header', 'storefront_primary_navigation', 30 );

			if ( function_exists( 'storefront_header_cart' ) ) {
				remove_action( 'storefront_header', 'storefront_header_cart', 60 );

				if ( function_exists( 'Storefront_WooCommerce_Customiser' ) ) {
					$header_cart = get_theme_mod( 'swc_header_cart' );
					if ( false == $header_cart ) {
						// don't add back the cart
					} else {
						add_action( 'storefront_header', 'storefront_header_cart', 35 );
					}
				} else {
					add_action( 'storefront_header', 'storefront_header_cart', 35 );
				}
			}

			remove_action( 'storefront_header', 'storefront_product_search', 40 );
//			* @hooked storefront_primary_navigation - 50
//			* @hooked storefront_header_cart - 60
		}
	}

	public function customize_register( WP_Customize_Manager $customizeManager ) {

		// use 'checked' bool in an array,
		// so WP_CustomizeSetting will add filter to 'option_' properly
		$customizeManager->add_setting( 'sfx-align-menu-right[checked]', array(
			'default' => false,
			'type'    => 'option',
		) );

		$customizeManager->add_control( new WP_Customize_Control( $customizeManager, 'sfx-align-menu-right', array(
			'type'        => 'checkbox',
			'label'       => 'Align menu right of logo',
			'description' => 'Moves the primary menu to the right of the logo and removes secondary menu',
			'settings'    => 'sfx-align-menu-right[checked]',
			'default'     => '0',
			'section'     => 'header_image',
			'priority'    => 100
		) ) );
	}

	public function option_css() {
		if ( $this->enableAlignMenuRight ) {
			echo <<<HTML
				<style>
					@media (min-width: 768px) {
						#masthead .col-full {
							display: flex;
							align-items: center;
						}
					
						#masthead .main-navigation {
							text-align: right;
						}
					
						.woocommerce-active .site-header div.site-branding {
							width: auto;
						}
					}
				</style>
HTML;
		}
	}

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'sfx-align-menu-right', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()
} // End Class
?>
