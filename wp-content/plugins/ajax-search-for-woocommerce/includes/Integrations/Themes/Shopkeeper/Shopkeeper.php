<?php

namespace DgoraWcas\Integrations\Themes\Shopkeeper;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shopkeeper {

	private $themeSlug = 'shopkeeper';

	private $themeName = 'Shopkeeper';

	public function __construct() {

		add_filter( 'dgwt/wcas/settings', array( $this, 'registerSettings' ) );

		$this->maybeReplaceSearch();
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
			'name'    => 'shopkeeper_replace_search',
			'label'   => __( 'Replace', 'ajax-search-for-woocommerce' ),
			'desc'    => sprintf( __( 'Replace all %s search bars with the %s.', 'ajax-search-for-woocommerce' ), $this->themeName, DGWT_WCAS_NAME ),
			'type'    => 'checkbox',
			'default' => 'on',
		);

		if( class_exists('Shopkeeper_Opt') && !\Shopkeeper_Opt::getOption( 'predictive_search', true ) ) {

			$desc =  '<p>'.__( 'To replace the search bar you have to enable "Predictive Search" option in the Shopkeeper settings.', 'ajax-search-for-woocommerce' ) . '<p>';
			$desc .=  '<p>'.sprintf(__( 'Go to <code>Appearance -> <a target="_blank" href="%s">Customize</a> -> Header -> Search</code> and enable <code>Predictive Search</code>', 'ajax-search-for-woocommerce' ), admin_url('customize.php') ) . '<p>';


			$settings[ $key ][58] = array(
				'name'  => 'shopkeeper_replace_search_info',
				'label' => __( 'Warning!', 'ajax-search-for-woocommerce' ),
				'desc'  => $desc,
				'type'  => 'desc',
				'class' => 'dgwt-wcas-sgs-themes-label',
			);
		}

		$settings[ $key ][90] = array(
			'name'  => $this->themeSlug . '_othersways__head',
			'label' => __( 'Alternative ways to embed a search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		return $settings;
	}

	/**
	 * Check if can replace the native Woodmart search form with the FiboSearch form.
	 *
	 * @return bool
	 */
	private function canReplaceSearch() {
		$canIntegrate = false;

		if ( DGWT_WCAS()->settings->getOption( 'shopkeeper_replace_search', 'on' ) === 'on' ) {
			$canIntegrate = true;
		}

		return $canIntegrate;
	}

	/**
	 * Maybe replace the default search bar
	 *
	 * @return void
	 */
	private function maybeReplaceSearch() {

		if ( $this->canReplaceSearch() ) {

			// Add scripts
			add_action( 'wp_head', array( $this, 'customCSS' ) );
			add_action( 'wp_footer', array( $this, 'customJS' ) );

			// Remove native form
			add_action( 'init', function () {
				remove_action( 'wp_loaded', 'shopkeeper_predictive_search', 100 );
			} );

			// Embed search bar
			add_action( 'getbowtied_product_search', function () {
				echo do_shortcode( '[wcas-search-form layout="classic" mobile_overlay="1" mobile_breakpoint="767"]' );
			} );

			// Change mobile breakpoint from 992 to 767
			add_filter( 'dgwt/wcas/scripts/mobile_breakpoint', function () {
				return 767;
			} );

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
			.site-search.off-canvas {
				min-height: 100px;
			}

			.admin-bar .site-search.off-canvas {
				min-height: 130px;
			}

			.site-search.off-canvas > .row {
				margin-top: 30px;
			}

			.site-search.off-canvas p.search-text {
				position: absolute;
				top: 14px;
				left: 20px;
			}

			.site-search-close {
				position: absolute;
				top: 5px;
				right: 20px;
			}

			.site-search .dgwt-wcas-search-wrapp {
				max-width: 800px;
			}

			.site-search .dgwt-wcas-search-input {
				font-size: 20px;
				border: none;
				border-bottom: 1px solid #ccc;
			}

			@media (max-width: 1400px) {
				.site-search .dgwt-wcas-search-wrapp {
					max-width: 700px;
				}
			}

			@media (max-width: 1250px) {
				.site-search .dgwt-wcas-search-wrapp {
					max-width: 500px;
				}
			}

			@media (max-width: 1000px) {
				.site-search.off-canvas p.search-text {
					display: none;
				}

				.site-search .dgwt-wcas-search-wrapp {
					max-width: calc(100% - 30px);
					margin-left: 0;
				}
			}

			@media (max-width: 768px) {
				/*.site-search.off-canvas {*/
				/*	display: none;*/
				/*}*/
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

				if ($(window).width() > 767) {

					$('.search-button').on('click', function () {

						var $input = $('.site-search .dgwt-wcas-search-input');

						if ($input.length) {
							setTimeout(function () {
								$input.focus();
							}, 300);
						}
					});

				} else {

					$('.search-button').on('click', function () {

						var $mobileHandler = $('.site-search .js-dgwt-wcas-enable-mobile-form');

						if ($mobileHandler.length) {
							$mobileHandler[0].click();

							setTimeout(function () {
								if($('.site-search-close button').length){
									$('.site-search-close button').click();
								}
							}, 300);

						}

					});


				}

			})(jQuery);
		</script>
		<?php
	}

}
