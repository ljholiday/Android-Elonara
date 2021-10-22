<?php

namespace DgoraWcas\Integrations\Themes\Enfold;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Enfold {

	private $themeSlug = 'enfold';

	private $themeName = 'Enfold';

	public function __construct() {
		$this->maybeOverwriteSearch();

		add_filter( 'dgwt/wcas/settings', array( $this, 'registerSettings' ) );

		add_action( 'init', function () {

			add_action( 'wp_head', array( $this, 'customCSS' ) );
			add_action( 'wp_footer', array( $this, 'customJS' ) );

		} );
	}

	/**
	 * Add settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function registerSettings( $settings ) {
		$key = 'dgwt_wcas_basic';

		$settings[ $key ][10] = array(
			'name'  => $this->themeSlug . '_main_head',
			'label' => sprintf( __( 'Replace %s search bar', 'ajax-search-for-woocommerce' ), $this->themeName ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		$settings[ $key ][52] = array(
			'name'  => $this->themeSlug . '_settings_head',
			'label' => sprintf( __( '%s Theme', 'ajax-search-for-woocommerce' ), $this->themeName ),
			'type'  => 'desc',
			'desc'  => Helpers::embeddingInThemeHtml(),
			'class' => 'dgwt-wcas-sgs-themes-label',
		);

		$img = DGWT_WCAS()->themeCompatibility->getThemeImageSrc();
		if ( ! empty( $img ) ) {
			$settings[ $key ][52]['label'] = '<img src="' . $img . '">';
		}

		$settings[ $key ][55] = array(
			'name'    => $this->themeSlug . '_replace_search',
			'label'   => __( 'Replace', 'ajax-search-for-woocommerce' ),
			'desc'    => sprintf( __( 'Replace all %s search bars with the %s.', 'ajax-search-for-woocommerce' ), $this->themeName, DGWT_WCAS_NAME ),
			'type'    => 'checkbox',
			'default' => 'off',
		);

		$settings[ $key ][90] = array(
			'name'  => $this->themeSlug . '_othersways__head',
			'label' => __( 'Alternative ways to embed a search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		return $settings;
	}

	/**
	 * Check if can replace the native search form with the FiboSearch form.
	 *
	 * @return bool
	 */
	private function canReplaceSearch() {
		$canIntegrate = false;

		if ( DGWT_WCAS()->settings->getOption( $this->themeSlug . '_replace_search', 'off' ) === 'on' ) {
			$canIntegrate = true;
		}

		return $canIntegrate;
	}

	/**
	 * Overwrite search
	 *
	 * @return void
	 */
	private function maybeOverwriteSearch() {
		if ( $this->canReplaceSearch() ) {

			// Force enable overlay for mobile search
			add_filter( 'dgwt/wcas/settings/load_value/key=enable_mobile_overlay', function () {
				return 'on';
			} );

			// Change mobile breakpoint to 768
			add_filter( 'dgwt/wcas/scripts/mobile_breakpoint', function () {
				return 768;
			} );

			require_once DGWT_WCAS_DIR . 'partials/themes/enfold.php';
		}
	}

	/**
	 * Custom CSS
	 *
	 * @return void
	 */
	public function customCSS() {
		?>
		<style>
			#top .dgwt-wcas-no-submit .dgwt-wcas-sf-wrapp input[type="search"].dgwt-wcas-search-input {
				padding: 10px 15px 10px 40px;
				margin: 0;
			}

			#top.rtl .dgwt-wcas-no-submit .dgwt-wcas-sf-wrapp input[type="search"].dgwt-wcas-search-input {
				padding: 10px 40px 10px 15px
			}

			#top .av-main-nav .dgwt-wcas-no-submit .dgwt-wcas-sf-wrapp input[type="search"].dgwt-wcas-search-input {
				padding: 10px 15px 10px 15px;
				margin: 0;
			}

			#top.rtl .av-main-nav .dgwt-wcas-no-submit .dgwt-wcas-sf-wrapp input[type="search"].dgwt-wcas-search-input {
				padding: 10px 15px 10px 15px
			}
		</style>
		<?php
	}

	/**
	 * Custom JS
	 *
	 * @return void
	 */
	public function customJS() {
		?>
		<script>
			(function ($) {
				function avia_apply_quant_btn() {
					jQuery(".quantity input[type=number]").each(function () {
						var number = $(this),
							max = parseFloat(number.attr('max')),
							min = parseFloat(number.attr('min')),
							step = parseInt(number.attr('step'), 10),
							newNum = jQuery(jQuery('<div />').append(number.clone(true)).html().replace('number', 'text')).insertAfter(number);
						number.remove();

						setTimeout(function () {
							if (newNum.next('.plus').length === 0) {
								var minus = jQuery('<input type="button" value="-" class="minus">').insertBefore(newNum),
									plus = jQuery('<input type="button" value="+" class="plus">').insertAfter(newNum);

								minus.on('click', function () {
									var the_val = parseInt(newNum.val(), 10) - step;
									the_val = the_val < 0 ? 0 : the_val;
									the_val = the_val < min ? min : the_val;
									newNum.val(the_val).trigger("change");
								});
								plus.on('click', function () {
									var the_val = parseInt(newNum.val(), 10) + step;
									the_val = the_val > max ? max : the_val;
									newNum.val(the_val).trigger("change");

								});
							}
						}, 10);

					});
				}

				$(document).ready(function () {

					$(document).on('dgwtWcasDetailsPanelLoaded', function () {
						avia_apply_quant_btn();
					});
				});

			}(jQuery));
		</script>
		<?php
	}

}
