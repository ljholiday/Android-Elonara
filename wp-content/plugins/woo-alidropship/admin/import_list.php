<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VI_WOO_ALIDROPSHIP_Admin_Import_List
 */
class VI_WOO_ALIDROPSHIP_Admin_Import_List {
	private static $settings;
	public static $process;
	private static $categories_options;
	private static $tags_options;
	private static $shipping_class_options;
	public static $process_image;
	public static $download_description;

	public function __construct() {
		self::$settings           = VI_WOO_ALIDROPSHIP_DATA::get_instance();
		self::$categories_options = self::$shipping_class_options = self::$tags_options = '';
		VILLATHEME_ADMIN_SHOW_MESSAGE::get_instance();
		add_action( 'init', array( $this, 'background_process' ) );
		add_action( 'admin_init', array( $this, 'empty_import_list' ) );
		add_action( 'admin_init', array( $this, 'move_queued_images' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 999999 );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
		add_action( 'wp_ajax_vi_wad_import', array( $this, 'import' ) );
		add_action( 'wp_ajax_vi_wad_switch_product_attributes_values', array(
			$this,
			'switch_product_attributes_values'
		) );
		add_action( 'wp_ajax_vi_wad_select_shipping', array(
			$this,
			'select_shipping'
		) );
		add_action( 'wp_ajax_vi_wad_load_variations_table', array(
			$this,
			'load_variations_table'
		) );
		add_action( 'wp_ajax_vi_wad_override', array( $this, 'override' ) );
		add_action( 'wp_ajax_vi_wad_remove', array( $this, 'remove' ) );
		add_action( 'wp_ajax_vi_wad_save_attributes', array( $this, 'save_attributes' ) );
		add_action( 'wp_ajax_vi_wad_remove_attribute', array( $this, 'ajax_remove_attribute' ) );
		add_action( 'admin_head', array( $this, 'menu_product_count' ), 999 );
	}

	public function ajax_remove_attribute() {
		$response = array(
			'status'  => 'error',
			'html'    => '',
			'message' => esc_html__( 'Invalid data', 'woo-alidropship' ),
		);
		parse_str( $_POST['form_data'], $form_data );
		$data             = isset( $form_data['vi_wad_product'] ) ? $form_data['vi_wad_product'] : array();
		$product_data     = array_values( $data )[0];
		$product_id       = array_keys( $data )[0];
		$attribute_value  = isset( $_POST['attribute_value'] ) ? sanitize_text_field( stripslashes( $_POST['attribute_value'] ) ) : '';
		$remove_attribute = isset( $product_data['attributes'] ) ? stripslashes_deep( $product_data['attributes'] ) : array();
		$product          = get_post( $product_id );
		if ( $product && $product->post_type === 'vi_wad_draft_product' && in_array( $product->post_status, array(
				'draft',
				'override'
			) ) ) {
			$attributes       = get_post_meta( $product_id, '_vi_wad_attributes', true );
			$variations       = get_post_meta( $product_id, '_vi_wad_variations', true );
			$split_variations = get_post_meta( $product_id, '_vi_wad_split_variations', true );
			if ( self::remove_product_attribute( $product_id, $remove_attribute, $attribute_value, $split_variations, $attributes, $variations ) ) {
				$response['status'] = 'success';
				if ( ! count( $attributes ) ) {
					$key                         = isset( $_POST['product_index'] ) ? absint( sanitize_text_field( $_POST['product_index'] ) ) : '';
					$currency                    = 'USD';
					$woocommerce_currency        = get_woocommerce_currency();
					$woocommerce_currency_symbol = get_woocommerce_currency_symbol();
					$manage_stock                = self::$settings->get_params( 'manage_stock' );
					$use_different_currency      = false;
//					$variations                  = self::get_product_variations( $product_id );
					$decimals = wc_get_price_decimals();
					if ( $decimals < 1 ) {
						$decimals = 1;
					} else {
						$decimals = pow( 10, ( - 1 * $decimals ) );
					}
					if ( strtolower( $woocommerce_currency ) != strtolower( $currency ) ) {
						$use_different_currency = true;
					}
					ob_start();
					self::simple_product_price_field_html( $key, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, '', '' );
					$response['html'] = ob_get_clean();
				}
				$response['message'] = esc_html__( 'Remove attribute successfully', 'woo-alidropship' );
			}
		} else {
			$response['message'] = esc_html__( 'Invalid product', 'woo-alidropship' );
		}
		wp_send_json( $response );
	}

	public static function remove_product_attribute( $product_id, $remove_attribute, $attribute_value, $split_variations, &$attributes, &$variations ) {
		$remove = false;
		if ( count( $remove_attribute ) && count( $attributes ) ) {
			$new_attribute_v = array_values( $remove_attribute )[0];
			$attribute_k     = array_keys( $remove_attribute )[0];
			if ( ( ! isset( $new_attribute_v['name'] ) || $new_attribute_v['name'] ) && isset( $attributes[ $attribute_k ] ) ) {
				$attribute_slug = isset( $attributes[ $attribute_k ]['slug_edited'] ) ? $attributes[ $attribute_k ]['slug_edited'] : $attributes[ $attribute_k ]['slug'];
				foreach ( $variations as $variation_k => $variation ) {
					if ( isset( $variation['attributes_edited'] ) ) {
						if ( isset( $variation['attributes_edited'][ $attribute_slug ] ) ) {
							if ( ! self::is_attribute_value_equal( $variation['attributes_edited'][ $attribute_slug ], $attribute_value ) ) {
								unset( $variations[ $variation_k ] );
								if ( is_array( $split_variations ) && count( $split_variations ) ) {
									$search = array_search( $variation['skuAttr'], $split_variations );
									if ( $search !== false ) {
										unset( $split_variations[ $search ] );
									} else {
										$search = array_search( "{$variation['skuId']}{$variation['skuAttr']}", $split_variations );
										if ( $search !== false ) {
											unset( $split_variations[ $search ] );
										}
									}
								}
							}
							unset( $variations[ $variation_k ]['attributes_edited'][ $attribute_slug ] );
						}
					} else {
						if ( isset( $variation['attributes'][ $attribute_slug ] ) ) {
							if ( ! self::is_attribute_value_equal( $variation['attributes'][ $attribute_slug ], $attribute_value ) ) {
								unset( $variations[ $variation_k ] );
								if ( is_array( $split_variations ) && count( $split_variations ) ) {
									$search = array_search( $variation['skuAttr'], $split_variations );
									if ( $search !== false ) {
										unset( $split_variations[ $search ] );
									} else {
										$search = array_search( "{$variation['skuId']}{$variation['skuAttr']}", $split_variations );
										if ( $search !== false ) {
											unset( $split_variations[ $search ] );
										}
									}
								}
							}
							unset( $variations[ $variation_k ]['attributes'][ $attribute_slug ] );
						}
					}
				}
				unset( $attributes[ $attribute_k ] );
				$variations = array_values( $variations );
				update_post_meta( $product_id, '_vi_wad_attributes', $attributes );
				update_post_meta( $product_id, '_vi_wad_variations', $variations );
				if ( is_array( $split_variations ) ) {
					update_post_meta( $product_id, '_vi_wad_split_variations', $split_variations );
				}
				$remove = true;
			}
		}

		return $remove;
	}

	public function empty_import_list() {
		global $wpdb;
		$page = isset( $_GET['page'] ) ? wp_unslash( $_GET['page'] ) : '';
		if ( ! empty( $_GET['vi_wad_empty_product_list'] ) && $page === 'woo-alidropship-import-list' ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
			if ( wp_verify_nonce( $nonce ) ) {
				$posts = "{$wpdb->prefix}posts";
				$wpdb->query( "DELETE from {$posts} WHERE {$posts}.post_type='vi_wad_draft_product' AND {$posts}.post_status='draft'" );
				wp_safe_redirect( admin_url( "admin.php?page={$page}" ) );
				exit();
			}
		}
	}

	public function move_queued_images() {
		global $wpdb;
		if ( ! empty( $_GET['vi_wad_move_queued_images'] ) ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
			if ( wp_verify_nonce( $nonce ) ) {
				$table   = "{$wpdb->prefix}options";
				$query   = "select * from {$table} where option_name like '%vi_wad_background_download_images_batch%'";
				$results = $wpdb->get_results( $query, ARRAY_A );
				foreach ( $results as $result ) {
					$images = maybe_unserialize( $result['option_value'] );
					$delete = false;
					foreach ( $images as $image ) {
						if ( get_post_type( $image['woo_product_id'] ) === 'product' ) {
							if ( VI_WOO_ALIDROPSHIP_Error_Images_Table::insert( $image['woo_product_id'], implode( ',', $image['product_ids'] ), $image['src'], intval( $image['set_gallery'] ) ) ) {
								$delete = true;
							}
						} else {
							$delete = true;
						}
					}
					if ( $delete ) {
						delete_option( $result['option_name'] );
					}
				}
				wp_safe_redirect( remove_query_arg( array( 'vi_wad_move_queued_images' ) ) );
				exit();
			}
		}
	}

	public function save_attributes() {
		$response = array(
			'status'       => 'error',
			'new_slug'     => '',
			'change_value' => false,
			'message'      => '',
		);
		parse_str( $_POST['form_data'], $form_data );
		$data          = isset( $form_data['vi_wad_product'] ) ? $form_data['vi_wad_product'] : array();
		$product_data  = array_values( $data )[0];
		$product_id    = array_keys( $data )[0];
		$new_attribute = isset( $product_data['attributes'] ) ? stripslashes_deep( $product_data['attributes'] ) : array();
		$attributes    = get_post_meta( $product_id, '_vi_wad_attributes', true );
		$variations    = get_post_meta( $product_id, '_vi_wad_variations', true );
		$change_slug   = '';
		$change_value  = false;
		if ( count( $new_attribute ) && count( $attributes ) ) {
			$response['status'] = 'success';
			$new_attribute_v    = array_values( $new_attribute )[0];
			$attribute_k        = array_keys( $new_attribute )[0];
			if ( ! empty( $new_attribute_v['name'] ) && isset( $attributes[ $attribute_k ] ) ) {
				$new_slug       = VI_WOO_ALIDROPSHIP_DATA::sanitize_taxonomy_name( $new_attribute_v['name'] );
				$attribute_slug = isset( $attributes[ $attribute_k ]['slug_edited'] ) ? $attributes[ $attribute_k ]['slug_edited'] : $attributes[ $attribute_k ]['slug'];
				if ( ! self::is_attribute_value_equal( $new_slug, $attribute_slug ) ) {
					$change_slug = $new_slug;
					foreach ( $variations as $variation_k => $variation ) {
						$v_attributes = isset( $variation['attributes_edited'] ) ? $variation['attributes_edited'] : $variation['attributes'];
						if ( isset( $v_attributes[ $attribute_slug ] ) ) {
							$v_attributes[ $new_slug ] = $v_attributes[ $attribute_slug ];
							unset( $v_attributes[ $attribute_slug ] );
							$variations[ $variation_k ]['attributes_edited'] = $v_attributes;
						}
					}
					$attributes[ $attribute_k ]['slug_edited'] = $new_slug;
					$attributes[ $attribute_k ]['name_edited'] = $new_attribute_v['name'];
					$attribute_slug                            = $new_slug;
				}
				if ( ! empty( $new_attribute_v['values'] ) ) {
					$new_values    = $new_attribute_v['values'];
					$values_edited = isset( $attributes[ $attribute_k ]['values_edited'] ) ? $attributes[ $attribute_k ]['values_edited'] : $attributes[ $attribute_k ]['values'];
					foreach ( $values_edited as $value_k => $value ) {
						if ( ! empty( $new_values[ $value_k ] ) ) {
							$new_value = trim( $new_values[ $value_k ] );
							if ( $new_value !== $value ) {
								$change_value = true;
								foreach ( $variations as $variation_k => $variation ) {
									$v_attributes = isset( $variation['attributes_edited'] ) ? $variation['attributes_edited'] : $variation['attributes'];
									if ( isset( $v_attributes[ $attribute_slug ] ) && self::is_attribute_value_equal( $v_attributes[ $attribute_slug ], $value ) ) {

										$values_edited[ $value_k ] = $new_value;

										$v_attributes[ $attribute_slug ] = $new_value;

										$variations[ $variation_k ]['attributes_edited'] = $v_attributes;
									}
								}
								$attributes[ $attribute_k ]['values_edited'] = $values_edited;
							}
						}
					}
				}
			}
		}
		if ( $change_slug || $change_value ) {
			update_post_meta( $product_id, '_vi_wad_attributes', $attributes );
			update_post_meta( $product_id, '_vi_wad_variations', $variations );
		}
		$response['new_slug']     = $change_slug;
		$response['change_value'] = $change_value;
		wp_send_json( $response );
	}

	public static function is_attribute_value_equal( $value_1, $value_2 ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return ( mb_strtolower( $value_1 ) === mb_strtolower( $value_2 ) );
		} else {
			return ( strtolower( $value_1 ) === strtolower( $value_2 ) );
		}
	}

	public function load_variations_table() {
		$key        = isset( $_GET['product_index'] ) ? absint( sanitize_text_field( $_GET['product_index'] ) ) : '';
		$product_id = isset( $_GET['product_id'] ) ? sanitize_text_field( $_GET['product_id'] ) : '';
		if ( $key > - 1 && $product_id ) {
			$currency                    = 'USD';
			$woocommerce_currency        = get_woocommerce_currency();
			$woocommerce_currency_symbol = get_woocommerce_currency_symbol();
			$manage_stock                = self::$settings->get_params( 'manage_stock' );
			$use_different_currency      = false;
//			$variations                  = get_post_meta( $product_id, '_vi_wad_variations', true );
			$variations = self::get_product_variations( $product_id );
			$decimals   = wc_get_price_decimals();
			if ( $decimals < 1 ) {
				$decimals = 1;
			} else {
				$decimals = pow( 10, ( - 1 * $decimals ) );
			}
			if ( strtolower( $woocommerce_currency ) != strtolower( $currency ) ) {
				$use_different_currency = true;
			}
//			$attributes = get_post_meta( $product_id, '_vi_wad_attributes', true );
			$attributes = self::get_product_attributes( $product_id );
			$parent     = array();
			if ( is_array( $attributes ) && count( $attributes ) ) {
				foreach ( $attributes as $attribute_k => $attribute_v ) {
					$parent[ $attribute_k ] = $attribute_v['slug'];
				}
			}
			ob_start();
			self::variation_html( $key, $parent, $attributes, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, false );
			$return = ob_get_clean();
			wp_send_json(
				array(
					'status' => 'success',
					'data'   => $return
				)
			);
		} else {
			wp_send_json(
				array(
					'status' => 'error',
					'data'   => esc_html__( 'Missing required arguments', 'woo-alidropship' )
				)
			);
		}
	}

	public function select_shipping() {
		$key          = isset( $_POST['product_index'] ) ? absint( sanitize_text_field( $_POST['product_index'] ) ) : '';
		$product_id   = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		$product_type = isset( $_POST['product_type'] ) ? sanitize_text_field( $_POST['product_type'] ) : '';
		$country      = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$company      = isset( $_POST['company'] ) ? sanitize_text_field( $_POST['company'] ) : '';
		if ( $key > - 1 && $product_id && $product_type ) {
			$currency                    = 'USD';
			$woocommerce_currency        = get_woocommerce_currency();
			$woocommerce_currency_symbol = get_woocommerce_currency_symbol();
			$manage_stock                = self::$settings->get_params( 'manage_stock' );
			$use_different_currency      = false;
//			$variations                  = get_post_meta( $product_id, '_vi_wad_variations', true );
			$variations = self::get_product_variations( $product_id );
			$decimals   = wc_get_price_decimals();
			if ( $decimals < 1 ) {
				$decimals = 1;
			} else {
				$decimals = pow( 10, ( - 1 * $decimals ) );
			}
			if ( strtolower( $woocommerce_currency ) != strtolower( $currency ) ) {
				$use_different_currency = true;
			}
			ob_start();
			if ( $product_type === 'variable' ) {
//				$attributes = get_post_meta( $product_id, '_vi_wad_attributes', true );
				$attributes = self::get_product_attributes( $product_id );
				$parent     = array();
				if ( is_array( $attributes ) && count( $attributes ) ) {
					foreach ( $attributes as $attribute_k => $attribute_v ) {
						$parent[ $attribute_k ] = $attribute_v['slug'];
					}
				}
				self::variation_html( $key, $parent, $attributes, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, false, $country, $company );
			} else {
				self::simple_product_price_field_html( $key, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, $country, $company );
			}
			$return = ob_get_clean();
			wp_send_json(
				array(
					'status' => 'success',
					'data'   => $return
				)
			);
		} else {
			wp_send_json(
				array(
					'status' => 'error',
					'data'   => esc_html__( 'Missing required arguments', 'woo-alidropship' )
				)
			);
		}
	}

	public function admin_enqueue_scripts() {
		$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
		global $pagenow;
		if ( $pagenow !== 'admin.php' ) {
			return;
		}
		if ( $page === 'woo-alidropship-import-list' ) {
			VI_WOO_ALIDROPSHIP_Admin_Settings::enqueue_semantic();
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_style( 'woo-alidropship-admin-style', VI_WOO_ALIDROPSHIP_CSS . 'import-list.css', '', VI_WOO_ALIDROPSHIP_VERSION );
			wp_enqueue_script( 'woo-alidropship-import-list', VI_WOO_ALIDROPSHIP_JS . 'import-list.js', array( 'jquery' ), VI_WOO_ALIDROPSHIP_VERSION );
			wp_localize_script( 'woo-alidropship-import-list', 'vi_wad_import_list_params', array(
				'url'                              => admin_url( 'admin-ajax.php' ),
				'decimals'                         => wc_get_price_decimals(),
				'i18n_empty_variation_error'       => esc_attr__( 'Please select at least 1 variation to import.', 'woo-alidropship' ),
				'i18n_empty_price_error'           => esc_attr__( 'Regular price can not be empty.', 'woo-alidropship' ),
				'i18n_sale_price_error'            => esc_attr__( 'Sale price must be smaller than regular price.', 'woo-alidropship' ),
				'i18n_not_found_error'             => esc_attr__( 'No product found.', 'woo-alidropship' ),
				'i18n_import_all_confirm'          => esc_attr__( 'Import all products on this page to your WooCommerce store?', 'woo-alidropship' ),
				'i18n_remove_product_confirm'      => esc_attr__( 'Remove this product from import list?', 'woo-alidropship' ),
				'i18n_bulk_remove_product_confirm' => esc_html__( 'Remove selected product(s) from import list?', 'woo-alidropship' ),
				'i18n_bulk_import_product_confirm' => esc_html__( 'Import all selected product(s)?', 'woo-alidropship' ),
			) );
			add_action( 'admin_footer', array( $this, 'set_price_modal' ) );
			add_action( 'admin_footer', array( $this, 'override_product_options' ) );
		}
	}

	public function override_product_options() {
		$all_options = array(
			'override-title'       => esc_html__( 'Replace product title', 'woo-alidropship' ),
			'override-images'      => esc_html__( 'Replace product image and gallery', 'woo-alidropship' ),
			'override-description' => esc_html__( 'Replace description and short description', 'woo-alidropship' ),
			'override-hide'        => wp_kses_post( __( 'Save my choices and do not show these options again(you can still change this in <a target="_blank" href="admin.php?page=woo-alidropship#/override">plugin settings</a>).', 'woo-alidropship' ) ),
		);
		?>
        <div class="<?php echo esc_attr( self::set( array(
			'override-product-options-container',
			'hidden'
		) ) ) ?>">
            <div class="<?php echo esc_attr( self::set( 'override-product-overlay' ) ) ?>"></div>
            <div class="<?php echo esc_attr( self::set( 'override-product-options-content' ) ) ?>">
                <div class="<?php echo esc_attr( self::set( 'override-product-options-content-header' ) ) ?>">
                    <h2><?php esc_html_e( 'Override: ', 'woo-alidropship' ) ?><span
                                class="<?php echo esc_attr( self::set( 'override-product-title' ) ) ?>"></span>
                    </h2>
                    <span class="<?php echo esc_attr( self::set( 'override-product-options-close' ) ) ?>"></span>
                    <div class="vi-ui message warning"><?php esc_html_e( 'Overridden product and all of its data(including variations, reviews, metadata...) will be deleted. Please make sure you had backed up those kinds of data before continuing!', 'woo-alidropship' ) ?></div>
                </div>
				<?php
				if ( ! self::$settings->get_params( 'override_hide' ) ) {
					?>
                    <div class="<?php echo esc_attr( self::set( array(
						'override-product-options-content-body',
						'override-product-options-content-body-option'
					) ) ) ?>">
						<?php
						foreach ( $all_options as $option_key => $option_value ) {
							?>
                            <div class="<?php echo esc_attr( self::set( array(
								'override-product-options-content-body-row',
								"override-product-options-content-body-row-{$option_key}"
							) ) ) ?>">
                                <div class="<?php echo esc_attr( self::set( 'override-product-options-option-wrap' ) ) ?>">
                                    <input type="checkbox"
                                           value="1" <?php checked( 1, self::$settings->get_params( str_replace( '-', '_', $option_key ) ) ) ?>
                                           data-order_option="<?php echo esc_attr( $option_key ) ?>"
                                           id="<?php echo esc_attr( self::set( 'override-product-options-' . $option_key ) ) ?>"
                                           class="<?php echo esc_attr( self::set( array(
										       'override-product-options-option',
										       'override-product-options-' . $option_key
									       ) ) ) ?>">
                                    <label for="<?php echo esc_attr( self::set( 'override-product-options-' . $option_key ) ) ?>"><?php echo $option_value ?></label>
                                </div>
                            </div>
							<?php
						}
						?>
                    </div>
					<?php
				}
				?>
                <div class="<?php echo esc_attr( self::set( array(
					'override-product-options-content-body',
					'override-product-options-content-body-override-old'
				) ) ) ?>">
                </div>
                <div class="<?php echo esc_attr( self::set( 'override-product-options-content-footer' ) ) ?>">
                    <span class="vi-ui button mini positive <?php echo esc_attr( self::set( array(
	                    'override-product-options-button-override',
                    ) ) ) ?>" data-override_product_id=""><?php esc_html_e( 'Override', 'woo-alidropship' ) ?></span>
                    <span class="vi-ui button mini <?php echo esc_attr( self::set( array(
						'override-product-options-button-cancel',
					) ) ) ?>"><?php esc_html_e( 'Cancel', 'woo-alidropship' ) ?></span>
                </div>
            </div>
            <div class="<?php echo esc_attr( self::set( 'override-product-overlay' ) ) ?>"></div>
        </div>
		<?php
	}

	public function set_price_modal() {
		?>
        <div class="<?php echo esc_attr( self::set( array( 'modal-popup-container', 'hidden' ) ) ) ?>">
            <div class="<?php echo esc_attr( self::set( 'overlay' ) ) ?>"></div>
            <div class="<?php echo esc_attr( self::set( array(
				'modal-popup-content',
				'modal-popup-content-set-price'
			) ) ) ?>">
                <div class="<?php echo esc_attr( self::set( 'modal-popup-header' ) ) ?>">
                    <h2><?php esc_html_e( 'Set price', 'woo-alidropship' ) ?></h2>
                    <span class="<?php echo esc_attr( self::set( 'modal-popup-close' ) ) ?>"></span>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-body' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( 'modal-popup-content-body-row' ) ) ?>">
                        <div class="<?php echo esc_attr( self::set( 'set-price-action-wrap' ) ) ?>">
                            <label for="<?php echo esc_attr( self::set( 'set-price-action' ) ) ?>"><?php esc_html_e( 'Action', 'woo-alidropship' ) ?></label>
                            <select id="<?php echo esc_attr( self::set( 'set-price-action' ) ) ?>"
                                    class="<?php echo esc_attr( self::set( 'set-price-action' ) ) ?>">
                                <option value="set_new_value"><?php esc_html_e( 'Set to this value', 'woo-alidropship' ) ?></option>
                                <option value="increase_by_fixed_value"><?php esc_html_e( 'Increase by fixed value(' . get_woocommerce_currency_symbol() . ')', 'woo-alidropship' ) ?></option>
                                <option value="increase_by_percentage"><?php esc_html_e( 'Increase by percentage(%)', 'woo-alidropship' ) ?></option>
                            </select>
                        </div>
                        <div class="<?php echo esc_attr( self::set( 'set-price-amount-wrap' ) ) ?>">
                            <label for="<?php echo esc_attr( self::set( 'set-price-amount' ) ) ?>"><?php esc_html_e( 'Amount', 'woo-alidropship' ) ?></label>
                            <input type="text"
                                   id="<?php echo esc_attr( self::set( 'set-price-amount' ) ) ?>"
                                   class="<?php echo esc_attr( self::set( 'set-price-amount' ) ) ?>">
                        </div>
                    </div>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-footer' ) ) ?>">
                        <span class="button button-primary <?php echo esc_attr( self::set( 'set-price-button-set' ) ) ?>">
                            <?php esc_html_e( 'Set', 'woo-alidropship' ) ?>
                        </span>
                    <span class="button <?php echo esc_attr( self::set( 'set-price-button-cancel' ) ) ?>">
                            <?php esc_html_e( 'Cancel', 'woo-alidropship' ) ?>
                        </span>
                </div>
            </div>
            <div class="<?php echo esc_attr( self::set( array(
				'modal-popup-content',
				'modal-popup-content-remove-attribute'
			) ) ) ?>">
                <div class="<?php echo esc_attr( self::set( 'modal-popup-header' ) ) ?>">
                    <h2><?php esc_html_e( 'Please select default value to fulfill orders after this attribute is removed', 'woo-alidropship' ) ?></h2>
                    <span class="<?php echo esc_attr( self::set( 'modal-popup-close' ) ) ?>"></span>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-body' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( array(
						'modal-popup-content-body-row',
						'modal-popup-select-attribute'
					) ) ) ?>">

                    </div>
                </div>
            </div>
            <div class="<?php echo esc_attr( self::set( array(
				'modal-popup-content',
				'modal-popup-content-set-categories'
			) ) ) ?>">
                <div class="<?php echo esc_attr( self::set( 'modal-popup-header' ) ) ?>">
                    <h2><?php esc_html_e( 'Bulk set product categories', 'woo-alidropship' ) ?></h2>
                    <span class="<?php echo esc_attr( self::set( 'modal-popup-close' ) ) ?>"></span>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-body' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( array(
						'modal-popup-content-body-row',
						'modal-popup-set-categories'
					) ) ) ?>">
                        <div class="<?php echo esc_attr( self::set( 'modal-popup-set-categories-select-wrap' ) ) ?>">
                            <select name="<?php echo esc_attr( 'vi_wad_bulk_set_categories' ) ?>"
                                    class="vi-ui dropdown fluid search <?php echo esc_attr( self::set( 'modal-popup-set-categories-select' ) ) ?>"
                                    multiple="multiple">
								<?php echo self::$categories_options; ?>
                            </select>
                            <span class="vi-ui black button mini <?php echo esc_attr( self::set( 'modal-popup-set-categories-clear' ) ) ?>"><?php esc_html_e( 'Clear selected', 'woo-alidropship' ) ?></span>
                        </div>
                    </div>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-footer' ) ) ?>">
                    <span class="button button-primary <?php echo esc_attr( self::set( 'set-categories-button-add' ) ) ?>"
                          title="<?php esc_attr_e( 'Add selected and keep existing categories', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Add', 'woo-alidropship' ) ?></span>
                    <span class="button button-primary <?php echo esc_attr( self::set( 'set-categories-button-set' ) ) ?>"
                          title="<?php esc_attr_e( 'Remove existing categories and add selected', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Set', 'woo-alidropship' ) ?></span>
                    <span class="button <?php echo esc_attr( self::set( 'set-categories-button-cancel' ) ) ?>"><?php esc_html_e( 'Cancel', 'woo-alidropship' ) ?></span>
                </div>
            </div>
            <div class="<?php echo esc_attr( self::set( array(
				'modal-popup-content',
				'modal-popup-content-set-tags'
			) ) ) ?>">
                <div class="<?php echo esc_attr( self::set( 'modal-popup-header' ) ) ?>">
                    <h2><?php esc_html_e( 'Bulk set product tags', 'woo-alidropship' ) ?></h2>
                    <span class="<?php echo esc_attr( self::set( 'modal-popup-close' ) ) ?>"></span>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-body' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( array(
						'modal-popup-content-body-row',
						'modal-popup-set-tags'
					) ) ) ?>">
                        <div class="<?php echo esc_attr( self::set( 'modal-popup-set-tags-select-wrap' ) ) ?>">
                            <select name="<?php echo esc_attr( 'vi_wad_bulk_set_tags' ) ?>"
                                    class="vi-ui dropdown fluid search <?php echo esc_attr( self::set( 'modal-popup-set-tags-select' ) ) ?>"
                                    multiple="multiple">
								<?php echo self::$tags_options; ?>
                            </select>
                            <span class="vi-ui black button mini <?php echo esc_attr( self::set( 'modal-popup-set-tags-clear' ) ) ?>"><?php esc_html_e( 'Clear selected', 'woo-alidropship' ) ?></span>
                        </div>
                    </div>
                </div>
                <div class="<?php echo esc_attr( self::set( 'modal-popup-content-footer' ) ) ?>">
                    <span class="button button-primary <?php echo esc_attr( self::set( 'set-tags-button-add' ) ) ?>"
                          title="<?php esc_attr_e( 'Add selected and keep existing tags', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Add', 'woo-alidropship' ) ?></span>
                    <span class="button button-primary <?php echo esc_attr( self::set( 'set-tags-button-set' ) ) ?>"
                          title="<?php esc_attr_e( 'Remove existing tags and add selected', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Set', 'woo-alidropship' ) ?></span>
                    <span class="button <?php echo esc_attr( self::set( 'set-tags-button-cancel' ) ) ?>"><?php esc_html_e( 'Cancel', 'woo-alidropship' ) ?></span>
                </div>
            </div>
            <div class="<?php echo esc_attr( self::set( array( 'saving-overlay', 'hidden' ) ) ) ?>"></div>
        </div>
		<?php
	}

	/**
	 * Adds the order processing count to the menu.
	 */
	public function menu_product_count() {
		global $submenu;
		if ( isset( $submenu['woo-alidropship'] ) ) {
			// Add count if user has access.
			if ( apply_filters( 'woo_aliexpress_dropship_product_count_in_menu', true ) && current_user_can( 'manage_options' ) ) {
				$count         = wp_count_posts( 'vi_wad_draft_product' );
				$product_count = $count->draft + $count->override;
				foreach ( $submenu['woo-alidropship'] as $key => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'Import List', 'Admin menu name', 'woo-alidropship' ) ) ) {
						$submenu['woo-alidropship'][ $key ][0] .= ' <span class="update-plugins count-' . esc_attr( $product_count ) . '"><span class="' . self::set( 'import-list-count' ) . '">' . number_format_i18n( $product_count ) . '</span></span>'; // WPCS: override ok.
						break;
					}
				}
			}
		}
	}

	public function remove() {
		vi_wad_set_time_limit();
		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		if ( $product_id ) {
			if ( wp_delete_post( $product_id, true ) ) {
				wp_send_json( array(
					'status'  => 'success',
					'message' => esc_html__( 'Removed', 'woo-alidropship' ),
				) );
			} else {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'Error', 'woo-alidropship' ),
				) );
			}
		} else {
			wp_send_json( array(
				'status'  => 'error',
				'message' => esc_html__( 'Not found', 'woo-alidropship' ),
			) );
		}
	}

	public static function get_override_simple_select_html( $variation ) {
		ob_start();
		?>
        <select class="vi-ui fluid dropdown <?php echo esc_attr( self::set( 'override-with' ) ) ?>">
            <option value="none"><?php esc_html_e( 'Do not replace', 'woo-alidropship' ) ?></option>
            <option value="<?php echo esc_attr( $variation['skuId'] ) ?>"><?php esc_html_e( 'Replace with new product', 'woo-alidropship' ) ?></option>
        </select>
		<?php
		return ob_get_clean();
	}

	public static function get_override_variable_select_html( $variations, $current = '' ) {
		ob_start();
		?>
        <select class="vi-ui fluid dropdown <?php echo esc_attr( self::set( 'override-with' ) ) ?>">
            <option value=""><?php esc_html_e( 'Do not replace', 'woo-alidropship' ) ?></option>
			<?php
			foreach ( $variations as $variation ) {
				$attribute = implode( ', ', array_values( $variation['attributes'] ) );
				$selected  = self::is_attribute_value_equal( $current, $attribute ) ? 'selected' : '';
				?>
                <option value="<?php echo esc_attr( $variation['skuId'] ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo $attribute ?></option>
				<?php
			}
			?>
        </select>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param $woo_product WC_Product
	 * @param $woo_product_child
	 * @param $variations
	 * @param $item_count
	 * @param string $override_with
	 *
	 * @return false|string
	 */
	public static function get_override_variation_html( $woo_product, $woo_product_child, $variations, $item_count, $override_with = '' ) {
		$html                  = '';
		$woo_product_child_obj = wc_get_product( $woo_product_child );
		if ( $woo_product_child_obj ) {
			$current = implode( ', ', $woo_product_child_obj->get_attributes() );
			ob_start();
			?>
            <tr class="<?php echo esc_attr( self::set( 'override-order-container' ) ) ?>"
                data-replace_item_id="<?php esc_attr_e( $woo_product_child ) ?>">
                <td class="<?php echo esc_attr( self::set( 'override-from-td' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( 'override-from' ) ) ?>">
						<?php
						if ( $woo_product_child_obj ) {
							if ( $woo_product_child_obj->get_image_id() ) {
								$image_src = wp_get_attachment_thumb_url( $woo_product_child_obj->get_image_id() );
							} elseif ( $woo_product->get_image_id() ) {
								$image_src = wp_get_attachment_thumb_url( $woo_product->get_image_id() );
							} else {
								$image_src = wc_placeholder_img_src();
							}
							if ( $image_src ) {
								?>
                                <div class="<?php echo esc_attr( self::set( 'override-from-image' ) ) ?>">
                                    <img src="<?php echo esc_url( $image_src ) ?>" width="30px"
                                         height="30px">
                                </div>
								<?php
							}

						}
						?>
                        <div class="<?php echo esc_attr( self::set( 'override-from-title' ) ) ?>">
							<?php
							echo $current;
							?>
                        </div>
                    </div>
                </td>
                <td>
					<?php
					echo $item_count;
					?>
                </td>
                <td class="<?php echo esc_attr( self::set( 'override-with-attributes' ) ) ?>">
					<?php
					if ( $override_with ) {
						echo $override_with;
					} else {
						echo self::get_override_variable_select_html( $variations, $current );
					}
					?>
                </td>
            </tr>
			<?php
			$html = ob_get_clean();
		}

		return $html;
	}

	/**
	 * @throws Exception
	 */
	public function override() {
		vi_wad_set_time_limit();
		parse_str( $_POST['form_data'], $form_data );
		if ( ! isset( $form_data['z_check_max_input_vars'] ) ) {
			/*z_check_max_input_vars is the last key of POST data. If it does not exist in $form_data after using parse_str(), some data may also be missing*/
			wp_send_json( array(
				'status'  => 'error',
				'message' => esc_html__( 'PHP max_input_vars is too low, please increase it in php.ini', 'woo-alidropship' ),
			) );
		}
		$data                = isset( $form_data['vi_wad_product'] ) ? $form_data['vi_wad_product'] : array();
		$selected            = isset( $_POST['selected'] ) ? vi_wad_json_decode( stripslashes_deep( $_POST['selected'] ) ) : array();
		$override_product_id = isset( $_POST['override_product_id'] ) ? sanitize_text_field( $_POST['override_product_id'] ) : '';
		$override_options    = array(
			'override_title'       => isset( $_POST['override_title'] ) ? sanitize_text_field( $_POST['override_title'] ) : '',
			'override_images'      => isset( $_POST['override_images'] ) ? sanitize_text_field( $_POST['override_images'] ) : '',
			'override_description' => isset( $_POST['override_description'] ) ? sanitize_text_field( $_POST['override_description'] ) : '',
		);
		$override_hide       = isset( $_POST['override_hide'] ) ? sanitize_text_field( $_POST['override_hide'] ) : '';
		if ( $override_hide ) {
			$params = self::$settings->get_params();
			foreach ( $override_options as $override_option_k => $override_option_v ) {
				$params[ $override_option_k ] = $override_option_v;
			}
			$params['override_hide'] = $override_hide;
			update_option( 'wooaliexpressdropship_params', $params );
		} elseif ( self::$settings->get_params( 'override_hide' ) ) {
			foreach ( $override_options as $override_option_k => $override_option_v ) {
				$override_options[ $override_option_k ] = self::$settings->get_params( $override_option_k );
			}
		}
		if ( $override_product_id ) {
			$product_data                        = array_values( $data )[0];
			$product_data['override_product_id'] = $override_product_id;
			$product_draft_id                    = array_keys( $data )[0];
			$check_orders                        = isset( $_POST['check_orders'] ) ? sanitize_text_field( $_POST['check_orders'] ) : '';
			$found_items                         = isset( $_POST['found_items'] ) ? stripslashes_deep( $_POST['found_items'] ) : array();
			$replace_items                       = isset( $_POST['replace_items'] ) ? stripslashes_deep( $_POST['replace_items'] ) : array();
			$woo_product_id                      = get_post_meta( $override_product_id, '_vi_wad_woo_id', true );
			$attributes                          = self::get_product_attributes( $product_draft_id );
			if ( ! count( $selected[ $product_draft_id ] ) ) {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'Please select at least 1 variation to import this product.', 'woo-alidropship' ),
				) );
			}
			if ( ! $product_draft_id || VI_WOO_ALIDROPSHIP_DATA::sku_exists( $product_data['sku'] ) ) {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'Sku exists.', 'woo-alidropship' ),
				) );
			}
			if ( VI_WOO_ALIDROPSHIP_DATA::product_get_id_by_aliexpress_id( get_post_meta( $product_draft_id, '_vi_wad_sku', true ), array( 'publish' ) ) ) {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'This product has already been imported', 'woo-alidropship' ),
				) );
			}
			$woo_product = wc_get_product( $woo_product_id );
			if ( $woo_product ) {
				if ( 1 != $check_orders ) {
					$is_simple = false;
					if ( ! is_array( $attributes ) || ! count( $attributes ) || ( isset( $product_data['variations'] ) && count( $selected[ $product_draft_id ] ) === 1 && self::$settings->get_params( 'simple_if_one_variation' ) ) ) {
						$is_simple = true;
					}
					if ( $is_simple ) {
						$variations      = array();
						$variation       = $product_data['variations'][0];
						$variations_html = self::get_override_simple_select_html( $variation );
					} else {
						$variations_html = '';
						if ( isset( $product_data['variations'] ) ) {
							$variations = array();
							foreach ( $selected[ $product_draft_id ] as $variation_k ) {
								if ( isset( $product_data['variations'][ $variation_k ] ) ) {
									$variations[] = $product_data['variations'][ $variation_k ];
								}
							}
						} else {
							$variations = self::get_product_variations( $product_draft_id );
						}
					}
					$replace_order_html = '';
					if ( $woo_product->is_type( 'variable' ) ) {
						$woo_product_children = $woo_product->get_children();
						if ( count( $woo_product_children ) ) {
							foreach ( $woo_product_children as $woo_product_child ) {
								$found_item = self::query_order_item_meta( array( 'order_item_type' => 'line_item' ), array(
									'meta_key'   => '_variation_id',
									'meta_value' => $woo_product_child
								) );
								self::skip_item_with_ali_order_id( $found_item );
								if ( count( $found_item ) ) {
									$found_items[ $woo_product_child ] = $found_item;
									$replace_order_html                .= self::get_override_variation_html( $woo_product, $woo_product_child, $variations, count( $found_item ), $variations_html );
								}
							}
						}
					} else {
						$found_item = self::query_order_item_meta( array( 'order_item_type' => 'line_item' ), array(
							'meta_key'   => '_product_id',
							'meta_value' => $woo_product_id,
						) );
						self::skip_item_with_ali_order_id( $found_item );
						if ( count( $found_item ) ) {
							$found_items[ $woo_product_id ] = $found_item;
							ob_start();
							?>
                            <tr class="<?php echo esc_attr( self::set( 'override-order-container' ) ) ?>"
                                data-replace_item_id="<?php esc_attr_e( $woo_product_id ) ?>">
                                <td class="<?php echo esc_attr( self::set( 'override-from-td' ) ) ?>">
                                    <div class="<?php echo esc_attr( self::set( 'override-from' ) ) ?>">
										<?php
										if ( $woo_product->get_image_id() ) {
											$image_src = wp_get_attachment_thumb_url( $woo_product->get_image_id() );
										} elseif ( $woo_product->get_image_id() ) {
											$image_src = wp_get_attachment_thumb_url( $woo_product->get_image_id() );
										} else {
											$image_src = wc_placeholder_img_src();
										}
										if ( $image_src ) {
											?>
                                            <div class="<?php echo esc_attr( self::set( 'override-from-image' ) ) ?>">
                                                <img src="<?php echo esc_url( $image_src ) ?>" width="30px"
                                                     height="30px">
                                            </div>
											<?php
										}
										?>
                                        <div class="<?php echo esc_attr( self::set( 'override-from-title' ) ) ?>">
											<?php
											echo $woo_product->get_title();
											?>
                                        </div>
                                    </div>
                                </td>
                                <td class="<?php echo esc_attr( self::set( 'override-unfulfilled-items-count' ) ) ?>">
									<?php
									echo count( $found_item );
									?>
                                </td>
                                <td class="<?php echo esc_attr( self::set( 'override-with-attributes' ) ) ?>">
									<?php
									if ( $variations_html ) {
										echo $variations_html;
									} else {
										echo self::get_override_variable_select_html( $variations );
									}
									?>
                                </td>
                            </tr>
							<?php
							$replace_order_html .= ob_get_clean();
						}
					}
					if ( count( $found_items ) ) {
						wp_send_json( array(
							'status'             => 'checked',
							'message'            => '',
							'found_items'        => $found_items,
							'replace_order_html' => '<table class="vi-ui celled table"><thead><tr><th>' . esc_html__( 'Overridden items', 'woo-alidropship' ) . '</th><th width="1%">' . esc_html__( 'Found in unfulfilled orders', 'woo-alidropship' ) . '</th><th>' . esc_html__( 'Replace', 'woo-alidropship' ) . '</th></tr></thead><tbody>' . $replace_order_html . '</tbody></table>',
						) );
					}
				}
			} else {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'Overridden product not exists', 'woo-alidropship' ),
				) );
			}
			$variations            = array();
			$variations_attributes = array();
			$attributes            = get_post_meta( $product_draft_id, '_vi_wad_attributes', true );

			if ( isset( $product_data['variations'] ) ) {
				foreach ( $selected[ $product_draft_id ] as $variation_k ) {
					if ( isset( $product_data['variations'][ $variation_k ] ) ) {
						$variations[]         = $product_data['variations'][ $variation_k ];
						$variations_attribute = isset( $product_data['variations'][ $variation_k ]['attributes'] ) ? $product_data['variations'][ $variation_k ]['attributes'] : array();
						if ( is_array( $variations_attribute ) && count( $variations_attribute ) ) {
							foreach ( $variations_attribute as $variations_attribute_k => $variations_attribute_v ) {
								if ( ! isset( $variations_attributes[ $variations_attribute_k ] ) ) {
									$variations_attributes[ $variations_attribute_k ] = array( $variations_attribute_v );
								} elseif ( ! in_array( $variations_attribute_v, $variations_attributes[ $variations_attribute_k ] ) ) {
									$variations_attributes[ $variations_attribute_k ][] = $variations_attribute_v;
								}
							}
						}
					}
				}
				$var_default = isset( $product_data['vi_wad_variation_default'] ) ? $product_data['vi_wad_variation_default'] : '';
				if ( $var_default !== '' ) {
					$product_data['variation_default'] = $variations[ $var_default ]['attributes'];
				}
				if ( is_array( $attributes ) && count( $attributes ) ) {
					foreach ( $attributes as $attributes_k => $attributes_v ) {
						if ( ! empty( $variations_attributes[ $attributes_v['slug'] ] ) ) {
							$attributes[ $attributes_k ]['values'] = array_intersect( $attributes[ $attributes_k ]['values'], $variations_attributes[ $attributes_v['slug'] ] );
						}
					}
				}
			} else {
//				$variations    = get_post_meta( $product_draft_id, '_vi_wad_variations', true );
				$variations    = self::get_product_variations( $product_draft_id );
				$shipping_cost = 0;
				if ( self::$settings->get_params( 'show_shipping_option' ) ) {
					$shipping_info = self::get_shipping_info( $product_draft_id, '', '' );
					$shipping_cost = abs( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $shipping_info['shipping_cost'] ) );
				}
				if ( self::$settings->get_params( 'shipping_cost_after_price_rules' ) ) {
					foreach ( $variations as $variations_k => $variations_v ) {
						$variation_sale_price    = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] );
						$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['regular_price'] );
						$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
						$sale_price              = self::$settings->process_price( $price, true );
						if ( $sale_price ) {
							$sale_price += $shipping_cost;
						}
						$regular_price                                = self::$settings->process_price( $price ) + $shipping_cost;
						$variations[ $variations_k ]['sale_price']    = self::$settings->process_exchange_price( $sale_price );
						$variations[ $variations_k ]['regular_price'] = self::$settings->process_exchange_price( $regular_price );
					}
				} else {
					foreach ( $variations as $variations_k => $variations_v ) {
						$variation_sale_price                         = $variations_v['sale_price'] ? ( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] ) + $shipping_cost ) : VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] );
						$variation_regular_price                      = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['regular_price'] ) + $shipping_cost;
						$price                                        = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
						$variations[ $variations_k ]['sale_price']    = self::$settings->process_exchange_price( self::$settings->process_price( $price, true ) );
						$variations[ $variations_k ]['regular_price'] = self::$settings->process_exchange_price( self::$settings->process_price( $price ) );
					}
				}
			}

			if ( count( $variations ) ) {
				if ( 1 != $override_options['override_title'] ) {
					$product_data['title'] = $woo_product->get_title();
				}
				if ( 1 != $override_options['override_images'] ) {
					$product_data['old_product_image']   = get_post_meta( $woo_product_id, '_thumbnail_id', true );
					$product_data['old_product_gallery'] = get_post_meta( $woo_product_id, '_product_image_gallery', true );
				}
				if ( 1 != $override_options['override_description'] ) {
					$product_data['short_description'] = $woo_product->get_short_description();
					$product_data['description']       = $woo_product->get_description();
				}
				$product_data['gallery'] = array_values( array_filter( $product_data['gallery'] ) );
				if ( $product_data['image'] ) {
					$product_image_key = array_search( $product_data['image'], $product_data['gallery'] );
					if ( $product_image_key !== false ) {
						unset( $product_data['gallery'][ $product_image_key ] );
						$product_data['gallery'] = array_values( $product_data['gallery'] );
					}
				}
				$variation_images                 = get_post_meta( $product_draft_id, '_vi_wad_variation_images', true );
				$product_data['attributes']       = $attributes;
				$product_data['variation_images'] = $variation_images;
				$product_data['variations']       = $variations;
				$product_data['parent_id']        = $product_draft_id;
				$product_data['replace_items']    = $replace_items;
				$product_data['replace_title']    = $override_options['override_title'];
				$product_data['found_items']      = $found_items;
				$product_data['ali_product_id']   = get_post_meta( $product_draft_id, '_vi_wad_sku', true );
				$this->import_product_to_override( $product_data );
			} else {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'Please select at least 1 variation to import this product.', 'woo-alidropship' ),
				) );
			}
		} else {
			wp_send_json( array(
				'status'  => 'error',
				'message' => esc_html__( 'Product is deleted from your store', 'woo-alidropship' ),
			) );
		}
	}

	/**
	 * @param $items
	 *
	 * @throws Exception
	 */
	public static function skip_item_with_ali_order_id( &$items ) {
		foreach ( $items as $key => $item ) {
			if ( wc_get_order_item_meta( $item['order_item_id'], '_vi_wad_aliexpress_order_id', true ) ) {
				unset( $items[ $key ] );
			}
		}
		$items = array_values( $items );
	}

	/**
	 * @param $product_data
	 *
	 * @throws Exception
	 */
	public function import_product_to_override( $product_data ) {
		$override_product_id = $product_data['override_product_id'];
		$woo_product_id      = get_post_meta( $override_product_id, '_vi_wad_woo_id', true );
		$product_id          = $this->import_product( $product_data );
		$response            = array(
			'status'     => 'error',
			'message'    => '',
			'product_id' => '',
		);
		if ( ! is_wp_error( $product_id ) ) {
			if ( $override_product_id ) {
				wp_delete_post( $override_product_id );
				wp_delete_post( $woo_product_id );
			}
			$response['status']     = 'success';
			$response['product_id'] = $product_id;
		} else {
			$response['message'] = $product_id->get_error_messages();
		}
		wp_send_json( $response );
	}

	/**
	 * @param array $args1 $key=>$value are key and value of woocommerce_order_items table
	 * @param array $args2 $key=>$value are key and value of woocommerce_order_itemmeta table
	 *
	 * @return array|null|object
	 */
	protected static function query_order_item_meta( $args1 = array(), $args2 = array() ) {
		global $wpdb;
		$sql  = "SELECT * FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta WHERE woocommerce_order_items.order_item_id=woocommerce_order_itemmeta.order_item_id";
		$args = array();
		if ( count( $args1 ) ) {
			foreach ( $args1 as $key => $value ) {
				if ( is_array( $value ) ) {
					$sql .= " AND woocommerce_order_items.{$key} IN (" . implode( ', ', array_fill( 0, count( $value ), '%s' ) ) . ")";
					foreach ( $value as $v ) {
						$args[] = $v;
					}
				} else {
					$sql    .= " AND woocommerce_order_items.{$key}='%s'";
					$args[] = $value;
				}
			}
		}
		if ( count( $args2 ) ) {
			foreach ( $args2 as $key => $value ) {
				if ( is_array( $value ) ) {
					$sql .= " AND woocommerce_order_itemmeta.{$key} IN (" . implode( ', ', array_fill( 0, count( $value ), '%s' ) ) . ")";
					foreach ( $value as $v ) {
						$args[] = $v;
					}
				} else {
					$sql    .= " AND woocommerce_order_itemmeta.{$key}='%s'";
					$args[] = $value;
				}
			}
		}
		$query      = $wpdb->prepare( $sql, $args );
		$line_items = $wpdb->get_results( $query, ARRAY_A );

		return $line_items;
	}

	public function switch_product_attributes_values() {
		$key        = isset( $_POST['product_index'] ) ? absint( sanitize_text_field( $_POST['product_index'] ) ) : '';
		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		if ( $key > - 1 && $product_id ) {
			$currency                    = 'USD';
			$woocommerce_currency        = get_woocommerce_currency();
			$woocommerce_currency_symbol = get_woocommerce_currency_symbol();
			$manage_stock                = self::$settings->get_params( 'manage_stock' );
			$use_different_currency      = false;
			$decimals                    = wc_get_price_decimals();
			$variations                  = get_post_meta( $product_id, '_vi_wad_variations', true );
			if ( is_array( $variations ) && count( $variations ) ) {
				foreach ( $variations as $variation_k => $variation ) {
					if ( isset( $variation['attributes_sub'] ) && is_array( $variation['attributes_sub'] ) && count( $variation['attributes_sub'] ) === count( $variation['attributes'] ) ) {
						$temp                                         = $variation['attributes'];
						$variations[ $variation_k ]['attributes']     = $variation['attributes_sub'];
						$variations[ $variation_k ]['attributes_sub'] = $temp;
					}
					if ( ! empty( $variation['sku'] ) ) {
						$temp                                  = $variation['sku'];
						$variations[ $variation_k ]['sku']     = $variation['sku_sub'];
						$variations[ $variation_k ]['sku_sub'] = $temp;
					}
				}
				update_post_meta( $product_id, '_vi_wad_variations', $variations );
			} else {
				wp_send_json(
					array(
						'status' => 'error',
						'data'   => esc_html__( 'Can not find replacement for product attributes values. Please remove this product and import it again with the latest version of this plugin and Chrome Extension', 'woo-alidropship' )
					)
				);
			}
			$attributes = get_post_meta( $product_id, '_vi_wad_attributes', true );
			if ( is_array( $attributes ) && count( $attributes ) ) {
				foreach ( $attributes as $attribute_k => $attribute ) {
					if ( ! empty( $attribute['values_sub'] ) ) {
						$temp                                     = $attribute['values'];
						$attributes[ $attribute_k ]['values']     = $attribute['values_sub'];
						$attributes[ $attribute_k ]['values_sub'] = $temp;
					}
				}
				update_post_meta( $product_id, '_vi_wad_attributes', $attributes );
			} else {
				wp_send_json(
					array(
						'status' => 'error',
						'data'   => esc_html__( 'Can not find replacement for product attributes values. Please remove this product and import it again with the latest version of this plugin and Chrome Extension', 'woo-alidropship' )
					)
				);
			}
			$list_attributes = get_post_meta( $product_id, '_vi_wad_list_attributes', true );
			if ( is_array( $list_attributes ) && count( $list_attributes ) ) {
				foreach ( $list_attributes as $list_attribute_k => $list_attribute ) {
					if ( ! empty( $list_attribute['name_sub'] ) ) {
						$temp                                             = $list_attribute['name'];
						$list_attributes[ $list_attribute_k ]['name']     = $list_attribute['name_sub'];
						$list_attributes[ $list_attribute_k ]['name_sub'] = $temp;
					}
				}
				update_post_meta( $product_id, '_vi_wad_list_attributes', $list_attributes );
			}
			$parent = array();
			if ( is_array( $attributes ) && count( $attributes ) ) {
				foreach ( $attributes as $attribute_k => $attribute_v ) {
					$parent[ $attribute_k ] = $attribute_v['slug'];
				}
			}
			if ( $decimals < 1 ) {
				$decimals = 1;
			} else {
				$decimals = pow( 10, ( - 1 * $decimals ) );
			}
			if ( strtolower( $woocommerce_currency ) != strtolower( $currency ) ) {
				$use_different_currency = true;
			}
			ob_start();
			self::variation_html( $key, $parent, $attributes, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, false );
			$return = ob_get_clean();
			wp_send_json(
				array(
					'status' => 'success',
					'data'   => $return
				)
			);
		} else {
			wp_send_json(
				array(
					'status' => 'error',
					'data'   => esc_html__( 'Can not find replacement for product attributes values. Please remove this product and import it again with the latest version of this plugin and Chrome Extension', 'woo-alidropship' )
				)
			);
		}
	}

	public function background_process() {
		self::$process              = new Vi_WAD_Background_Import_Product();
		self::$process_image        = new Vi_WAD_Background_Download_Images();
		self::$download_description = new Vi_WAD_Background_Download_Description();
		$nonce                      = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
		if ( wp_verify_nonce( $nonce ) ) {
			if ( ! empty( $_REQUEST['vi_wad_cancel_import_product'] ) ) {
				self::$process->kill_process();
				wp_safe_redirect( @remove_query_arg( array( 'vi_wad_cancel_import_product', '_wpnonce' ) ) );
				exit;
			}
			if ( ! empty( $_REQUEST['vi_wad_cancel_download_product_image'] ) ) {
				self::$process_image->kill_process();
				wp_safe_redirect( @remove_query_arg( array( 'vi_wad_cancel_download_product_image', '_wpnonce' ) ) );
				exit;
			}
			if ( ! empty( $_REQUEST['vi_wad_run_download_product_image'] ) ) {
				if ( ! self::$process_image->is_process_running() && ! self::$process_image->is_queue_empty() ) {
					self::$process_image->dispatch();
				}
				wp_safe_redirect( @remove_query_arg( array( 'vi_wad_run_download_product_image', '_wpnonce' ) ) );
				exit;
			}
			if ( ! empty( $_REQUEST['vi_wad_cancel_download_product_description'] ) ) {
				self::$download_description->kill_process();
				wp_safe_redirect( @remove_query_arg( array(
					'vi_wad_cancel_download_product_description',
					'_wpnonce'
				) ) );
				exit;
			}
		}
	}

	public function admin_notices() {
		if ( self::$process_image->is_process_running() ) {
			$is_late = false;
			$next    = wp_next_scheduled( 'wp_vi_wad_background_download_images_cron' );
			if ( $next ) {
				$late = $next - time();
				if ( $late < - 300 ) {
					$is_late = true;
				}
			}
			if ( $is_late ) {
				?>
                <div class="notice notice-error">
                    <p>
						<?php printf( __( '<strong>Dropshipping and Fulfillment for AliExpress and WooCommerce</strong>: <i>wp_vi_wad_background_download_images_cron</i> is late, queued product images may not be processed. If you want to move all queued images to Failed images page to handle them manually, please click <a href="%s">Move</a>', 'woo-alidropship' ), wp_nonce_url( add_query_arg( array( 'vi_wad_move_queued_images' => 1 ) ) ) ) ?>
                    </p>
                </div>
				<?php
			} else {
				?>
                <div class="notice notice-warning">
                    <p>
						<?php _e( '<strong>Dropshipping and Fulfillment for AliExpress and WooCommerce</strong>: Product images are still being processed in the background, please do not edit products/go to product edit page until all images are processed completely.', 'woo-alidropship' ) ?>
                    </p>
                </div>
				<?php
			}
		} else {
			if ( self::$process_image->is_queue_empty() ) {
				if ( get_transient( 'vi_wad_background_download_images_complete' ) ) {
					delete_transient( 'vi_wad_background_download_images_complete' );
					?>
                    <div class="updated">
                        <p>
							<?php _e( '<strong>Dropshipping and Fulfillment for AliExpress and WooCommerce</strong>: Finish processing product images', 'woo-alidropship' ) ?>
                        </p>
                    </div>
					<?php
				}
			} else {
				?>
                <div class="notice notice-warning">
                    <p>
						<?php _e( '', 'woo-alidropship' ) ?>
						<?php printf( __( '<strong>Dropshipping and Fulfillment for AliExpress and WooCommerce</strong>: There are still images in the queue but background process is not running. <a href="%s">Run</a> or <a href="%s">Move to Failed images</a>', 'woo-alidropship' ), wp_nonce_url( add_query_arg( array( 'vi_wad_run_download_product_image' => 1 ) ) ), wp_nonce_url( add_query_arg( array( 'vi_wad_move_queued_images' => 1 ) ) ) ) ?>
                    </p>
                </div>
				<?php
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function import() {
		parse_str( $_POST['form_data'], $form_data );
		if ( ! isset( $form_data['z_check_max_input_vars'] ) ) {
			/*z_check_max_input_vars is the last key of POST data. If it does not exist in $form_data after using parse_str(), some data may also be missing*/
			wp_send_json( array(
				'status'  => 'error',
				'message' => esc_html__( 'PHP max_input_vars is too low, please increase it in php.ini', 'woo-alidropship' ),
			) );
		}
		$data     = isset( $form_data['vi_wad_product'] ) ? stripslashes_deep( $form_data['vi_wad_product'] ) : array();
		$selected = isset( $_POST['selected'] ) ? vi_wad_json_decode( stripslashes_deep( $_POST['selected'] ) ) : array();
		$response = array(
			'status'         => 'error',
			'message'        => '',
			'woo_product_id' => '',
			'button_html'    => '',
		);
		if ( count( $data ) === 0 ) {
			$response['message'] = esc_html__( 'Please select product to import', 'woo-alidropship' );
		} else {
			$product_data     = array_values( $data )[0];
			$product_draft_id = array_keys( $data )[0];
			if ( ! count( $selected[ $product_draft_id ] ) ) {
				$response['message'] = esc_html__( 'Please select at least 1 variation to import this product.', 'woo-alidropship' );
				wp_send_json( $response );
			}
			if ( ! $product_draft_id || VI_WOO_ALIDROPSHIP_DATA::sku_exists( $product_data['sku'] ) ) {
				$response['message'] = esc_html__( 'Sku exists.', 'woo-alidropship' );
				wp_send_json( $response );
			}
			if ( VI_WOO_ALIDROPSHIP_DATA::product_get_id_by_aliexpress_id( get_post_meta( $product_draft_id, '_vi_wad_sku', true ), array( 'publish' ) ) ) {
				wp_send_json( array(
					'status'  => 'error',
					'message' => esc_html__( 'This product has already been imported', 'woo-alidropship' ),
				) );
			}
			$variations            = array();
			$variations_attributes = array();
//			$attributes            = get_post_meta( $product_draft_id, '_vi_wad_attributes', true );
			$attributes = self::get_product_attributes( $product_draft_id );
			if ( isset( $product_data['variations'] ) ) {
				foreach ( $selected[ $product_draft_id ] as $variation_k ) {
					if ( isset( $product_data['variations'][ $variation_k ] ) ) {
						$variations[]         = $product_data['variations'][ $variation_k ];
						$variations_attribute = isset( $product_data['variations'][ $variation_k ]['attributes'] ) ? $product_data['variations'][ $variation_k ]['attributes'] : array();
						if ( is_array( $variations_attribute ) && count( $variations_attribute ) ) {
							foreach ( $variations_attribute as $variations_attribute_k => $variations_attribute_v ) {
								if ( ! isset( $variations_attributes[ $variations_attribute_k ] ) ) {
									$variations_attributes[ $variations_attribute_k ] = array( $variations_attribute_v );
								} elseif ( ! in_array( $variations_attribute_v, $variations_attributes[ $variations_attribute_k ] ) ) {
									$variations_attributes[ $variations_attribute_k ][] = $variations_attribute_v;
								}
							}
						}
					}
				}
				$var_default = isset( $product_data['vi_wad_variation_default'] ) ? $product_data['vi_wad_variation_default'] : '';
				if ( $var_default !== '' ) {
					$product_data['variation_default'] = $product_data['variations'][ $var_default ]['attributes'];
				}
				if ( is_array( $attributes ) && count( $attributes ) ) {
					foreach ( $attributes as $attributes_k => $attributes_v ) {
						if ( ! empty( $variations_attributes[ $attributes_v['slug'] ] ) ) {
							$attributes[ $attributes_k ]['values'] = array_intersect( $attributes[ $attributes_k ]['values'], $variations_attributes[ $attributes_v['slug'] ] );
						}
					}
				}
			} else {
//				$variations    = get_post_meta( $product_draft_id, '_vi_wad_variations', true );
				$variations    = self::get_product_variations( $product_draft_id );
				$shipping_cost = 0;
				if ( self::$settings->get_params( 'show_shipping_option' ) ) {
					$shipping_info = self::get_shipping_info( $product_draft_id, '', '' );
					$shipping_cost = abs( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $shipping_info['shipping_cost'] ) );
				}
				if ( self::$settings->get_params( 'shipping_cost_after_price_rules' ) ) {
					foreach ( $variations as $variations_k => $variations_v ) {
						$variation_sale_price    = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] );
						$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['regular_price'] );
						$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
						$sale_price              = self::$settings->process_price( $price, true );
						if ( $sale_price ) {
							$sale_price += $shipping_cost;
						}
						$regular_price                                = self::$settings->process_price( $price ) + $shipping_cost;
						$variations[ $variations_k ]['sale_price']    = self::$settings->process_exchange_price( $sale_price );
						$variations[ $variations_k ]['regular_price'] = self::$settings->process_exchange_price( $regular_price );
					}
				} else {
					foreach ( $variations as $variations_k => $variations_v ) {
						$variation_sale_price                         = $variations_v['sale_price'] ? ( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] ) + $shipping_cost ) : VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['sale_price'] );
						$variation_regular_price                      = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations_v['regular_price'] ) + $shipping_cost;
						$price                                        = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
						$variations[ $variations_k ]['sale_price']    = self::$settings->process_exchange_price( self::$settings->process_price( $price, true ) );
						$variations[ $variations_k ]['regular_price'] = self::$settings->process_exchange_price( self::$settings->process_price( $price ) );
					}
				}
			}
			if ( count( $variations ) ) {
				$product_data['gallery'] = array_values( array_filter( $product_data['gallery'] ) );
				if ( $product_data['image'] ) {
					$product_image_key = array_search( $product_data['image'], $product_data['gallery'] );
					if ( $product_image_key !== false ) {
						unset( $product_data['gallery'][ $product_image_key ] );
						$product_data['gallery'] = array_values( $product_data['gallery'] );
					}
				}
				$variation_images                 = get_post_meta( $product_draft_id, '_vi_wad_variation_images', true );
				$product_data['attributes']       = $attributes;
				$product_data['variation_images'] = $variation_images;
				$product_data['variations']       = $variations;
				$product_data['parent_id']        = $product_draft_id;
				$product_data['ali_product_id']   = get_post_meta( $product_draft_id, '_vi_wad_sku', true );
				$woo_product_id                   = $this->import_product( $product_data );

				if ( ! is_wp_error( $woo_product_id ) ) {
					$response['status']         = 'success';
					$response['message']        = esc_html__( 'Import successfully', 'woo-alidropship' );
					$response['woo_product_id'] = $woo_product_id;

					$response['button_html'] = self::get_button_view_edit_html( $woo_product_id );
				} else {
					$response['message'] = $woo_product_id->get_error_messages();
				}
			} else {
				$response['message'] = esc_html__( 'Please select at least 1 variation to import this product.', 'woo-alidropship' );
			}
		}
		wp_send_json( $response );
	}

	/**
	 * @param $product_data
	 *
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public static function import_product( $product_data ) {
		global $wp_taxonomies;
		vi_wad_set_time_limit();
		$ali_product_id             = $product_data['ali_product_id'];
		$parent_id                  = $product_data['parent_id'];
		$image                      = $product_data['image'];
		$categories                 = isset( $product_data['categories'] ) ? $product_data['categories'] : array();
		$shipping_class             = isset( $product_data['shipping_class'] ) ? $product_data['shipping_class'] : '';
		$title                      = $product_data['title'];
		$sku                        = $product_data['sku'];
		$status                     = $product_data['status'];
		$tags                       = isset( $product_data['tags'] ) ? $product_data['tags'] : array();
		$description                = $product_data['description'];
		$variations                 = $product_data['variations'];
		$gallery                    = $product_data['gallery'];
		$attributes                 = $product_data['attributes'];
		$catalog_visibility         = $product_data['catalog_visibility'];
		$default_attr               = isset( $product_data['variation_default'] ) ? $product_data['variation_default'] : '';
		$disable_background_process = self::$settings->get_params( 'disable_background_process' );
		$use_global_attributes      = self::$settings->get_params( 'use_global_attributes' );
		if ( is_array( $attributes ) && count( $attributes ) && ( count( $variations ) > 1 || ! self::$settings->get_params( 'simple_if_one_variation' ) ) ) {
			$attr_data = array();
			$position  = 0;
			if ( $use_global_attributes ) {
				foreach ( $attributes as $key => $attr ) {
					$attribute_name = isset( $attr['name'] ) ? $attr['name'] : VI_WOO_ALIDROPSHIP_DATA::get_attribute_name_by_slug( $attr['slug'] );
					$attribute_id   = wc_attribute_taxonomy_id_by_name( $attribute_name );
					if ( ! $attribute_id ) {
						$attribute_id = wc_create_attribute( array(
							'name'         => $attribute_name,
							'slug'         => $attr['slug'],
							'type'         => 'select',
							'order_by'     => 'menu_order',
							'has_archives' => false,
						) );
					}
					if ( $attribute_id && ! is_wp_error( $attribute_id ) ) {
						$attribute_obj     = wc_get_attribute( $attribute_id );
						$attribute_options = array();
						if ( ! empty( $attribute_obj ) ) {
							$taxonomy = $attribute_obj->slug; // phpcs:ignore
							/*Update global $wp_taxonomies for latter insert attribute values*/
							$wp_taxonomies[ $taxonomy ] = new WP_Taxonomy( $taxonomy, 'product' );
							if ( count( $attr['values'] ) ) {
								foreach ( $attr['values'] as $attr_value ) {
									$attr_value  = strval( wc_clean( $attr_value ) );
									$insert_term = wp_insert_term( $attr_value, $taxonomy );
									if ( ! is_wp_error( $insert_term ) ) {
										$attribute_options[] = $insert_term['term_id'];
									} elseif ( isset( $insert_term->error_data ) && isset( $insert_term->error_data['term_exists'] ) ) {
										$attribute_options[] = $insert_term->error_data['term_exists'];
									}
								}
							}
						}
						$attribute_object = new WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( wc_attribute_taxonomy_name_by_id( $attribute_id ) );
						if ( count( $attribute_options ) ) {
							$attribute_object->set_options( $attribute_options );
						} else {
							$attribute_object->set_options( $attr['values'] );
						}
						$attribute_object->set_position( isset( $attr['position'] ) ? $attr['position'] : $position );
						$attribute_object->set_visible( self::$settings->get_params( 'variation_visible' ) ? 1 : '' );
						$attribute_object->set_variation( 1 );
						$attr_data[] = $attribute_object;
					}
					$position ++;
				}
			} else {
				foreach ( $attributes as $key => $attr ) {
					$attribute_name   = isset( $attr['name'] ) ? $attr['name'] : VI_WOO_ALIDROPSHIP_DATA::get_attribute_name_by_slug( $attr['slug'] );
					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_name( $attribute_name );
					$attribute_object->set_options( $attr['values'] );
					$attribute_object->set_position( isset( $attr['position'] ) ? $attr['position'] : $position );
					$attribute_object->set_visible( self::$settings->get_params( 'variation_visible' ) ? 1 : '' );
					$attribute_object->set_variation( 1 );
					$attr_data[] = $attribute_object;
					$position ++;
				}
			}
			/*Create data for product*/
			$data = array( // Set up the basic post data to insert for our product
				'post_excerpt' => '',
				'post_content' => $description,
				'post_title'   => $title,
				'post_status'  => $status,
				'post_type'    => 'product',
				'meta_input'   => array(
					'_sku'                => wc_product_generate_unique_sku( 0, $sku ),
					'_visibility'         => 'visible',
					'_default_attributes' => $default_attr,
				)
			);

			$product_id = wp_insert_post( $data ); // Insert the post returning the new post id

			if ( ! is_wp_error( $product_id ) ) {
				if ( $parent_id ) {
					$update_data = array(
						'ID'          => $parent_id,
						'post_status' => 'publish'
					);
					wp_update_post( $update_data );
					update_post_meta( $parent_id, '_vi_wad_woo_id', $product_id );
				}

				update_post_meta( $product_id, '_vi_wad_aliexpress_product_id', $ali_product_id );
				// Set it to a variable product type
				wp_set_object_terms( $product_id, 'variable', 'product_type' );
				if ( count( $attr_data ) ) {
					$product_obj = wc_get_product( $product_id );
					if ( $product_obj ) {
						$product_obj->set_attributes( $attr_data );
						$product_obj->save();
						/*Use this twice in case other plugin override product type after product is saved*/
						wp_set_object_terms( $product_id, 'variable', 'product_type' );
					}
				}
				/*download image gallery*/
				$dispatch = false;
				if ( isset( $product_data['old_product_image'] ) ) {
					if ( $product_data['old_product_image'] ) {
						update_post_meta( $product_id, '_thumbnail_id', $product_data['old_product_image'] );
					}
					if ( isset( $product_data['old_product_gallery'] ) && $product_data['old_product_gallery'] ) {
						update_post_meta( $product_id, '_product_image_gallery', $product_data['old_product_gallery'] );
					}
				} else {
					if ( $image ) {
						$thumb_id = VI_WOO_ALIDROPSHIP_DATA::download_image( $image_id, $image, $product_id );
						if ( ! is_wp_error( $thumb_id ) ) {
							update_post_meta( $product_id, '_thumbnail_id', $thumb_id );
						}
					}
					self::process_gallery_images( $gallery, $disable_background_process, $product_id, $parent_id, $dispatch );
				}
				self::process_description_images( $description, $disable_background_process, $product_id, $parent_id, $dispatch );
				/*Set product tag*/
				if ( is_array( $tags ) && count( $tags ) ) {
					wp_set_post_terms( $product_id, $tags, 'product_tag', true );
				}
				/*Set product categories*/
				if ( is_array( $categories ) && count( $categories ) ) {
					wp_set_post_terms( $product_id, $categories, 'product_cat', true );
				}
				/*Set product shipping class*/
				if ( $shipping_class && get_term_by( 'id', $shipping_class, 'product_shipping_class' ) ) {
					wp_set_post_terms( $product_id, array( intval( $shipping_class ) ), 'product_shipping_class', false );
				}
				/*Create product variation*/
				self::import_product_variation( $product_id, $product_data, $dispatch, $disable_background_process );
				vi_wad_set_catalog_visibility( $product_id, $catalog_visibility );
			}
		} else {
			/*Create data for product*/
			$sale_price    = isset( $variations[0]['sale_price'] ) ? floatval( $variations[0]['sale_price'] ) : '';
			$regular_price = isset( $variations[0]['regular_price'] ) ? floatval( $variations[0]['regular_price'] ) : '';
			$data          = array( // Set up the basic post data to insert for our product
				'post_excerpt' => '',
				'post_content' => $description,
				'post_title'   => $title,
				'post_status'  => $status,
				'post_type'    => 'product',
				'meta_input'   => array(
					'_sku'           => wc_product_generate_unique_sku( 0, $sku ),
					'_visibility'    => 'visible',
					'_regular_price' => $regular_price,
					'_price'         => $regular_price,
					'_manage_stock'  => 'yes',
					'_stock_status'  => 'instock',
					'_stock'         => isset( $variations[0]['stock'] ) ? absint( $variations[0]['stock'] ) : 0,
				)
			);
			if ( $sale_price ) {
				$data['meta_input']['_sale_price'] = $sale_price;
				$data['meta_input']['_price']      = $sale_price;
			}
			$product_id = wp_insert_post( $data ); // Insert the post returning the new post id

			if ( ! is_wp_error( $product_id ) ) {
				if ( $parent_id ) {
					$update_data = array(
						'ID'          => $parent_id,
						'post_status' => 'publish'
					);
					wp_update_post( $update_data );
					update_post_meta( $parent_id, '_vi_wad_woo_id', $product_id );
				}
				// Set it to a variable product type
				wp_set_object_terms( $product_id, 'simple', 'product_type' );
				/*download image gallery*/
				$dispatch = false;
				if ( isset( $product_data['old_product_image'] ) ) {
					if ( $product_data['old_product_image'] ) {
						update_post_meta( $product_id, '_thumbnail_id', $product_data['old_product_image'] );
					}
					if ( isset( $product_data['old_product_gallery'] ) && $product_data['old_product_gallery'] ) {
						update_post_meta( $product_id, '_product_image_gallery', $product_data['old_product_gallery'] );
					}
				} else {
					if ( $image ) {
						$thumb_id = VI_WOO_ALIDROPSHIP_DATA::download_image( $image_id, $image, $product_id );
						if ( ! is_wp_error( $thumb_id ) ) {
							update_post_meta( $product_id, '_thumbnail_id', $thumb_id );
						}
					}
					self::process_gallery_images( $gallery, $disable_background_process, $product_id, $parent_id, $dispatch );
				}
				self::process_description_images( $description, $disable_background_process, $product_id, $parent_id, $dispatch );
				if ( $dispatch ) {
					self::$process_image->save()->dispatch();
				}
				/*Set product tag*/
				if ( is_array( $tags ) && count( $tags ) ) {
					wp_set_post_terms( $product_id, $tags, 'product_tag', true );
				}
				/*Set product categories*/
				if ( is_array( $categories ) && count( $categories ) ) {
					wp_set_post_terms( $product_id, $categories, 'product_cat', true );
				}
				/*Set product shipping class*/
				if ( $shipping_class && get_term_by( 'id', $shipping_class, 'product_shipping_class' ) ) {
					wp_set_post_terms( $product_id, array( intval( $shipping_class ) ), 'product_shipping_class', false );
				}
				update_post_meta( $product_id, '_vi_wad_aliexpress_product_id', $ali_product_id );
				if ( ! empty( $variations[0]['skuId'] ) ) {
					update_post_meta( $product_id, '_vi_wad_aliexpress_variation_id', $variations[0]['skuId'] );
				}
				if ( ! empty( $variations[0]['skuAttr'] ) ) {
					update_post_meta( $product_id, '_vi_wad_aliexpress_variation_attr', $variations[0]['skuAttr'] );
				}
				$found_items   = isset( $product_data['found_items'] ) ? $product_data['found_items'] : array();
				$replace_items = isset( $product_data['replace_items'] ) ? $product_data['replace_items'] : array();
				$replace_title = isset( $product_data['replace_title'] ) ? $product_data['replace_title'] : '';
				$replaces      = array_keys( $replace_items, $variations[0]['skuId'] );

				if ( count( $replaces ) ) {
					foreach ( $replaces as $old_variation_id ) {
						$order_item_data = isset( $found_items[ $old_variation_id ] ) ? $found_items[ $old_variation_id ] : array();
						if ( count( $order_item_data ) ) {
							foreach ( $order_item_data as $order_item_data_k => $order_item_data_v ) {
								$order_id      = $order_item_data_v['order_id'];
								$order_item_id = $order_item_data_v['order_item_id'];
								if ( 1 == $replace_title ) {
									wc_update_order_item( $order_item_id, array( 'order_item_name' => $title ) );
								}
								if ( $order_item_data_v['meta_key'] === '_variation_id' ) {
									$old_variation = wc_get_product( $old_variation_id );
									if ( $old_variation ) {
										$_product_id = wc_get_order_item_meta( $order_item_id, '_product_id', true );
										$note        = sprintf( esc_html__( 'Product #%s is replaced with product #%s.', 'woo-alidropship' ), $_product_id, $product_id );
										self::add_order_note( $order_id, $note );
										$old_attributes = $old_variation->get_attributes();
										if ( count( $old_attributes ) ) {
											foreach ( $old_attributes as $old_attribute_k => $old_attribute_v ) {
												wc_delete_order_item_meta( $order_item_id, $old_attribute_k );
											}
										}
									}
									wc_delete_order_item_meta( $order_item_id, '_variation_id' );
								} else {
									$note = sprintf( esc_html__( 'Product #%s is replaced with product #%s.', 'woo-alidropship' ), $old_variation_id, $product_id );
									self::add_order_note( $order_id, $note );
								}
								wc_update_order_item_meta( $order_item_id, '_product_id', $product_id );
							}
						}
					}
				}
				vi_wad_set_catalog_visibility( $product_id, $catalog_visibility );
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->save();
				}
			}
		}

		return $product_id;
	}

	public static function import_product_variation( $product_id, $product_data, $dispatch, $disable_background_process ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			if ( is_array( $product_data['variations'] ) && count( $product_data['variations'] ) ) {
				$found_items   = isset( $product_data['found_items'] ) ? $product_data['found_items'] : array();
				$replace_items = isset( $product_data['replace_items'] ) ? $product_data['replace_items'] : array();
				$replace_title = isset( $product_data['replace_title'] ) ? $product_data['replace_title'] : '';
				$variation_ids = [];
				if ( count( $product_data['variation_images'] ) ) {
					foreach ( $product_data['variation_images'] as $key => $val ) {
						$variation_ids[ $key ] = array();
					}
				}
				$use_global_attributes = self::$settings->get_params( 'use_global_attributes' );
				$manage_stock          = self::$settings->get_params( 'manage_stock' );
				$manage_stock          = $manage_stock ? 'yes' : 'no';

				foreach ( $product_data['variations'] as $product_variation ) {
					$stock_quantity = isset( $product_variation['stock'] ) ? absint( $product_variation['stock'] ) : 0;
					$variation      = new WC_Product_Variation();
					$variation->set_parent_id( $product_id );
					$attributes = array();
					if ( $use_global_attributes ) {
						foreach ( $product_variation['attributes'] as $option_k => $attr ) {
							$attribute_id  = wc_attribute_taxonomy_id_by_name( $option_k );
							$attribute_obj = wc_get_attribute( $attribute_id );
							if ( $attribute_obj ) {
								$attribute_value = self::get_term_by_name( $attr, $attribute_obj->slug );
								if ( $attribute_value ) {
									$attributes[ strtolower( urlencode( $attribute_obj->slug ) ) ] = $attribute_value->slug;
								}
							}
						}
					} else {
						foreach ( $product_variation['attributes'] as $option_k => $attr ) {
							$attributes[ strtolower( urlencode( $option_k ) ) ] = $attr;
						}
					}
					$variation->set_attributes( $attributes );
					/*Set metabox for variation . Check field name at woocommerce/includes/class-wc-ajax.php*/
					$fields = array(
						'sku'            => wc_product_generate_unique_sku( 0, $product_variation['sku'] ),
						'regular_price'  => $product_variation['regular_price'],
						'price'          => $product_variation['regular_price'],
						'manage_stock'   => $manage_stock,
						'stock_status'   => 'instock',
						'stock_quantity' => $stock_quantity,
					);
					if ( isset( $product_variation['sale_price'] ) && $product_variation['sale_price'] && $product_variation['sale_price'] < $product_variation['regular_price'] ) {
						$fields['sale_price'] = $product_variation['sale_price'];
						$fields['price']      = $product_variation['sale_price'];
					}
					foreach ( $fields as $field => $value ) {
						$variation->{"set_$field"}( wc_clean( $value ) );
					}
					do_action( 'product_variation_linked', $variation->save() );
					$variation_id = $variation->get_id();
					$replaces     = array_keys( $replace_items, $product_variation['skuId'] );
					if ( count( $replaces ) ) {
						foreach ( $replaces as $old_variation_id ) {
							$order_item_data = isset( $found_items[ $old_variation_id ] ) ? $found_items[ $old_variation_id ] : array();
							if ( count( $order_item_data ) ) {
								foreach ( $order_item_data as $order_item_data_k => $order_item_data_v ) {
									$order_id      = $order_item_data_v['order_id'];
									$order_item_id = $order_item_data_v['order_item_id'];
									if ( 1 == $replace_title ) {
										wc_update_order_item( $order_item_id, array( 'order_item_name' => $replace_title ) );
									}
									if ( $order_item_data_v['meta_key'] === '_variation_id' ) {
										$old_variation = wc_get_product( $old_variation_id );
										if ( $old_variation ) {
											$_product_id = wc_get_order_item_meta( $order_item_id, '_product_id', true );
											$note        = sprintf( esc_html__( 'Product #%s is replaced with product #%s. Variation #%s is replaced with variation #%s.', 'woo-alidropship' ), $_product_id, $product_id, $old_variation_id, $variation_id );
											self::add_order_note( $order_id, $note );
											$old_attributes = $old_variation->get_attributes();
											if ( count( $old_attributes ) ) {
												foreach ( $old_attributes as $old_attribute_k => $old_attribute_v ) {
													wc_delete_order_item_meta( $order_item_id, $old_attribute_k );
												}
											}
										}

									} else {
										$note = sprintf( esc_html__( 'Product #%s is replaced with product #%s.', 'woo-alidropship' ), $old_variation_id, $product_id );
										self::add_order_note( $order_id, $note );
										foreach ( $product_variation['attributes'] as $new_attribute_k => $new_attribute_v ) {
											wc_update_order_item_meta( $order_item_id, $new_attribute_k, $new_attribute_v );
										}
									}
									foreach ( $product_variation['attributes'] as $new_attribute_k => $new_attribute_v ) {
										wc_update_order_item_meta( $order_item_id, $new_attribute_k, $new_attribute_v );
									}
									wc_update_order_item_meta( $order_item_id, '_product_id', $product_id );
									wc_update_order_item_meta( $order_item_id, '_variation_id', $variation_id );
								}
							}
						}
					}
					update_post_meta( $variation_id, '_vi_wad_aliexpress_variation_id', $product_variation['skuId'] );
					update_post_meta( $variation_id, '_vi_wad_aliexpress_variation_attr', $product_variation['skuAttr'] );
					if ( ! empty( $product_variation['ship_from'] ) ) {
						update_post_meta( $variation_id, '_vi_wad_aliexpress_variation_ship_from', $product_variation['ship_from'] );
					}
					if ( $product_variation['image'] ) {
						$pos = array_search( $product_variation['image'], $product_data['variation_images'] );
						if ( $pos !== false ) {
							$variation_ids[ $pos ][] = $variation_id;
						}
					}
				}
				if ( count( $variation_ids ) ) {
					if ( $disable_background_process ) {
						foreach ( $variation_ids as $key => $values ) {
							if ( count( $values ) && ! empty( $product_data['variation_images'][ $key ] ) ) {
								$image_data = array(
									'woo_product_id' => $product_id,
									'parent_id'      => '',
									'src'            => $product_data['variation_images'][ $key ],
									'product_ids'    => $values,
									'set_gallery'    => 0,
								);
								VI_WOO_ALIDROPSHIP_Error_Images_Table::insert( $product_id, implode( ',', $image_data['product_ids'] ), $image_data['src'], intval( $image_data['set_gallery'] ) );
							}
						}
					} else {
						foreach ( $variation_ids as $key => $values ) {
							if ( count( $values ) && ! empty( $product_data['variation_images'][ $key ] ) ) {
								$dispatch   = true;
								$image_data = array(
									'woo_product_id' => $product_id,
									'parent_id'      => '',
									'src'            => $product_data['variation_images'][ $key ],
									'product_ids'    => $values,
									'set_gallery'    => 0,
								);
								self::$process_image->push_to_queue( $image_data );
							}
						}
					}
				}
			}

			$data_store = $product->get_data_store();
			$data_store->sort_all_product_variations( $product->get_id() );
		}
		if ( $dispatch ) {
			self::$process_image->save()->dispatch();
		}
	}

	public static function get_term_by_name( $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		// 'term_taxonomy_id' lookups don't require taxonomy checks.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		// No need to perform a query for empty 'slug' or 'name'.
		$value = (string) $value;

		if ( 0 === strlen( $value ) ) {
			return false;
		}

		$args = array(
			'get'                    => 'all',
			'name'                   => $value,
			'number'                 => 0,
			'taxonomy'               => $taxonomy,
			'update_term_meta_cache' => false,
			'orderby'                => 'none',
			'suppress_filter'        => true,
		);

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}
		if ( count( $terms ) > 1 ) {
			foreach ( $terms as $term ) {
				if ( $term->name === $value ) {
					return get_term( $term, $taxonomy, $output, $filter );
				}
			}
		}
		$term = array_shift( $terms );

		return get_term( $term, $taxonomy, $output, $filter );
	}

	public static function add_order_note( $order_id, $note ) {
		$commentdata = apply_filters(
			'woocommerce_new_order_note_data',
			array(
				'comment_post_ID'      => $order_id,
				'comment_author'       => '',
				'comment_author_email' => __( 'WooCommerce', 'woocommerce' ),
				'comment_author_url'   => '',
				'comment_content'      => $note,
				'comment_agent'        => 'WooCommerce',
				'comment_type'         => 'order_note',
				'comment_parent'       => 0,
				'comment_approved'     => 1,
			),
			array(
				'order_id'         => $order_id,
				'is_customer_note' => 0,
			)
		);
		wp_insert_comment( $commentdata );
	}

	public static function process_gallery_images( $gallery, $disable_background_process, $product_id, $parent_id, &$dispatch ) {
		if ( is_array( $gallery ) && count( $gallery ) ) {
			if ( $disable_background_process ) {
				foreach ( $gallery as $image_url ) {
					$image_data = array(
						'woo_product_id' => $product_id,
						'parent_id'      => $parent_id,
						'src'            => $image_url,
						'product_ids'    => array(),
						'set_gallery'    => 1,
					);
					VI_WOO_ALIDROPSHIP_Error_Images_Table::insert( $product_id, implode( ',', $image_data['product_ids'] ), $image_data['src'], intval( $image_data['set_gallery'] ) );
				}
			} else {
				$dispatch = true;
				foreach ( $gallery as $image_url ) {
					$image_data = array(
						'woo_product_id' => $product_id,
						'parent_id'      => $parent_id,
						'src'            => $image_url,
						'product_ids'    => array(),
						'set_gallery'    => 1,
					);
					self::$process_image->push_to_queue( $image_data );
				}
			}
		}
	}

	public static function process_description_images( $description, $disable_background_process, $product_id, $parent_id, &$dispatch ) {
		if ( $description && self::$settings->get_params( 'download_description_images' ) ) {
			preg_match_all( '/src="([\s\S]*?)"/im', $description, $matches );
			if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
				$description_images = array_unique( $matches[1] );
				if ( $disable_background_process ) {
					foreach ( $description_images as $description_image ) {
						VI_WOO_ALIDROPSHIP_Error_Images_Table::insert( $product_id, '', $description_image, 2 );
					}
				} else {
					foreach ( $description_images as $description_image ) {
						$images_data = array(
							'woo_product_id' => $product_id,
							'parent_id'      => $parent_id,
							'src'            => $description_image,
							'product_ids'    => array(),
							'set_gallery'    => 2,
						);
						self::$process_image->push_to_queue( $images_data );
					}
					$dispatch = true;
				}
			}
		}
	}

	private static function set( $name, $set_name = false ) {
		return VI_WOO_ALIDROPSHIP_DATA::set( $name, $set_name );
	}

	/**
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public function save_screen_options( $status, $option, $value ) {
		if ( $option === 'vi_wad_per_page' ) {
			return $value;
		}

		return $status;
	}

	public function admin_menu() {
		$import_list = add_submenu_page( 'woo-alidropship', esc_html__( 'Import List - Dropshipping and Fulfillment for AliExpress and WooCommerce', 'woo-alidropship' ), esc_html__( 'Import List', 'woo-alidropship' ), 'manage_options', 'woo-alidropship-import-list', array(
			$this,
			'import_list_callback'
		) );
		add_action( "load-$import_list", array( $this, 'screen_options_page' ) );
	}

	public function screen_options_page() {
		add_screen_option( 'per_page', array(
			'label'   => esc_html__( 'Number of items per page', 'wp-admin' ),
			'default' => 5,
			'option'  => 'vi_wad_per_page'
		) );
	}

	public function import_list_callback() {
		$user     = get_current_user_id();
		$screen   = get_current_screen();
		$option   = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $option, true );
		$decimals = wc_get_price_decimals();
		if ( $decimals < 1 ) {
			$decimals = 1;
		} else {
			$decimals = pow( 10, ( - 1 * $decimals ) );
		}
		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$paged = isset( $_GET['paged'] ) ? sanitize_text_field( $_GET['paged'] ) : 1;
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Import List', 'woo-alidropship' ) ?></h2>
			<?php
			$args             = array(
				'post_type'      => 'vi_wad_draft_product',
				'post_status'    => array( 'draft', 'override' ),
				'order'          => 'DESC',
				'orderby'        => 'date',
				'fields'         => 'ids',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
			);
			$vi_wad_search_id = isset( $_GET['vi_wad_search_id'] ) ? sanitize_text_field( $_GET['vi_wad_search_id'] ) : '';
			$keyword          = isset( $_GET['vi_wad_search'] ) ? sanitize_text_field( $_GET['vi_wad_search'] ) : '';
			if ( $vi_wad_search_id ) {
				$args['post__in']       = array( $vi_wad_search_id );
				$args['posts_per_page'] = 1;
				$keyword                = '';
			} else if ( $keyword ) {
				$args['s'] = $keyword;
			}
			$the_query    = new WP_Query( $args );
			$count        = $the_query->found_posts;
			$total_page   = $the_query->max_num_pages;
			$page_content = '';
			if ( $the_query->have_posts() ) {
				ob_start();
				?>
                <form method="get" class="vi-ui segment <?php echo esc_attr( self::set( 'pagination-form' ) ) ?>">
                    <input type="hidden" name="page" value="woo-alidropship-import-list">
                    <div class="tablenav top">
                        <div class="<?php echo esc_attr( self::set( 'button-import-all-container' ) ) ?>">
                            <input type="checkbox"
                                   class="<?php echo esc_attr( self::set( 'accordion-bulk-item-check-all' ) ) ?>">
                            <span class="vi-ui button mini primary <?php echo esc_attr( self::set( 'button-import-all' ) ) ?>"
                                  title="<?php esc_attr_e( 'Import all products on this page', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Import All', 'woo-alidropship' ) ?></span>
                            <a class="vi-ui button negative mini <?php echo esc_attr( self::set( 'button-empty-import-list' ) ) ?>"
                               href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'vi_wad_empty_product_list', 1 ) ) ) ?>"
                               title="<?php esc_attr_e( 'Remove all products(except overriding products) from Import list', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Empty List', 'woo-alidropship' ) ?></a>
                            <span class="<?php echo esc_attr( self::set( 'accordion-bulk-actions-container' ) ) ?>">
                                <select name="<?php echo esc_attr( 'vi_wad_bulk_actions' ) ?>"
                                        class="vi-ui dropdown <?php echo esc_attr( self::set( 'accordion-bulk-actions' ) ) ?>">
                                    <option value=""><?php esc_html_e( 'Bulk Action', 'woo-alidropship' ) ?></option>
                                    <option value="set_categories"><?php esc_html_e( 'Set categories', 'woo-alidropship' ) ?></option>
                                    <option value="set_tags"><?php esc_html_e( 'Set tags', 'woo-alidropship' ) ?></option>
                                    <option value="set_status_publish"><?php esc_html_e( 'Set status - Publish', 'woo-alidropship' ) ?></option>
                                    <option value="set_status_pending"><?php esc_html_e( 'Set status - Pending', 'woo-alidropship' ) ?></option>
                                    <option value="set_status_draft"><?php esc_html_e( 'Set status - Draft', 'woo-alidropship' ) ?></option>
                                    <option value="set_visibility_visible"><?php esc_html_e( 'Set visibility - Shop and search results', 'woo-alidropship' ) ?></option>
                                    <option value="set_visibility_catalog"><?php esc_html_e( 'Set visibility - Shop only', 'woo-alidropship' ) ?></option>
                                    <option value="set_visibility_search"><?php esc_html_e( 'Set visibility - Search results only', 'woo-alidropship' ) ?></option>
                                    <option value="set_visibility_hidden"><?php esc_html_e( 'Set visibility - Hidden', 'woo-alidropship' ) ?></option>
                                    <option value="import"><?php esc_html_e( 'Import selected', 'woo-alidropship' ) ?></option>
                                    <option value="remove"><?php esc_html_e( 'Remove selected', 'woo-alidropship' ) ?></option>
                                </select>
                            </span>
                        </div>
                        <div class="tablenav-pages">
                            <div class="pagination-links">
								<?php
								if ( $paged > 2 ) {
									?>
                                    <a class="prev-page button" href="<?php echo esc_url( add_query_arg(
										array(
											'page'          => 'woo-alidropship-import-list',
											'paged'         => 1,
											'vi_wad_search' => $keyword,
										), admin_url( 'admin.php' )
									) ) ?>"><span
                                                class="screen-reader-text"><?php esc_html_e( 'First Page', 'woo-alidropship' ) ?></span><span
                                                aria-hidden="true">«</span></a>
									<?php
								} else {
									?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
									<?php
								}
								/*Previous button*/
								if ( $per_page * $paged > $per_page ) {
									$p_paged = $paged - 1;
								} else {
									$p_paged = 0;
								}
								if ( $p_paged ) {
									$p_url = add_query_arg(
										array(
											'page'          => 'woo-alidropship-import-list',
											'paged'         => $p_paged,
											'vi_wad_search' => $keyword,
										), admin_url( 'admin.php' )
									);
									?>
                                    <a class="prev-page button" href="<?php echo esc_url( $p_url ) ?>"><span
                                                class="screen-reader-text"><?php esc_html_e( 'Previous Page', 'woo-alidropship' ) ?></span><span
                                                aria-hidden="true">‹</span></a>
									<?php
								} else {
									?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
									<?php
								}
								?>
                                <span class="screen-reader-text"><?php esc_html_e( 'Current Page', 'woo-alidropship' ) ?></span>
                                <span id="table-paging" class="paging-input">
                                    <input class="current-page" type="text" name="paged" size="1"
                                           value="<?php echo esc_html( $paged ) ?>"><span class="tablenav-paging-text"> of <span
                                                class="total-pages"><?php echo esc_html( $total_page ) ?></span></span>

							</span>
								<?php /*Next button*/
								if ( $per_page * $paged < $count ) {
									$n_paged = $paged + 1;
								} else {
									$n_paged = 0;
								}
								if ( $n_paged ) {
									$n_url = add_query_arg(
										array(
											'page'          => 'woo-alidropship-import-list',
											'paged'         => $n_paged,
											'vi_wad_search' => $keyword,
										), admin_url( 'admin.php' )
									); ?>
                                    <a class="next-page button" href="<?php echo esc_url( $n_url ) ?>"><span
                                                class="screen-reader-text"><?php esc_html_e( 'Next Page', 'woo-alidropship' ) ?></span><span
                                                aria-hidden="true">›</span></a>
									<?php
								} else {
									?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
									<?php
								}
								if ( $total_page > $paged + 1 ) {
									?>
                                    <a class="next-page button" href="<?php echo esc_url( add_query_arg(
										array(
											'page'          => 'woo-alidropship-import-list',
											'paged'         => $total_page,
											'vi_wad_search' => $keyword,
										), admin_url( 'admin.php' )
									) ) ?>"><span
                                                class="screen-reader-text"><?php esc_html_e( 'Last Page', 'woo-alidropship' ) ?></span><span
                                                aria-hidden="true">»</span></a>
									<?php
								} else {
									?>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
									<?php
								}
								?>
                            </div>
                        </div>
                        <p class="search-box">
                            <input type="search" class="text short" name="vi_wad_search"
                                   placeholder="<?php esc_attr_e( 'Search product in import list', 'woo-alidropship' ) ?>"
                                   value="<?php echo esc_attr( $keyword ) ?>">
                            <input type="submit" name="submit" class="button"
                                   value="<?php echo esc_attr( 'Search product', 'woo-alidropship' ) ?>">
                        </p>
                    </div>
                </form>
				<?php
				$pagination_html             = ob_get_clean();
				$key                         = 0;
				$currency                    = 'USD';
				$woocommerce_currency        = get_woocommerce_currency();
				$woocommerce_currency_symbol = get_woocommerce_currency_symbol();
				$default_select_image        = self::$settings->get_params( 'product_gallery' );
				$manage_stock                = self::$settings->get_params( 'manage_stock' );
				$product_categories          = self::$settings->get_params( 'product_categories' );
				$product_tags                = self::$settings->get_params( 'product_tags' );
				$product_shipping_class      = self::$settings->get_params( 'product_shipping_class' );
				$catalog_visibility          = self::$settings->get_params( 'catalog_visibility' );
				$product_status              = self::$settings->get_params( 'product_status' );
				$use_different_currency      = false;
				if ( strtolower( $woocommerce_currency ) != strtolower( $currency ) ) {
					$use_different_currency = true;
				}
				ob_start();
				?>
                <option value="publish" <?php selected( $product_status, 'publish' ) ?>><?php esc_html_e( 'Publish', 'woo-alidropship' ) ?></option>
                <option value="pending" <?php selected( $product_status, 'pending' ) ?>><?php esc_html_e( 'Pending', 'woo-alidropship' ) ?></option>
                <option value="draft" <?php selected( $product_status, 'draft' ) ?>><?php esc_html_e( 'Draft', 'woo-alidropship' ) ?></option>
				<?php
				$product_status_options = ob_get_clean();
				ob_start();
				?>
                <option value="visible" <?php selected( $catalog_visibility, 'visible' ) ?>><?php esc_html_e( 'Shop and search results', 'woo-alidropship' ) ?></option>
                <option value="catalog" <?php selected( $catalog_visibility, 'catalog' ) ?>><?php esc_html_e( 'Shop only', 'woo-alidropship' ) ?></option>
                <option value="search" <?php selected( $catalog_visibility, 'search' ) ?>><?php esc_html_e( 'Search results only', 'woo-alidropship' ) ?></option>
                <option value="hidden" <?php selected( $catalog_visibility, 'hidden' ) ?>><?php esc_html_e( 'Hidden', 'woo-alidropship' ) ?></option>
				<?php
				$catalog_visibility_options = ob_get_clean();
				$categories                 = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'orderby'    => 'name',
						'order'      => 'ASC',
						'hide_empty' => false
					)
				);
				if ( is_array( $categories ) && count( $categories ) ) {
					ob_start();
					foreach ( $categories as $category ) {
						?>
                        <option value="<?php echo esc_attr( $category->term_id ) ?>"
							<?php if ( in_array( $category->term_id, $product_categories ) ) {
								echo esc_attr( 'selected' );
							} ?>><?php echo esc_html( $category->name ); ?></option>
						<?php
					}
					self::$categories_options = ob_get_clean();
				}

				$tags = get_terms(
					array(
						'taxonomy'   => 'product_tag',
						'orderby'    => 'name',
						'order'      => 'ASC',
						'hide_empty' => false
					)
				);
				if ( is_array( $tags ) && count( $tags ) ) {
					ob_start();
					foreach ( $tags as $tag ) {
						?>
                        <option value="<?php echo esc_attr( $tag->name ) ?>"
							<?php if ( in_array( $tag->name, $product_tags ) ) {
								echo esc_attr( 'selected' );
							} ?>><?php echo esc_html( $tag->name ); ?></option>
						<?php
					}
					self::$tags_options = ob_get_clean();
				}
				$shipping_classes = get_terms(
					array(
						'taxonomy'   => 'product_shipping_class',
						'orderby'    => 'name',
						'order'      => 'ASC',
						'hide_empty' => false
					)
				);
				if ( is_array( $shipping_classes ) && count( $shipping_classes ) ) {
					ob_start();
					foreach ( $shipping_classes as $shipping_class ) {
						?>
                        <option value="<?php echo esc_attr( $shipping_class->term_id ) ?>"
							<?php selected( $shipping_class->term_id, $product_shipping_class ) ?>><?php echo esc_html( $shipping_class->name ); ?></option>
						<?php
					}
					self::$shipping_class_options = ob_get_clean();
				}
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$product_id  = get_the_ID();
					$product     = get_post( $product_id );
					$title       = $product->post_title;
					$description = $product->post_content;
					$sku         = get_post_meta( $product_id, '_vi_wad_sku', true );
//					$attributes  = get_post_meta( $product_id, '_vi_wad_attributes', true );
					$attributes = self::get_product_attributes( $product_id );
					$store_info = get_post_meta( $product_id, '_vi_wad_store_info', true );
					$parent     = array();
					if ( is_array( $attributes ) && count( $attributes ) ) {
						foreach ( $attributes as $attribute_k => $attribute_v ) {
							$parent[ $attribute_k ] = $attribute_v['slug'];
						}
					}
					$gallery = get_post_meta( $product_id, '_vi_wad_gallery', true );
					if ( ! $gallery ) {
						$gallery = array();
					}
					$desc_images = get_post_meta( $product_id, '_vi_wad_description_images', true );
					if ( ! $desc_images ) {
						$desc_images = array();
					} else {
						$desc_images = array_values( array_unique( $desc_images ) );
					}
					$image = isset( $gallery[0] ) ? $gallery[0] : '';
//					$variations          = get_post_meta( $product_id, '_vi_wad_variations', true );
					$variations          = self::get_product_variations( $product_id );
					$is_variable         = ( is_array( $parent ) && count( $parent ) ) ? 1 : 0;
					$product_type        = $product->post_status;
					$override_product_id = $product->post_parent;
					$override_product    = '';
					if ( $product_type === 'override' && $override_product_id ) {
						$override_product = get_post( $override_product_id );
						if ( ! $override_product ) {
							$product_type        = 'draft';
							$override_product_id = '';
							wp_update_post( array(
								'ID'          => $product_id,
								'post_parent' => 0,
								'post_status' => $product_type,
							) );
						}
					}
					ob_start();
					?>
                    <div class="vi-ui styled fluid accordion active <?php echo esc_attr( self::set( 'accordion' ) ) ?>"
                         id="<?php echo esc_attr( self::set( 'product-item-id-' . $product_id ) ) ?>">
                        <div class="title active">
                            <input type="checkbox"
                                   class="<?php echo esc_attr( self::set( 'accordion-bulk-item-check' ) ); ?>">
                            <i class="dropdown icon <?php echo esc_attr( self::set( 'accordion-title-icon' ) ); ?>"></i>
                            <div class="<?php echo esc_attr( self::set( 'accordion-product-image-title-container' ) ) ?>">
                                <div class="<?php echo esc_attr( self::set( 'accordion-product-image-title' ) ) ?>">
                                    <img src="<?php echo esc_url( $image ? $image : wc_placeholder_img_src() ) ?>"
                                         class="<?php echo esc_attr( self::set( 'accordion-product-image' ) ) ?>">
                                    <div class="<?php echo esc_attr( self::set( 'accordion-product-title-container' ) ) ?>">
                                        <div class="<?php echo esc_attr( self::set( 'accordion-product-title' ) ) ?>"
                                             title="<?php esc_attr_e( $title ) ?>"><?php echo esc_html( $title ) ?></div>
										<?php
										if ( ! empty( $store_info['name'] ) ) {
											$store_name = $store_info['name'];
											if ( ! empty( $store_info['url'] ) ) {
												$store_name = '<a class="' . esc_attr__( self::set( 'accordion-store-url' ) ) . '" href="' . esc_attr__( $store_info['url'] ) . '" target="_blank">' . $store_name . '</a>';
											}
											?>
                                            <div>
												<?php
												esc_html_e( 'Store: ', 'woo-alidropship' );
												echo $store_name;
												?>
                                            </div>
											<?php
										}
										?>
                                        <div class="<?php echo esc_attr( self::set( 'accordion-product-date' ) ) ?>"><?php esc_html_e( 'Date: ', 'woo-alidropship' ) ?>
                                            <span><?php echo $product->post_date ?></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="<?php echo esc_attr( self::set( 'button-view-and-edit' ) ) ?>">
                                <a href="<?php echo esc_url( "https://www.aliexpress.com/item/{$sku}.html" ); ?>"
                                   target="_blank" class="vi-ui button mini" rel="nofollow"
                                   title="<?php esc_attr_e( 'View this product on AliExpress.com', 'woo-alidropship' ) ?>">
									<?php esc_html_e( 'View on AliExpress', 'woo-alidropship' ) ?></a>
                                <span class="vi-ui button mini negative <?php echo esc_attr( self::set( 'button-remove' ) ) ?>"
                                      data-product_id="<?php echo esc_attr( $product_id ) ?>"
                                      title="<?php esc_attr_e( 'Remove this product from import list', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Remove', 'woo-alidropship' ) ?></span>
								<?php
								if ( $override_product ) {
									?>
                                    <span class="vi-ui button mini positive <?php echo esc_attr( self::set( 'button-override' ) ) ?>"
                                          data-product_id="<?php echo esc_attr( $product_id ) ?>"
                                          data-override_product_id="<?php esc_attr_e( $override_product_id ) ?>"><?php esc_html_e( 'Import & Override', 'woo-alidropship' ) ?></span>
									<?php
								} else {
									?>
                                    <span class="vi-ui button mini positive <?php echo esc_attr( self::set( 'button-import' ) ) ?>"
                                          data-product_id="<?php echo esc_attr( $product_id ) ?>"
                                          title="<?php esc_attr_e( 'Import this product to your WooComemrce store', 'woo-alidropship' ) ?>"><?php esc_html_e( 'Import Now', 'woo-alidropship' ) ?></span>
									<?php
								}
								?>
                            </div>
                        </div>
                        <div class="content active">
							<?php
							if ( $override_product ) {
								?>
                                <div class="vi-ui message <?php echo esc_attr( self::set( 'override-product-message' ) ) ?>"><?php esc_html_e( 'This product will override: ', 'woo-alidropship' ) ?>
                                    <strong class="<?php echo esc_attr( self::set( 'override-product-product-title' ) ) ?>"><?php echo esc_html( $override_product->post_title ) ?></strong>
                                </div>
								<?php
							}
							?>
                            <div class="<?php echo esc_attr( self::set( 'message' ) ) ?>">
                            </div>
                            <form class="vi-ui form <?php echo esc_attr( self::set( 'product-container' ) ) ?>"
                                  method="post">
                                <div class="vi-ui attached tabular menu">
                                    <div class="item active" data-tab="<?php echo esc_attr( 'product-' . $key ) ?>">
										<?php esc_html_e( 'Product', 'woo-alidropship' ) ?>
                                    </div>
                                    <div class="item <?php echo esc_attr( self::set( 'description-tab-menu' ) ) ?>"
                                         data-tab="<?php echo esc_attr( 'description-' . $key ) ?>">
										<?php esc_html_e( 'Description', 'woo-alidropship' ) ?>
                                    </div>
									<?php
									if ( $is_variable ) {
										$tab_class = array( 'variations-tab-menu' );
										if ( ! self::load_variations_ajax( $variations ) ) {
											$tab_class[] = 'lazy-load';
										}
										?>
                                        <div class="item <?php echo esc_attr( self::set( 'attributes-tab-menu' ) ) ?>"
                                             data-tab="<?php echo esc_attr( 'attributes-' . $key ) ?>">
											<?php esc_html_e( 'Attributes', 'woo-alidropship' ) ?>
                                        </div>
                                        <div class="item <?php echo esc_attr( self::set( $tab_class ) ) ?>"
                                             data-tab="<?php echo esc_attr( 'variations-' . $key ) ?>">
											<?php printf( __( 'Variations(%s)', 'woo-alidropship' ), '<span class="' . self::set( 'selected-variation-count' ) . '">' . count( $variations ) . '</span>', 'woo-alidropship' ) ?>
                                        </div>
										<?php
									}
									if ( count( $gallery ) ) {
										$gallery_count = $default_select_image ? count( $gallery ) : 0;
										?>
                                        <div class="item <?php echo esc_attr( self::set( array(
											'lazy-load',
											'gallery-tab-menu'
										) ) ) ?>"
                                             data-tab="<?php echo esc_attr( 'gallery-' . $key ) ?>">
											<?php printf( __( 'Gallery(%s)', 'woo-alidropship' ), '<span class="' . self::set( 'selected-gallery-count' ) . '">' . $gallery_count . '</span>', 'woo-alidropship' ) ?>
                                        </div>
										<?php
									}
									?>
                                </div>
                                <div class="vi-ui bottom attached tab segment active <?php echo esc_attr( self::set( 'product-tab' ) ) ?>"
                                     data-tab="<?php echo esc_attr( 'product-' . $key ) ?>">
                                    <div class="field">
                                        <div class="fields">
                                            <div class="three wide field">
                                                <div class="<?php echo esc_attr( self::set( 'product-image' ) ) ?> <?php if ( $default_select_image )
													esc_attr_e( self::set( 'selected-item' ) ) ?> ">
                                                    <span class="<?php echo esc_attr( self::set( 'selected-item-icon-check' ) ) ?>"></span>
													<?php
													if ( $image ) {
														?>
                                                        <img style="width: 100%"
                                                             src="<?php echo esc_url( $image ) ?>"
                                                             class="<?php echo esc_attr( self::set( 'import-data-image' ) ) ?>">
                                                        <input type="hidden"
                                                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][image]' ) ?>"
                                                               value="<?php echo esc_attr( $default_select_image ? $image : '' ) ?>">
														<?php
													} else {
														?>
                                                        <img style="width: 100%"
                                                             src="<?php echo esc_url( wc_placeholder_img_src() ) ?>"
                                                             class="<?php echo esc_attr( self::set( 'import-data-image' ) ) ?>">
                                                        <input type="hidden"
                                                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][image]' ) ?>"
                                                               value="">
														<?php
													}
													?>

                                                </div>
                                            </div>
                                            <div class="thirteen wide field">
                                                <div class="field">
                                                    <label><?php esc_html_e( 'Product title', 'woo-alidropship' ) ?></label>
                                                    <input type="text" value="<?php echo esc_attr( $title ) ?>"
                                                           name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][title]' ) ?>"
                                                           class="<?php echo esc_attr( self::set( 'import-data-title' ) ) ?>">
                                                </div>
                                                <div class="field <?php echo esc_attr( self::set( 'import-data-sku-status-visibility' ) ) ?>">
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Sku', 'woo-alidropship' ) ?></label>
                                                            <input type="text" value="<?php echo esc_attr( $sku ) ?>"
                                                                   name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][sku]' ) ?>"
                                                                   class="<?php echo esc_attr( self::set( 'import-data-sku' ) ) ?>">
                                                        </div>
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Product status', 'woo-alidropship' ) ?></label>
                                                            <select
                                                                    name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][status]' ) ?>"
                                                                    class="<?php echo esc_attr( self::set( 'import-data-status' ) ) ?> vi-ui fluid dropdown">
																<?php echo $product_status_options ?>
                                                            </select>

                                                        </div>
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Catalog visibility', 'woo-alidropship' ) ?></label>
                                                            <select
                                                                    name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][catalog_visibility]' ) ?>"
                                                                    class="<?php echo esc_attr( self::set( 'import-data-catalog-visibility' ) ) ?> vi-ui fluid dropdown">
																<?php echo $catalog_visibility_options ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
												<?php
												if ( ! $is_variable ) {
													self::simple_product_price_field_html( $key, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals );
												}
												?>
                                                <div class="field">
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Categories', 'woo-alidropship' ) ?></label>
                                                            <select name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][categories][]' ) ?>"
                                                                    class="vi-ui dropdown search <?php echo esc_attr( self::set( 'import-data-categories' ) ) ?>"
                                                                    multiple="multiple">
																<?php echo self::$categories_options; ?>
                                                            </select>
                                                        </div>
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Tags', 'woo-alidropship' ) ?></label>
                                                            <select name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][tags][]' ) ?>"
                                                                    class="vi-ui dropdown search  <?php echo esc_attr( self::set( 'import-data-tags' ) ) ?>"
                                                                    multiple="multiple">
																<?php echo self::$tags_options; ?>
                                                            </select>
                                                        </div>
                                                        <div class="field">
                                                            <label><?php esc_html_e( 'Shipping class', 'woo-alidropship' ) ?></label>
                                                            <select name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][shipping_class]' ) ?>"
                                                                    class="vi-ui dropdown search <?php echo esc_attr( self::set( 'import-data-shipping-class' ) ) ?>">
                                                                <option value=""><?php esc_html_e( 'No shipping class', 'woo-alidropship' ) ?></option>
																<?php echo self::$shipping_class_options; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="vi-ui bottom attached tab segment <?php echo esc_attr( self::set( 'description-tab' ) ) ?>"
                                     data-tab="<?php echo esc_attr( 'description-' . $key ) ?>">
									<?php
									wp_editor( $description, self::set( 'product-description-' ) . $product_id, array(
										'default_editor' => 'html',
										'media_buttons'  => false,
										'editor_class'   => esc_attr__( self::set( 'import-data-description' ) ),
										'textarea_name'  => esc_attr__( 'vi_wad_product[' . $product_id . '][description]' ),
									) );
									?>
                                </div>
								<?php
								if ( $is_variable ) {
									$variations_tab_class = array( 'variations-tab' );
									$variations_html      = '';
									if ( ! self::load_variations_ajax( $variations ) ) {
										$variations_tab_class[] = 'variations-tab-loaded';
										$variations_tab_class[] = 'lazy-load-tab-data';
										ob_start();
										self::variation_html( $key, $parent, $attributes, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals );
										$variations_html = ob_get_clean();
									}
									?>
                                    <div class="vi-ui bottom attached tab segment <?php echo esc_attr( self::set( 'attributes-tab' ) ) ?>"
                                         data-tab="<?php echo esc_attr( 'attributes-' . $key ) ?>"
                                         data-product_id="<?php echo esc_attr( $product_id ) ?>">
                                        <table class="vi-ui celled table">
                                            <thead>
                                            <tr>
                                                <th class="<?php echo esc_attr( self::set( 'attributes-attribute-col-position' ) ) ?>"><?php esc_html_e( 'Position', 'woo-alidropship' ) ?></th>
                                                <th class="<?php echo esc_attr( self::set( 'attributes-attribute-col-name' ) ) ?>"><?php esc_html_e( 'Name', 'woo-alidropship' ) ?></th>
                                                <th class="<?php echo esc_attr( self::set( 'attributes-attribute-col-slug' ) ) ?>"><?php esc_html_e( 'Slug', 'woo-alidropship' ) ?></th>
                                                <th class="<?php echo esc_attr( self::set( 'attributes-attribute-col-values' ) ) ?>"><?php esc_html_e( 'Values', 'woo-alidropship' ) ?></th>
                                                <th class="<?php echo esc_attr( self::set( 'attributes-attribute-col-action' ) ) ?>"><?php esc_html_e( 'Action', 'woo-alidropship' ) ?></th>
                                            </tr>
                                            </thead>
                                            <tbody class="ui sortable">
											<?php
											$position = 1;
											foreach ( $attributes as $attributes_key => $attribute ) {
												$attribute_name = isset( $attribute['name'] ) ? $attribute['name'] : VI_WOO_ALIDROPSHIP_DATA::get_attribute_name_by_slug( $attribute['slug'] );
												?>
                                                <tr class="<?php echo esc_attr( self::set( 'attributes-attribute-row' ) ) ?>">
                                                    <td><?php echo esc_html( $position ) ?></td>
                                                    <td><input type="text"
                                                               class="<?php echo esc_attr( self::set( 'attributes-attribute-name' ) ) ?>"
                                                               value="<?php echo esc_attr( $attribute_name ) ?>"
                                                               data-attribute_name="<?php echo esc_attr( $attribute_name ) ?>"
                                                               readonly
                                                               name="<?php echo esc_attr( "vi_wad_product[{$product_id}][attributes][{$attributes_key}][name]" ) ?>">
                                                    </td>
                                                    <td>
                                                        <span class="<?php echo esc_attr( self::set( 'attributes-attribute-slug' ) ) ?>"
                                                              data-attribute_slug="<?php echo esc_attr( $attribute['slug'] ) ?>"><?php echo esc_html( $attribute['slug'] ) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="<?php echo esc_attr( self::set( 'attributes-attribute-values' ) ) ?>">
															<?php
															foreach ( $attribute['values'] as $values_k => $values_v ) {
																?>
                                                                <input type="text"
                                                                       class="<?php echo esc_attr( self::set( 'attributes-attribute-value' ) ) ?>"
                                                                       value="<?php echo esc_attr( $values_v ) ?>"
                                                                       data-attribute_value="<?php echo esc_attr( $values_v ) ?>"
                                                                       readonly
                                                                       name="<?php echo esc_attr( "vi_wad_product[{$product_id}][attributes][{$attributes_key}][values][{$values_k}]" ) ?>">
																<?php
															}
															?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                       <span class="vi-ui button mini icon <?php echo esc_attr( self::set( 'attributes-button-edit' ) ) ?>"
                                                             title="<?php esc_attr_e( 'Edit this attribute', 'woo-alidropship' ) ?>"><i
                                                                   class="icon edit"></i></span>
                                                        <span class="vi-ui button mini negative icon <?php echo esc_attr( self::set( 'attributes-attribute-remove' ) ) ?>"
                                                              title="<?php esc_attr_e( 'Remove this attribute', 'woo-alidropship' ) ?>"><i
                                                                    class="icon trash"></i></span>
                                                        <div class="<?php echo esc_attr( self::set( array(
															'attributes-button-save-cancel',
														) ) ) ?>">
                                                            <span class="vi-ui button mini green icon <?php echo esc_attr( self::set( array(
	                                                            'attributes-button-save',
                                                            ) ) ) ?>"
                                                                  title="<?php esc_attr_e( 'Save', 'woo-alidropship' ) ?>"><i
                                                                        class="icon save"></i></span>
                                                            <span class="vi-ui button mini icon <?php echo esc_attr( self::set( array(
																'attributes-button-cancel',
															) ) ) ?>"
                                                                  title="<?php esc_attr_e( 'Cancel', 'woo-alidropship' ) ?>"><i
                                                                        class="icon cancel"></i></span>
                                                        </div>
                                                    </td>
                                                </tr>
												<?php
												$position ++;
											}
											?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="vi-ui bottom attached tab segment <?php echo esc_attr( self::set( $variations_tab_class ) ) ?>"
                                         data-tab="<?php echo esc_attr( 'variations-' . $key ) ?>"
                                         data-product_id="<?php echo esc_attr( $product_id ) ?>">
										<?php
										if ( count( $variations ) ) {
											?>
                                            <div class="vi-ui positive message">
                                                <div class="header">
                                                    <p><?php _e( 'You can edit product attributes on Attributes tab', 'woo-alidropship' ) ?></p>
                                                </div>
                                            </div>
                                            <table class="form-table <?php echo esc_attr( self::set( array(
												'variations-table',
												'table-fix-head',
												'variation-table-attributes-count-' . count( $attributes )
											) ) ) ?>"><?php echo $variations_html; ?></table>
											<?php
										}
										?>
                                    </div>
									<?php
								}
								$gallery = array_merge( $gallery, $desc_images );
								if ( count( $gallery ) ) {
									?>
                                    <div class="vi-ui bottom attached tab segment <?php echo esc_attr( self::set( array(
										'product-gallery',
										'lazy-load-tab-data'
									) ) ) ?>"
                                         data-tab="<?php echo esc_attr( 'gallery-' . $key ) ?>">
                                        <div class="segment ui-sortable">
											<?php
											if ( $default_select_image ) {
												foreach ( $gallery as $gallery_k => $gallery_v ) {
													if ( ! in_array( $gallery_v, $desc_images ) ) {
														$item_class = array(
															'product-gallery-item',
															'selected-item'
														);
														if ( $gallery_k === 0 ) {
															$item_class[] = 'is-product-image';
														}
														?>
                                                        <div class="<?php echo esc_attr( self::set( $item_class ) ) ?>">
                                                            <span class="<?php echo esc_attr( self::set( 'selected-item-icon-check' ) ) ?>"></span>
                                                            <i class="<?php echo esc_attr( self::set( 'set-product-image' ) ) ?> star icon"></i>
                                                            <i class="<?php echo esc_attr( self::set( 'set-variation-image' ) ) ?> hand outline up icon"
                                                               title="<?php esc_attr_e( 'Set image for selected variation(s)', 'woo-alidropship' ) ?>"></i>
                                                            <img src="<?php echo esc_url( VI_WOO_ALIDROPSHIP_IMAGES . 'loading.gif' ) ?>"
                                                                 data-image_src="<?php echo esc_url( $gallery_v ) ?>"
                                                                 class="<?php echo esc_attr( self::set( 'product-gallery-image' ) ) ?>">
                                                            <input type="hidden"
                                                                   name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][gallery][]' ) ?>"
                                                                   value="<?php echo esc_attr( $gallery_v ) ?>">
                                                        </div>
														<?php
													} else {
														?>
                                                        <div class="<?php echo esc_attr( self::set( 'product-gallery-item' ) ) ?>">
                                                            <span class="<?php echo esc_attr( self::set( 'selected-item-icon-check' ) ) ?>"></span>
                                                            <i class="<?php echo esc_attr( self::set( 'set-product-image' ) ) ?> star icon"></i>
                                                            <i class="<?php echo esc_attr( self::set( 'set-variation-image' ) ) ?> hand outline up icon"
                                                               title="<?php esc_attr_e( 'Set image for selected variation(s)', 'woo-alidropship' ) ?>"></i>
                                                            <img src="<?php echo esc_url( VI_WOO_ALIDROPSHIP_IMAGES . 'loading.gif' ) ?>"
                                                                 data-image_src="<?php echo esc_url( $gallery_v ) ?>"
                                                                 class="<?php echo esc_attr( self::set( 'product-gallery-image' ) ) ?>">
                                                            <input type="hidden"
                                                                   name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][gallery][]' ) ?>"
                                                                   value="">
                                                        </div>
														<?php
													}
												}
											} else {
												foreach ( $gallery as $gallery_k => $gallery_v ) {
													?>
                                                    <div class="<?php echo esc_attr( self::set( 'product-gallery-item' ) ) ?>">
                                                        <span class="<?php echo esc_attr( self::set( 'selected-item-icon-check' ) ) ?>"></span>
                                                        <i class="<?php echo esc_attr( self::set( 'set-product-image' ) ) ?> star icon"></i>
                                                        <i class="<?php echo esc_attr( self::set( 'set-variation-image' ) ) ?> hand outline up icon"
                                                           title="<?php esc_attr_e( 'Set image for selected variation(s)', 'woo-alidropship' ) ?>"></i>
                                                        <img src="<?php echo esc_url( VI_WOO_ALIDROPSHIP_IMAGES . 'loading.gif' ) ?>"
                                                             data-image_src="<?php echo esc_url( $gallery_v ) ?>"
                                                             class="<?php echo esc_attr( self::set( 'product-gallery-image' ) ) ?>">
                                                        <input type="hidden"
                                                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][gallery][]' ) ?>"
                                                               value="">
                                                    </div>
													<?php
												}
											}
											?>
                                        </div>
                                    </div>
									<?php
								}
								?>
                            </form>
                        </div>
                        <div class="<?php echo esc_attr( self::set( array(
							'product-overlay',
							'hidden'
						) ) ) ?>"></div>
                    </div>
					<?php
					$page_content .= ob_get_clean();
					$key ++;
				}

			} else {
				ob_start();
				?>
                <form method="get">
                    <input type="hidden" name="page" value="woo-alidropship-import-list">
                    <input type="search" class="text short" name="vi_wad_search"
                           placeholder="<?php esc_attr_e( 'Search product', 'woo-alidropship' ) ?>"
                           value="<?php echo esc_attr( $keyword ) ?>">
                    <input type="submit" name="submit" class="button"
                           value="<?php echo esc_attr( 'Search product', 'woo-alidropship' ) ?>">
                    <p>
						<?php esc_html_e( 'No products found', 'woo-alidropship' ) ?>
                    </p>
                </form>
				<?php
				$pagination_html = ob_get_clean();
			}
			wp_reset_postdata();
			echo $pagination_html;
			if ( $page_content ) {
				?>
                <div class="vi-ui segment <?php echo esc_attr( self::set( 'import-list' ) ) ?>">
					<?php
					echo $page_content;
					?>
                </div>
				<?php
			}
			?>
        </div>
		<?php
	}

	/**
	 * @param $ali_product_id
	 * @param $country
	 * @param string $currency
	 *
	 * @return array
	 */
	public static function get_freight( $ali_product_id, $country, $currency = 'USD' ) {
		$response = array(
			'status'  => 'error',
			'freight' => array(),
		);
		$request  = VI_WOO_ALIDROPSHIP_DATA::wp_remote_get( add_query_arg( array(
				'productId'     => $ali_product_id,
				'country'       => $country,
				'tradeCurrency' => $currency,
			), 'https://www.aliexpress.com/aeglodetailweb/api/logistics/freight?count=1&provinceCode=&cityCode=&sellerAdminSeq=&minPrice=1&maxPrice=1&userScene=PC_DETAIL_SHIPPING_PANEL&displayMultipleFreight=false' )
		);
		if ( $request['status'] === 'success' ) {
			$data = $request['data'];
			if ( isset( $data['body'] ) && isset( $data['body']['freightResult'] ) && is_array( $data['body']['freightResult'] ) ) {
				$response['status']  = 'success';
				$response['freight'] = $data['body']['freightResult'];
			}
		}

		return $response;
	}

	/**
	 * @param $product_id
	 * @param $country
	 * @param string $company
	 * @param bool $latest
	 * @param bool $update
	 *
	 * @return array|mixed
	 */
	public static function get_shipping_info( $product_id, $country, $company = '', $latest = false, $update = true ) {
		$shipping_info = get_post_meta( $product_id, '_vi_wad_shipping_info', true );
		$now           = time();
		$freight       = array();
		if ( ! $shipping_info ) {
			if ( ! $country ) {
				$country = 'US';
			}
			$shipping_info = array(
				'time'          => 0,
				'country'       => $country,
				'company'       => '',
				'freight'       => json_encode( $freight ),
				'shipping_cost' => '',
				'delivery_time' => '',
			);
		} else {
			$freight = vi_wad_json_decode( $shipping_info['freight'] );
			if ( ! $country ) {
				$country = $shipping_info['country'];
				if ( ! $company ) {
					$company = $shipping_info['company'];
				}
			}
		}
		$ali_product_id = get_post_meta( $product_id, '_vi_wad_sku', true );
		if ( $ali_product_id ) {
			$maybe_update = false;
			if ( $latest || ( $now - floatval( $shipping_info['time'] ) > 3600 ) || $country !== $shipping_info['country'] ) {
				$shipping_info['country'] = $country;
				$shipping_info['time']    = $now;
				$get_freight              = self::get_freight( $ali_product_id, VI_WOO_ALIDROPSHIP_Admin_API::filter_country( $country ) );
				if ( $get_freight['status'] === 'success' ) {
					$freight                  = $get_freight['freight'];
					$shipping_info['freight'] = json_encode( $freight );
					$maybe_update             = true;
				}
			}
			if ( count( $freight ) ) {
				$found = false;
				if ( $company && $company !== $shipping_info['company'] || $shipping_info['shipping_cost'] === null ) {
					$shipping_info['shipping_cost'] = '';
					$shipping_info['delivery_time'] = '';
					$maybe_update                   = true;
					foreach ( $freight as $key => $value ) {
						if ( $value['serviceName'] === $company ) {
							$shipping_info['company']       = $company;
							$shipping_info['delivery_time'] = $value['time'];
							if ( isset( $value['standardFreightAmount']['value'] ) ) {
								$shipping_info['shipping_cost'] = $value['standardFreightAmount']['value'];
							} elseif ( isset( $value['freightAmount']['value'] ) ) {
								$shipping_info['shipping_cost'] = $value['freightAmount']['value'];
							}
							$found = true;
							break;
						}
					}
				}
				if ( ! $found && ! $company ) {
					$shipping_info['company']       = $freight[0]['serviceName'];
					$shipping_info['delivery_time'] = $freight[0]['time'];
					if ( isset( $freight[0]['standardFreightAmount']['value'] ) ) {
						$shipping_info['shipping_cost'] = $freight[0]['standardFreightAmount']['value'];
					} elseif ( isset( $freight[0]['freightAmount']['value'] ) ) {
						$shipping_info['shipping_cost'] = $freight[0]['freightAmount']['value'];
					}
					$maybe_update = true;
				}
			} else {
				$shipping_info['shipping_cost'] = '';
				$shipping_info['delivery_time'] = '';
			}
			if ( $maybe_update ) {
				update_post_meta( $product_id, '_vi_wad_shipping_info', $shipping_info );
			}
		}
		$shipping_info['freight'] = $freight;

		return $shipping_info;
	}

	public static function get_button_view_edit_html( $woo_product_id ) {
		ob_start();
		?>
        <a href="<?php echo esc_attr( get_post_permalink( $woo_product_id ) ) ?>"
           target="_blank" class="button"
           rel="nofollow"><?php esc_html_e( 'View product', 'woo-alidropship' ); ?></a>
        <a href="<?php echo esc_url( admin_url( "post.php?post={$woo_product_id}&action=edit" ) ) ?>"
           target="_blank" class="button button-primary"
           rel="nofollow"><?php esc_html_e( 'Edit product', 'woo-alidropship' ) ?></a>
		<?php
		return ob_get_clean();
	}

	public static function variation_html( $key, $parent, $attributes, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, $lazy_load = true, $coutry = '', $company = '' ) {
		?>
        <thead>
        <tr>
            <td width="1%"></td>
            <td class="<?php echo esc_attr( self::set( 'fix-width' ) ) ?>">
                <input type="checkbox" checked
                       class="<?php echo esc_attr( self::set( array(
					       'variations-bulk-enable',
					       'variations-bulk-enable-' . $key
				       ) ) ) ?>">
            </td>
            <td class="<?php echo esc_attr( self::set( 'fix-width' ) ) ?>">
                <input type="checkbox" checked
                       class="<?php echo esc_attr( self::set( array(
					       'variations-bulk-select-image',
				       ) ) ) ?>">
            </td>
            <th class="<?php echo esc_attr( self::set( 'fix-width' ) ) ?>"><?php esc_html_e( 'Default variation', 'woo-alidropship' ) ?></th>
            <th><?php esc_html_e( 'Sku', 'woo-alidropship' ) ?></th>
			<?php
			if ( is_array( $parent ) && count( $parent ) ) {
				foreach ( $parent as $parent_k => $parent_v ) {
					?>
                    <th class="<?php echo esc_attr( self::set( 'attribute-filter-list-container' ) ) ?>">
						<?php
						$attribute_name = isset( $attributes[ $parent_k ]['name'] ) ? $attributes[ $parent_k ]['name'] : VI_WOO_ALIDROPSHIP_DATA::get_attribute_name_by_slug( $parent_v );
						echo esc_html( $attribute_name );
						$attribute_values = isset( $attributes[ $parent_k ]['values'] ) ? $attributes[ $parent_k ]['values'] : array();
						if ( count( $attribute_values ) ) {
							?>
                            <ul class="<?php echo esc_attr( self::set( 'attribute-filter-list' ) ) ?>"
                                data-attribute_slug="<?php echo esc_attr( $parent_v ) ?>">
								<?php
								foreach ( $attribute_values as $attribute_value ) {
									?>
                                    <li class="<?php echo esc_attr( self::set( 'attribute-filter-item' ) ) ?>"
                                        title="<?php esc_attr_e( $attribute_value ) ?>"
                                        data-attribute_slug="<?php echo esc_attr( $parent_v ) ?>"
                                        data-attribute_value="<?php echo esc_attr( trim( $attribute_value ) ) ?>"><?php echo esc_html( $attribute_value ) ?></li>
									<?php
								}
								?>
                            </ul>
							<?php
						}
						?>
                    </th>
					<?php
				}
			}
			$show_shipping_option            = self::$settings->get_params( 'show_shipping_option' );
			$shipping_cost_after_price_rules = self::$settings->get_params( 'shipping_cost_after_price_rules' );
			$shipping_cost_html              = '';
			$shipping_cost                   = 0;
			if ( $show_shipping_option ) {
				$shipping_info      = self::get_shipping_info( $product_id, $coutry, $company );
				$shipping_cost      = abs( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $shipping_info['shipping_cost'] ) );
				$shipping_cost_html = $shipping_info['shipping_cost'] === '' ? esc_html__( 'Not available', 'woo-alidropship' ) : wc_price( $shipping_info['shipping_cost'], array(
					'currency'     => $currency,
					'decimals'     => 2,
					'price_format' => '%1$s&nbsp;%2$s'
				) );
				if ( $use_different_currency && $shipping_cost ) {
					$shipping_cost_html .= '(' . wc_price( self::$settings->process_exchange_price( $shipping_cost ) ) . ')';
				}
				?>
                <th class="<?php echo esc_attr( self::set( 'sale-price-col' ) ) ?>"><?php esc_html_e( 'Shipping cost', 'woo-alidropship' ) ?>
					<?php self::shipping_option_html( $shipping_info, $key, $product_id ); ?>
                </th>
				<?php
			}
			?>
            <th><?php esc_html_e( 'Cost', 'woo-alidropship' ) ?>
				<?php
				if ( $show_shipping_option && ! $shipping_cost_after_price_rules ) {
					?>
                    <div><?php esc_html_e( '(price+shipping)', 'woo-alidropship' ) ?></div>
					<?php
				}
				?>
            </th>
            <th class="<?php echo esc_attr( self::set( 'sale-price-col' ) ) ?>"><?php esc_html_e( 'Sale price', 'woo-alidropship' ) ?>
                <div class="<?php echo esc_attr( self::set( 'set-price' ) ) ?>"
                     data-set_price="sale_price"><?php esc_html_e( 'Set price', 'woo-alidropship' ) ?></div>
            </th>
            <th class="<?php echo esc_attr( self::set( 'regular-price-col' ) ) ?>"><?php esc_html_e( 'Regular price', 'woo-alidropship' ) ?>
                <div class="<?php echo esc_attr( self::set( 'set-price' ) ) ?>"
                     data-set_price="regular_price"><?php esc_html_e( 'Set price', 'woo-alidropship' ) ?></div>
            </th>
			<?php
			if ( $manage_stock ) {
				?>
                <th class="<?php echo esc_attr( self::set( 'inventory-col' ) ) ?>"><?php esc_html_e( 'Inventory', 'woo-alidropship' ) ?></th>
				<?php
			}
			?>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $variations as $variation_key => $variation ) {
			$variation_image = $variation['image'];
			$inventory       = intval( $variation['stock'] );
			if ( $shipping_cost_after_price_rules ) {
				$variation_sale_price    = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variation['sale_price'] );
				$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variation['regular_price'] );
				$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
				$sale_price              = self::$settings->process_price( $price, true );
				if ( $sale_price ) {
					$sale_price += $shipping_cost;
				}
				$regular_price = self::$settings->process_price( $price ) + $shipping_cost;
			} else {
				$variation_sale_price    = $variation['sale_price'] ? ( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variation['sale_price'] ) + $shipping_cost ) : VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variation['sale_price'] );
				$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variation['regular_price'] ) + $shipping_cost;
				$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
				$sale_price              = self::$settings->process_price( $price, true );
				$regular_price           = self::$settings->process_price( $price );
			}
			$profit      = $variation_sale_price ? ( $sale_price - $variation_sale_price ) : ( $regular_price - $variation_regular_price );
			$cost_html   = wc_price( $price, array(
				'currency'     => $currency,
				'decimals'     => 2,
				'price_format' => '%1$s&nbsp;%2$s'
			) );
			$profit_html = wc_price( $profit, array(
				'currency'     => $currency,
				'decimals'     => 2,
				'price_format' => '%1$s&nbsp;%2$s'
			) );
			if ( $use_different_currency ) {
				$cost_html   .= '(' . wc_price( self::$settings->process_exchange_price( $price ) ) . ')';
				$profit_html .= '(' . wc_price( self::$settings->process_exchange_price( $profit ) ) . ')';
			}
			$sale_price    = self::$settings->process_exchange_price( $sale_price );
			$regular_price = self::$settings->process_exchange_price( $regular_price );
			$image_src     = $variation_image ? $variation_image : wc_placeholder_img_src();
			?>
            <tr class="<?php echo esc_attr( self::set( 'product-variation-row' ) ) ?>">
                <td><?php echo esc_html( $variation_key + 1 ) ?></td>
                <td>
                    <input type="checkbox" checked
                           class="<?php echo esc_attr( self::set( array(
						       'variation-enable',
						       'variation-enable-' . $key,
						       'variation-enable-' . $key . '-' . $variation_key
					       ) ) ) ?>">
                </td>
                <td>
                    <div class="<?php echo esc_attr( self::set( array(
						'variation-image',
						'selected-item'
					) ) ) ?>">
                        <span class="<?php echo esc_attr( self::set( 'selected-item-icon-check' ) ) ?>"></span>
                        <img style="width: 64px;height: 64px"
                             src="<?php echo esc_url( $lazy_load ? VI_WOO_ALIDROPSHIP_IMAGES . 'loading.gif' : $image_src ) ?>"
                             data-image_src="<?php echo esc_url( $image_src ) ?>"
                             class="<?php echo esc_attr( self::set( 'import-data-variation-image' ) ) ?>">
                        <input type="hidden"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][image]' ) ?>"
                               value="<?php echo esc_attr( $variation_image ? $variation_image : '' ) ?>">
                    </div>
                </td>
                <td><input type="radio"
                           class="<?php echo esc_attr( self::set( 'import-data-variation-default' ) ) ?>"
                           name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][vi_wad_variation_default]' ) ?>"
                           value="<?php echo esc_attr( $variation_key ) ?>">
                </td>
                <td>
                    <div>
                        <input type="text"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][sku]' ) ?>"
                               value="<?php echo esc_attr( $variation['sku'] ) ?>"
                               class="<?php echo esc_attr( self::set( 'import-data-variation-sku' ) ) ?>">
                        <input type="hidden"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][skuId]' ) ?>"
                               value="<?php echo esc_attr( $variation['skuId'] ) ?>">
                        <input type="hidden"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][skuAttr]' ) ?>"
                               value="<?php echo esc_attr( $variation['skuAttr'] ) ?>">
                    </div>
                </td>
				<?php
				if ( is_array( $parent ) && count( $parent ) ) {
					foreach ( $parent as $parent_k => $parent_v ) {
						?>
                        <td>
                            <input type="text" readonly
                                   data-attribute_slug="<?php echo esc_attr( $parent_v ) ?>"
                                   data-attribute_value="<?php echo esc_attr( isset( $variation['attributes'][ $parent_v ] ) ? trim( $variation['attributes'][ $parent_v ] ) : '' ) ?>"
                                   name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][attributes][' . $parent_v . ']' ) ?>"
                                   class="<?php echo esc_attr( self::set( 'import-data-variation-attribute' ) ) ?>"
                                   value="<?php echo esc_attr( isset( $variation['attributes'][ $parent_v ] ) ? $variation['attributes'][ $parent_v ] : '' ) ?>">
                        </td>
						<?php
					}
				}
				if ( $shipping_cost_html !== '' ) {
					?>
                    <td>
                        <div class="<?php echo esc_attr( self::set( 'price-field' ) ) ?>">
                            <span class="<?php echo esc_attr( self::set( 'import-data-shipping-cost' ) ) ?>"><?php echo $shipping_cost_html ?></span>
                        </div>
                    </td>
					<?php
				}
				?>
                <td>
                    <div class="<?php echo esc_attr( self::set( 'price-field' ) ) ?>">
                        <span class="<?php echo esc_attr( self::set( 'import-data-variation-cost' ) ) ?>"><?php echo $cost_html ?></span>
                    </div>
                </td>
                <td>
                    <div class="vi-ui left labeled input">
                        <label for="amount"
                               class="vi-ui label"><?php echo $woocommerce_currency_symbol ?></label>
                        <input type="number" min="0"
                               step="<?php echo esc_attr( $decimals ) ?>"
                               value="<?php echo esc_attr( $sale_price ) ?>"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][sale_price]' ) ?>"
                               class="<?php echo esc_attr( self::set( 'import-data-variation-sale-price' ) ) ?>">
                    </div>
                </td>
                <td>
                    <div class="vi-ui left labeled input">
                        <label for="amount"
                               class="vi-ui label"><?php echo $woocommerce_currency_symbol ?></label>
                        <input type="number" min="0"
                               step="<?php echo esc_attr( $decimals ) ?>"
                               value="<?php echo esc_attr( $regular_price ) ?>"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][regular_price]' ) ?>"
                               class="<?php echo esc_attr( self::set( 'import-data-variation-regular-price' ) ) ?>">
                    </div>
                </td>
				<?php
				if ( $manage_stock ) {
					?>
                    <td>
                        <input type="number" min="0"
                               step="<?php echo esc_attr( $decimals ) ?>"
                               value="<?php echo esc_attr( $inventory ) ?>"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][' . $variation_key . '][stock]' ) ?>"
                               class="<?php echo esc_attr( self::set( 'import-data-variation-inventory' ) ) ?>">
                    </td>
					<?php
				}
				?>

            </tr>
			<?php
		}
		?>
        </tbody>
		<?php
	}

	public static function shipping_option_html( $shipping_info, $key, $product_id, $product_type = 'variable' ) {
		$wc_country       = new WC_Countries();
		$countries        = $wc_country->get_countries();
		$shipping_company = $shipping_info['company'];
		$shipping_country = $shipping_info['country'];
		?>
        <div class="<?php echo esc_attr( self::set( 'shipping-info' ) ) ?>"
             data-product_index="<?php echo esc_attr( $key ) ?>"
             data-product_type="<?php echo esc_attr( $product_type ) ?>"
             data-product_id="<?php echo esc_attr( $product_id ) ?>">
			<?php
			if ( count( $countries ) ) {
				?>
                <div class="<?php echo esc_attr( self::set( 'shipping-info-country-wrap' ) ) ?>">
                    <select name="<?php echo esc_attr( self::set( 'shipping-info-country', true ) ) ?>"
                            class="vi-ui fluid search dropdown <?php echo esc_attr( self::set( 'shipping-info-country' ) ) ?>">
                        <option><?php esc_html_e( 'Select country', 'woo-alidropship' ) ?></option>
						<?php
						foreach ( $countries as $key => $value ) {
							?>
                            <option value="<?php echo esc_attr( $key ) ?>" <?php selected( $shipping_country, $key ) ?>><?php echo esc_html( $value ) ?></option>
							<?php
						}
						?>
                    </select>
                </div>
				<?php
			}
			if ( count( $shipping_info['freight'] ) ) {
				?>
                <div class="<?php echo esc_attr( self::set( 'shipping-info-company-wrap' ) ) ?>">
                    <select name="<?php echo esc_attr( self::set( 'shipping-info-company', true ) ) ?>"
                            class="vi-ui dropdown fluid <?php echo esc_attr( self::set( 'shipping-info-company' ) ) ?>">
						<?php
						if ( $product_type === 'simple' ) {
							foreach ( $shipping_info['freight'] as $freight ) {
								$delivery_time   = self::process_delivery_time( $freight['time'] );
								$shipping_amount = isset( $freight['standardFreightAmount']['value'] ) ? $freight['standardFreightAmount']['value'] : ( isset( $freight['freightAmount']['value'] ) ? $freight['freightAmount']['value'] : '' );
								?>
                                <option value="<?php echo esc_attr( $freight['serviceName'] ) ?>" <?php selected( $shipping_company, $freight['serviceName'] ) ?>><?php echo esc_html( "\${$shipping_amount}-{$freight['company']}({$delivery_time})" ) ?></option>
								<?php
							}
						} else {
							foreach ( $shipping_info['freight'] as $freight ) {
								$delivery_time   = self::process_delivery_time( $freight['time'] );
								$shipping_amount = isset( $freight['standardFreightAmount']['value'] ) ? $freight['standardFreightAmount']['value'] : ( isset( $freight['freightAmount']['value'] ) ? $freight['freightAmount']['value'] : '' );
								?>
                                <option value="<?php echo esc_attr( $freight['serviceName'] ) ?>" <?php selected( $shipping_company, $freight['serviceName'] ) ?>><?php echo esc_html( "{$freight['company']}({$delivery_time}, \${$shipping_amount})" ) ?></option>
								<?php
							}
						}
						?>
                    </select>
                </div>
				<?php
			} elseif ( $product_type === 'simple' ) {
				?>
                <div class="<?php echo esc_attr( self::set( 'shipping-info-company-wrap' ) ) ?>"><?php esc_html_e( 'Not available', 'woo-alidropship' ) ?></div>
				<?php
			}
			?>
        </div>
		<?php
	}

	public static function simple_product_price_field_html( $key, $manage_stock, $variations, $use_different_currency, $currency, $product_id, $woocommerce_currency_symbol, $decimals, $coutry = '', $company = '' ) {
		$show_shipping_option            = self::$settings->get_params( 'show_shipping_option' );
		$shipping_cost_after_price_rules = self::$settings->get_params( 'shipping_cost_after_price_rules' );
		$shipping_cost_html              = '';
		$cost_label                      = esc_html__( 'Cost', 'woo-alidropship' );
		$shipping_cost                   = 0;
		if ( $show_shipping_option ) {
			if ( ! $shipping_cost_after_price_rules ) {
				$cost_label = esc_html__( 'Cost(price+shipping)', 'woo-alidropship' );
			}
			$shipping_info = self::get_shipping_info( $product_id, $coutry, $company );
			$shipping_cost = abs( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $shipping_info['shipping_cost'] ) );
			ob_start();
			?>
            <div class="field">
                <label><?php esc_html_e( 'Shipping cost', 'woo-alidropship' ) ?></label>
				<?php self::shipping_option_html( $shipping_info, $key, $product_id, 'simple' ); ?>
            </div>
			<?php
			$shipping_cost_html = ob_get_clean();
		}
		$inventory = intval( $variations[0]['stock'] );
		if ( $shipping_cost_after_price_rules ) {
			$variation_sale_price    = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations[0]['sale_price'] );
			$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations[0]['regular_price'] );
			$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
			$sale_price              = self::$settings->process_price( $price, true );
			if ( $sale_price ) {
				$sale_price += $shipping_cost;
			}
			$regular_price = self::$settings->process_price( $price ) + $shipping_cost;
		} else {
			$variation_sale_price    = $variations[0]['sale_price'] ? ( VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations[0]['sale_price'] ) + $shipping_cost ) : VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations[0]['sale_price'] );
			$variation_regular_price = VI_WOO_ALIDROPSHIP_DATA::string_to_float( $variations[0]['regular_price'] ) + $shipping_cost;
			$price                   = $variation_sale_price ? $variation_sale_price : $variation_regular_price;
			$sale_price              = self::$settings->process_price( $price, true );
			$regular_price           = self::$settings->process_price( $price );
		}
		$profit      = $variation_sale_price ? ( $sale_price - $variation_sale_price ) : ( $regular_price - $variation_regular_price );
		$cost_html   = wc_price( $price, array(
			'currency'     => $currency,
			'decimals'     => 2,
			'price_format' => '%1$s&nbsp;%2$s'
		) );
		$profit_html = wc_price( $profit, array(
			'currency'     => $currency,
			'decimals'     => 2,
			'price_format' => '%1$s&nbsp;%2$s'
		) );
		if ( $use_different_currency ) {
			$cost_html   .= '(' . wc_price( self::$settings->process_exchange_price( $price ) ) . ')';
			$profit_html .= '(' . wc_price( self::$settings->process_exchange_price( $profit ) ) . ')';
		}
		$sale_price    = self::$settings->process_exchange_price( $sale_price );
		$regular_price = self::$settings->process_exchange_price( $regular_price );
		?>
        <div class="field <?php echo esc_attr( self::set( 'simple-product-price-field' ) ) ?>">
            <input type="hidden"
                   name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][0][skuId]' ) ?>"
                   value="<?php echo esc_attr( $variations[0]['skuId'] ) ?>">
            <div class="equal width fields">
				<?php echo $shipping_cost_html; ?>
                <div class="field">
                    <label><?php echo $cost_label; ?></label>
                    <div class="<?php echo esc_attr( self::set( 'price-field' ) ) ?>">
						<?php echo $cost_html ?>
                    </div>
                </div>
				<?php
				/*
				?>
				<div class="field">
					<label><?php esc_html_e( "Profit", 'woo-alidropship' ) ?></label>
					<div class="<?php echo esc_attr( self::set( 'price-field' ) ) ?>">
						<?php echo $profit_html ?>
					</div>
				</div>
				<?php
				*/
				?>
                <div class="field">
                    <label><?php esc_html_e( 'Sale price', 'woo-alidropship' ) ?></label>
                    <div class="vi-ui left labeled input">
                        <label for="amount"
                               class="vi-ui label"><?php echo $woocommerce_currency_symbol ?></label>
                        <input
                                type="number" min="0"
                                step="<?php echo esc_attr( $decimals ) ?>"
                                value="<?php echo esc_attr( $sale_price ) ?>"
                                name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][0][sale_price]' ) ?>"
                                class="<?php echo esc_attr( self::set( 'import-data-variation-sale-price' ) ) ?>">
                    </div>
                </div>
                <div class="field">
                    <label><?php esc_html_e( 'Regular price', 'woo-alidropship' ) ?></label>
                    <div class="vi-ui left labeled input">
                        <label for="amount"
                               class="vi-ui label"><?php echo $woocommerce_currency_symbol ?></label>
                        <input type="number" min="0"
                               step="<?php echo esc_attr( $decimals ) ?>"
                               value="<?php echo esc_attr( $regular_price ) ?>"
                               name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][0][regular_price]' ) ?>"
                               class="<?php echo esc_attr( self::set( 'import-data-variation-regular-price' ) ) ?>">
                    </div>
                </div>
				<?php
				if ( $manage_stock ) {
					?>
                    <div class="field">
                        <label><?php esc_html_e( 'Inventory', 'woo-alidropship' ) ?></label>
                        <input
                                type="number" min="0"
                                step="<?php echo esc_attr( $decimals ) ?>"
                                value="<?php echo esc_attr( $inventory ) ?>"
                                name="<?php echo esc_attr( 'vi_wad_product[' . $product_id . '][variations][0][stock]' ) ?>"
                                class="<?php echo esc_attr( self::set( 'import-data-variation-inventory' ) ) ?>">
                    </div>
					<?php
				}
				?>
            </div>
        </div>
		<?php
	}

	public static function load_variations_ajax( $variations ) {
		return ( self::$settings->get_params( 'show_shipping_option' ) || count( $variations ) >= 10 );
	}

	public static function process_delivery_time( $time ) {
		$time_arr = explode( '-', $time );
		if ( count( $time_arr ) === 2 ) {
			$min = intval( $time_arr[0] );
			if ( $min === intval( $time_arr[1] ) ) {
				$return = sprintf( _n( '%s day', '%s days', $min, 'woo-alidropship' ), $min );
			} else {
				$return = sprintf( esc_html__( '%s days', 'woo-alidropship' ), $time );
			}
		} else {
			$return = sprintf( _n( '%s day', '%s days', $time, 'woo-alidropship' ), $time );
		}

		return $return;
	}

	public static function get_product_attributes( $product_id ) {
		$attributes = get_post_meta( $product_id, '_vi_wad_attributes', true );
		if ( is_array( $attributes ) && count( $attributes ) ) {
			foreach ( $attributes as $key => $value ) {
				if ( ! empty( $value['slug_edited'] ) ) {
					$attributes[ $key ]['slug'] = $value['slug_edited'];
					unset( $attributes[ $key ]['slug_edited'] );
				}
				if ( ! empty( $value['name_edited'] ) ) {
					$attributes[ $key ]['name'] = $value['name_edited'];
					unset( $attributes[ $key ]['name_edited'] );
				}
				if ( ! empty( $value['values_edited'] ) ) {
					$attributes[ $key ]['values'] = $value['values_edited'];
					unset( $attributes[ $key ]['values_edited'] );
				}
			}
		}

		return $attributes;
	}

	public static function get_product_variations( $product_id ) {
		$variations = get_post_meta( $product_id, '_vi_wad_variations', true );
		if ( is_array( $variations ) && count( $variations ) ) {
			foreach ( $variations as $key => $value ) {
				if ( ! empty( $value['attributes_edited'] ) ) {
					$variations[ $key ]['attributes'] = $value['attributes_edited'];
					unset( $variations[ $key ]['attributes_edited'] );
				}
			}
		}

		return $variations;
	}
}