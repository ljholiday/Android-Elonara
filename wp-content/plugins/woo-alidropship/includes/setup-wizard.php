<?php

use Automattic\WooCommerce\Admin\PluginsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Vi_Wad_Setup_Wizard' ) ) {
	class Vi_Wad_Setup_Wizard {
		protected static $settings;
		protected $data;
		protected $current_url;
		protected $plugins;

		function __construct() {
			self::$settings = VI_WOO_ALIDROPSHIP_DATA::get_instance();
			$this->plugins_init();
			add_action( 'admin_head', array( $this, 'setup_wizard' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'vi_wad_print_scripts', array( $this, 'print_script' ) );
			add_action( 'wp_ajax_vi_wad_setup_install_plugins', array( $this, 'install_plugins' ) );
			add_action( 'wp_ajax_vi_wad_setup_activate_plugins', array( $this, 'activate_plugins' ) );
		}

		public static function recommended_plugins() {
			return array(
				array(
					'slug' => 'bulky-bulk-edit-products-for-woo',
					'name' => 'Bulky – Bulk Edit Products for WooCommerce',
					'desc' => __( 'The plugin offers sufficient simple and advanced tools to help filter various available attributes of simple and variable products such as  ID, Title, Content, Excerpt, Slugs, SKU, Post date, range of regular price and sale price, Sale date, range of stock quantity, Product type, Categories.... Users can quickly search for wanted products fields and work with the product fields in bulk.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/bulky-bulk-edit-products-for-woo/assets/icon-128x128.png'
				),
				array(
					'slug' => 'woo-cart-all-in-one',
					'name' => 'Cart All In One For WooCommerce',
					'desc' => __( 'All cart features you need in one simple plugin', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/woo-cart-all-in-one/assets/icon-128x128.png'
				),
				array(
					'slug' => 'email-template-customizer-for-woo',
					'name' => 'Email Template Customizer for WooCommerce',
					'desc' => __( 'Customize WooCommerce emails to make them more beautiful and professional after only several mouse clicks.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/email-template-customizer-for-woo/assets/icon-128x128.jpg'
				),
				array(
					'slug' => 'product-variations-swatches-for-woocommerce',
					'name' => 'Product Variations Swatches for WooCommerce',
					'desc' => __( 'Product Variations Swatches for WooCommerce is a professional plugin that allows you to show and select attributes for variation products. The plugin displays variation select options of the products under colors, buttons, images, variation images, radio so it helps the customers observe the products they need more visually, save time to find the wanted products than dropdown type for variations of a variable product.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/product-variations-swatches-for-woocommerce/assets/icon-128x128.png'
				),
				array(
					'slug' => 'woo-abandoned-cart-recovery',
					'name' => 'Abandoned Cart Recovery for WooCommerce',
					'desc' => __( 'Helps you to recovery unfinished order in your store. When a customer adds a product to cart but does not complete check out. After a scheduled time, the cart will be marked as “abandoned”. The plugin will start to send cart recovery email or facebook message to the customer, remind him/her to complete the order.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/woo-abandoned-cart-recovery/assets/icon-128x128.jpg'
				),
				array(
					'slug' => 'woo-photo-reviews',
					'name' => 'Photo Reviews for WooCommerce',
					'desc' => __( 'An ultimate review plugin for WooCommerce which helps you send review reminder emails, allows customers to post reviews include product pictures and send thank you emails with WooCommerce coupons to customers.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/woo-photo-reviews/assets/icon-128x128.jpg'
				),
				array(
					'slug' => 'woo-orders-tracking',
					'name' => 'Order Tracking for WooCommerce',
					'desc' => __( 'Allows you to bulk add tracking code to WooCommerce orders. Then the plugin will send tracking email with tracking URLs to customers. The plugin also helps you to add tracking code and carriers name to your PayPal transactions. This option will save you tons of time and avoid mistake when adding tracking code to PayPal.', 'woo-alidropship' ),
					'img'  => 'https://ps.w.org/woo-orders-tracking/assets/icon-128x128.jpg'
				),
			);
		}

		protected function plugins_init() {
			return $this->plugins = self::recommended_plugins();
		}

		public function admin_enqueue_scripts() {
			if ( isset( $_GET['vi_wad_setup_wizard'], $_GET['_wpnonce'] ) && $_GET['vi_wad_setup_wizard'] && wp_verify_nonce( $_GET['_wpnonce'], 'vi_wad_setup' ) ) {
				wp_enqueue_style( 'woo-alidropship-input', VI_WOO_ALIDROPSHIP_CSS . 'input.min.css' );
				wp_enqueue_style( 'woo-alidropship-label', VI_WOO_ALIDROPSHIP_CSS . 'label.min.css' );
				wp_enqueue_style( 'woo-alidropship-image', VI_WOO_ALIDROPSHIP_CSS . 'image.min.css' );
				wp_enqueue_style( 'woo-alidropship-transition', VI_WOO_ALIDROPSHIP_CSS . 'transition.min.css' );
				wp_enqueue_style( 'woo-alidropship-form', VI_WOO_ALIDROPSHIP_CSS . 'form.min.css' );
				wp_enqueue_style( 'woo-alidropship-icon', VI_WOO_ALIDROPSHIP_CSS . 'icon.min.css' );
				wp_enqueue_style( 'woo-alidropship-dropdown', VI_WOO_ALIDROPSHIP_CSS . 'dropdown.min.css' );
				wp_enqueue_style( 'woo-alidropship-checkbox', VI_WOO_ALIDROPSHIP_CSS . 'checkbox.min.css' );
				wp_enqueue_style( 'woo-alidropship-segment', VI_WOO_ALIDROPSHIP_CSS . 'segment.min.css' );
				wp_enqueue_style( 'woo-alidropship-button', VI_WOO_ALIDROPSHIP_CSS . 'button.min.css' );
				wp_enqueue_style( 'woo-alidropship-table', VI_WOO_ALIDROPSHIP_CSS . 'table.min.css' );
				wp_enqueue_style( 'select2', VI_WOO_ALIDROPSHIP_CSS . 'select2.min.css' );
				wp_enqueue_script( 'woocommerce-alidropship-transition', VI_WOO_ALIDROPSHIP_JS . 'transition.min.js', array( 'jquery' ) );
				wp_enqueue_script( 'woo-alidropship-dropdown', VI_WOO_ALIDROPSHIP_JS . 'dropdown.min.js', array( 'jquery' ) );
				wp_enqueue_script( 'woo-alidropship-checkbox', VI_WOO_ALIDROPSHIP_JS . 'checkbox.js', array( 'jquery' ) );
				wp_enqueue_script( 'select2-v4', VI_WOO_ALIDROPSHIP_JS . 'select2.js', array( 'jquery' ), '4.0.3' );
				wp_enqueue_style( 'woo-alidropship-admin-style', VI_WOO_ALIDROPSHIP_CSS . 'admin.css' );
				if ( isset( $_GET['step'] ) && $_GET['step'] == 2 ) {
					wp_enqueue_script( 'woo-alidropship-admin', VI_WOO_ALIDROPSHIP_JS . 'setup-wizard.js', array( 'jquery' ) );
				}
			}
		}

		/**
		 * @throws Exception
		 */
		public function setup_wizard() {
			if ( isset( $_POST['submit'] ) && $_POST['submit'] === 'vi_wad_install_recommend_plugins' ) {
				$wc_install = new WC_Install();
				if ( is_array( $this->plugins ) && ! empty( $this->plugins ) ) {
					foreach ( $this->plugins as $plugin ) {
						$slug_name = $this->set_name( $plugin['slug'] );
						if ( ! empty( $_POST[ $slug_name ] ) ) {
							$wc_install::background_installer(
								$plugin['slug'],
								array(
									'name'      => $plugin['name'],
									'repo-slug' => $plugin['slug'],
								)
							);
						}
					}
				}
				wp_safe_redirect( admin_url( 'admin.php?page=woo-alidropship' ) );
				exit;
			}

			if ( isset( $_GET['vi_wad_setup_wizard'], $_GET['_wpnonce'] ) && $_GET['vi_wad_setup_wizard'] && wp_verify_nonce( $_GET['_wpnonce'], 'vi_wad_setup' ) ) {
				$step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : 1;
				$func = 'set_up_step_' . $step;

				if ( method_exists( $this, $func ) ) {
					$this->current_url = remove_query_arg( 'step', esc_url_raw( $_SERVER['REQUEST_URI'] ) );
					?>
                    <div id="vi-wad-setup-wizard">
                        <div class="vi-wad-logo">
                            <img src="<?php echo esc_url( VI_WOO_ALIDROPSHIP_IMAGES . 'icon-256x256.png' ) ?>"
                                 width="80"/>
                        </div>
                        <h1><?php esc_html_e( 'Dropshipping and Fulfillment for AliExpress and WooCommerce Setup Wizard' ); ?></h1>
                        <div class="vi-wad-wrapper vi-ui segment">
							<?php
							$this->$func();
							?>
                        </div>
                        <div class="vi-wad-skip-btn">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-alidropship' ) ) ?>"><?php esc_html_e( 'Skip', 'woo-alidropship' ); ?></a>
                        </div>
                    </div>
					<?php
					do_action( 'vi_wad_print_scripts' );
				}
				exit;
			}
		}

		public function set_up_step_1() {
			$key = self::$settings->get_params( 'secret_key' ) ? self::$settings->get_params( 'secret_key' ) : '';
			?>
            <h2><?php esc_html_e( 'Extension configuration', 'woo-alidropship' ); ?></h2>
            <div class="vi-wad-step-1">
                <table class="vi-ui table">
                    <tr>
                        <td><?php esc_html_e( 'Install Chrome Extension', 'woo-alidropship' ); ?></td>
                        <td>
                            <a href="https://downloads.villatheme.com/?download=alidropship-extension"
                               target="_blank">
								<?php esc_html_e( 'WooCommerce AliExpress Dropshipping Extension', 'woo-alidropship' ); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Copy domain to extension', 'woo-alidropship' ); ?></td>
                        <td><?php echo esc_url( site_url() ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Copy secret key to extension', 'woo-alidropship' ); ?></td>
                        <td><?php echo esc_html( $key ) ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Example', 'woo-alidropship' ); ?></td>
                        <td>
                            <div class="vi-wad-settings-container vi-ui segment">
                                <div class="vi-wad-settings-title">
                                    <span>WooCommerce AliExpress Dropshipping Extension</span>
                                </div>
                                <table class="vi-wad-settings-container-main form-table">
                                    <tbody>
                                    <tr>
                                        <th>Domain</th>
                                        <td>
                                            <input type="text" class="vi-wad-params-domain"
                                                   readonly value="<?php echo esc_url( site_url() ) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Secret key</th>
                                        <td>
                                            <input type="text" class="vi-wad-params-secret-key"
                                                   readonly value="<?php echo esc_html( $key ) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Shipping method</th>
                                        <td>
                                            <select class="vi-wad-params-shipping-company"
                                                    disabled="disabled">
                                                <option value="EMS_ZX_ZX_US">ePacket</option>
                                            </select>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Video guide', 'woo-alidropship' ); ?></td>
                        <td>
                            <iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/AZxnEFfEGfo"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="vi-wad-btn-group">
                <a href="<?php echo esc_url( $this->current_url . '&step=2' ) ?>" class="vi-ui button primary">
					<?php esc_html_e( 'Next', 'woo-alidropship' ); ?>
                </a>
            </div>
			<?php
		}

		public function set_up_step_2() {
			?>
            <h2><?php esc_html_e( 'Plugin configuration', 'woo-alidropship' ); ?></h2>
            <form method="post" action="" class="vi-ui form setup-wizard">
                <div class="vi-wad-step-2">
					<?php wp_nonce_field( 'wooaliexpressdropship_save_settings', '_wooaliexpressdropship_nonce' ) ?>
                    <input type="hidden" name="vi_wad_setup_redirect"
                           value="<?php echo esc_url( $this->current_url . '&step=3' ) ?>">
                    <table class="vi-ui table">
                        <tr>
                            <td>
                                <label for="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'product_categories', true ) ?>"><?php esc_html_e( 'Default categories', 'woo-alidropship' ); ?></label>
                            </td>
                            <td>

                                <select name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'product_categories', false, true ) ?>"
                                        class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'product_categories', true ) ?> search-category"
                                        id="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'product_categories', true ) ?>"
                                        multiple="multiple">
									<?php

									if ( is_array( self::$settings->get_params( 'product_categories' ) ) && count( self::$settings->get_params( 'product_categories' ) ) ) {
										$categories = self::$settings->get_params( 'product_categories' );
										foreach ( $categories as $category_id ) {
											$category = get_term( $category_id );
											if ( $category ) {
												?>
                                                <option value="<?php echo $category_id ?>"
                                                        selected><?php echo $category->name; ?></option>
												<?php
											}
										}
									}
									?>
                                </select>
                                <p><?php esc_html_e( 'Imported products will be added to these categories.', 'woo-alidropship' ) ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Import products currency exchange rate', 'woo-alidropship' ) ?></td>
                            <td>
                                <input type="text"
                                       id="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'import_currency_rate', true ) ?>"
                                       value="<?php echo self::$settings->get_params( 'import_currency_rate' ) ?>"
                                       name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'import_currency_rate' ) ?>"/>
                                <p><?php esc_html_e( 'This is exchange rate to convert from USD to your store currency.', 'woo-alidropship' ) ?></p>
                                <p><?php esc_html_e( 'E.g: Your Woocommerce store currency is VND, exchange rate is: 1 USD = 21 000 VND', 'woo-alidropship' ) ?></p>
                                <p><?php esc_html_e( '=> set "Import products currency exchange rate" 21 000', 'woo-alidropship' ) ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="vi-ui celled table price-rule">
                                    <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Price range', 'woo-alidropship' ) ?></th>
                                        <th><?php esc_html_e( 'Actions', 'woo-alidropship' ) ?></th>
                                        <th><?php esc_html_e( 'Sale price', 'woo-alidropship' ) ?>
                                            <div class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'description', true ) ?>">
												<?php esc_html_e( '(Set -1 to not use sale price)', 'woo-alidropship' ) ?>
                                            </div>
                                        </th>
                                        <th style="min-width: 135px"><?php esc_html_e( 'Regular price', 'woo-alidropship' ) ?></th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_rule_container', true ) ?> ui-sortable">
									<?php
									$price_from       = self::$settings->get_params( 'price_from' );
									$price_default    = self::$settings->get_params( 'price_default' );
									$price_to         = self::$settings->get_params( 'price_to' );
									$plus_value       = self::$settings->get_params( 'plus_value' );
									$plus_sale_value  = self::$settings->get_params( 'plus_sale_value' );
									$plus_value_type  = self::$settings->get_params( 'plus_value_type' );
									$price_from_count = count( $price_from );
									if ( $price_from_count > 0 ) {
										/*adjust price rules since version 1.0.5*/
										if ( ! is_array( $price_to ) || count( $price_to ) !== $price_from_count ) {
											if ( $price_from_count > 1 ) {
												$price_to   = array_values( array_slice( $price_from, 1 ) );
												$price_to[] = '';
											} else {
												$price_to = array( '' );
											}
										}
										for ( $i = 0; $i < count( $price_from ); $i ++ ) {
											switch ( $plus_value_type[ $i ] ) {
												case 'fixed':
													$value_label_left  = '+';
													$value_label_right = '$';
													break;
												case 'percent':
													$value_label_left  = '+';
													$value_label_right = '%';
													break;
												case 'multiply':
													$value_label_left  = 'x';
													$value_label_right = '';
													break;
												default:
													$value_label_left  = '=';
													$value_label_right = '$';
											}
											?>
                                            <tr class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_rule_row', true ) ?>">
                                                <td>
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <div class="vi-ui left labeled input fluid">
                                                                <label for="amount" class="vi-ui label">$</label>
                                                                <input
                                                                        step="any"
                                                                        type="number"
                                                                        min="0"
                                                                        value="<?php echo esc_attr( $price_from[ $i ] ); ?>"
                                                                        name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_from', false, true ); ?>"
                                                                        class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_from', true ); ?>">
                                                            </div>
                                                        </div>
                                                        <span class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_from_to_separator', true ); ?>">-</span>
                                                        <div class="field">
                                                            <div class="vi-ui left labeled input fluid">
                                                                <label for="amount" class="vi-ui label">$</label>
                                                                <input
                                                                        step="any"
                                                                        type="number"
                                                                        min="0"
                                                                        value="<?php echo esc_attr( $price_to[ $i ] ); ?>"
                                                                        name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_to', false, true ); ?>"
                                                                        class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_to', true ); ?>">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value_type', false, true ); ?>"
                                                            class="vi-ui fluid dropdown <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value_type', true ); ?>">
                                                        <option value="fixed" <?php selected( $plus_value_type[ $i ], 'fixed' ) ?>><?php esc_html_e( 'Increase by Fixed amount($)', 'woo-alidropship' ) ?></option>
                                                        <option value="percent" <?php selected( $plus_value_type[ $i ], 'percent' ) ?>><?php esc_html_e( 'Increase by Percentage(%)', 'woo-alidropship' ) ?></option>
                                                        <option value="multiply" <?php selected( $plus_value_type[ $i ], 'multiply' ) ?>><?php esc_html_e( 'Multiply with', 'woo-alidropship' ) ?></option>
                                                        <option value="set_to" <?php selected( $plus_value_type[ $i ], 'set_to' ) ?>><?php esc_html_e( 'Set to', 'woo-alidropship' ) ?></option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="vi-ui right labeled input fluid">
                                                        <label for="amount"
                                                               class="vi-ui label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-left', true ); ?>"><?php echo esc_html( $value_label_left ) ?></label>
                                                        <input type="number" min="-1" step="any"
                                                               value="<?php echo esc_attr( $plus_sale_value[ $i ] ); ?>"
                                                               name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_sale_value', false, true ); ?>"
                                                               class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_sale_value', true ); ?>">
                                                        <div class="vi-ui basic label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-right', true ); ?>"><?php echo esc_html( $value_label_right ) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="vi-ui right labeled input fluid">
                                                        <label for="amount"
                                                               class="vi-ui label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-left', true ); ?>"><?php echo esc_html( $value_label_left ) ?></label>
                                                        <input type="number" min="0" step="any"
                                                               value="<?php echo esc_attr( $plus_value[ $i ] ); ?>"
                                                               name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value', false, true ); ?>"
                                                               class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value', true ); ?>">
                                                        <div class="vi-ui basic label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-right', true ); ?>"><?php echo esc_html( $value_label_right ) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="">
                                                <span class="vi-ui button icon negative mini <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_rule_remove', true ) ?>"><i
                                                            class="icon trash"></i></span>
                                                    </div>
                                                </td>
                                            </tr>
											<?php
										}
									}
									?>
                                    </tbody>
                                    <tfoot>
									<?php
									$plus_value_type_d = isset( $price_default['plus_value_type'] ) ? $price_default['plus_value_type'] : 'multiply';
									$plus_sale_value_d = isset( $price_default['plus_sale_value'] ) ? $price_default['plus_sale_value'] : 1;
									$plus_value_d      = isset( $price_default['plus_value'] ) ? $price_default['plus_value'] : 2;
									switch ( $plus_value_type_d ) {
										case 'fixed':
											$value_label_left  = '+';
											$value_label_right = '$';
											break;
										case 'percent':
											$value_label_left  = '+';
											$value_label_right = '%';
											break;
										case 'multiply':
											$value_label_left  = 'x';
											$value_label_right = '';
											break;
										default:
											$value_label_left  = '=';
											$value_label_right = '$';
									}
									?>
                                    <tr class="<?php echo esc_attr( VI_WOO_ALIDROPSHIP_DATA::set( array( 'price-rule-row-default' ) ) ) ?>">
                                        <th><?php esc_html_e( 'Default', 'woo-alidropship' ) ?></th>
                                        <th>
                                            <select name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_default[plus_value_type]', false ); ?>"
                                                    class="vi-ui fluid dropdown <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value_type', true ); ?>">
                                                <option value="fixed" <?php selected( $plus_value_type_d, 'fixed' ) ?>><?php esc_html_e( 'Increase by Fixed amount($)', 'woo-alidropship' ) ?></option>
                                                <option value="percent" <?php selected( $plus_value_type_d, 'percent' ) ?>><?php esc_html_e( 'Increase by Percentage(%)', 'woo-alidropship' ) ?></option>
                                                <option value="multiply" <?php selected( $plus_value_type_d, 'multiply' ) ?>><?php esc_html_e( 'Multiply with', 'woo-alidropship' ) ?></option>
                                                <option value="set_to" <?php selected( $plus_value_type_d, 'set_to' ) ?>><?php esc_html_e( 'Set to', 'woo-alidropship' ) ?></option>
                                            </select>
                                        </th>
                                        <th>
                                            <div class="vi-ui right labeled input fluid">
                                                <label for="amount"
                                                       class="vi-ui label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-left', true ); ?>"><?php echo esc_html( $value_label_left ) ?></label>
                                                <input type="number" min="-1" step="any"
                                                       value="<?php echo esc_attr( $plus_sale_value_d ); ?>"
                                                       name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_default[plus_sale_value]', false ); ?>"
                                                       class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_sale_value', true ); ?>">
                                                <div class="vi-ui basic label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-right', true ); ?>"><?php echo esc_html( $value_label_right ) ?></div>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="vi-ui right labeled input fluid">
                                                <label for="amount"
                                                       class="vi-ui label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-left', true ); ?>"><?php echo esc_html( $value_label_left ) ?></label>
                                                <input type="number" min="0" step="any"
                                                       value="<?php echo esc_attr( $plus_value_d ); ?>"
                                                       name="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_default[plus_value]', false ); ?>"
                                                       class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'plus_value', true ); ?>">
                                                <div class="vi-ui basic label <?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'value-label-right', true ); ?>"><?php echo esc_html( $value_label_right ) ?></div>
                                            </div>
                                        </th>
                                        <th>
                                        </th>
                                    </tr>
                                    </tfoot>
                                </table>
                                <span class="<?php VI_WOO_ALIDROPSHIP_Admin_Settings::set_params( 'price_rule_add', true ) ?> vi-ui button icon positive mini"><i
                                            class="icon add"></i></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="vi-wad-btn-group">
                    <a href="<?php echo esc_url( $this->current_url . '&step=1' ) ?>" class="vi-ui button">
						<?php esc_html_e( 'Back', 'woo-alidropship' ); ?>
                    </a>
                    <button type="submit"
                            name="<?php esc_attr_e( VI_WOO_ALIDROPSHIP_DATA::set( 'save-settings', true ) ) ?>"
                            class="vi-ui button primary"
                            value="vi_wad_wizard_submit"><?php esc_html_e( 'Next', 'woo-alidropship' ); ?></button>
                </div>
            </form>
			<?php
		}

		public function set_up_step_3() {
			$plugins = $this->plugins;
			?>
            <form method="post" style="margin-bottom: 0"
                  action="<?php echo esc_url( admin_url( 'admin.php?page=woo-alidropship' ) ) ?>">
                <div class="vi-wad-step-3">
                    <div class="">
                        <table id="status" class="vi-ui table">
                            <thead>
                            <tr>
                                <th><input type="checkbox" checked class="vi-wad-toggle-select-plugin"></th>
                                <th></th>
                                <th><?php esc_html_e( 'Recommended plugins', 'woo-alidropship' ) ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							foreach ( $plugins as $plugin ) {
								$plugin_url = "https://wordpress.org/plugins/{$plugin['slug']}";
								?>
                                <tr>
                                    <td>
                                        <input type="checkbox" value="1" checked class="vi-wad-select-plugin"
                                               data-plugin_slug="<?php echo esc_attr( $plugin['slug'] ) ?>"
                                               name="<?php echo $this->set_name( $plugin['slug'] ) ?>">
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( $plugin_url ) ?>" target="_blank">
                                            <img src="<?php echo $plugin['img'] ?>" width="60" height="60">
                                        </a>
                                    </td>
                                    <td>
                                        <div class="vi-wad-plugin-name">
                                            <a href="<?php echo esc_url( $plugin_url ) ?>" target="_blank"><span
                                                        style="font-weight: 700"> <?php echo $plugin['name'] ?></span></a>
                                        </div>
                                        <div style="text-align: justify"><?php echo $plugin['desc'] ?></div>
                                    </td>
                                </tr>
								<?php
							}
							?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="vi-wad-btn-group">
                    <a href="<?php echo esc_url( $this->current_url . '&step=2' ) ?>" class="vi-ui button">
						<?php esc_html_e( 'Back', 'woo-alidropship' ); ?>
                    </a>
                    <button type="submit" class="vi-ui button primary vi-wad-finish" name="submit"
                            value="vi_wad_install_recommend_plugins">
						<?php esc_html_e( 'Install & Return to Dashboard', 'woo-alidropship' ); ?>
                    </button>
                </div>
            </form>
			<?php
		}

		public function set_name( $slug ) {
			return esc_attr( 'vi_install_' . str_replace( '-', '_', $slug ) );
		}

		public function print_script() {
			?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    'use strict';
                    $('.vi-wad-select-plugin').on('change', function () {
                        let checkedCount = $('.vi-wad-select-plugin:checked').length;
                        if (checkedCount === 0) {
                            $('.vi-wad-finish').text('<?php esc_html_e( 'Return to Dashboard', 'woo-alidropship' );?>');
                        } else {
                            $('.vi-wad-finish').text(<?php echo json_encode( __( 'Install & Return to Dashboard', 'woo-alidropship' ) )?>);
                        }
                    });
                    $('.vi-wad-toggle-select-plugin').on('change', function () {
                        let checked = $(this).prop('checked');
                        $('.vi-wad-select-plugin').prop('checked', checked);
                        if (!checked) {
                            $('.vi-wad-finish').text('<?php esc_html_e( 'Return to Dashboard', 'woo-alidropship' );?>');
                        } else {
                            $('.vi-wad-finish').text(<?php echo json_encode( __( 'Install & Return to Dashboard', 'woo-alidropship' ) )?>);
                        }
                    });

                    $('.vi-wad-finish').on('click', function () {
                        let $button = $(this), install_plugins = [];
                        $('.vi-wad-select-plugin').map(function () {
                            let $plugin = $(this);
                            if ($plugin.prop('checked')) {
                                install_plugins.push($plugin.data('plugin_slug'));
                            }
                        });
                        if (install_plugins.length > 0) {
                            $button.addClass('loading');
                            $.ajax({
                                url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) );?>',
                                type: 'POST',
                                dataType: 'JSON',
                                data: {
                                    action: 'vi_wad_setup_install_plugins',
                                    _vi_wad_ajax_nonce: '<?php echo wp_create_nonce( 'woocommerce_alidropship_admin_ajax' )?>',
                                    install_plugins: install_plugins,
                                },
                                success: function (response) {

                                },
                                error: function (err) {

                                },
                                complete: function () {
                                    $.ajax({
                                        url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) );?>',
                                        type: 'POST',
                                        dataType: 'JSON',
                                        data: {
                                            action: 'vi_wad_setup_activate_plugins',
                                            _vi_wad_ajax_nonce: '<?php echo wp_create_nonce( 'woocommerce_alidropship_admin_ajax' )?>',
                                            install_plugins: install_plugins,
                                        },
                                        success: function (response) {

                                        },
                                        error: function (err) {

                                        },
                                        complete: function () {
                                            $button.removeClass('loading');
                                            window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=woo-alidropship' ) )?>';
                                        }
                                    })
                                }
                            })
                        } else {
                            window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=woo-alidropship' ) )?>';
                        }
                        return false;
                    });
                });
            </script>
			<?php
		}

		public function install_plugins() {
			check_ajax_referer( 'woocommerce_alidropship_admin_ajax', '_vi_wad_ajax_nonce' );
			$plugins = isset( $_POST['install_plugins'] ) ? stripslashes_deep( $_POST['install_plugins'] ) : array();
			if ( ! is_array( $plugins ) && ! count( $plugins ) ) {
				wp_send_json_error();
			}

			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			include_once ABSPATH . '/wp-admin/includes/admin.php';
			include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
			include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';

			$existing_plugins  = PluginsHelper::get_installed_plugins_paths();
			$installed_plugins = array();

			foreach ( $plugins as $plugin ) {
				$slug = sanitize_key( $plugin );

				if ( isset( $existing_plugins[ $slug ] ) ) {
					$installed_plugins[] = $plugin;
					continue;
				}

				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => $slug,
						'fields' => array(
							'sections' => false,
						),
					)
				);

				if ( ! is_wp_error( $api ) ) {
					$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
					$result   = $upgrader->install( $api->download_link );
					if ( ! is_wp_error( $result ) && ! is_null( $result ) ) {
						$installed_plugins[] = $plugin;
					}
				}
			}
			if ( count( $installed_plugins ) ) {
				wp_send_json_success( array( 'installed_plugins' => $installed_plugins ) );
			} else {
				wp_send_json_error();
			}
		}

		public function activate_plugins() {
			check_ajax_referer( 'woocommerce_alidropship_admin_ajax', '_vi_wad_ajax_nonce' );
			$plugin_paths = PluginsHelper::get_installed_plugins_paths();
			$plugins      = isset( $_POST['install_plugins'] ) ? stripslashes_deep( $_POST['install_plugins'] ) : array();
			if ( ! is_array( $plugins ) && ! count( $plugins ) ) {
				wp_send_json_error();
			}
			$activated_plugins = array();
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			// the mollie-payments-for-woocommerce plugin calls `WP_Filesystem()` during it's activation hook, which crashes without this include.
			require_once ABSPATH . 'wp-admin/includes/file.php';

			foreach ( $plugins as $plugin ) {
				$slug = $plugin;
				$path = isset( $plugin_paths[ $slug ] ) ? $plugin_paths[ $slug ] : false;
				if ( $path ) {
					$result = activate_plugin( $path );
					if ( is_null( $result ) ) {
						$activated_plugins[] = $plugin;
					}
				}
			}
			if ( count( $activated_plugins ) ) {
				wp_send_json_success( array( 'activated_plugins' => $activated_plugins ) );
			} else {
				wp_send_json_error();
			}
		}
	}
}

new Vi_Wad_Setup_Wizard();