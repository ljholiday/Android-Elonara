<?php

namespace DgoraWcas\Integrations\Themes\Savoy;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Savoy {

	private $themeSlug = 'savoy';

	private $themeName = 'Savoy';

	public function __construct() {
		$this->maybeOverwriteSearch();

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
			$this->applyCSS();

			add_filter( 'wc_get_template', array( $this, 'getTemplate' ), 10, 5 );
			add_filter( 'nm_header_default_links', array( $this, 'headerLinks' ) );

			add_action( 'wp_footer', array( $this, 'overwriteMobileSearch' ), 100 );
		}
	}

	/**
	 * Overwrite search template
	 *
	 * @return string
	 */
	public function getTemplate( $template, $template_name, $args, $template_path, $default_path ) {
		if ( $template_name === 'product-searchform_nm.php' ) {
			$template = DGWT_WCAS_DIR . 'partials/themes/savoy/product-searchform_nm.php';
		}

		return $template;
	}

	/**
	 * Replace search icon in header
	 *
	 * @return array
	 */
	public function headerLinks( $links ) {
		if ( isset( $links['search'] ) ) {
			$links['search'] = '<li class="nm-menu-search menu-item">' . do_shortcode( '[wcas-search-form layout="icon"]' ) . '</li>';
		}

		return $links;
	}

	/**
	 * Overwrite search bar in mobile menu
	 *
	 * @return void
	 */
	public function overwriteMobileSearch() {
		global $nm_globals;
		if ( isset( $nm_globals['shop_search_header'] ) && $nm_globals['shop_search_header'] ) {
			echo '<div id="wcas-savoy-mobile-search" style="display: none;">' . do_shortcode( '[wcas-search-form]' ) . '</div>';
			?>
			<script>
				(function ($) {
					$(window).on('load', function () {
						$('.nm-mobile-menu-item-search').replaceWith($('#wcas-savoy-mobile-search > div'));
					});
				}(jQuery));
			</script>
			<?php
		}
	}

	/**
	 * Apply custom CSS
	 *
	 * @return void
	 */
	private function applyCSS() {
		add_action( 'wp_head', function () {
			global $nm_theme_options;
			?>
			<style>
				.nm-shop-search-input-wrap .dgwt-wcas-search-wrapp {
					max-width: 100%;
				}

				.nm-menu-search .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon {
					padding: 16px 12px 16px 0;
					margin-left: 12px;
				}

				.nm-menu-search .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon .dgwt-wcas-ico-magnifier-handler {
					max-width: 16px;
				}

				<?php if (isset($nm_theme_options['header_navigation_highlight_color'])) { ?>
				.nm-menu-search .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon .dgwt-wcas-ico-magnifier-handler {
					fill: <?php echo esc_attr( $nm_theme_options['header_navigation_color'] ); ?>
				}

				<?php }
				if (isset($nm_theme_options['header_navigation_highlight_color'])) { ?>
				.nm-menu-search .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon .dgwt-wcas-ico-magnifier-handler:hover {
					fill: <?php echo esc_attr( $nm_theme_options['header_navigation_highlight_color'] ); ?>
				}

				<?php } ?>
			</style>
			<?php
		} );
	}
}
