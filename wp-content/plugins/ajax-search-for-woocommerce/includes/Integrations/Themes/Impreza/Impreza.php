<?php

namespace DgoraWcas\Integrations\Themes\Impreza;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Impreza {

	private $themeSlug = 'impreza';

	private $themeName = 'Impreza';

	public function __construct() {
		$this->replaceForm();

		add_filter( 'dgwt/wcas/settings', array( $this, 'registerSettings' ) );
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

		$articleLink = 'https://fibosearch.com/documentation/themes-integrations/impreza-theme/';
		$articleText = sprintf(__( 'Here is <a href="%s" target="_blank">article</a> about how to do it using Impreza child-theme.', 'ajax-search-for-woocommerce' ), $articleLink);

		$settings[ $key ][10] = array(
			'name'  => $this->themeSlug . '_main_head',
			'label' => __( 'Replace Impreza search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		$settings[ $key ][52] = array(
			'name'  => $this->themeSlug . '_settings_head',
			'label' => __( 'Impreza Theme', 'ajax-search-for-woocommerce' ),
			'type'  => 'desc',
			'desc'  => Helpers::embeddingInThemeHtml() . '<br />' . $articleText,
			'class' => 'dgwt-wcas-sgs-themes-label',
		);

		$img = DGWT_WCAS()->themeCompatibility->getThemeImageSrc();
		if ( ! empty( $img ) ) {
			$settings[ $key ][52]['label'] = '<img src="' . $img . '">';
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
	 * Mark the native search form to replace
	 *
	 * @return void
	 */
	private function replaceForm() {

			$this->applyCSS();
			$this->applyJS();

			add_filter( 'body_class', function ( $classes ) {
				$classes[] = 'dgwt-wcas-theme-' . $this->themeSlug;

				return $classes;
			} );

			// Force enable overlay for mobile search
			add_filter( 'dgwt/wcas/settings/load_value/key=enable_mobile_overlay', function () {
				return 'on';
			} );

			// Change mobile breakpoint from 992 to 850
			add_filter( 'dgwt/wcas/scripts/mobile_breakpoint', function () {
				return 899;
			} );

	}


	/**
	 * Apply custom CSS
	 *
	 * @return void
	 */
	private function applyCSS() {

		add_action( 'wp_head', function () {
			?>
			<style>
				.w-search.layout_modern .w-search-close {

				}
				.w-search.layout_modern .w-search-close {
					color: rgba(0,0,0,0.5)!important;
				}
				.w-search.layout_modern .dgwt-wcas-close {
					display: none;
				}
				.w-search.layout_modern .dgwt-wcas-preloader {
					right: 20px;
				}
				.w-search.layout_fullscreen .w-form-row-field {
					top:48px;
				}
			</style>
			<?php
		} );

	}

	/**
	 * Apply custom JS
	 *
	 * @return void
	 */
	private function applyJS() {

		add_action( 'wp_footer', function () {

			?>
			<script>
				(function ($) {

					function dgwtWcasImprezaGetActiveInstance() {
						var $el = $('.dgwt-wcas-search-wrapp.dgwt-wcas-active'),
							instance;
						if ($el.length > 0) {
							$el.each(function () {
								var $input = $(this).find('.dgwt-wcas-search-input');
								if (typeof $input.data('autocomplete') == 'object') {
									instance = $input.data('autocomplete');
									return false;
								}
							});
						}

						return instance;
					}

					$(document).ready(function () {

						$('.w-search.layout_modern .w-search-close').on('click', function(){

							var instance = dgwtWcasImprezaGetActiveInstance();

							if(typeof instance == 'object'){
								instance.suggestions = [];
								instance.hide();
								instance.el.val('');
							}

						});

						$('.w-search-open').on('click', function(e){

							if($(window).width() < 900){
								e.preventDefault();

								var $mobileHandler = $(e.target).closest('.w-search').find('.js-dgwt-wcas-enable-mobile-form');

								if($mobileHandler.length){
									$mobileHandler[0].click();
								}

								setTimeout(function(){
									$('.w-search').removeClass('active');
								},500);
							}

						});

					});




				})(jQuery);

			</script>
			<?php
		}, 1000 );

	}

}
