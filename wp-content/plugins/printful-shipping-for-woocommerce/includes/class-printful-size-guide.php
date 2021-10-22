<?php


class Printful_Size_Guide {
    /**
     * Bump this everytime you make changes to size guide CSS file
     */
    const CSS_VERSION = '1';

	public static function init() {
		$sizeGuide = new self();

		$sizeGuide->hook_actions();
	}

	public function hook_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_size_guide_scripts' ) );
	}

	public function load_size_guide_scripts() {
		global $post;

		$sizeGuideData = self::get_size_guide_for_product( $post );
		if ( ! $sizeGuideData ) {
			return;
		}

		$handle = 'printful-product-size-guide';
		wp_enqueue_script( $handle, plugins_url( '../assets/js/product-size-guide.js', __FILE__ ) );
		wp_localize_script(
			$handle,
			'pfGlobal',
			array(
				'sg_modal_title'                 => Printful_Integration::instance()
				                                                        ->get_option( 'pfsg_modal_title', Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_MODAL_TITLE ),
				'sg_modal_text_color'            => Printful_Integration::instance()
				                                                        ->get_option( 'pfsg_modal_text_color', Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_MODAL_TEXT_COLOR ),
				'sg_modal_background_color'      => Printful_Integration::instance()
				                                                        ->get_option(
					                                                        'pfsg_modal_background_color',
					                                                        Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_MODAL_BACKGROUND_COLOR
				                                                        ),
				'sg_tab_background_color'        => Printful_Integration::instance()
				                                                        ->get_option(
					                                                        'pfsg_tab_background_color',
					                                                        Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_TAB_BACKGROUND_COLOR
				                                                        ),
				'sg_active_tab_background_color' => Printful_Integration::instance()
				                                                        ->get_option(
					                                                        'pfsg_active_tab_background_color',
					                                                        Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_ACTIVE_TAB_BACKGROUND_COLOR
				                                                        ),
				'sg_primary_unit'                => Printful_Integration::instance()
				                                                        ->get_option( 'pfsg_primary_unit', Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_UNIT ),
				'sg_data_raw'                    => json_encode( $sizeGuideData ),
				'sg_tab_title_person'            => __( 'Measure yourself', 'printful' ),
				'sg_tab_title_product'           => __( 'Product measurements', 'printful' ),
				'sg_table_header_size'           => __( 'Size', 'printful' ),
				'sg_unit_translations'           => json_encode( [
					'inch'       => __( 'Inches', 'printful' ),
					'centimeter' => __( 'Centimeters', 'printful' ),
				] )
			)
		);
		wp_register_style( $handle, plugins_url( '../assets/css/size-guide.css', __FILE__ ), [], self::CSS_VERSION );
		wp_enqueue_style( $handle );
	}

	/**
	 * @param WP_Post $product
	 *
	 * @return array|null
	 */
	public static function get_size_guide_for_product( $product = null ) {
		if ( ! $product || ! get_post_meta( $product->ID, 'pf_advanced_size_chart', true ) ) {
			return null;
		}

		// Size guide data should be a valid JSON
		$size_guide_data = json_decode( get_post_meta( $product->ID, 'pf_advanced_size_chart', true ), true );

		if ( ! self::is_size_guide_valid( $size_guide_data ) ) {
			return null;
		}

		// URLs to size guide images
		if ( isset( $size_guide_data['modelMeasurements']['imageId'] ) ) {
			$size_guide_data['modelMeasurements']['imageUrl'] = self::get_attached_image_src( $size_guide_data['modelMeasurements']['imageId'] );
		}

		if ( isset( $size_guide_data['productMeasurements']['imageId'] ) ) {
			$size_guide_data['productMeasurements']['imageUrl'] = self::get_attached_image_src( $size_guide_data['productMeasurements']['imageId'] );
		}

		return $size_guide_data;
	}

	/**
	 * @param array $size_guide
	 *
	 * @return bool
	 */
	public static function is_size_guide_valid( $size_guide = null ) {
		if ( empty( $size_guide ) || ! is_array( $size_guide ) ) {
			return false;
		}

		// Size guide should have at least model or product measurement data
		if ( ( empty( $size_guide['modelMeasurements'] ) && empty( $size_guide['productMeasurements'] ) )
		     || empty( $size_guide['availableSizes'] ) ) {
			return false;
		}

		// Validate size guide rows
		$rows = [];
		if ( ! empty( $size_guide['modelMeasurements']['sizeTableRows'] ) ) {
			$rows += $size_guide['modelMeasurements']['sizeTableRows'];
		}

		if ( ! empty( $size_guide['productMeasurements']['sizeTableRows'] ) ) {
			$rows += $size_guide['productMeasurements']['sizeTableRows'];
		}

		foreach ( $rows as $row ) {
			if ( empty( $row['unit'] ) || empty( $row['sizes'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param int $attachment_id
	 *
	 * @return string|null
	 */
	public static function get_attached_image_src( $attachment_id ) {
		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! is_array( $attachment ) ) {
			return null;
		}

		return current( $attachment );
	}

	/**
	 * Creates file from given url and stores as post attachment
	 *
	 * @param string $url
	 * @param string $file_name
	 *
	 * @return int|WP_Error
	 */
	public static function save_image( $url, $file_name ) {
		if ( ! $url ) {
			return null;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Download size guide img to temp file
		$temp_file_name = download_url( $url, 20 );
		if ( is_wp_error( $temp_file_name ) ) {
			return $temp_file_name;
		}

		// Validate image readability
		require_once ABSPATH . 'wp-admin/includes/image.php';
		if ( ! file_is_displayable_image( $temp_file_name ) ) {
			@unlink( $temp_file_name );

			return new WP_Error( 'pf_size_guide_img', 'Size guide attachment is not a valid image' );
		}

		$file_data = array(
			'name'     => $file_name,
			'tmp_name' => $temp_file_name,
		);

		// Handle side load and attach to a post
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$id = media_handle_sideload( $file_data );

		@unlink( $temp_file_name );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return $id;
	}

	/**
	 * @param array $size_guide_data
	 * @param int $product_id
	 *
	 * @return array
	 */
	public static function process_size_guide_for_storage( $size_guide_data, $product_id ) {
		$modelImageUrl = ! empty( $size_guide_data['modelMeasurements']['imageUrl'] )
			? $size_guide_data['modelMeasurements']['imageUrl'] : null;

		$productImageUrl = ! empty( $size_guide_data['productMeasurements']['imageUrl'] )
			? $size_guide_data['productMeasurements']['imageUrl'] : null;

		// Remove original URLs
		unset( $size_guide_data['modelMeasurements']['imageUrl'] );
		unset( $size_guide_data['productMeasurements']['imageUrl'] );

		$modelImageId   = self::save_image( $modelImageUrl, $product_id . '_model_size_guide.png' );
		$productImageId = self::save_image( $productImageUrl, $product_id . '_product_size_guide.png' );

		// Link size guide image attachments to size guide
		$size_guide_data['modelMeasurements']['imageId']   = ! is_wp_error( $modelImageId ) ? $modelImageId : null;
		$size_guide_data['productMeasurements']['imageId'] = ! is_wp_error( $productImageId ) ? $productImageId : null;

		return $size_guide_data;
	}
}
