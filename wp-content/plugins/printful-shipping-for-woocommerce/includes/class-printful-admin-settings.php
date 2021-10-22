<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Admin_Settings {

	public static $_instance;
	const CARRIER_TYPE_STANDARD = 'standard';
	const CARRIER_TYPE_EXPEDITED = 'expedited';
	const CARRIER_TYPE_DOMESTIC = 'domestic';
	const CARRIER_TYPE_INTERNATIONAL = 'international';
    const CARRIER_REGION_US = 'US';
    const CARRIER_REGION_EU = 'LV';
    const DEFAULT_PERSONALIZE_BUTTON_TEXT = 'Personalize Design';
    const DEFAULT_PERSONALIZE_BUTTON_COLOR = '#eee';
    const DEFAULT_PERSONALIZE_MODAL_TITLE = 'Create a personalized design';

    // Size guide modal settings
    const DEFAULT_SIZE_GUIDE_BUTTON_TEXT = 'Size Guide';
    const DEFAULT_SIZE_GUIDE_BUTTON_COLOR = '#1164A9';
    const DEFAULT_SIZE_GUIDE_MODAL_TITLE = 'Size guide';
    const DEFAULT_SIZE_GUIDE_MODAL_TEXT_COLOR = '#000';
    const DEFAULT_SIZE_GUIDE_MODAL_BACKGROUND_COLOR = '#fff';
    const DEFAULT_SIZE_GUIDE_TAB_BACKGROUND_COLOR = '#fff';
    const DEFAULT_SIZE_GUIDE_ACTIVE_TAB_BACKGROUND_COLOR = '#fff';
    const DEFAULT_SIZE_GUIDE_UNIT = 'inch';

    /**
     * @return array
     */
	public static function getIntegrationFields()
    {
        $sales_tax_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url( 'https://www.printful.com/faq/taxes-and-billing/sales-tax/371-which-states-does-printful-charge-sales-tax-in-' ),
            esc_html__( 'states where Printful applies sales tax', 'printful' )
        );

        return array(
            'printful_key' => array(
                'title' => __( 'Printful store API key', 'printful' ),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __( 'Your store\'s Printful API key. Create it in the Prinful dashboard', 'printful' ),
                'default' => false,
            ),
            'calculate_tax' => array(
                'title' => __( 'Calculate sales tax', 'printful' ),
                'type' => 'checkbox',
                'label' => sprintf(
                    __('Calculated for all products listed on your store (including non-Printful products) that ship to %s. Before enabling, make sure you are registered for a seller permit in all these states.'),
                    $sales_tax_link
                ),
                'default' => 'no',
            ),
            'disable_ssl' => array(
                'title' => __( 'Disable SSL', 'printful' ),
                'type' => 'checkbox',
                'label' => __( 'Use HTTP instead of HTTPS to connect to the Printful API (may be required if the plugin does not work for some hosting configurations)', 'printful' ),
                'default' => 'no',
            ),
        );
    }

    public static function getPersonalizationFields()
    {
        return array(
            'pfc_button_text' => array(
                'title' => __( 'Personalization button text', 'printful' ),
                'type' => 'text',
                'description' => __( 'Personalization button text', 'printful' ),
                'default' => self::DEFAULT_PERSONALIZE_BUTTON_TEXT
            ),
            'pfc_button_color' => array(
                'title' => __( 'Personalization button color', 'printful' ),
                'type' => 'color-picker',
                'description' => __( 'Personalization button background color', 'printful' ),
                'default' => self::DEFAULT_PERSONALIZE_BUTTON_COLOR,
            ),
            'pfc_modal_title' => array(
                'title' => __( 'Personalization popup title', 'printful' ),
                'type' => 'text',
                'description' => __( 'Personalization popup title text', 'printful' ),
                'default' => self::DEFAULT_PERSONALIZE_MODAL_TITLE,
            ),
        );
    }

    public static function getSizeGuideFields()
    {
        return array(
            'pfsg_modal_title' => array(
                'title' => __( 'Size guide popup title', 'printful' ),
                'type' => 'text',
                'description' => __( 'Size guide popup title text', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_MODAL_TITLE,
            ),
            'pfsg_modal_text_color' => array(
                'title' => __( 'Size guide popup text color', 'printful' ),
                'type' => 'color-picker',
                'description' => __( 'Size guide popup text color', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_MODAL_TEXT_COLOR,
            ),
            'pfsg_modal_background_color' => array(
                'title' => __( 'Size guide popup background color', 'printful' ),
                'type' => 'color-picker',
                'description' => __( 'Size guide popup background color', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_MODAL_BACKGROUND_COLOR,
            ),
            'pfsg_tab_background_color' => array(
	            'title' => __( 'Size guide tab background color', 'printful' ),
	            'type' => 'color-picker',
	            'description' => __( 'Size guide tab background color', 'printful' ),
	            'default' => self::DEFAULT_SIZE_GUIDE_TAB_BACKGROUND_COLOR,
            ),
            'pfsg_active_tab_background_color' => array(
	            'title' => __( 'Size guide active tab background color', 'printful' ),
	            'type' => 'color-picker',
	            'description' => __( 'Size guide active tab background color', 'printful' ),
	            'default' => self::DEFAULT_SIZE_GUIDE_ACTIVE_TAB_BACKGROUND_COLOR,
            ),
            'pfsg_button_text' => array(
                'title' => __( 'Size guide button text', 'printful' ),
                'type' => 'text',
                'description' => __( 'Size guide button text', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_BUTTON_TEXT,
            ),
            'pfsg_button_color' => array(
                'title' => __( 'Size guide button text color', 'printful' ),
                'type' => 'color-picker',
                'description' => __( 'Size guide button text color', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_BUTTON_COLOR,
            ),
            'pfsg_primary_unit' => array(
                'title' => __( 'Primary measurement unit', 'printful' ),
                'type' => 'dropdown',
                'description' => __( 'Primary measurement unit (cm/inch)', 'printful' ),
                'default' => self::DEFAULT_SIZE_GUIDE_UNIT,
                'selected' => Printful_Integration::instance()->get_option( 'pfsg_primary_unit' ),
                'items' => [
                    'inch' => __('Inches', 'printful'),
                    'centimeter' => __('Centimeters', 'printful'),
                ],
            ),
        );
    }

	/**
	 * @return array
	 */
	public static function getAllFields() {
		return array_merge(self::getIntegrationFields(), self::getPersonalizationFields(), self::getSizeGuideFields());
    }

	/**
	 * @return Printful_Admin_Settings
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

		$settings = self::instance();
		$settings->render();
	}

	/**
	 * Display the view
	 */
	public function render() {

		Printful_Admin::load_template( 'header', array( 'tabs' => Printful_Admin::get_tabs() ) );

		echo '<form method="post" name="printful_settings" action="' . esc_url( admin_url( 'admin-ajax.php?action=save_printful_settings' ) ) . '">';

		// Integration settings
		$integration_settings = $this->setup_fields( __('Integration settings', 'printful'), '', self::getIntegrationFields() );
		Printful_Admin::load_template( 'setting-group', $integration_settings );

		// Product personalization settings
        $personalization_settings = $this->setup_fields( __('Product personalization settings', 'printful'), '', self::getPersonalizationFields() );
        Printful_Admin::load_template( 'setting-group', $personalization_settings );

        // Size guide settings
        $size_guide_settings = $this->setup_fields(
            __('Size guide settings', 'printful'),
            __('These settings control how the new size guide will look on your WooCommerce storefront. Products with an old size guide will not be affected.', 'printful'),
            self::getSizeGuideFields()
        );
        Printful_Admin::load_template( 'setting-group', $size_guide_settings );

		Printful_Admin::load_template( 'shipping-notification' );

		//carriers settings
		Printful_Admin::load_template( 'ajax-loader', array( 'action' => 'get_printful_carriers', 'message' => 'Loading your carriers...' ) );

		Printful_Admin::load_template( 'setting-submit', array( 'nonce' => wp_create_nonce( 'printful_settings' ), 'disabled' => true ) );

        echo '</form>';

		Printful_Admin::load_template( 'footer' );
	}

    /**
     * Display the ajax content for carrier settings
     * @throws PrintfulException
     */
	public static function render_carriers_ajax() {

		$carrier_settings = self::instance()->setup_carrier_fields();
		Printful_Admin::load_template( 'setting-group', $carrier_settings );
		$enable_submit = 'Printful_Settings.enable_submit_btn();';
		Printful_Admin::load_template( 'inline-script', array( 'script' => $enable_submit ) );

		exit;
	}

    /**
     * @param string $title Settings section title
     * @param string $description Section description
     * @param array $fields
     *
     * @return array
     */
    public function setup_fields($title, $description = '', $fields = [])
    {
        $fieldGroup = array(
            'title'       => $title,
            'description' => $description,
            'settings'    => $fields,
        );

        foreach ( $fieldGroup['settings'] as $key => $setting ) {
            if ( $setting['type'] !== 'title' ) {
                $fieldGroup['settings'][ $key ]['value'] = Printful_Integration::instance()->get_option( $key, $setting['default'] );
            }
        }

        return $fieldGroup;
	}

    /**
     * @internal param $carrier_settings
     * @throws PrintfulException
     */
	public function setup_carrier_fields() {
		$carrier_settings = array(
			'title'       => __( 'Shipping Methods', 'printful' ),
            'description' => __( 'Here you can choose the shipping methods you want Printful to use for shipping your orders.
            Uncheck the ones you want to disable. From your selection, our algorithm will determine the fastest, most cost-effective, and most reliable method for each order.', 'printful' ),
			'settings'    => array(),
		);

		if ( ! Printful_Integration::instance()->is_connected() ) {
			$carrier_settings['description'] = __( 'You need to be connected to Printful API to edit carrier settings!', 'printful' );

			return $carrier_settings;
		}

        $carrier_regions = Printful_Carriers::instance()->carriers;

        if ( empty( $carrier_regions ) ) {
            return false;
		}

        $carrier_settings[ 'settings' ] = $this->prepare_form_data( $carrier_regions );

		return $carrier_settings;
	}

	/**
	 * Prepare carrier data for posting to Printful API
	 * @return array|bool
	 */
	public function prepare_carriers() {

        $carrier_regions = Printful_Carriers::instance()->carriers;

        if ( empty( $carrier_regions ) ) {
            return false;
        }

        $us_carriers[ self::CARRIER_REGION_US ]  = ( ( ! empty( $_POST[ self::CARRIER_REGION_US ] ) && wp_verify_nonce( $_POST['_wpnonce'], 'printful_settings' ) ) ? $_POST[ self::CARRIER_REGION_US ] : array() );
        $eu_carriers[ self::CARRIER_REGION_EU ] = ( ( ! empty( $_POST[ self::CARRIER_REGION_EU ] ) && wp_verify_nonce( $_POST['_wpnonce'], 'printful_settings' ) ) ? $_POST[ self::CARRIER_REGION_EU ] : array() );

        $parsed_carriers = $this->parse_region_carriers( $us_carriers, $eu_carriers );
        $us_carriers = $parsed_carriers['us_carriers'];
        $eu_carriers = $parsed_carriers['eu_carriers'];

        $saved_carriers = array_merge( $us_carriers, $eu_carriers );
		$request_body   = array();

        foreach ( $carrier_regions as $region => $carrier_region ) {
            foreach ( $carrier_region as $carrier_type => $carrier_methods ) {
                foreach ( $carrier_methods as $carrier_method => $carrier_data ) {
                    $is_active = false;

                    if ( in_array( $carrier_method, $saved_carriers[ $region ][ $carrier_type ] ) ) {
                        $is_active = true;
                    }

                    // `isDisabled` means that user is not allowed to change `isActive` state
                    if ($carrier_data['isDisabled']) {
                        $is_active = $carrier_data['isActive'];
                    }

                    $request_body[ $region ][ $carrier_type ][ $carrier_method ] = array(
                        'isActive' => $is_active
                    );
                }
            }
        }

        return $request_body;
	}

    /**
     * Ajax endpoint for saving the settings
     * @throws PrintfulException
     */
	public static function save_printful_settings() {

		if ( ! empty( $_POST ) ) {

			check_admin_referer( 'printful_settings' );
			$error_msg = null;

			//save carriers first, so API key change does not affect this
			if ( Printful_Integration::instance()->is_connected(true) ) {

				//save remote carrier settings
				$request_body = Printful_Admin_Settings::instance()->prepare_carriers();
				$result = Printful_Carriers::instance()->post_carriers( $request_body );

				if ( ! $result ) {
					$error_msg = 'Error: failed to save carriers';
				} else if (isset($result['error'])) {
					$error_msg = $result['error'];
                }
			}

			$options = array();

			//build save options list
			foreach ( self::getAllFields() as $key => $field ) {

				if ( $field['type'] == 'checkbox' ) {
					if ( isset( $_POST[ $key ] ) ) {
						$options[ $key ] = 'yes';
					} else {
						$options[ $key ] = 'no';
					}
				} else {
					if ( isset( $_POST[ $key ] ) ) {
						$options[ $key ] = $_POST[ $key ];
					}
				}
			}

			//save integration settings
			Printful_Integration::instance()->update_settings( $options );

			if ( $error_msg ) {
				die( $error_msg );
			}

			die('OK');
		}
	}

    /**
     * @param array $carrier_regions
     * @return array|bool
     */
    private function prepare_form_data( $carrier_regions )
    {
        $carrier_settings = array();

        foreach ( $carrier_regions as $region => $carrier_types ) {
            foreach ($carrier_types as $carrier_type => $carrier_data) {
                foreach ($carrier_data as $key => $carrier) {
                    $carrier_item = array(
                        'label' => $carrier[ 'label' ] . ' <i>' . $carrier[ 'subtitle' ] . '</i>',
                        'value' => ( $carrier[ 'isActive' ] == true ? 'yes' : 'no' ),
                        'isDisabled' => $carrier[ 'isDisabled' ]
                    );

                    $carrier_regions[ $region ][ $carrier_type ][ $carrier['key'] ] = $carrier_item;
                }
            }
        }

        $item_array = $this->format_carrier_items( $carrier_regions );

        $carrier_settings[ self::CARRIER_REGION_US ] = array(
            'title' => __( 'International from USA', 'printful' ),
            'type'  => 'checkbox-group',
            'items' => $item_array[ self::CARRIER_REGION_US ]
        );

        $carrier_settings[ self::CARRIER_REGION_EU ] = array(
            'title' => __( 'International from EU', 'printful' ),
            'type'  => 'checkbox-group',
            'items' => $item_array[ self::CARRIER_REGION_EU ]
        );

        return $carrier_settings;
    }

    /**
     * Prepare carrier item data for form
     * @param array $carrier_regions
     * @return array
     */
    private function format_carrier_items( $carrier_regions )
    {
        $item_array = array();

        foreach ( $carrier_regions as $region => $carrier_types ) {
            foreach ( $carrier_types as $carrier_type => $carrier_data ) {
                $item_array[ $region ][ $carrier_type ] = array(
                    'subtitle' => ucfirst($carrier_type) . ' shipping',
                    'carriers' => $carrier_data
                );
            }
        }

        return array(
            self::CARRIER_REGION_US => $item_array[ self::CARRIER_REGION_US ],
            self::CARRIER_REGION_EU => $item_array[ self::CARRIER_REGION_EU ]
        );
    }

    /**
     * @param array $us_carriers
     * @param array $eu_carriers
     * @return array
     */
    private function parse_region_carriers( $us_carriers, $eu_carriers )
    {
        foreach ( $us_carriers[ self::CARRIER_REGION_US ] as $carrier_type => $carrier_methods ) {
            foreach ( $carrier_methods as $key => $carrier_method ) {
                $us_carriers[ self::CARRIER_REGION_US ][ $carrier_type ][ $key ] = str_replace( self::CARRIER_REGION_US . '_' . $carrier_type . '_', '', $carrier_method );
            }
        }

        foreach ( $eu_carriers[ self::CARRIER_REGION_EU ] as $carrier_type => $carrier_methods ) {
            foreach ( $carrier_methods as $key => $carrier_method ) {
                $eu_carriers[ self::CARRIER_REGION_EU ][ $carrier_type ][ $key ] = str_replace( self::CARRIER_REGION_EU . '_' . $carrier_type.'_', '', $carrier_method );
            }
        }

        return array(
            'eu_carriers' => $eu_carriers,
            'us_carriers' => $us_carriers
        );
    }
}