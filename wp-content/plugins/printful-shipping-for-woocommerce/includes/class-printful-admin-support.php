<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Admin_Support {

    public static $_instance;

    /**
     * @return Printful_Admin_Support
     */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Setup the view
	 */
	public static function view() {

		$support = self::instance();
		$support->render();
	}

    /**
     * Display support report
     */
	public function render() {

		Printful_Admin::load_template( 'header', array( 'tabs' => Printful_Admin::get_tabs() ) );

		Printful_Admin::load_template( 'ajax-loader', array( 'action' => 'get_printful_status_report', 'message' => __( 'Building support report (this may take up to 30 seconds)...', 'printful' ) ) );

		Printful_Admin::load_template( 'support-info' );

		Printful_Admin::load_template( 'footer' );
	}

	/**
	 * Build the content for status page
	 */
	public static function render_status_report_ajax() {

		$status_report = self::instance()->generate_report();
		Printful_Admin::load_template( 'status-report', array( 'status_report' => $status_report ) );

		exit;
	}

    /**
     * Create system status report
     * @return string
     * @throws PrintfulException
     */
	public function generate_report() {

		if ( ! class_exists( 'WC_REST_System_Status_Controller' ) ) {
			return false;
		}

		$system_status = new WC_REST_System_Status_Controller; //make use of the woocommerce system status report

		ob_start();

		echo __( "##### Printful Checklist #####\n", 'printful' );
		$checklist = Printful_Admin_Status::get_checklist();
		foreach ( $checklist['items'] as $item ) {
			$status = 'OK';
			if($item['status'] == Printful_Admin_Status::PF_STATUS_WARNING) {
				$status = 'WARNING';
			} else if($item['status'] == Printful_Admin_Status::PF_STATUS_FAIL) {
				$status = 'FAIL';
			} else if ($item['status'] == Printful_Admin_Status::PF_STATUS_NOT_CONNECTED) {
			    $status = 'NOT CONNECTED';
            }
			echo "* ";
			echo esc_html( str_pad( esc_html( $item['name'] ), 30 ) ) . '=> ' . esc_html( $status ) . "\n";
		}

		echo "\n\n##### Printful Last Sync's #####\n";
		$syncReport = $this->get_sync_report();
		if ( ! empty( $syncReport ) ) {
			echo esc_html( str_pad( 'Date', 30 ) );
			echo esc_html( str_pad( 'Request', 30 ) );
			echo esc_html( str_pad( 'Message', 30 ) );
			echo "\n";

			foreach ( $syncReport as $sr ) {
				echo "* ";
				echo esc_html( str_pad( $sr['date'] . ';', 30 ) );
				echo esc_html( str_pad( $sr['path'] . ';', 30 ) );
				echo esc_html( str_pad( $sr['message'] . ';', 30 ) );
				echo "\n";
			}
		}

		echo "\n\n##### Environment #####\n";
		$this->output_report_block( $system_status->get_environment_info() );

		echo "\n\n##### Database #####\n";
		$this->output_report_block( $system_status->get_database_info() );

		echo "\n\n##### Active Plugins #####\n";
		foreach ( $system_status->get_active_plugins() as $plugin ) {
			if ( ! empty( $plugin['name'] ) ) {
				echo "* ";
				echo esc_html( $plugin['name'] ) . " (" . esc_html( $plugin['version'] ) . ")\n";
			}
		}

		echo "\n\n##### Theme #####\n";
		$this->output_report_block( $system_status->get_theme_info() );

		echo "\n\n##### WooCommerce settings #####\n";
		$this->output_report_block( $system_status->get_settings() );

		if (
			( defined( 'WP_DEBUG' ) && WP_DEBUG == true )
			&&
			( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG == true )
		) {
			echo "\n\n##### Wordpress Error log (last 50 entries) #####\n";
			$contents = $this->get_error_log_contents();
			if ( $contents ) {
				print_r( $contents );
			}
		}

		$report = ob_get_contents();
		ob_end_clean();

		return $report;
	}

	/**
     * Get last 50 lines of error log
	 * @return bool|string
	 */
	public function get_error_log_contents() {

		if ( ! function_exists( 'fopen' ) ) {
			return false;
		}

		return $this->file_tail( WP_CONTENT_DIR . '/debug.log', 50 );
	}

	/**
     * source: https://gist.github.com/lorenzos/1711e81a9162320fde20
	 * @param $filepath
	 * @param int $lines
	 * @param bool $adaptive
	 *
	 * @return bool|string
	 */
	function file_tail( $filepath, $lines = 1, $adaptive = true ) {

		$f = @fopen( $filepath, "rb" );
		if ( $f === false ) {
			return false;
		}

		// Sets buffer size, according to the number of lines to retrieve.
		if ( ! $adaptive ) {
			$buffer = 4096;
		} else {
			$buffer = ( $lines < 2 ? 64 : ( $lines < 10 ? 512 : 4096 ) );
		}

		// Jump to last character
		fseek( $f, - 1, SEEK_END );
		if ( fread( $f, 1 ) != "\n" ) {
			$lines -= 1;
		}

		$output = '';
		$chunk  = '';
		while ( ftell( $f ) > 0 && $lines >= 0 ) {
			// Figure out how far back we should jump
			$seek = min( ftell( $f ), $buffer );
			// Do the jump (backwards, relative to where we are)
			fseek( $f, - $seek, SEEK_CUR );
			$output = ( $chunk = fread( $f, $seek ) ) . $output;
			fseek( $f, - mb_strlen( $chunk, '8bit' ), SEEK_CUR );
			$lines -= substr_count( $chunk, "\n" );
		}
		while ( $lines ++ < 0 ) {
			$output = substr( $output, strpos( $output, "\n" ) + 1 );
		}
		fclose( $f );

		return trim( $output );
	}


	/**
	 * Displays the data
	 * @param $data
	 */
	public function output_report_block( $data ) {

		foreach ( $data as $key => $item ) {
			if ( is_string( $item ) ) {
				echo "* ";
				echo esc_html( str_pad($key, 30) ) . "=> " . esc_html($item) . "\n";
			}
		}
	}

	/**
	 * Returns log of last incoming API requests from Printful
	 * @return array
	 */
	public function get_sync_report() {

		$report      = array();
		$request_log = get_option( Printful_Request_log::PF_OPTION_INCOMING_API_REQUEST_LOG, array() );
		$request_log = array_reverse( $request_log );

		if ( empty( $request_log ) ) {
			return $report;
		}

		foreach ( $request_log as $log ) {
			$report[] = array(
				'date'    => $log['date'],
				'path'    => $log['request'],
				'message' => $log['result'],
			);
		}

		return $report;
	}
}