<?php

namespace DgoraWcas\Integrations\Themes\Sober;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sober {

	private $themeSlug = 'sober';

	private $themeName = 'Sober';

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

			add_filter( 'dgwt/wcas/form/magnifier_ico', function ( $html, $class ) {
				if ( $class === 'dgwt-wcas-ico-magnifier-handler' ) {
					$html = '<svg class="dgwt-wcas-ico-magnifier-handler" viewBox="0 0 20 20" id="search"><circle fill="none" stroke-width="2" stroke-miterlimit="10" cx="8.35" cy="8.35" r="6.5"></circle><path fill="none" stroke-width="2" stroke-miterlimit="10" d="M12.945 12.945l5.205 5.205"></path></svg>';
				}
				return $html;
			}, 10, 2 );

			require_once DGWT_WCAS_DIR . 'partials/themes/sober.php';
		}
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
				#mobile-menu .dgwt-wcas-search-wrapp {
					margin-bottom: 15px;
				}
				.menu-item-search .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon .dgwt-wcas-ico-magnifier-handler {
					max-width: 18px;
				}
			</style>
			<?php
		} );
	}
}
