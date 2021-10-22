<?php
/**
 * Plugin Name:			Title Toggle for Storefront Theme
 * Plugin URI:			https://wordpress.org/plugins/storefront-title-toggle/
 * Description:			Hide titles on a per post/page basis. Must be using the Storefront theme.
 * Version:				1.2.4
 * Author:				Wooassist
 * Author URI:			http://wooassist.com/
 * Requires at least:	4.0.0
 * Tested up to:		5.2.3
 *
 * Text Domain: storefront-title-toggle
 * Domain Path: /languages/
 *
 * @package Storefront_Title_Toggle
 * @category Core
 * @author WooAssist
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of Storefront_Title_Toggle to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Title_Toggle
 */
function Storefront_Title_Toggle() {
	return Storefront_Title_Toggle::instance();
} // End Storefront_Title_Toggle()

Storefront_Title_Toggle();

/**
 * Main Storefront_Title_Toggle Class
 *
 * @class Storefront_Title_Toggle
 * @version	1.0.0
 * @since 1.0.0
 * @package	Storefront_Title_Toggle
 */
final class Storefront_Title_Toggle {
	/**
	 * Storefront_Title_Toggle The single instance of Storefront_Title_Toggle.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
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

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token 			= 'storefront-title-toggle';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.2.3';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'woa_sf_load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'woa_sf_setup' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'woa_sf_plugin_links' ) );
	}

	/**
	 * Main Storefront_Title_Toggle Instance
	 *
	 * Ensures only one instance of Storefront_Title_Toggle is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Title_Toggle()
	 * @return Main Storefront_Title_Toggle instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function woa_sf_load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-title-toggle', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Plugin page links
	 *
	 * @since  1.0.0
	 */
	public function woa_sf_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="https://wordpress.org/support/plugin/storefront-title-toggle">' . __( 'Support', 'storefront-title-toggle' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();

		// Check if using Storefront theme
		if( 'storefront' != basename( TEMPLATEPATH ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __('Sorry, you can&rsquo;t activate this plugin unless you have installed the Storefront theme.', 'storefront-title-toggle' ) );
		}

		// get theme customizer url
		$url = admin_url() . 'customize.php?';
		$url .= 'url=' . urlencode( site_url() . '?storefront-customizer=true' ) ;
		$url .= '&return=' . urlencode( admin_url() . 'plugins.php' );
		$url .= '&storefront-customizer=true';

		$notices 		= get_option( 'woa_sf_activation_notice', array() );
		$notices[]		= sprintf( __( '%sThanks for installing the Storefront Title Toggle extension. To get started, edit a page and find the title toggle metabox.%s', 'storefront-title-toggle' ), '<p>', '</p>' );

		update_option( 'woa_sf_activation_notice', $notices );
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if Storefront or a child theme using Storefront as a parent is active and the extension specific filter returns true.
	 * Child themes can disable this extension using the storefront_title_toggle_enabled filter
	 * @return void
	 */
	public function woa_sf_setup() {
		$theme = wp_get_theme();

		if ( 'Storefront' == $theme->name || 'storefront' == $theme->template && apply_filters( 'storefront_title_toggle_supported', true ) ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' )         );
			add_action( 'save_post',      array( $this, 'metabox_save'     ),  1, 2  );
			add_action( 'admin_notices', array( $this, 'woa_sf_customizer_notice' ) );
			add_action( 'wp', array( $this, 'title_toggle' ) );

			// Hide the 'More' section in the customizer
			add_filter( 'storefront_customizer_more', '__return_false' );
		}
	}

	/**
	 * Admin notice
	 * Checks the notice setup in install(). If it exists display it then delete the option so it's not displayed again.
	 * @since   1.0.0
	 * @return  void
	 */
	public function woa_sf_customizer_notice() {
		$notices = get_option( 'woa_sf_activation_notice' );

		if ( $notices = get_option( 'woa_sf_activation_notice' ) ) {

			foreach ( $notices as $notice ) {
				echo '<div class="updated">' . $notice . '</div>';
			}

			delete_option( 'woa_sf_activation_notice' );
		}
	}

	/**
	 * Register Metabox
	 * Function to register the metabox on WordPress
	 * @since 1.0.0
	 * @return void
	 */
	public function add_meta_box() {

		// Allow devs to control what post types this is allowed on
		$post_types = apply_filters( 'woa_sf_title_toggle_post_types', array( 'page', 'post', 'product' ) );

		// Add metabox for each post type found
		foreach ( $post_types as $post_type ) {
			add_meta_box( 'woa-sf-title-toggle', __('Title and Meta Toggle', 'storefront-title-toggle'), array( $this, 'metabox_render' ), $post_type, 'normal', 'high' );
		}
	}

	/**
	 * Render Metabox
	 * Function to render the metabox on supported post types
	 * @since 1.0.0
	 * @return void
	 */
	function metabox_render( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'woa_sf_title_toggle', 'woa_sf_title_toggle_nonce' );

		$title = self::get_meta( $post->ID, 'woa_sf_title_toggle' );
		$meta  = self::get_meta( $post->ID, 'woa_sf_meta_toggle' );

		// start html content ?>
			<p>
				<input type="checkbox" id="woa_sf_title_toggle" name="woa_sf_title_toggle" value="true" <?php checked( 'true', $title ); ?>>
				<label for="woa_sf_title_toggle"><strong><?php echo __( 'Hide Title', 'storefront-title-toggle' ); ?></strong></label>
				<em style="color:#aaa;"><?php echo __('This checkbox will hide the title from view.', 'storefront-title-toggle'); ?></em>
			</p>
			<?php if ( 'post' == $post->post_type ) : ?>
				<p>
					<input type="checkbox" id="woa_sf_meta_toggle" name="woa_sf_meta_toggle" value="true" <?php checked( 'true', $meta ); ?>>
					<label for="woa_sf_meta_toggle"><strong><?php echo __( 'Hide Post Meta', 'storefront-title-toggle' ); ?></strong></label>
					<em style="color:#aaa;"><?php echo __('This checkbox will hide the post meta (categories and tags) from view.', 'storefront-title-toggle'); ?></em>
				</p>
			<?php endif; ?>

		<?php // end html content
	}

	/**
	 * Save Metabox
	 * Function to handle saving of the options modified on the metabox
	 * @since 1.0.0
	 * @return void
	 */
	function metabox_save( $post_id ) {

		// Security check
		if ( ! isset( $_POST['woa_sf_title_toggle_nonce'] ) || ! wp_verify_nonce( $_POST['woa_sf_title_toggle_nonce'], 'woa_sf_title_toggle' ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$var = array();

		$var['woa_sf_title_toggle'] = array_key_exists( 'woa_sf_title_toggle', $_POST ) ? $_POST['woa_sf_title_toggle'] : '';
		$var['woa_sf_meta_toggle'] 	= array_key_exists( 'woa_sf_meta_toggle', $_POST ) ? $_POST['woa_sf_meta_toggle'] : '';

		foreach( $var as $key => $v ) {
			if ( $v == 'true' ) {
				update_post_meta( $post_id, $key, $v );
			} else {
				delete_post_meta( $post_id, $key, $v );
			}
		}
	}

	/**
	 * Main plugin logic
	 * Implements code if it should show/hide title and/or meta.
	 * @since 1.0.0
	 * @return void
	 */
	function title_toggle() {

		global $post;

		if ( ! is_object( $post ) )
			return;

		$title = self::get_meta( $post->ID, 'woa_sf_title_toggle' );
		$meta  = self::get_meta( $post->ID, 'woa_sf_meta_toggle' );

		if ( $title == 'true' ) {
			remove_action( 'storefront_single_post', 'storefront_post_header' );
			remove_action( 'storefront_page', 'storefront_page_header' );

			if ( is_front_page() ) {
				remove_action( 'storefront_homepage', 'storefront_homepage_header', 10 );
			}
			if ( is_page() ) {
				add_action( 'storefront_page', 'storefront_stt_page_header' );
			}


		}

		if ( function_exists( 'is_woocommerce' ) ) {

			if ( is_shop() ) {
				$shop_title = get_post_meta( get_option( 'woocommerce_shop_page_id' ) , 'woa_sf_title_toggle', true );

				if( $shop_title == 'true' )
					add_filter( 'woocommerce_show_page_title', '__return_false' );

			} else if ( is_product() && ( $title == 'true' ) ) {

				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'product_title_margin_fix' ), 5 );
			}
		}

		if( $meta == 'true' ) {
			remove_action( 'storefront_single_post', 'storefront_post_meta', 20);
		}
	}

	/**
	 * Product title margin fix
	 * This is a temporary fix for the margin error when hiding the title for a Product
	 * @since 1.2.1
	 * @return void
	 */
	function product_title_margin_fix() {
		?>
			<div class="margin-fix" style="height:0.618em"></div>
		<?php
	}

	/**
	 * Helper function to get the meta data.
	 * added filter to set the default value of the checkbox
	 *
	 * @since 1.2.3
	 * @return string	'true'/'false'
	 */
	function get_meta( $id, $key ) {

		if ( ! $id || ! $key )
			return;

		// dynamic filter to set the default value of the meta
		$value = apply_filters( $key . '_default', 'false', $id );

		if ( $fetch = get_post_meta( $id, $key, true ) )
			$value = $fetch;

		return $value;
	}

} // End Class

/**
 * Replaces the default page header to still display the featured photo on Pages.
 * @since 1.2.2
 * @return void
 */
function storefront_stt_page_header() {

	if ( ! has_post_thumbnail() )
		return;

	?>
		<figure class="entry-thumbnail">
			<?php storefront_post_thumbnail( 'full' ); ?>
		</figure>
	<?php
}
