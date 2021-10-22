<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Carriers {

	public $carriers;
	public static $_instance;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->carriers = $this->get_carriers();
	}

	/**
	 * Get carrier data
	 * @return mixed
	 */
	public function get_carriers() {
		$carriers = get_transient( 'printful_carriers' );
		if ( ! $carriers ) {
			$carriers = $this->refresh_carriers();
		}

		return $carriers;
	}

	/**
	 * Refresh carrier data from Printful
	 * @return mixed
	 */
	public function refresh_carriers() {

		try {
            $carriers = Printful_Integration::instance()->get_client()->get( 'store/get-shipping-methods' );
			$this->update_carrier_cache( $carriers );
		} catch (PrintfulApiException $e) {
			$carriers = array();
		} catch (PrintfulException $e) {
			$carriers = array();
		}

		return $carriers;
	}

	/**
	 * Update carrier transient
	 * @param $carriers
	 */
	public function update_carrier_cache($carriers) {

		set_transient( 'printful_carriers', $carriers,  MINUTE_IN_SECONDS * 5);    //5mins
	}

	/**
	 * Post carrier settings to printful
	 * @param $data
	 * @return mixed
	 */
	public function post_carriers( $data ) {

        if ( empty( $data ) ) {
            return false;
        }

		$shipping = new self;
		try {
			$carriers = Printful_Integration::instance()->get_client()->patch( 'store/update-shipping-methods', $data );

			if ( empty( $carriers['error'] ) ) {
				$shipping->update_carrier_cache( $carriers );
			}
		} catch ( PrintfulApiException $e ) {
			$carriers = false;
		} catch ( PrintfulException $e ) {
			$carriers = false;
		}

		return $carriers;
	}

}