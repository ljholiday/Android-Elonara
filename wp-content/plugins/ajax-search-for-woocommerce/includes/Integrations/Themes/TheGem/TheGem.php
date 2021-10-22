<?php

namespace DgoraWcas\Integrations\Themes\TheGem;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TheGem {

	private $themeSlug = 'thegem';

	private $themeName = 'TheGem';

	public function __construct() {
		add_filter( 'dgwt/wcas/settings', array( $this, 'registerSettings' ) );

		if ( $this->canReplaceSearch() ) {
			add_action( 'init', function () {

				// Header Vertical
				remove_filter( 'wp_nav_menu_items', 'thegem_menu_item_search', 10, 2 );
				add_filter( 'wp_nav_menu_items', array( $this, 'replaceSearchInMenu' ), 10, 2 );

				// Header Fullwidth hamburger
				remove_filter( 'wp_nav_menu_items', 'thegem_menu_item_hamburger_widget', 100, 2 );
				add_action( 'thegem_before_nav_menu', function () {
					if ( in_array( thegem_get_option( 'header_layout' ), array( 'perspective', 'fullwidth_hamburger' ) ) ) {
						echo do_shortcode( '[wcas-search-form]' );
					}
				} );

				// Perspective header
				remove_filter( 'get_search_form', 'thegem_serch_form_vertical_header' );
				add_action( 'thegem_perspective_menu_buttons', function () {
					echo do_shortcode( '[wcas-search-form]' );
				} );

			} );

			add_filter( 'get_search_form', array( $this, 'removeSearchBarFromVerticalHeader' ), 100 );
			add_action( 'thegem_before_header', array( $this, 'addSearchBarToVerticalHeader' ), 20 );

			// Force enable overlay for mobile search
			add_filter( 'dgwt/wcas/settings/load_value/key=enable_mobile_overlay', function () {
				return 'on';
			} );

			add_action( 'wp_head', array( $this, 'customCSS' ) );
			add_action( 'wp_footer', array( $this, 'customJS' ) );
		}


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
			'label' => __( 'Replace a search bar in TheGem ', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		$settings[ $key ][52] = array(
			'name'  => $this->themeSlug . '_settings_head',
			'label' => __( 'TheGem Theme', 'ajax-search-for-woocommerce' ),
			'type'  => 'desc',
			'desc'  => Helpers::embeddingInThemeHtml(),
			'class' => 'dgwt-wcas-sgs-themes-label',
		);

		$img = DGWT_WCAS()->themeCompatibility->getThemeImageSrc();
		if ( ! empty( $img ) ) {
			$settings[ $key ][52]['label'] = '<img src="' . $img . '">';
		}

		$settings[ $key ][55] = array(
			'name'    => 'thegem_replace_search',
			'label'   => __( 'Replace', 'ajax-search-for-woocommerce' ),
			'desc'    => __( 'Replace the TheGem default search', 'ajax-search-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'off',
		);

		$settings[ $key ][90] = array(
			'name'  => $this->themeSlug . '_othersways__head',
			'label' => __( 'Alternative ways to embed a search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);


		$settings[ $key ][90] = array(
			'name'  => $this->themeSlug . '_othersways__head',
			'label' => __( 'Alternative ways to embed a search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		// Remove overlay search from settings because enable mobile overlay will be forced
		if ( $this->canReplaceSearch() ) {
			unset( $settings['dgwt_wcas_form_body'][1300] );
			unset( $settings['dgwt_wcas_form_body'][1400] );
		}

		return $settings;
	}

	/**
	 * Check if can replace the native TheGem search form with the FiboSearch form.
	 *
	 * @return bool
	 */
	private function canReplaceSearch() {
		$canIntegrate = false;

		if ( DGWT_WCAS()->settings->getOption( 'thegem_replace_search', 'off' ) === 'on' ) {
			$canIntegrate = true;
		}

		return $canIntegrate;
	}

	/**
	 * Replace the search in main menu
	 *
	 * @param $items
	 * @param $args
	 *
	 * @return string
	 */
	public function replaceSearchInMenu( $items, $args ) {

		if ( $args->theme_location == 'primary' && ! thegem_get_option( 'hide_search_icon' ) ) {
			$items .= '<li class="menu-item menu-item-search dgwt-wcas-thegem-menu-search">';
			$items .= '<a href="#"></a>';
			$items .= '<div class="minisearch">';
			$items .= do_shortcode( '[wcas-search-form]' );
			$items .= '</div>';
			$items .= '</li>';

		}

		return $items;
	}

	/**
	 * Remove the search bar from vertical header
	 *
	 * @param string $form
	 *
	 * @return string
	 */
	public function removeSearchBarFromVerticalHeader( $form ) {

		if ( in_array( thegem_get_option( 'header_layout' ), array( 'fullwidth_hamburger', 'vertical' ) ) ) {
			$form = '';
		}


		return $form;
	}

	/**
	 * Remove the search bar from vertical header
	 *
	 * @return void
	 */
	public function addSearchBarToVerticalHeader() {

		if ( ! in_array( thegem_get_option( 'header_layout' ), array( 'vertical' ) ) ) {
			return;
		}

		$html = '<div class="dgwt-wcas-thegem-vertical-search">';
		$html .= do_shortcode( '[wcas-search-form]' );
		$html .= '</div>';

		echo $html;
	}


	/**
	 * Custom CSS
	 *
	 * @return void
	 */
	public function customCSS() {
		?>
		<style>
			.dgwt-wcas-thegem-menu-search .minisearch {
				width: 500px;
			}

			.header-layout-perspective > .dgwt-wcas-search-wrapp {
				top: 30px;
				position: absolute;
				max-width: 600px;
				left: 270px;
				right: auto;
				margin: 0 auto;
				z-index: 10;
			}

			@media (max-width: 979px) {
				.dgwt-wcas-thegem-menu-search .minisearch {
					width: 100%;
				}

				.header-layout-fullwidth_hamburger #primary-navigation > .dgwt-wcas-search-wrapp,
				.header-layout-perspective > .dgwt-wcas-search-wrapp {
					max-width: 350px;
				}

				.header-style-vertical #site-header-wrapper .dgwt-wcas-thegem-vertical-search {
					display: none;
				}
			}

			@media (max-width: 769px) {
				.header-layout-fullwidth_hamburger #primary-navigation > .dgwt-wcas-search-wrapp,
				.header-layout-perspective > .dgwt-wcas-search-wrapp {
					display: none;
				}
			}


			#page.vertical-header .dgwt-wcas-thegem-vertical-search {
				margin-right: auto;
				margin-left: auto;
				padding-left: 21px;
				padding-right: 21px;
			}

			.header-layout-fullwidth_hamburger #primary-navigation > .dgwt-wcas-search-wrapp {
				top: 30px;
				position: absolute;
				left: 50px;
				max-width: 600px;
			}

			.site-header.fixed .header-layout-fullwidth_hamburger #primary-navigation > .dgwt-wcas-search-wrapp,
			.site-header.fixed .header-layout-perspective > .dgwt-wcas-search-wrapp {
				top: 8px;
			}

			body .header-layout-overlay #primary-menu.no-responsive.overlay-search-form-show.animated-minisearch > li.menu-item-search > .minisearch {
				top: 0;
				bottom: auto;
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

				$('.header-layout-overlay .dgwt-wcas-thegem-menu-search').on('click', function () {
					var $searchHandler = $(this).find('.js-dgwt-wcas-enable-mobile-form');

					if ($searchHandler.length) {
						$searchHandler[0].click();
					}


				});

				$('.dgwt-wcas-thegem-menu-search').on('click', function () {
					var $input = $(this).find('.dgwt-wcas-search-input');

					if ($input.length) {
						setTimeout(function () {
							$input.focus();
						}, 300);
					}
				});

			})(jQuery);
		</script>
		<?php
	}

}
