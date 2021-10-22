<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WOO_ALIDROPSHIP_DATA {
	private static $prefix;
	private $params;
	private $default;
	private static $countries;
	private static $states;
	protected static $instance = null;

	/**
	 * VI_WOO_ALIDROPSHIP_DATA constructor.
	 */
	public function __construct() {
		self::$prefix = 'vi-wad-';
		global $wooaliexpressdropship_settings;
		if ( ! $wooaliexpressdropship_settings ) {
			$wooaliexpressdropship_settings = get_option( 'wooaliexpressdropship_params', array() );
		}
		$this->default = array(
			'enable'                                => '1',
			'secret_key'                            => '',
			'product_status'                        => 'publish',
			'catalog_visibility'                    => 'visible',
			'product_gallery'                       => '1',
			'product_categories'                    => array(),
			'product_tags'                          => array(),
			'product_shipping_class'                => '',
			'product_description'                   => 'item_specifics_and_description',
			'variation_visible'                     => '',
			'manage_stock'                          => '1',
			'ignore_ship_from'                      => '',
			'price_from'                            => array( 0 ),
			'price_to'                              => array( '' ),
			'plus_value'                            => array( 200 ),
			'plus_sale_value'                       => array( - 1 ),
			'plus_value_type'                       => array( 'percent' ),
			'price_default'                         => array(
				'plus_value'      => 2,
				'plus_sale_value' => 1,
				'plus_value_type' => 'multiply',
			),
			'import_product_currency'               => 'USD',
			'import_currency_rate'                  => '1',
			'fulfill_default_carrier'               => 'EMS_ZX_ZX_US',
			'fulfill_default_phone_number'          => '',
			'fulfill_default_phone_number_override' => '',
			'fulfill_default_phone_country'         => '',
			'fulfill_order_note'                    => 'I\'m dropshipping. Please DO NOT put any invoices, QR codes, promotions or your brand name logo in the shipments. Please ship as soon as possible for repeat business. Thank you!',
			'order_status_for_fulfill'              => array( 'wc-completed', 'wc-on-hold', 'wc-processing' ),
			'order_status_after_sync'               => 'wc-completed',
			'string_replace'                        => array(),
			'carrier_name_replaces'                 => array(
				'from_string' => array(),
				'to_string'   => array(),
				'sensitive'   => array(),
			),
			'carrier_url_replaces'                  => array(
				'from_string' => array(),
				'to_string'   => array(),
			),
			'disable_background_process'            => '',
			'simple_if_one_variation'               => '',
			'download_description_images'           => '',
			'show_shipping_option'                  => '1',
			'shipping_cost_after_price_rules'       => '1',
			'use_global_attributes'                 => '1',
			'format_price_rules_enable'             => '',
			'format_price_rules_test'               => 0,
			'format_price_rules'                    => array(),
			'override_hide'                         => 0,
			'override_title'                        => 0,
			'override_images'                       => 0,
			'override_description'                  => 0,
			'delete_woo_product'                    => 1,
			'cpf_custom_meta_key'                   => '',
		);

		$this->params = apply_filters( 'wooaliexpressdropship_params', wp_parse_args( $wooaliexpressdropship_settings, $this->default ) );
	}

	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		} elseif ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 'wooaliexpressdropship_params_' . $name, $this->params[ $name ] );
		} else {
			return false;
		}
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function get_attribute_name_by_slug( $slug ) {
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * @param $url
	 *
	 * @return mixed
	 */
	public static function get_domain_from_url( $url ) {
		$url     = strtolower( $url );
		$url_arr = explode( '//', $url );
		if ( count( $url_arr ) > 1 ) {
			$url = str_replace( 'www.', '', $url_arr[1] );

		} else {
			$url = str_replace( 'www.', '', $url_arr[0] );
		}
		$url_arr = explode( '/', $url );
		$url     = $url_arr[0];

		return $url;
	}

	/**
	 * @param array $args
	 * @param bool $return_sku
	 *
	 * @return array
	 */
	public static function get_imported_products( $args = array(), $return_sku = false ) {
		$imported_products = array();
		$args              = wp_parse_args( $args, array(
			'post_type'      => 'vi_wad_draft_product',
			'posts_per_page' => - 1,
			'meta_key'       => '_vi_wad_sku',
			'post_status'    => array(
				'publish',
				'draft',
				'override'
			),
			'fields'         => 'id'
		) );

		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			if ( $return_sku ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$product_id  = get_the_ID();
					$product_sku = get_post_meta( $product_id, '_vi_wad_sku', true );
					if ( $product_sku ) {
						$imported_products[] = $product_sku;
					}
				}
			} else {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$imported_products[] = get_the_ID();
				}
			}
		}
		wp_reset_postdata();

		return $imported_products;
	}

	public static function product_get_woo_id_by_aliexpress_id( $aliexpress_id, $is_variation = false, $count = false, $multiple = false ) {
		global $wpdb;
		if ( $aliexpress_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			if ( $is_variation ) {
				$post_type = 'product_variation';
				$meta_key  = '_vi_wad_aliexpress_variation_id';
			} else {
				$post_type = 'product';
				$meta_key  = '_vi_wad_aliexpress_product_id';
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $aliexpress_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $aliexpress_id ), ARRAY_A );
				} else {
					$query   .= ' LIMIT 1';
					$results = $wpdb->get_var( $wpdb->prepare( $query, $aliexpress_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * @param $product_id
	 * @param bool $count
	 * @param bool $multiple
	 * @param array $status
	 *
	 * @return array|bool|object|string|null
	 */
	public static function product_get_id_by_woo_id(
		$product_id, $count = false, $multiple = false, $status = array(
		'publish',
		'draft',
		'override'
	)
	) {
		global $wpdb;
		if ( $product_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			$post_type      = 'vi_wad_draft_product';
			$meta_key       = '_vi_wad_woo_id';
			$post_status    = '';
			if ( $status ) {
				if ( is_array( $status ) ) {
					$status_count = count( $status );
					if ( $status_count === 1 ) {
						$post_status = " AND {$table_posts}.post_status='{$status[0]}' ";
					} elseif ( $status_count > 1 ) {
						$post_status = " AND {$table_posts}.post_status IN ('" . implode( "','", $status ) . "') ";
					}
				} else {
					$post_status = " AND {$table_posts}.post_status='{$status}' ";
				}
			}

			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'{$post_status}and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $product_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'{$post_status}and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $product_id ), ARRAY_A );
				} else {
					$query   .= ' LIMIT 1';
					$results = $wpdb->get_var( $wpdb->prepare( $query, $product_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**Get vi_wad_draft_product ID that will override $product_id
	 *
	 * @param $product_id
	 *
	 * @return bool|string|null
	 */
	public static function get_overriding_product( $product_id ) {
		global $wpdb;
		if ( $product_id ) {
			$table_posts = "{$wpdb->prefix}posts";
			$query       = "SELECT ID from {$table_posts} where {$table_posts}.post_type = 'vi_wad_draft_product' and {$table_posts}.post_status = 'override' and {$table_posts}.post_parent = %s LIMIT 1";

			return $wpdb->get_var( $wpdb->prepare( $query, $product_id ), 0 );
		} else {
			return false;
		}
	}

	/**
	 * @param $aliexpress_id
	 * @param array $post_status
	 * @param bool $count
	 * @param bool $multiple
	 *
	 * @return array|string|null
	 */
	public static function product_get_id_by_aliexpress_id(
		$aliexpress_id, $post_status = array(
		'publish',
		'draft',
		'override'
	), $count = false, $multiple = false
	) {
		global $wpdb;
		$table_posts    = "{$wpdb->prefix}posts";
		$table_postmeta = "{$wpdb->prefix}postmeta";
		$post_type      = 'vi_wad_draft_product';
		$meta_key       = '_vi_wad_sku';
		$args           = array();
		$where          = array();
		if ( $post_status ) {
			if ( is_array( $post_status ) ) {
				if ( count( $post_status ) === 1 ) {
					$where[] = "{$table_posts}.post_status=%s";
					$args[]  = $post_status[0];
				} else {
					$where[] = "{$table_posts}.post_status IN (" . implode( ', ', array_fill( 0, count( $post_status ), '%s' ) ) . ")";
					foreach ( $post_status as $v ) {
						$args[] = $v;
					}
				}
			} else {
				$where[] = "{$table_posts}.post_status=%s";
				$args[]  = $post_status;
			}
		}
		if ( $aliexpress_id ) {
			$where[] = "{$table_postmeta}.meta_key = '{$meta_key}'";
			$where[] = "{$table_postmeta}.meta_value = %s";
			$args[]  = $aliexpress_id;
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'";
				$query   .= ' AND ' . implode( ' AND ', $where );
				$results = $wpdb->get_var( $wpdb->prepare( $query, $args ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'";
				$query .= ' AND ' . implode( ' AND ', $where );
				if ( $multiple ) {
					$results = $wpdb->get_col( $wpdb->prepare( $query, $args ), 1 );
				} else {
					$query   .= ' LIMIT 1';
					$results = $wpdb->get_var( $wpdb->prepare( $query, $args ), 1 );
				}
			}

		} else {
			$where[] = "{$table_postmeta}.meta_key = '{$meta_key}'";
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'";
				$query   .= ' AND ' . implode( ' AND ', $where );
				$results = $wpdb->get_var( count( $args ) ? $wpdb->prepare( $query, $args ) : $query );
			} else {
				$query   = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}'";
				$query   .= ' AND ' . implode( ' AND ', $where );
				$results = $wpdb->get_col( count( $args ) ? $wpdb->prepare( $query, $args ) : $query, 1 );
			}
		}

		return $results;
	}

	public static function modify_cookie( $cookie ) {
		if ( $cookie ) {
			$cookie_ar   = explode( '&', $cookie );
			$new_cookies = array();
			foreach ( $cookie_ar as $cookie_ar_k => $cookie_ar_v ) {
				$cookie_pat = explode( '=', $cookie_ar_v );
				switch ( $cookie_pat[0] ) {
					case 'site';
						$cookie_pat[1] = 'glo_v';
						break;
					case 'c_tp';
						$cookie_pat[1] = 'USD';
						break;
					default:
				}

				$new_cookies[] = implode( '=', $cookie_pat );
			}

			return implode( '&', $new_cookies );
		} else {
			return $cookie;
		}
	}

	/**
	 * @param $url
	 * @param array $args
	 * @param string $html
	 * @param bool $skip_ship_from_check
	 *
	 * @return array
	 */
	public static function get_data( $url, $args = array(), $html = '', $skip_ship_from_check = false ) {
		$response   = array(
			'status'  => 'success',
			'message' => '',
			'code'    => '',
			'data'    => array(),
		);
		$attributes = array(
			'sku'        => '',
			'is_offline' => false,
		);
		if ( ! $html ) {
			$args             = wp_parse_args( $args, array(
				'user-agent' => self::get_user_agent(),
				'timeout'    => 10,
			) );
			$request          = wp_remote_get( $url, $args );
			$response['code'] = wp_remote_retrieve_response_code( $request );
			if ( ! is_wp_error( $request ) ) {
				$html = $request['body'];
			} else {
				$response['status']  = 'error';
				$response['message'] = $request->get_error_messages();

				return $response;
			}
		}
		$search                     = array( "\n", "\r", "\t" );
		$replace                    = array( "", "", "" );
		$html                       = str_replace( $search, $replace, $html );
		$productVariationMaps       = array();
		$listAttributes             = array();
		$listAttributesDisplayNames = array();
		$propertyValueNames         = array();
		$listAttributesNames        = array();
		$listAttributesSlug         = array();
		$listAttributesIds          = array();
		$variationImages            = array();
		$regSku                     = '/window\.runParams\.productId="([\s\S]*?)";/im';
		preg_match( $regSku, $html, $match_product_sku );
		$variations = array();

		$ignore_ship_from = $skip_ship_from_check ? false : ( new self )->get_params( 'ignore_ship_from' );
		if ( count( $match_product_sku ) === 2 && $match_product_sku[1] ) {
			$attributes['sku'] = $match_product_sku[1];
			$reg               = '/var skuProducts=(\[[\s\S]*?]);/im';
			$regId             = '/<a[\s\S]*?data-sku-id="(\d*?)"[\s\S]*?>(.*?)<\/a>/im';
			$regTitle          = '/<dt class="p-item-title">(.*?)<\/dt>[\s\S]*?data-sku-prop-id="(.*?)"/im';
			$regGallery        = '/imageBigViewURL=(\[[\s\S]*?]);/im';
			$regCurrencyCode   = '/window\.runParams\.currencyCode="([\s\S]*?)";/im';
			$regDetailDesc     = '/window\.runParams\.detailDesc="([\s\S]*?)";/im';
			$regOffline        = '/window\.runParams\.offline=([\s\S]*?);/im';
			$regName           = '/class="product-name" itemprop="name">([\s\S]*?)<\/h1>/im';
			$regDescription    = '/<ul class="product-property-list util-clearfix">([\s\S]*?)<\/ul>/im';
			preg_match( $regOffline, $html, $offlineMatches );
			if ( count( $offlineMatches ) == 2 ) {
				$offline = $offlineMatches[1];
			}

			preg_match( $reg, $html, $matches );
			if ( $matches ) {
				$productVariationMaps = vi_wad_json_decode( $matches[1] );
			}

			preg_match( $regDetailDesc, $html, $detailDescMatches );
			if ( $detailDescMatches ) {
				$attributes['description_url'] = $detailDescMatches[1];
			}

			preg_match( $regDescription, $html, $regDescriptionMatches );
			if ( $regDescriptionMatches ) {
				$attributes['short_description'] = $regDescriptionMatches[0];
			}

			$reg = '/<dl class="p-property-item">([\s\S]*?)<\/dl>/im';
			preg_match_all( $reg, $html, $matches );

			if ( count( $matches[0] ) ) {
				$match_variations = $matches[0];
				$title            = '';
				$titleSlug        = '';
				$reTitle1         = '/title="(.*?)"/mi';
				$reImage          = '/bigpic="(.*?)"/mi';
				$attr_parent_id   = '';
				for ( $i = 0; $i < count( $match_variations ); $i ++ ) {
					preg_match( $regTitle, $match_variations[ $i ], $matchTitle );

					if ( count( $matchTitle ) == 3 ) {
						$title          = $matchTitle[1];
						$title          = substr( $title, 0, strlen( $title ) - 1 );
						$titleSlug      = strtolower( trim( preg_replace( '/[^\w]+/i', '-', $title ) ) );
						$attr_parent_id = $matchTitle[2];
					}

					$attr   = array();
					$images = array();
					preg_match_all( $regId, $match_variations[ $i ], $matchId );

					if ( count( $matchId ) == 3 ) {
						foreach ( $matchId[1] as $matchID_k => $matchID_v ) {
							$listAttributesNames[ $matchID_v ] = $title;
							$listAttributesIds[ $matchID_v ]   = $attr_parent_id;
							$listAttributesSlug[ $matchID_v ]  = $titleSlug;
							preg_match( $reTitle1, $matchId[2][ $matchID_k ], $title1 );

							if ( count( $title1 ) == 2 ) {
								$attr[ $matchID_v ]           = $title1[1];
								$listAttributes[ $matchID_v ] = $title1[1];
							} else {
								$end                          = strlen( $matchId[2][ $matchID_k ] ) - 13;
								$attr[ $matchID_v ]           = substr( $matchId[2][ $matchID_k ], 6, $end );
								$listAttributes[ $matchID_v ] = $attr[ $matchID_v ];
							}

							preg_match( $reImage, $matchId[2][ $matchID_k ], $image );

							if ( count( $image ) == 2 ) {
								$images[ $matchID_v ]          = $image[1];
								$variationImages[ $matchID_v ] = $image[1];
							}
						}

					}
					$attributes['list_attributes']               = $listAttributes;
					$attributes['list_attributes_names']         = $listAttributesNames;
					$attributes['list_attributes_ids']           = $listAttributesIds;
					$attributes['list_attributes_slugs']         = $listAttributesSlug;
					$attributes['variation_images']              = $variationImages;
					$attributes['attributes'][ $attr_parent_id ] = $attr;
					if ( count( $images ) > 0 ) {
						$attributes['images'][ $attr_parent_id ] = $images;
					}
					$attributes['parent'][ $attr_parent_id ]             = $title;
					$attributes['attribute_position'][ $attr_parent_id ] = $i;
					$attributes['parent_slug'][ $attr_parent_id ]        = $titleSlug;
				}
			}

			preg_match( $regGallery, $html, $matchGallery );
			if ( count( $matchGallery ) == 2 ) {
				$attributes['gallery'] = vi_wad_json_decode( $matchGallery[1] );
			}

			for ( $j = 0; $j < count( $productVariationMaps ); $j ++ ) {
				$temp = array(
					'skuId'         => isset( $productVariationMaps[ $j ]['skuIdStr'] ) ? strval( $productVariationMaps[ $j ]['skuIdStr'] ) : strval( $productVariationMaps[ $j ]['skuId'] ),
					'skuPropIds'    => isset( $productVariationMaps[ $j ]['skuPropIds'] ) ? $productVariationMaps[ $j ]['skuPropIds'] : '',
					'skuAttr'       => isset( $productVariationMaps[ $j ]['skuAttr'] ) ? $productVariationMaps[ $j ]['skuAttr'] : '',
					'skuVal'        => $productVariationMaps[ $j ]['skuVal'],
					'image'         => '',
					'variation_ids' => array(),
				);

				if ( $temp['skuPropIds'] ) {
					$temAttr = array();
					$attrIds = explode( ',', $temp['skuPropIds'] );
					for ( $k = 0; $k < count( $attrIds ); $k ++ ) {
						$temAttr[ $attributes['list_attributes_slugs'][ $attrIds[ $k ] ] ] = $attributes['list_attributes'][ $attrIds[ $k ] ];
					}
					$temp['variation_ids'] = $temAttr;
					$temp['image']         = $attributes['variation_images'][ $attrIds[0] ];
				}
				array_push( $variations, $temp );
			}
			$attributes['variations'] = $variations;
			preg_match( $regName, $html, $matchName );
			if ( count( $matchName ) == 2 ) {
				$attributes['name'] = $matchName[1];
			}
			preg_match( $regCurrencyCode, $html, $matchCurrency );
			if ( count( $matchCurrency ) == 2 ) {
				$attributes['currency_code'] = $matchCurrency[1];
			}
		} else {
			/*Data passed from chrome extension in JSON format*/
			$ali_product_data = vi_wad_json_decode( $html );
			if ( json_last_error() ) {
				/*Data crawled directly with PHP is string. Find needed data in JSON then convert to array*/
				preg_match( '/{"actionModule".+}}/im', $html, $match_html );
				if ( count( $match_html ) === 1 && $match_html[0] ) {
					$html             = $match_html[0];
					$ali_product_data = vi_wad_json_decode( $html );
				}
			}
			if ( is_array( $ali_product_data ) && count( $ali_product_data ) ) {
				$actionModule          = isset( $ali_product_data['actionModule'] ) ? $ali_product_data['actionModule'] : array();
				$descriptionModule     = isset( $ali_product_data['descriptionModule'] ) ? $ali_product_data['descriptionModule'] : array();
				$storeModule           = isset( $ali_product_data['storeModule'] ) ? $ali_product_data['storeModule'] : array();
				$imageModule           = isset( $ali_product_data['imageModule'] ) ? $ali_product_data['imageModule'] : array();
				$skuModule             = isset( $ali_product_data['skuModule'] ) ? $ali_product_data['skuModule'] : array();
				$titleModule           = isset( $ali_product_data['titleModule'] ) ? $ali_product_data['titleModule'] : array();
				$webEnv                = isset( $ali_product_data['webEnv'] ) ? $ali_product_data['webEnv'] : array();
				$specsModule           = isset( $ali_product_data['specsModule'] ) ? $ali_product_data['specsModule'] : array();
				$priceModule           = isset( $ali_product_data['priceModule'] ) ? $ali_product_data['priceModule'] : array();
				$attributes['use_lot'] = isset( $priceModule['lot'] ) ? boolval( $priceModule['lot'] ) : false;
				if ( ! empty( $actionModule['productId'] ) ) {
					$attributes['sku'] = $actionModule['productId'];
				} elseif ( ! empty( $descriptionModule['productId'] ) ) {
					$attributes['sku'] = $descriptionModule['productId'];
				}
				if ( isset( $actionModule['itemStatus'] ) && $actionModule['itemStatus'] == 1 ) {
					$attributes['is_offline'] = true;
				}
				$attributes['description_url'] = isset( $descriptionModule['descriptionUrl'] ) ? $descriptionModule['descriptionUrl'] : '';
				$attributes['specsModule']     = isset( $specsModule['props'] ) ? $specsModule['props'] : array();
				$attributes['store_info']      = array(
					'name' => $storeModule['storeName'],
					'url'  => $storeModule['storeURL'],
					'num'  => $storeModule['storeNum'],
				);
				$attributes['gallery']         = isset( $imageModule['imagePathList'] ) ? $imageModule['imagePathList'] : array();
				if ( count( $skuModule ) ) {
					$productSKUPropertyList = isset( $skuModule['productSKUPropertyList'] ) ? $skuModule['productSKUPropertyList'] : array();
					$china_id               = '';
					if ( is_array( $productSKUPropertyList ) && count( $productSKUPropertyList ) ) {
						for ( $i = 0; $i < count( $productSKUPropertyList ); $i ++ ) {
							$images            = array();
							$skuPropertyValues = $productSKUPropertyList[ $i ]['skuPropertyValues'];
							$attr_parent_id    = $productSKUPropertyList[ $i ]['skuPropertyId'];
							$skuPropertyName   = wc_sanitize_taxonomy_name( $productSKUPropertyList[ $i ]['skuPropertyName'] );
							if ( strtolower( $skuPropertyName ) == 'ships-from' && $ignore_ship_from ) {
								foreach ( $skuPropertyValues as $value ) {
									if ( $value['skuPropertySendGoodsCountryCode'] == 'CN' ) {
										$china_id = $value['propertyValueId'] ? $value['propertyValueId'] : $value['propertyValueIdLong'];
									}
								}
								continue;
							} //point 1
							$attr = array(
								'values'   => array(),
								'slug'     => $skuPropertyName,
								'name'     => $productSKUPropertyList[ $i ]['skuPropertyName'],
								'position' => $i,
							);
							for ( $j = 0; $j < count( $skuPropertyValues ); $j ++ ) {
								$skuPropertyValue         = $skuPropertyValues[ $j ];
								$org_propertyValueId      = $skuPropertyValue['propertyValueId'] ? $skuPropertyValue['propertyValueId'] : $skuPropertyValue['propertyValueIdLong'];
								$propertyValueId          = "{$attr_parent_id}:{$org_propertyValueId}";
								$propertyValueName        = $skuPropertyValue['propertyValueName'];
								$propertyValueDisplayName = $skuPropertyValue['propertyValueDisplayName'];
								if ( in_array( $propertyValueDisplayName, $listAttributesDisplayNames ) ) {
									$propertyValueDisplayName = "{$propertyValueDisplayName}-{$org_propertyValueId}";
								}
								if ( in_array( $propertyValueName, $propertyValueNames ) ) {
									$propertyValueName = "{$propertyValueName}-{$org_propertyValueId}";
								}
								$listAttributesNames[ $propertyValueId ]        = $skuPropertyName;
								$listAttributesDisplayNames[ $propertyValueId ] = $propertyValueDisplayName;
								$propertyValueNames[ $propertyValueId ]         = $propertyValueName;
								$listAttributesIds[ $propertyValueId ]          = $attr_parent_id;
								$listAttributesSlug[ $propertyValueId ]         = $skuPropertyName;
								$attr['values'][ $propertyValueId ]             = $propertyValueDisplayName;
								$attr['values_sub'][ $propertyValueId ]         = $propertyValueName;
								$listAttributes[ $propertyValueId ]             = array(
									'name'      => $propertyValueDisplayName,
									'name_sub'  => $propertyValueName,
									'color'     => isset( $skuPropertyValue['skuColorValue'] ) ? $skuPropertyValue['skuColorValue'] : '',
									'image'     => '',
									'ship_from' => isset( $skuPropertyValue['skuPropertySendGoodsCountryCode'] ) ? $skuPropertyValue['skuPropertySendGoodsCountryCode'] : ''
								);
								if ( isset( $skuPropertyValue['skuPropertyImagePath'] ) && $skuPropertyValue['skuPropertyImagePath'] ) {
									$images[ $propertyValueId ]                  = $skuPropertyValue['skuPropertyImagePath'];
									$variationImages[ $propertyValueId ]         = $skuPropertyValue['skuPropertyImagePath'];
									$listAttributes[ $propertyValueId ]['image'] = $skuPropertyValue['skuPropertyImagePath'];
								}
							}

							$attributes['list_attributes']               = $listAttributes;
							$attributes['list_attributes_names']         = $listAttributesNames;
							$attributes['list_attributes_ids']           = $listAttributesIds;
							$attributes['list_attributes_slugs']         = $listAttributesSlug;
							$attributes['variation_images']              = $variationImages;
							$attributes['attributes'][ $attr_parent_id ] = $attr;
							$attributes['images'][ $attr_parent_id ]     = $images;

							$attributes['parent'][ $attr_parent_id ] = $skuPropertyName;
						}
					}

					$skuPriceList = $skuModule['skuPriceList'];
					for ( $j = 0; $j < count( $skuPriceList ); $j ++ ) {
						$temp = array(
							'skuId'              => isset( $skuPriceList[ $j ]['skuIdStr'] ) ? strval( $skuPriceList[ $j ]['skuIdStr'] ) : strval( $skuPriceList[ $j ]['skuId'] ),
							'skuAttr'            => isset( $skuPriceList[ $j ]['skuAttr'] ) ? $skuPriceList[ $j ]['skuAttr'] : '',
							'skuPropIds'         => isset( $skuPriceList[ $j ]['skuPropIds'] ) ? $skuPriceList[ $j ]['skuPropIds'] : '',
							'skuVal'             => $skuPriceList[ $j ]['skuVal'],
							'image'              => '',
							'variation_ids'      => array(),
							'variation_ids_sub'  => array(),
							'variation_ids_slug' => array(),
							'ship_from'          => '',
						);
						if ( $temp['skuPropIds'] ) {
							$temAttr        = array();
							$temAttrSub     = array();
							$attrIds        = explode( ',', $temp['skuPropIds'] );
							$parent_attrIds = explode( ';', $temp['skuAttr'] );

							if ( $china_id && ! in_array( $china_id, $attrIds ) && $ignore_ship_from ) {
								continue;
							}

							for ( $k = 0; $k < count( $attrIds ); $k ++ ) {
								$propertyValueId = explode( ':', $parent_attrIds[ $k ] )[0] . ':' . $attrIds[ $k ];
								if ( isset( $listAttributesDisplayNames[ $propertyValueId ] ) ) {
									$temAttr[ $attributes['list_attributes_slugs'][ $propertyValueId ] ]    = $listAttributesDisplayNames[ $propertyValueId ];
									$temAttrSub[ $attributes['list_attributes_slugs'][ $propertyValueId ] ] = $propertyValueNames[ $propertyValueId ];
									if ( ! empty( $attributes['variation_images'][ $propertyValueId ] ) ) {
										$temp['image'] = $attributes['variation_images'][ $propertyValueId ];
									}
								}
								if ( ! empty( $listAttributes[ $propertyValueId ]['ship_from'] ) ) {
									$temp['ship_from'] = $listAttributes[ $propertyValueId ]['ship_from'];
								}
							}
							$temp['variation_ids']     = $temAttr;
							$temp['variation_ids_sub'] = $temAttrSub;
						}
						$variations [] = $temp;
					}
					$attributes['variations'] = $variations;
				}
				$attributes['name']          = isset( $titleModule['subject'] ) ? $titleModule['subject'] : '';
				$attributes['currency_code'] = isset( $webEnv['currency'] ) ? $webEnv['currency'] : '';
			} else {
				$descriptionModuleReg = '/"descriptionModule":(.*?),"features":{},"feedbackModule"/';
				preg_match( $descriptionModuleReg, $html, $descriptionModule );
				if ( $descriptionModule ) {
					$descriptionModule             = vi_wad_json_decode( $descriptionModule[1] );
					$attributes['sku']             = $descriptionModule['productId'];
					$attributes['description_url'] = $descriptionModule['descriptionUrl'];
				}

				$specsModuleReg = '/"specsModule":(.*?),"storeModule"/';
				preg_match( $specsModuleReg, $html, $specsModule );
				if ( $specsModule ) {
					$specsModule = vi_wad_json_decode( $specsModule[1] );
					if ( isset( $specsModule['props'] ) ) {
						$attributes['specsModule'] = $specsModule['props'];
					}
				}
				$storeModuleReg = '/"storeModule":(.*?),"titleModule"/';
				preg_match( $storeModuleReg, $html, $storeModule );
				if ( $storeModule ) {
					$storeModule              = vi_wad_json_decode( $storeModule[1] );
					$attributes['store_info'] = array(
						'name' => $storeModule['storeName'],
						'url'  => $storeModule['storeURL'],
						'num'  => $storeModule['storeNum'],
					);
				}
				$imagePathListReg = '/"imagePathList":(.*?),"name":"ImageModule"/';
				preg_match( $imagePathListReg, $html, $imagePathList );
				if ( $imagePathList ) {
					$imagePathList         = vi_wad_json_decode( $imagePathList[1] );
					$attributes['gallery'] = $imagePathList;
				}
				$skuModuleReg = '/"skuModule":(.*?),"specsModule"/';
				preg_match( $skuModuleReg, $html, $skuModule );
				if ( count( $skuModule ) == 2 ) {
					$skuModule              = vi_wad_json_decode( $skuModule[1] );
					$productSKUPropertyList = isset( $skuModule['productSKUPropertyList'] ) ? $skuModule['productSKUPropertyList'] : array();
					$china_id               = '';
					if ( is_array( $productSKUPropertyList ) && count( $productSKUPropertyList ) ) {
						for ( $i = 0; $i < count( $productSKUPropertyList ); $i ++ ) {
							$images            = array();
							$skuPropertyValues = $productSKUPropertyList[ $i ]['skuPropertyValues'];
							$attr_parent_id    = $productSKUPropertyList[ $i ]['skuPropertyId'];
							$skuPropertyName   = wc_sanitize_taxonomy_name( $productSKUPropertyList[ $i ]['skuPropertyName'] );
							if ( strtolower( $skuPropertyName ) == 'ships-from' && $ignore_ship_from ) {
								foreach ( $skuPropertyValues as $value ) {
									if ( $value['skuPropertySendGoodsCountryCode'] == 'CN' ) {
										$china_id = $value['propertyValueId'] ? $value['propertyValueId'] : $value['propertyValueIdLong'];
									}
								}
								continue;
							} //point 1
							$attr = array(
								'values'   => array(),
								'slug'     => $skuPropertyName,
								'name'     => $productSKUPropertyList[ $i ]['skuPropertyName'],
								'position' => $i,
							);
							for ( $j = 0; $j < count( $skuPropertyValues ); $j ++ ) {
								$skuPropertyValue                               = $skuPropertyValues[ $j ];
								$org_propertyValueId                            = $skuPropertyValue['propertyValueId'] ? $skuPropertyValue['propertyValueId'] : $skuPropertyValue['propertyValueIdLong'];
								$propertyValueId                                = "{$attr_parent_id}:{$org_propertyValueId}";
								$propertyValueName                              = $skuPropertyValue['propertyValueName'];
								$propertyValueDisplayName                       = $skuPropertyValue['propertyValueDisplayName'];
								$listAttributesNames[ $propertyValueId ]        = $skuPropertyName;
								$listAttributesDisplayNames[ $propertyValueId ] = $propertyValueDisplayName;
								$propertyValueNames[ $propertyValueId ]         = $propertyValueName;
								$listAttributesIds[ $propertyValueId ]          = $attr_parent_id;
								$listAttributesSlug[ $propertyValueId ]         = $skuPropertyName;
								$attr['values'][ $propertyValueId ]             = $propertyValueDisplayName;
								$attr['values_sub'][ $propertyValueId ]         = $propertyValueName;
								$listAttributes[ $propertyValueId ]             = array(
									'name'      => $propertyValueDisplayName,
									'name_sub'  => $propertyValueName,
									'color'     => isset( $skuPropertyValue['skuColorValue'] ) ? $skuPropertyValue['skuColorValue'] : '',
									'image'     => '',
									'ship_from' => isset( $skuPropertyValue['skuPropertySendGoodsCountryCode'] ) ? $skuPropertyValue['skuPropertySendGoodsCountryCode'] : ''
								);
								if ( isset( $skuPropertyValue['skuPropertyImagePath'] ) && $skuPropertyValue['skuPropertyImagePath'] ) {
									$images[ $propertyValueId ]                  = $skuPropertyValue['skuPropertyImagePath'];
									$variationImages[ $propertyValueId ]         = $skuPropertyValue['skuPropertyImagePath'];
									$listAttributes[ $propertyValueId ]['image'] = $skuPropertyValue['skuPropertyImagePath'];
								}
							}

							$attributes['list_attributes']               = $listAttributes;
							$attributes['list_attributes_names']         = $listAttributesNames;
							$attributes['list_attributes_ids']           = $listAttributesIds;
							$attributes['list_attributes_slugs']         = $listAttributesSlug;
							$attributes['variation_images']              = $variationImages;
							$attributes['attributes'][ $attr_parent_id ] = $attr;
							$attributes['images'][ $attr_parent_id ]     = $images;

							$attributes['parent'][ $attr_parent_id ] = $skuPropertyName;
						}
					}

					$skuPriceList = $skuModule['skuPriceList'];
					for ( $j = 0; $j < count( $skuPriceList ); $j ++ ) {
						$temp = array(
							'skuId'              => isset( $skuPriceList[ $j ]['skuIdStr'] ) ? strval( $skuPriceList[ $j ]['skuIdStr'] ) : strval( $skuPriceList[ $j ]['skuId'] ),
							'skuAttr'            => isset( $skuPriceList[ $j ]['skuAttr'] ) ? $skuPriceList[ $j ]['skuAttr'] : '',
							'skuPropIds'         => isset( $skuPriceList[ $j ]['skuPropIds'] ) ? $skuPriceList[ $j ]['skuPropIds'] : '',
							'skuVal'             => $skuPriceList[ $j ]['skuVal'],
							'image'              => '',
							'variation_ids'      => array(),
							'variation_ids_sub'  => array(),
							'variation_ids_slug' => array(),
							'ship_from'          => '',
						);
						if ( $temp['skuPropIds'] ) {
							$temAttr        = array();
							$temAttrSub     = array();
							$attrIds        = explode( ',', $temp['skuPropIds'] );
							$parent_attrIds = explode( ';', $temp['skuAttr'] );

							if ( $china_id && ! in_array( $china_id, $attrIds ) && $ignore_ship_from ) {
								continue;
							}

							for ( $k = 0; $k < count( $attrIds ); $k ++ ) {
								$propertyValueId = explode( ':', $parent_attrIds[ $k ] )[0] . ':' . $attrIds[ $k ];
								if ( isset( $listAttributesDisplayNames[ $propertyValueId ] ) ) {
									$temAttr[ $attributes['list_attributes_slugs'][ $propertyValueId ] ]    = $listAttributesDisplayNames[ $propertyValueId ];
									$temAttrSub[ $attributes['list_attributes_slugs'][ $propertyValueId ] ] = $propertyValueNames[ $propertyValueId ];
									if ( ! empty( $attributes['variation_images'][ $propertyValueId ] ) ) {
										$temp['image'] = $attributes['variation_images'][ $propertyValueId ];
									}
								}
								if ( ! empty( $listAttributes[ $propertyValueId ]['ship_from'] ) ) {
									$temp['ship_from'] = $listAttributes[ $propertyValueId ]['ship_from'];
								}
							}
							$temp['variation_ids']     = $temAttr;
							$temp['variation_ids_sub'] = $temAttrSub;
						}
						$variations [] = $temp;
					}
					$attributes['variations'] = $variations;
				}
				$titleModuleReg = '/"titleModule":(.*?),"webEnv"/';
				preg_match( $titleModuleReg, $html, $titleModule );
				if ( count( $titleModule ) == 2 ) {
					$titleModule        = vi_wad_json_decode( $titleModule[1] );
					$attributes['name'] = $titleModule['subject'];
				}

				$webEnvReg = '/"webEnv":(.*?)}}/';
				preg_match( $webEnvReg, $html, $webEnv );
				if ( count( $webEnv ) == 2 ) {
					$webEnv                      = vi_wad_json_decode( $webEnv[1] . '}' );
					$attributes['currency_code'] = $webEnv['currency'];
				}
			}
		}
		if ( $attributes['sku'] ) {
			$response['data'] = $attributes;
		} else {
			$response['status'] = 'error';
		}

		return $response;
	}

	public static function get_user_agent() {
		$user_agent_list = get_option( 'vi_wad_user_agent_list' );
		if ( ! $user_agent_list ) {
			$user_agent_list = '["Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.1.1 Safari\/605.1.15","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.80 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (X11; Ubuntu; Linux x86_64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10.14; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) HeadlessChrome\/60.0.3112.78 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; rv:60.0) Gecko\/20100101 Firefox\/60.0","Mozilla\/5.0 (Windows NT 6.1; Win64; x64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.90 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/64.0.3282.140 Safari\/537.36 Edge\/17.17134","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.131 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/64.0.3282.140 Safari\/537.36 Edge\/18.17763","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.80 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.1 Safari\/605.1.15","Mozilla\/5.0 (Windows NT 10.0; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.1.1 Safari\/605.1.15","Mozilla\/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64; Trident\/7.0; rv:11.0) like Gecko","Mozilla\/5.0 (X11; Linux x86_64; rv:60.0) Gecko\/20100101 Firefox\/60.0","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36 OPR\/60.0.3255.151","Mozilla\/5.0 (Windows NT 6.1; WOW64; Trident\/7.0; rv:11.0) like Gecko","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.80 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10.13; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.80 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/62.0.3202.94 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.157 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko\/20100101 Firefox\/66.0","Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko\/20100101 Firefox\/68.0","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/72.0.3626.109 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.90 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36 OPR\/60.0.3255.109","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36 OPR\/60.0.3255.170","Mozilla\/5.0 (Windows NT 6.3; Win64; x64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Windows NT 10.0; WOW64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (iPad; CPU OS 12_3_1 like Mac OS X) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.1.1 Mobile\/15E148 Safari\/604.1","Mozilla\/5.0 (Windows NT 6.1; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) HeadlessChrome\/60.0.3112.78 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 YaBrowser\/19.6.1.153 Yowser\/2.5 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/70.0.3538.77 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 YaBrowser\/19.4.3.370 Yowser\/2.5 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 YaBrowser\/19.6.0.1574 Yowser\/2.5 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Ubuntu Chromium\/74.0.3729.169 Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.131 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.0 Safari\/605.1.15","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.86 Safari\/537.36","Mozilla\/5.0 (Linux; U; Android 4.3; en-us; SM-N900T Build\/JSS15J) AppleWebKit\/534.30 (KHTML, like Gecko) Version\/4.0 Mobile Safari\/534.30","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.0.3 Safari\/605.1.15","Mozilla\/5.0 (Windows NT 6.1) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/11.1.2 Safari\/605.1.15","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.80 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/12.0.2 Safari\/605.1.15","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko\/20100101 Firefox\/45.0","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.90 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.157 Safari\/537.36","Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.90 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.169 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/72.0.3626.121 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.86 Safari\/537.36","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/75.0.3770.100 Safari\/537.36","Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko\/20100101 Firefox\/60.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10.12; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit\/605.1.15 (KHTML, like Gecko) Version\/13.0 Safari\/605.1.15","Mozilla\/5.0 (Windows NT 6.1; rv:67.0) Gecko\/20100101 Firefox\/67.0","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36 OPR\/60.0.3255.151","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 Safari\/537.36 OPR\/60.0.3255.170","Mozilla\/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/74.0.3729.131 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/73.0.3683.103 YaBrowser\/19.4.3.370 Yowser\/2.5 Safari\/537.36","Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:56.0) Gecko\/20100101 Firefox\/56.0","Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:56.0) Gecko\/20100101 Firefox\/56.0"]';
			update_option( 'vi_wad_user_agent_list', $user_agent_list );
		}
		$user_agent_list_array = vi_wad_json_decode( $user_agent_list );
		$return_agent          = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
		$last_used             = get_option( 'vi_wad_last_used_user_agent', 0 );
		if ( $last_used == count( $user_agent_list_array ) - 1 ) {
			$last_used = 0;
			shuffle( $user_agent_list_array );
			update_option( 'vi_wad_user_agent_list', json_encode( $user_agent_list_array ) );
		} else {
			$last_used ++;
		}
		update_option( 'vi_wad_last_used_user_agent', $last_used );
		if ( isset( $user_agent_list_array[ $last_used ] ) && $user_agent_list_array[ $last_used ] ) {
			$return_agent = $user_agent_list_array[ $last_used ];
		}

		return $return_agent;
	}

	public static function sku_exists( $sku = '' ) {
		$sku_exists = false;
		if ( $sku ) {
			$id_from_sku = wc_get_product_id_by_sku( $sku );
			$product     = $id_from_sku ? wc_get_product( $id_from_sku ) : false;
			$sku_exists  = $product && 'importing' !== $product->get_status();
		}

		return $sku_exists;
	}

	public static function set( $name, $set_name = false ) {
		if ( is_array( $name ) ) {
			return implode( ' ', array_map( array( 'VI_WOO_ALIDROPSHIP_DATA', 'set' ), $name ) );
		} else {
			if ( $set_name ) {
				return str_replace( '-', '_', self::$prefix . $name );
			} else {
				return self::$prefix . $name;
			}
		}
	}

	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 'wooaliexpressdropship_params_default_' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}

	/**
	 * @param $string_number
	 *
	 * @return float
	 */
	public static function string_to_float( $string_number ) {
		return floatval( str_replace( ',', '', $string_number ) );
	}

	public function process_exchange_price( $price ) {
		if ( ! $price ) {
			return $price;
		}
		$rate = floatval( $this->get_params( 'import_currency_rate' ) );
		if ( $rate ) {
			$price = $price * $rate;
		}
		if ( $this->get_params( 'format_price_rules_enable' ) ) {
			self::format_price( $price );
		}

		return round( $price, wc_get_price_decimals() );
	}

	protected static function calculate_price_base_on_type( $price, $value, $type ) {
		$match_value = floatval( $value );
		switch ( $type ) {
			case 'fixed':
				$price = $price + $match_value;
				break;
			case 'percent':
				$price = $price * ( 1 + $match_value / 100 );
				break;
			case 'multiply':
				$price = $price * $match_value;
				break;
			default:
				$price = $match_value;
		}

		return $price;
	}

	/**
	 * @param $price
	 * @param bool $is_sale_price
	 *
	 * @return float|int
	 */
	public function process_price( $price, $is_sale_price = false ) {
		if ( ! $price ) {
			return $price;
		}
		$price_default   = $this->get_params( 'price_default' );
		$price_from      = $this->get_params( 'price_from' );
		$price_to        = $this->get_params( 'price_to' );
		$plus_value_type = $this->get_params( 'plus_value_type' );

		if ( $is_sale_price ) {
			$plus_sale_value = $this->get_params( 'plus_sale_value' );
			$level_count     = count( $price_from );
			if ( $level_count > 0 ) {
				/*adjust price rules since version 1.0.1.1*/
				if ( ! is_array( $price_to ) || count( $price_to ) !== $level_count ) {
					if ( $level_count > 1 ) {
						$price_to   = array_values( array_slice( $price_from, 1 ) );
						$price_to[] = '';
					} else {
						$price_to = array( '' );
					}
				}
				$match = false;
				for ( $i = 0; $i < $level_count; $i ++ ) {
					if ( $price >= $price_from[ $i ] && ( $price_to[ $i ] === '' || $price <= $price_to[ $i ] ) ) {
						$match = $i;
						break;
					}
				}
				if ( $match !== false ) {
					if ( $plus_sale_value[ $match ] < 0 ) {
						$price = 0;
					} else {
						$price = self::calculate_price_base_on_type( $price, $plus_sale_value[ $match ], $plus_value_type[ $match ] );
					}
				} else {
					$price = self::calculate_price_base_on_type( $price, isset( $price_default['plus_sale_value'] ) ? $price_default['plus_sale_value'] : 1, isset( $price_default['plus_value_type'] ) ? $price_default['plus_value_type'] : 'multiply' );
				}
			}
		} else {
			$plus_value  = $this->get_params( 'plus_value' );
			$level_count = count( $price_from );
			if ( $level_count > 0 ) {
				/*adjust price rules since version 1.0.1.1*/
				if ( ! is_array( $price_to ) || count( $price_to ) !== $level_count ) {
					if ( $level_count > 1 ) {
						$price_to   = array_values( array_slice( $price_from, 1 ) );
						$price_to[] = '';
					} else {
						$price_to = array( '' );
					}
				}
				$match = false;
				for ( $i = 0; $i < $level_count; $i ++ ) {
					if ( $price >= $price_from[ $i ] && ( $price_to[ $i ] === '' || $price <= $price_to[ $i ] ) ) {
						$match = $i;
						break;
					}
				}
				if ( $match !== false ) {
					$price = self::calculate_price_base_on_type( $price, $plus_value[ $match ], $plus_value_type[ $match ] );
				} else {
					$price = self::calculate_price_base_on_type( $price, isset( $price_default['plus_value'] ) ? $price_default['plus_value'] : 2, isset( $price_default['plus_value_type'] ) ? $price_default['plus_value_type'] : 'multiply' );
				}
			}
		}

		return $price;
	}

	public static function format_price( &$price ) {
		$applied = array();
		if ( $price ) {
			$instance = self::get_instance();
			$rules    = $instance->get_params( 'format_price_rules' );
			if ( is_array( $rules ) && count( $rules ) ) {
				$decimals        = wc_get_price_decimals();
				$price           = self::string_to_float( $price );
				$int_part        = intval( $price );
				$decimal_part    = number_format( $price - $int_part, $decimals );
				$int_part_length = strlen( $int_part );
				if ( $decimals > 0 ) {
					foreach ( $rules as $key => $rule ) {
						if ( $rule['part'] === 'fraction' ) {
							if ( ( ! $rule['from'] && ! $rule['to'] ) || ( $price >= $rule['from'] && $price <= $rule['to'] ) || ( ! $rule['from'] && $price <= $rule['to'] ) || ( ! $rule['to'] && $price >= $rule['from'] ) ) {
								$compare_value = $decimal_part;
								$string        = substr( strval( $decimal_part ), 2 );
								if ( ( $rule['value_from'] === '' && $rule['value_to'] === '' ) || ( $compare_value >= self::string_to_float( ".{$rule['value_from']}" ) && $compare_value <= self::string_to_float( ".{$rule['value_to']}" ) ) || ( $rule['value_from'] === '' && $compare_value <= self::string_to_float( ".{$rule['value_to']}" ) ) || ( $rule['value_to'] === '' && $compare_value >= self::string_to_float( ".{$rule['value_from']}" ) ) ) {
									while ( ( $pos = strpos( $rule['value'], 'x' ) ) !== false ) {
										$replace = 'y';
										if ( $pos < strlen( $string ) ) {
											$replace = substr( $string, $pos, 1 );
										}
										$rule['value'] = substr_replace( $rule['value'], $replace, $pos, 1 );
									}
									$price        = $int_part + self::string_to_float( ".{$rule['value']}" );
									$decimal_part = $price - $int_part;
									$applied[]    = $key;
									break;
								}
							}
						}
					}
				}
				foreach ( $rules as $key => $rule ) {
					if ( $rule['part'] === 'integer' ) {
						if ( $price >= $rule['from'] && $price <= $rule['to'] ) {
							if ( $rule['value_from'] === '' && $rule['value_to'] === '' ) {
								$max = min( $int_part_length - 1, strlen( $rule['value'] ) );
								if ( $max > 0 ) {
									$compare_value = intval( substr( $int_part, $int_part_length - $max ) );
									$string        = strval( zeroise( $compare_value, $max ) );
									$rule['value'] = zeroise( $rule['value'], $max );
									while ( ( $pos = strpos( $rule['value'], 'x' ) ) !== false ) {
										$replace = 'y';
										if ( $pos < strlen( $string ) ) {
											$replace = substr( $string, $pos, 1 );
										}
										$rule['value'] = substr_replace( $rule['value'], $replace, $pos, 1 );
									}
									$price     = $int_part - $compare_value + intval( $rule['value'] ) + $decimal_part;
									$applied[] = $key;
									break;
								}
							} else {
								$max = min( $int_part_length, max( strlen( $rule['value_from'] ), strlen( $rule['value_to'] ), strlen( $rule['value'] ) ) );
								if ( $max > 0 ) {
									$compare_value = intval( substr( $int_part, $int_part_length - $max ) );
									$string        = strval( zeroise( $compare_value, $max ) );
									$rule['value'] = zeroise( $rule['value'], $max );
									if ( ( $compare_value >= intval( $rule['value_from'] ) && $compare_value <= intval( $rule['value_to'] ) ) ) {
										while ( ( $pos = strpos( $rule['value'], 'x' ) ) !== false ) {
											$replace = 'y';
											if ( $pos < strlen( $string ) ) {
												$replace = substr( $string, $pos, 1 );
											}
											$rule['value'] = substr_replace( $rule['value'], $replace, $pos, 1 );
										}
										$price     = $int_part - $compare_value + intval( $rule['value'] ) + $decimal_part;
										$applied[] = $key;
										break;
									}
								}
							}
						}
					}
				}
			}
		}

		return $applied;
	}

	public static function process_variation_sku( $sku, $variation_ids ) {
		$return = '';
		if ( is_array( $variation_ids ) && count( $variation_ids ) ) {
			foreach ( $variation_ids as $key => $value ) {
				$variation_ids[ $key ] = wc_sanitize_taxonomy_name( $value );
			}
			$return = $sku . '-' . implode( '-', $variation_ids );
		}

		return $return;
	}

	public static function download_description( $product_id, $description_url, $description, $product_description ) {
		if ( $description_url && $product_id ) {
			$request = wp_remote_get(
				$description_url,
				array(
					'user-agent' => self::get_user_agent(),
					'timeout'    => 3,
				)
			);
			if ( ! is_wp_error( $request ) && get_post_type( $product_id ) === 'vi_wad_draft_product' ) {
				if ( isset( $request['body'] ) && $request['body'] ) {
					$body = preg_replace( '/<script\>[\s\S]*?<\/script>/im', '', $request['body'] );
					preg_match_all( '/src="([\s\S]*?)"/im', $body, $matches );
					if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
						update_post_meta( $product_id, '_vi_wad_description_images', array_values( array_unique( $matches[1] ) ) );
					}
					$instance    = self::get_instance();
					$str_replace = $instance->get_params( 'string_replace' );
					if ( isset( $str_replace['to_string'] ) && is_array( $str_replace['to_string'] ) && $str_replace_count = count( $str_replace['to_string'] ) ) {
						for ( $i = 0; $i < $str_replace_count; $i ++ ) {
							if ( $str_replace['sensitive'][ $i ] ) {
								$body = str_replace( $str_replace['from_string'][ $i ], $str_replace['to_string'][ $i ], $body );
							} else {
								$body = str_ireplace( $str_replace['from_string'][ $i ], $str_replace['to_string'][ $i ], $body );
							}
						}

					}
					if ( $product_description === 'item_specifics_and_description' || $product_description === 'description' ) {
						$description .= $body;
						wp_update_post( array( 'ID' => $product_id, 'post_content' => $description ) );
					}
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public static function get_disable_wp_cron() {
		return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true;
	}

	/**Download image from url
	 *
	 * @param $image_id
	 * @param $url
	 * @param int $post_parent
	 * @param array $exclude
	 * @param string $post_title
	 * @param null $desc
	 *
	 * @return array|bool|int|object|string|WP_Error|null
	 */
	public static function download_image( &$image_id, $url, $post_parent = 0, $exclude = array(), $post_title = '', $desc = null ) {
		global $wpdb;
		$new_url   = $url;
		$parse_url = wp_parse_url( $new_url );
		$scheme    = empty( $parse_url['scheme'] ) ? 'http' : $parse_url['scheme'];
		$image_id  = "{$parse_url['host']}{$parse_url['path']}";
		$new_url   = "{$scheme}://{$image_id}";
		preg_match( '/[^\?]+\.(jpg|JPG|jpeg|JPEG|jpe|JPE|gif|GIF|png|PNG)/', $new_url, $matches );
		if ( ! is_array( $matches ) || ! count( $matches ) ) {
			preg_match( '/[^\?]+\.(jpg|JPG|jpeg|JPEG|jpe|JPE|gif|GIF|png|PNG)/', $url, $matches );
			if ( is_array( $matches ) && count( $matches ) ) {
				$new_url  .= "?{$matches[0]}";
				$image_id .= "?{$matches[0]}";
			}
		}

		$thumb_id = self::get_id_by_image_id( $image_id );
		if ( ! $thumb_id ) {
			$thumb_id = vi_wad_upload_image( $new_url, $post_parent, $exclude, $post_title, $desc );
			if ( ! is_wp_error( $thumb_id ) ) {
				update_post_meta( $thumb_id, '_vi_wad_image_id', $image_id );
			}
		} elseif ( $post_parent ) {
			$table_postmeta = "{$wpdb->prefix}posts";
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_postmeta} set post_parent=%s WHERE ID=%s AND post_parent = 0 LIMIT 1", array(
				$post_parent,
				$thumb_id
			) ) );
		}

		return $thumb_id;
	}

	/**
	 * @param $image_id
	 * @param bool $count
	 * @param bool $multiple
	 *
	 * @return array|bool|object|string|null
	 */
	public static function get_id_by_image_id( $image_id, $count = false, $multiple = false ) {
		global $wpdb;
		if ( $image_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			$post_type      = 'attachment';
			$meta_key       = "_vi_wad_image_id";
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $image_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $image_id ), ARRAY_A );
				} else {
					$query   .= ' LIMIT 1';
					$results = $wpdb->get_var( $wpdb->prepare( $query, $image_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	public static function count_posts( $status ) {
		$args_publish = array(
			'post_type'      => 'vi_wad_draft_product',
			'post_status'    => $status,
			'order'          => 'DESC',
			'meta_key'       => '_vi_wad_woo_id',
			'orderby'        => 'meta_value_num',
			'posts_per_page' => - 1,
		);
		$the_query    = new WP_Query( $args_publish );
		$total        = isset( $the_query->post_count ) ? $the_query->post_count : 0;
		wp_reset_postdata();

		return $total;
	}

	/**Get available shipping company
	 *
	 * @param string $slug
	 *
	 * @return array|mixed|string
	 */
	public static function get_shipping_companies( $slug = '' ) {
		$shipping_companies = apply_filters( 'vi_wad_aliexpress_shipping_companies', array(
			'AE_CAINIAO_STANDARD'      => "Cainiao Expedited Standard",
			'AE_CN_SUPER_ECONOMY_G'    => "Cainiao Super Economy Global",
			'ARAMEX'                   => "ARAMEX",
			'CAINIAO_CONSOLIDATION_SA' => "AliExpress Direct(SA)",
			'CAINIAO_CONSOLIDATION_AE' => "AliExpress Direct(AE)",
			'CAINIAO_ECONOMY'          => "AliExpress Saver Shipping",
			'CAINIAO_PREMIUM'          => "AliExpress Premium Shipping",
			'CAINIAO_STANDARD'         => "AliExpress Standard Shipping",
			'CHP'                      => "Swiss Post",
			'CPAM'                     => "China Post Registered Air Mail",
			'DHL'                      => "DHL",
			'DHLECOM'                  => "DHL e-commerce",
			'EMS'                      => "EMS",
			'EMS_ZX_ZX_US'             => "ePacket",
			'E_EMS'                    => "e-EMS",
			'FEDEX'                    => "Fedex IP",
			'FEDEX_IE'                 => "Fedex IE",
			'GATI'                     => "GATI",
			'POST_NL'                  => "PostNL",
			'PTT'                      => "Turkey Post",
			'SF'                       => "SF Express",
			'SF_EPARCEL'               => "SF eParcel",
			'SGP'                      => "Singapore Post",
			'SUNYOU_ECONOMY'           => "SunYou Economic Air Mail",
			'TNT'                      => "TNT",
			'TOLL'                     => "DPEX",
			'UBI'                      => "UBI",
			'UPS'                      => "UPS Express Saver",
			'UPSE'                     => "UPS Expedited",
			'USPS'                     => "USPS",
			'YANWEN_AM'                => "Yanwen Special Line-YW",
			'YANWEN_ECONOMY'           => "Yanwen Economic Air Mail",
			'YANWEN_JYT'               => "China Post Ordinary Small Packet Plus",
			'POLANDPOST_PL'            => "Poland Post",
			'Other'                    => "Seller's Shipping Method",
		) );
		if ( $slug ) {
			return isset( $shipping_companies[ $slug ] ) ? $shipping_companies[ $slug ] : '';
		} else {
			return $shipping_companies;
		}
	}

	public static function wp_remote_get( $url, $args = array() ) {
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		$args    = array_merge( array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => 3,
			)
			, $args );
		$request = wp_remote_get(
			$url, $args
		);
		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			$return['status'] = 'success';
			$return['data']   = json_decode( $request['body'], true );
		} else {
			$return['status'] = 'error';
			$return['data']   = $request->get_error_message();
		}

		return $return;
	}

	public static function sanitize_taxonomy_name( $name ) {
		return urldecode( function_exists( 'mb_strtolower' ) ? mb_strtolower( urlencode( wc_sanitize_taxonomy_name( $name ) ) ) : strtolower( urlencode( wc_sanitize_taxonomy_name( $name ) ) ) );
	}

	public static function get_aliexpress_product_url( $sku ) {
		return "https://www.aliexpress.com/item/{$sku}.html";
	}

	/**Get WooCommerce countries in English
	 * @return mixed
	 */
	public static function get_countries() {
		if ( self::$countries === null ) {
			unload_textdomain( 'woocommerce' );
			self::$countries = apply_filters( 'woocommerce_countries', include WC()->plugin_path() . '/i18n/countries.php' );
			if ( apply_filters( 'woocommerce_sort_countries', true ) ) {
				wc_asort_by_locale( self::$countries );
			}
			$locale = determine_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce' );
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( WC_PLUGIN_FILE ) ) . '/i18n/languages' );
		}

		return self::$countries;
	}

	/**Get WooCommerce states in English
	 *
	 * @param $cc
	 *
	 * @return bool|mixed
	 */
	public static function get_states( $cc ) {
		if ( self::$states === null ) {
			unload_textdomain( 'woocommerce' );
			self::$states = apply_filters( 'woocommerce_states', include WC()->plugin_path() . '/i18n/states.php' );
			$locale       = determine_locale();
			$locale       = apply_filters( 'plugin_locale', $locale, 'woocommerce' );
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( WC_PLUGIN_FILE ) ) . '/i18n/languages' );
		}

		if ( ! is_null( $cc ) ) {
			return isset( self::$states[ $cc ] ) ? self::$states[ $cc ] : false;
		} else {
			return self::$states;
		}
	}

	/**Allows only numbers
	 *
	 * @param $phone
	 *
	 * @return string
	 */
	public static function sanitize_phone_number( $phone ) {
		return preg_replace( '/[^\d]/', '', $phone );
	}
}

VI_WOO_ALIDROPSHIP_DATA::get_instance();