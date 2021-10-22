<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Admin {

	const MENU_TITLE_TOP = 'Printful';
	const PAGE_TITLE_DASHBOARD = 'Dashboard';
	const MENU_TITLE_DASHBOARD = 'Dashboard';
	const MENU_SLUG_DASHBOARD = 'printful-dashboard';
	const CAPABILITY = 'manage_options';

	public static function init() {
		$admin = new self;
		$admin->register_admin();
	}

    /**
     * Register admin scripts
     */
	public function register_admin() {

		add_action( 'admin_menu', array( $this, 'register_admin_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_global_style' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_printful_status_toolbar' ), 999 );
    }

    /**
     * Loads stylesheets used in printful admin pages
     * @param $hook
     */
    public function add_admin_styles($hook) {

	    wp_enqueue_style( 'printful-global', plugins_url( '../assets/css/global.css', __FILE__ ) );

	    if ( strpos( $hook, 'printful-dashboard' ) !== false ) {
		    wp_enqueue_style( 'wp-color-picker' );
		    wp_enqueue_style( 'printful-dashboard', plugins_url( '../assets/css/dashboard.css', __FILE__ ) );
		    wp_enqueue_style( 'printful-status', plugins_url( '../assets/css/status.css', __FILE__ ) );
		    wp_enqueue_style( 'printful-support', plugins_url( '../assets/css/support.css', __FILE__ ) );
		    wp_enqueue_style( 'printful-settings', plugins_url( '../assets/css/settings.css', __FILE__ ) );
	    }
    }

	/**
	 * Loads stylesheet for printful toolbar element
	 */
    public function add_global_style() {
	    if ( is_user_logged_in() ) {
		    wp_enqueue_style( 'printful-global', plugins_url( '../assets/css/global.css', __FILE__ ) );
	    }
    }

	/**
	 * Loads scripts used in printful admin pages
	 * @param $hook
	 */
	public function add_admin_scripts($hook) {
		if ( strpos( $hook, 'printful-dashboard' ) !== false ) {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'printful-settings', plugins_url( '../assets/js/settings.js', __FILE__ ) );
			wp_enqueue_script( 'printful-connect', plugins_url( '../assets/js/connect.js', __FILE__ ) );
			wp_enqueue_script( 'printful-block-loader', plugins_url( '../assets/js/block-loader.js', __FILE__ ) );
			wp_enqueue_script( 'printful-intercom', plugins_url( '../assets/js/intercom.min.js', __FILE__ ) );
		}
	}

    /**
     * Register admin menu pages
     */
	public function register_admin_menu_page() {

		add_menu_page(
			__( 'Dashboard', 'printful' ),
			self::MENU_TITLE_TOP,
			self::CAPABILITY,
			self::MENU_SLUG_DASHBOARD,
			array( 'Printful_Admin', 'route' ),
			Printful_Base::get_asset_url() . 'images/printful-menu-icon.png',
			58
		);
	}

	/**
	 * Route the tabs
	 */
	public static function route() {

		$tabs = array(
			'dashboard' => 'Printful_Admin_Dashboard',
			'settings'  => 'Printful_Admin_Settings',
			'status'    => 'Printful_Admin_Status',
			'support'   => 'Printful_Admin_Support',
		);

		$tab = ( ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'dashboard' );
		if ( ! empty( $tabs[ $tab ] ) ) {
			call_user_func( array( $tabs[ $tab ], 'view' ) );
		}
	}

    /**
     * Get the tabs used in printful admin pages
     * @return array
     * @throws PrintfulException
     */
	public static function get_tabs() {

		$tabs = array(
			array( 'name' => __( 'Settings', 'printful' ), 'tab_url' => 'settings' ),
			array( 'name' => __( 'Status', 'printful' ), 'tab_url' => 'status' ),
			array( 'name' => __( 'Support', 'printful' ), 'tab_url' => 'support' ),
		);

		if ( Printful_Integration::instance()->is_connected() ) {
			array_unshift( $tabs, array( 'name' => __( 'Dashboard', 'printful' ), 'tab_url' => false ) );
		} else {
			array_unshift( $tabs, array( 'name' => __( 'Connect', 'printful' ), 'tab_url' => false ) );
		}

		return $tabs;
	}

	/**
	 * Create the printful toolbar
	 * @param $wp_admin_bar
	 */
	public function add_printful_status_toolbar( $wp_admin_bar ) {

		$issueCount = get_transient( Printful_Admin_Status::PF_STATUS_ISSUE_COUNT );

		if ( $issueCount ) {
			//Add top level menu item
			$args = array(
				'id'    => 'printful_toolbar',
				'title' => 'Printful Integration' . ( $issueCount > 0 ? ' <span class="printful-toolbar-issues">' . esc_attr( $issueCount ) . '</span>' : '' ),
				'href'  => get_admin_url( null, 'admin.php?page=' . Printful_Admin::MENU_SLUG_DASHBOARD ),
				'meta'  => array( 'class' => 'printful-toolbar' ),
			);
			$wp_admin_bar->add_node( $args );

			//Add status
			$args = array(
				'id'     => 'printful_toolbar_status',
				'parent' => 'printful_toolbar',
				'title'  => 'Integration status' . ( $issueCount > 0 ? ' (' . esc_attr( $issueCount ) . _n( ' issue', ' issues', $issueCount ) . ')' : '' ),
				'href'   => get_admin_url( null, 'admin.php?page=' . Printful_Admin::MENU_SLUG_DASHBOARD . '&tab=status' ),
				'meta'   => array( 'class' => 'printful-toolbar-status' ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Load a template file. Extract any variables that are passed
	 * @param $name
	 * @param array $variables
	 */
	public static function load_template( $name, $variables = array() ) {

		if ( ! empty( $variables ) ) {
			extract( $variables );
		}

		$filename = plugin_dir_path( __FILE__ ) . 'templates/' . $name . '.php';
		if ( file_exists( $filename ) ) {
			include( $filename );
		}
	}

}