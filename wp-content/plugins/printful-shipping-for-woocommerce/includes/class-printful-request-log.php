<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Request_log {

	const PF_USER_AGENT = 'Printful WooCommerce Integration';
	const PF_OPTION_LAST_API_RESPONSE = 'printful_last_api_response';
	const PF_OPTION_INCOMING_API_REQUEST_LOG = 'printful_incoming_api_request_log';
	const PF_OPTION_OUTGOING_API_REQUEST_LOG = 'printful_outgoing_api_request_log';
	const PF_INCOMING_API_ERRORS = 'printful-incoming-api-errors';
	const PF_OUTGOING_API_ERRORS = 'printful-outgoing-api-errors';

	public static function init() {
		$printful_log = new self;
		add_filter( 'woocommerce_api_serve_request', array( $printful_log, 'log_incoming_printful_api_requests' ), 10, 3 );
		add_filter( 'printful_api_result', array( $printful_log, 'log_outgoing_printful_api_requests'), 10, 4 );
	}

	/**
	 * Log printful API errors and save the last 20 API responses
	 * @param $served
	 * @param $result
	 * @param $request
	 *
	 * @return mixed
	 */
	public function log_incoming_printful_api_requests( $served, $result, $request ) {

		if ( ! $this->isPrintfulApiRequest( $request ) ) {
			return $served;
		}

		$last_api_response = get_option( self::PF_OPTION_LAST_API_RESPONSE, false );
		$response_hash     = md5( serialize( array( 'request' => $request, 'results' => $result ) ) );

		if ( $last_api_response == $response_hash ) {  //do not allow the same response to be logged twice
			return $served;
		}

		//save full error to WC logs
		if ( ! empty( $result['errors'] ) ) {
			$this->save_to_wc_log( $request, $result, self::PF_INCOMING_API_ERRORS );
		}

		//save summary in database to be easily accessible for status page
		$this->save_to_printful_log( $request->method . ' ' . $request->path, $result, $response_hash, self::PF_OPTION_INCOMING_API_REQUEST_LOG );

		return $served; //we avoid changing the result
	}


	/**
	 * @param $result
	 * @param $method
	 * @param $url
	 * @param $request
	 * @return array|mixed|object
	 */
	public function log_outgoing_printful_api_requests($result, $method, $url, $request) {

		$original_result = $result;
		$request['path'] = $url;
        $params_set = null;
        $code_success = null;

		if ( ! is_wp_error( $result ) ) {
			$result       = json_decode( $result['body'], true );
			$params_set   = ! isset( $result['code'], $result['result'] );
			$status       = (int) $result['code'];
			$code_success = ( $status < 200 || $status >= 300 );
		}

		//if the request contains error, log it
		if ( is_wp_error($result) || $params_set || $code_success ) {
			$this->save_to_wc_log( $request, $result, self::PF_OUTGOING_API_ERRORS );
		}

		$response_hash = md5( serialize( array( 'request' => $request, 'results' => $result ) ) );

		//save summary in database to be easily accessible for status page
		$this->save_to_printful_log( $method . ' ' . $url, $result, $response_hash,  self::PF_OPTION_OUTGOING_API_REQUEST_LOG );

		return $original_result; //don't change the result
	}

	/**
	 * Write Printful API request errors to log
	 *
	 * @param $request
	 * @param $result
	 * @param string $context
	 * @return bool
	 */
	private function save_to_wc_log( $request, $result, $context ) {

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return false;
		}

		$logger   = wc_get_logger();
		$context  = array( 'source' => $context );
		$log_item = array(
			'request' => (array) $request,
			'results' => (array) $result,
		);
		$logger->error( wc_print_r( $log_item, true ), $context );

		return true;
	}

	/**
	 * Save 20 lasts requests in easily accessible location
	 *
	 * @param $request_title
	 * @param $result
	 * @param $response_hash
	 * @param $log
	 */
	private function save_to_printful_log( $request_title, $result, $response_hash, $log ) {

		$request_log = get_option( $log, array() );
		if ( count( $request_log ) > 20 ) {
			$request_log = array_slice( $request_log, 1, 19 ); //if there are more than 20, remove the first entry
		}

		$is_error = is_wp_error($result) || !empty( $result['errors'] );

		$request_log[] = array(
			'date'    => date( 'Y-m-d H:i:s' ),
			'request' => $request_title,
			'result'  => ( $is_error ? 'ERROR' : 'OK' ),
		);

		update_option( $log, $request_log );
		update_option( self::PF_OPTION_LAST_API_RESPONSE, $response_hash );
	}

	/**
	 * Check requests header for indications that this is a printful api request
	 * @param $request
	 * @return bool
	 */
	private function isPrintfulApiRequest( $request ) {

		if ( ! empty( $request->headers ) && ! empty( $request->headers['USER_AGENT'] ) && $request->headers['USER_AGENT'] == self::PF_USER_AGENT ) {
			return true;
		}

		return false;
	}
}