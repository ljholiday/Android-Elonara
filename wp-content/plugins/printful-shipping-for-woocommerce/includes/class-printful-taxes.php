<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Taxes {

	/**
	 * Initialize the values, hooks and actions
	 */
	public static function init() {
		if ( Printful_Integration::instance()->get_option( 'calculate_tax' ) == 'yes' ) {
			//Update tax options if taxes are enabled
			if ( get_option( 'woocommerce_calc_taxes' ) != 'yes' ) {
				update_option( 'woocommerce_calc_taxes', 'yes' );
			}
			if ( get_option( 'woocommerce_tax_based_on' ) != 'shipping' ) {
				update_option( 'woocommerce_tax_based_on', 'shipping' );
			}

			//Override tax rates calculated by Woocommerce
			$taxes = new self;
			add_filter( 'woocommerce_matched_tax_rates', array( $taxes, 'calculate_tax' ), 10, 6 );
		}
	}

	/**
	 * @param $matched_tax_rates
	 * @param $country
	 * @param $state
	 * @param $postcode
	 * @param $city
	 * @param $tax_class
	 *
	 * @return mixed
	 */
	public function calculate_tax( $matched_tax_rates, $country, $state, $postcode, $city, $tax_class ) {
		//if a tax rate is already matched, avoid adding extra one
		if ( ! empty( $matched_tax_rates ) ) {
			return $matched_tax_rates;
		}

		$countries = $this->get_tax_countries();
		if ( isset( $countries[ $country ][ $state ] ) && !empty($postcode) ) {     //only make the request if country, state and zip are set
			$key  = 'printful_tax_rate_' . $country . '-' . $state . '-' . $city . '-' . $postcode;
			$rate = get_transient( $key );
			$response = null;

			if ( $rate === false ) {
				try {
					$client   = Printful_Integration::instance()->get_client();
					$response = $client->post( 'tax/rates', array(
						'recipient' => array(
							'country_code' => $country,
							'state_code'   => $state,
							'city'         => $city,
							'zip'          => $postcode,
						),
					) );
				} catch ( Exception $e ) {
				}

				if ( isset( $response['rate'] ) ) {
					$rate = $response;
				} else {
					$rate = array(
						'required'         => false,
						'rate'             => 0,
						'shipping_taxable' => false,
					);
				}
				set_transient( $key, $rate, 1800 );
			}

			if ( $rate['required'] ) {
				$rate_item = array(
					'rate'     => $rate['rate'] * 100,
					'label'    => 'Sales Tax',
					'shipping' => $rate['shipping_taxable'] ? 'yes' : 'no',
					'compound' => 'no',
				);

				if ( $this->isRateUnique( $rate_item, $matched_tax_rates ) ) {
					$id                       = $this->get_printful_rate_id( $country, $state, $rate['shipping_taxable'] );
					$matched_tax_rates[ $id ] = $rate_item;
				}
			}
		}

		return $matched_tax_rates;
	}

	/**
	 * Checks if a equal tax rate is not already set
	 *
	 * @param $rate
	 * @param $matched_tax_rates
	 *
	 * @return bool
	 */
	private function isRateUnique( $rate, $matched_tax_rates ) {
		if ( empty( $matched_tax_rates ) ) {
			return true;
		}

		foreach ( $matched_tax_rates as $mr ) {
			if ( floatval( $mr['rate'] ) == floatval( $rate['rate'] ) && $mr['shipping'] == $rate['shipping'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets list of countries and states where Printful needs to calculate sales tax
	 * @return array|mixed
	 */
	private function get_tax_countries() {
		$countries = get_transient( 'printful_tax_countries' );

		if ( ! $countries ) {
			$countries = array();

			try {
				$client = Printful_Integration::instance()->get_client();
				$list   = $client->get( 'tax/countries' );

				foreach ( $list as $country ) {
					foreach ( $country['states'] as $state ) {
						$countries[ $country['code'] ][ $state['code'] ] = 1;
					}
				}

				if ( ! empty( $countries ) ) {
					set_transient( 'printful_tax_countries', $countries, 6 * 3600 );
				}
			} catch ( Exception $e ) {
				//Default to CA if can't get the actual state list
				return array( 'US' => array( 'CA' => 1 ) );
			}
		}

		return $countries;
	}

	/**
	 * Creates dummy tax rate ID to display Printful tax rates in the cart summary.
	 *
	 * @param $cc
	 * @param $state
	 * @param bool $includeShipping
	 *
	 * @return int|null|string
	 */
	private function get_printful_rate_id( $cc, $state, $includeShipping = false ) {
		global $wpdb;

		$includeShipping = (int) $includeShipping;

		$states    = WC()->countries->get_states( $cc );
		$tax_title = ( isset( $states[ $state ] ) ? $states[ $state ] . ' ' : '' ) . __( 'Sales Tax', 'printful' );
		$id        = $wpdb->get_var(
			$wpdb->prepare( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class='printful'
            and tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_shipping = %s  LIMIT 1",
				$cc,
				$state,
				$includeShipping
			) );
		if ( empty( $id ) ) {
			$wpdb->insert(
				$wpdb->prefix . "woocommerce_tax_rates",
				array(
					'tax_rate_country'  => $cc,
					'tax_rate_state'    => $state,
					'tax_rate'          => 0,
					'tax_rate_name'     => $tax_title,
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => $includeShipping,
					'tax_rate_class'    => 'printful',
				)
			);
			$id = $wpdb->insert_id;
		}

		return $id;
	}


}