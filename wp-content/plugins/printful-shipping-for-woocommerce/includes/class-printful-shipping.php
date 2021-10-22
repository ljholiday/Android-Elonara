<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Shipping extends WC_Shipping_Method
{
    public $show_warnings = false;
    public $calculate_tax = false;
    public $override_defaults = true;
    private $last_error = false;

    const PRINTFUL_SHIPPING = 'printful_shipping';

    //Store whether currently processed package contains Printful products (for WC<2.6)
    private $printful_package = true;

    public static function init() {
    	new self;
    }

	public function __construct() {

		$this->id                 = 'printful_shipping';
		$this->method_title       = $this->title = 'Printful Shipping';
		$this->method_description = 'Calculate live shipping rates based on actual Printful shipping costs.';

		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );

		$this->enabled           = $this->get_option( 'enabled' );
		$this->show_warnings     = $this->get_option( 'show_warnings' ) == 'yes';
		$this->override_defaults = $this->get_option( 'override_defaults' ) == 'yes';

		//Initialize shipping methods for specific package (or no package)
		add_filter( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ), 10000 );

		//Remove other shipping methods for Printful package on WC < 2.6
		add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ), 10000 );

		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'woocommerce_cart_shipping_packages' ), 10000 );
	}

	/**
	 * Init fields for Printful Shipping form
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this shipping method', 'woocommerce' ),
				'default' => 'no',
			),
			'override_defaults' => array(
				'title'   => __( 'Disable Woocommerce rates', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Disable standard Woocommerce rates for products fulfilled by Printful', 'woocommerce' ),
				'default' => 'yes',
			),
			'show_warnings'     => array(
				'title'   => __( 'Show Printful warnings', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Display Printful status messages if rate API request fails', 'woocommerce' ),
				'default' => 'yes',
			),
		);

		if ( !Printful_Integration::instance()->get_option('printful_key') ) {
			$this->form_fields['info'] = array(
				'type'        => 'title',
				'description' => 'Please add Printful API key to the
                    <a href="' . admin_url( 'admin.php?page=printful-dashboard&tab=settings' ) . '">Printful Integration settings section</a>
                    to enable rate calculation.',
			);
		}
	}

	/**
	 * Enable only Printful shipping method for Printful packages
	 * @param array $package
	 */
	public function woocommerce_load_shipping_methods( $package = array() ) {

		$this->printful_package = false;

		if ( $package && ! empty( $package['printful'] ) ) {
			if ( $this->enabled == 'yes' ) {
				$this->printful_package = true;
				if ( $this->override_defaults ) {
					//Remove default methods if we process Printful package
					WC()->shipping()->unregister_shipping_methods();
				}
				WC()->shipping()->register_shipping_method( $this );
			}
		} else if ( ! $package ) {
			//Show Printful tab on Shipping rate settings
			WC()->shipping()->register_shipping_method( $this );
		}
	}

	/**
	 * Remove non-Printful methods for Printful packages on WC < 2.6
	 * @param $methods
	 *
	 * @return array
	 */
	public function woocommerce_shipping_methods( $methods ) {

		if ( $this->override_defaults && $this->printful_package && version_compare( WC()->version, '2.6', '<' ) ) {
			//For WC < 2.6 woocommerce_shipping_methods is executed after woocommerce_load_shipping_methods
			//So we need to clean up unnecessary methods from there
			return array();
		}

		return $methods;
	}

	/**
	 * Split Printul products to a separate package if there are any
	 * @param array $packages
	 *
	 * @return array
	 */
	public function woocommerce_cart_shipping_packages( $packages = array() ) {
		//Printful rates are turned off, do not split products
		if ( $this->enabled !== 'yes' ) {
			return $packages;
		}

		$return_packages = array();

		foreach ( $packages as $package ) {
			$ids = array();
			foreach ( $package['contents'] as $key => $item ) {
				$ids[ $key ] = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
			}

			$printful_ids = array();
			if ( $ids ) {
				asort( $ids );
				$values       = implode( ',', array_unique( $ids ) );
				$key          = 'printful_productids_' . md5( $values );
				$printful_ids = get_transient( $key );
				if ( ! is_array( $printful_ids ) ) {
					$printful_ids = array();
					try {
						$client = Printful_Integration::instance()->get_client();
						$status = $client->get( 'sync/variants', array(
							'external_ids' => $values,
						) );
						if ( ! empty( $status['sync_variants'] ) ) {
							foreach ( $status['sync_variants'] as $variant ) {
								if ( $variant['synced'] ) {
									$printful_ids[] = $variant['external_id'];
								}
							}
						}
						set_transient( $key, $printful_ids, 1800 );
					} catch ( PrintfulException $e ) {
						$this->set_error( $e );

						//Failed to get Printful status, return default packages
						return $packages;
					}
				}
			}
			$new_contents = array(
				'printful'    => array(),
				'virtual'     => array(),
				'woocommerce' => array(),
			);

			foreach ( $ids as $key => $external_id ) {
				$item = $package['contents'][ $key ];
				if ( in_array( $external_id, $printful_ids ) ) {
					$new_contents['printful'][ $key ] = $item;
				} else if ( $item['data']->is_virtual() || $item['data']->is_downloadable() ) {
					$new_contents['virtual'][ $key ] = $item;
				} else {
					$new_contents['woocommerce'][ $key ] = $item;
				}
			}

			//Put virtual products together with any other package
			if ( $new_contents['virtual'] ) {
				if ( $new_contents['printful'] && ! $new_contents['woocommerce'] ) {
					$new_contents['printful'] += $new_contents['virtual'];
				} else {
					$new_contents['woocommerce'] += $new_contents['virtual'];
				}
				unset ( $new_contents['virtual'] );
			}

			foreach ( $new_contents as $key => $contents ) {
				if ( $contents ) {
					$new_package                  = $package;
					$new_package['contents_cost'] = 0;
					$new_package['contents']      = $contents;
					foreach ( $contents as $item ) {
						if ( $item['data']->needs_shipping() ) {
							if ( isset( $item['line_total'] ) ) {
								$new_package['contents_cost'] += $item['line_total'];
							}
						}
					}
					if ( $key == 'printful' ) {
						$new_package['printful'] = true;
					}
					$return_packages[] = $new_package;
				}
			}
		}

		return $return_packages;
	}

	/**
	 * @param array $package
	 *
	 * @return bool
	 */
	public function calculate_shipping( $package = array() ) {
		$request = array(
			'recipient' => array(
				'address1'     => $package['destination']['address'],
				'address2'     => $package['destination']['address_2'],
				'city'         => $package['destination']['city'],
				'state_code'   => $package['destination']['state'],
				'country_code' => $package['destination']['country'],
				'zip'          => $package['destination']['postcode'],
			),
			'items'     => array(),
			'currency'  => get_woocommerce_currency(),
            'locale'    => get_locale()
		);


		if ( $request['recipient']['country_code'] == 'US' &&
		     ( empty( $request['recipient']['state_code'] ) )
		) {
			return false;
		}

		foreach ( $package['contents'] as $item ) {
			if ( ! empty( $item['data'] ) && ( $item['data']->is_virtual() || $item['data']->is_downloadable() ) ) {
				continue;
			}
			$request['items'] [] = array(
				'external_variant_id' => $item['variation_id'] ? $item['variation_id'] : $item['product_id'],
				'quantity'            => $item['quantity'],
				'value'               => $item['line_total'] / $item['quantity'],
			);
		}

		if ( ! $request['items'] ) {
			return false;
		}

		try {
			$client = Printful_Integration::instance()->get_client();
		} catch ( PrintfulException $e ) {
			$this->set_error( $e );

			return false;
		}

		try {
			$key      = 'printful_rates_' . md5( json_encode( $request ) );
			$response = get_transient( $key );
			if ( $response === false ) {
				$response = $client->post( 'shipping/rates', $request, array(
					'expedited' => true,
				) );
				//Cache locally, since WC < 2.6 had problems with caching rates form multiple packages internally
				set_transient( $key, $response, 1800 );
			}

			foreach ( $response as $rate ) {
				$rateData = array(
                    'id'       => $this->id . '_' . $rate['id'],
					'label'    => $rate['name'],
					'cost'     => $rate['rate'],
					'calc_tax' => 'per_order',
				);

				// Before 3.4.0 rate could be passed as ID, after it's set as method_id which refers to class ID
                if ( version_compare( WC()->version, '3.4.0', '>=' ) ) {
				    $this->id = self::PRINTFUL_SHIPPING . '_' . $rate['id'];
                }

				$this->add_rate( $rateData );
                // Reset class ID after adding rate so ID name does not stack as huge string in foreach
                $this->id = self::PRINTFUL_SHIPPING;
			}
		} catch ( PrintfulException $e ) {
			$this->set_error( $e );
			return false;
		}

		return false;
	}

	/**
	 * @param $error
	 */
	private function set_error( $error ) {
		if ( $this->show_warnings ) {
			$this->last_error = $error;
			add_filter( 'woocommerce_cart_no_shipping_available_html', array( $this, 'show_error' ) );
			add_filter( 'woocommerce_no_shipping_available_html', array( $this, 'show_error' ) );
		}
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function show_error( $data ) {
		$error   = $this->last_error;
		$message = $error->getMessage();

		if ( $error instanceof PrintfulApiException && $error->getCode() == 401 ) {
			$message = 'Invalid API key';
		}

		return '<p>ERROR: ' . htmlspecialchars( $message ) . '</p>';
	}
}