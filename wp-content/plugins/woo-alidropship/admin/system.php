<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class VI_WOO_ALIDROPSHIP_Admin_System
 */
class VI_WOO_ALIDROPSHIP_Admin_System {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu_page' ),30 );
	}

	public function page_callback() {
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'System Status', 'woo-alidropship' ) ?></h2>
            <table cellspacing="0" id="status" class="widefat">
                <tbody>
                <tr>
                    <td data-export-label="<?php esc_html_e( 'PHP Time Limit', 'woo-alidropship' ) ?>"><?php esc_html_e( 'PHP Max Execution Time', 'woo-alidropship' ) ?></td>
                    <td><?php echo ini_get( 'max_execution_time' ); ?></td>
                    <td><?php esc_html_e( 'Should be greater than 100', 'woo-alidropship' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_html_e( 'PHP Max Input Vars', 'woo-alidropship' ) ?>"><?php esc_html_e( 'PHP Max Input Vars', 'woo-alidropship' ) ?></td>

                    <td><?php echo ini_get( 'max_input_vars' ); ?></td>
                    <td><?php esc_html_e( 'Should be greater than 10000', 'woo-alidropship' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_html_e( 'Memory Limit', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Memory Limit', 'woo-alidropship' ) ?></td>

                    <td><?php echo ini_get( 'memory_limit' ); ?></td>
                    <td><?php esc_html_e( 'Should be greater than 128MB', 'woo-alidropship' ) ?></td>
                </tr>

                </tbody>
            </table>
        </div>
		<?php
	}

	/**
	 * Register a custom menu page.
	 */
	public function menu_page() {
		add_submenu_page(
			'woo-alidropship',
			esc_html__( 'System Status', 'woo-alidropship' ),
			esc_html__( 'System Status', 'woo-alidropship' ),
			'manage_options',
			'woo-ali-status',
			array( $this, 'page_callback' )
		);

	}
}
