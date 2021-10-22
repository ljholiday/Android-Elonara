<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ThisNew_Admin {

	const MENU_TITLE_TOP = 'ThisNew';
	const PAGE_TITLE_DASHBOARD = 'Dashboard';
	const MENU_TITLE_DASHBOARD = 'Dashboard';
	const MENU_SLUG_DASHBOARD = 'thisnew-dashboard';
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
//		add_action( 'admin_bar_menu', array( $this, 'add_thisnew_status_toolbar' ), 999 );
    }

    /**
     * Loads stylesheets used in thisnew admin pages
     * @param $hook
     */
    public function add_admin_styles($hook) {

	    wp_enqueue_style( 'thisnew-global', plugins_url( '../assets/css/global.css', __FILE__ ) );

	    if ( strpos( $hook, 'thisnew-dashboard' ) !== false ) {
		    wp_enqueue_style( 'wp-color-picker' );
		    wp_enqueue_style( 'thisnew-dashboard', plugins_url( '../assets/css/dashboard.css', __FILE__ ) );
		    wp_enqueue_style( 'thisnew-status', plugins_url( '../assets/css/status.css', __FILE__ ) );
		    wp_enqueue_style( 'thisnew-support', plugins_url( '../assets/css/support.css', __FILE__ ) );
		    wp_enqueue_style( 'thisnew-settings', plugins_url( '../assets/css/settings.css', __FILE__ ) );
	    }
    }

	/**
	 * Loads stylesheet for thisnew toolbar element
	 */
    public function add_global_style() {
	    if ( is_user_logged_in() ) {
		    wp_enqueue_style( 'thisnew-global', plugins_url( '../assets/css/global.css', __FILE__ ) );
	    }
    }

	/**
	 * Loads scripts used in thisnew admin pages
	 * @param $hook
	 */
	public function add_admin_scripts($hook) {
		if ( strpos( $hook, 'thisnew-dashboard' ) !== false ) {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'thisnew-settings', plugins_url( '../assets/js/settings.js', __FILE__ ) );
			wp_enqueue_script( 'thisnew-connect', plugins_url( '../assets/js/connect.js', __FILE__ ) );
			wp_enqueue_script( 'thisnew-block-loader', plugins_url( '../assets/js/block-loader.js', __FILE__ ) );
			wp_enqueue_script( 'thisnew-intercom', plugins_url( '../assets/js/intercom.min.js', __FILE__ ) );
		}
	}

    /**
     * Register admin menu pages
     */
	public function register_admin_menu_page() {

		add_menu_page(
			__( 'Dashboard', 'thisnew' ),
			self::MENU_TITLE_TOP,
			self::CAPABILITY,
			self::MENU_SLUG_DASHBOARD,
			array( 'ThisNew_Admin', 'route' ),
			ThisNew_Base::get_asset_url() . 'images/thisnew-menu-icon.png',
			58
		);
	}

	/**
	 * Route the tabs
	 */
	public static function route() {

		$tabs = array(
			'dashboard' => 'ThisNew_Admin_Dashboard',
			'settings'  => 'ThisNew_Admin_Settings',
			'support'   => 'ThisNew_Admin_Support',
		);

		$tab = ( ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'dashboard' );
		if ( ! empty( $tabs[ $tab ] ) ) {
			call_user_func( array( $tabs[ $tab ], 'view' ) );
		}
	}

    /**
     * Get the tabs used in thisnew admin pages
     * @return array
     * @throws thisnewException
     */
	public static function get_tabs() {

		$tabs = array(
			array( 'name' => __( 'Settings', 'thisnew' ), 'tab_url' => 'settings' ),
			array( 'name' => __( 'Support', 'thisnew' ), 'tab_url' => 'support' ),
		);

		if ( ThisNew_Integration::instance()->is_connected() ) {
			array_unshift( $tabs, array( 'name' => __( 'Dashboard', 'thisnew' ), 'tab_url' => false ) );
		} else {
			array_unshift( $tabs, array( 'name' => __( 'Connect', 'thisnew' ), 'tab_url' => false ) );
		}

		return $tabs;
	}

	/**
	 * Create the thisnew toolbar
	 * @param $wp_admin_bar
	 */
	public function add_thisnew_status_toolbar( $wp_admin_bar ) {

		$issueCount = get_transient( ThisNew_Admin_Status::PF_STATUS_ISSUE_COUNT );

		if ( $issueCount ) {
			//Add top level menu item
			$args = array(
				'id'    => 'thisnew_toolbar',
				'title' => 'ThisNew Integration' . ( $issueCount > 0 ? ' <span class="thisnew-toolbar-issues">' . esc_attr( $issueCount ) . '</span>' : '' ),
				'href'  => get_admin_url( null, 'admin.php?page=' . ThisNew_Admin::MENU_SLUG_DASHBOARD ),
				'meta'  => array( 'class' => 'thisnew-toolbar' ),
			);
			$wp_admin_bar->add_node( $args );

			//Add status
			$args = array(
				'id'     => 'thisnew_toolbar_status',
				'parent' => 'thisnew_toolbar',
				'title'  => 'Integration status' . ( $issueCount > 0 ? ' (' . esc_attr( $issueCount ) . _n( ' issue', ' issues', $issueCount ) . ')' : '' ),
				'href'   => get_admin_url( null, 'admin.php?page=' . ThisNew_Admin::MENU_SLUG_DASHBOARD . '&tab=status' ),
				'meta'   => array( 'class' => 'thisnew-toolbar-status' ),
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