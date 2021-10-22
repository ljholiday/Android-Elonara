<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Printful API client
 */
class Printful_Client {

	private $key = false;
	private $lastResponseRaw;
	private $lastResponse;
	private $userAgent = 'Printful WooCommerce Plugin';
	private $apiUrl;

	/**
	 * @param string $key Printful Store API key
	 * @param bool|string $disable_ssl Force HTTP instead of HTTPS for API requests
	 *
	 * @throws PrintfulException if the library failed to initialize
	 */
	public function __construct( $key = '', $disable_ssl = false ) {

		$key = (string) $key;

		$this->userAgent .= ' ' . Printful_Base::VERSION . ' (WP ' . get_bloginfo( 'version' ) . ' + WC ' . WC()->version . ')';

		if ( ! function_exists( 'json_decode' ) || ! function_exists( 'json_encode' ) ) {
			throw new PrintfulException( 'PHP JSON extension is required for the Printful API library to work!' );
		}
		if ( strlen( $key ) < 32 ) {
			throw new PrintfulException( 'Missing or invalid Printful store key!' );
		}
		$this->key = $key;

		if ( $disable_ssl ) {
			$this->apiUrl = str_replace( 'https://', 'http://', $this->apiUrl );
		}

		//setup api host
		$this->apiUrl = Printful_Base::get_printful_api_host();
	}

    /**
     * Returns total available item count from the last request if it supports paging (e.g order list) or null otherwise.
     *
     * @return int|null Item count
     */
	public function getItemCount() {
		return isset( $this->lastResponse['paging']['total'] ) ? $this->lastResponse['paging']['total'] : null;
	}

    /**
     * Perform a GET request to the API
     * @param string $path Request path (e.g. 'orders' or 'orders/123')
     * @param array $params Additional GET parameters as an associative array
     * @return mixed API response
     * @throws PrintfulApiException if the API call status code is not in the 2xx range
     * @throws PrintfulException if the API call has failed or the response is invalid
     */
	public function get( $path, $params = array() ) {
		return $this->request( 'GET', $path, $params );
	}

    /**
     * Perform a DELETE request to the API
     * @param string $path Request path (e.g. 'orders' or 'orders/123')
     * @param array $params Additional GET parameters as an associative array
     * @return mixed API response
     * @throws PrintfulApiException if the API call status code is not in the 2xx range
     * @throws PrintfulException if the API call has failed or the response is invalid
     */
	public function delete( $path, $params = array() ) {
		return $this->request( 'DELETE', $path, $params );
	}

    /**
     * Perform a POST request to the API
     * @param string $path Request path (e.g. 'orders' or 'orders/123')
     * @param array $data Request body data as an associative array
     * @param array $params Additional GET parameters as an associative array
     * @return mixed API response
     * @throws PrintfulApiException if the API call status code is not in the 2xx range
     * @throws PrintfulException if the API call has failed or the response is invalid
     */
	public function post( $path, $data = array(), $params = array() ) {
		return $this->request( 'POST', $path, $params, $data );
	}
    /**
     * Perform a PUT request to the API
     * @param string $path Request path (e.g. 'orders' or 'orders/123')
     * @param array $data Request body data as an associative array
     * @param array $params Additional GET parameters as an associative array
     * @return mixed API response
     * @throws PrintfulApiException if the API call status code is not in the 2xx range
     * @throws PrintfulException if the API call has failed or the response is invalid
     */
	public function put( $path, $data = array(), $params = array() ) {
		return $this->request( 'PUT', $path, $params, $data );
	}


    /**
     * Perform a PATCH request to the API
     * @param string $path Request path
     * @param array $data Request body data as an associative array
     * @param array $params
     * @return mixed API response
     * @throws PrintfulApiException if the API call status code is not in the 2xx range
     * @throws PrintfulException if the API call has failed or the response is invalid
     */
    public function patch( $path, $data = array(), $params = array() )
    {
        return $this->request( 'PATCH', $path, $params, $data );
    }

    /**
     * Return raw response data from the last request
     * @return string|null Response data
     */
	public function getLastResponseRaw() {
		return $this->lastResponseRaw;
	}
    /**
     * Return decoded response data from the last request
     * @return array|null Response data
     */
	public function getLastResponse() {
		return $this->lastResponse;
	}

	/**
	 * Internal request implementation
	 *
	 * @param $method
	 * @param $path
	 * @param array $params
	 * @param null $data
	 *
	 * @return
	 * @throws PrintfulApiException
	 * @throws PrintfulException
	 */
	private function request( $method, $path, array $params = array(), $data = null ) {

		$this->lastResponseRaw = null;
		$this->lastResponse    = null;

		$url = trim( $path, '/' );

		if ( ! empty( $params ) ) {
			$url .= '?' . http_build_query( $params );
		}

		$request = array(
			'timeout'    => 10,
			'user-agent' => $this->userAgent,
			'method'     => $method,
			'headers'    => array( 'Authorization' => 'Basic ' . base64_encode( $this->key ) ),
			'body'       => $data !== null ? json_encode( $data ) : null,
		);

		$result = wp_remote_get( $this->apiUrl . $url, $request );

		//allow other methods to hook in on the api result
		$result = apply_filters( 'printful_api_result', $result, $method, $this->apiUrl . $url, $request );

		if ( is_wp_error( $result ) ) {
			throw new PrintfulException( "API request failed - " . $result->get_error_message() );
		}
		$this->lastResponseRaw = $result['body'];
		$this->lastResponse    = $response = json_decode( $result['body'], true );

		if ( ! isset( $response['code'], $response['result'] ) ) {
			throw new PrintfulException( 'Invalid API response' );
		}
		$status = (int) $response['code'];
		if ( $status < 200 || $status >= 300 ) {
			throw new PrintfulApiException( (string) $response['result'], $status );
		}

		return $response['result'];
	}
}

/**
 * Class PrintfulException Generic Printful exception
 */
class PrintfulException extends Exception {}
/**
 * Class PrintfulException Printful exception returned from the API
 */
class PrintfulApiException extends PrintfulException {}