<?php

namespace DgoraWcas\Integrations\Themes\Storefront;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Storefront {

	private $themeSlug = 'storefront';

	private $themeName = 'Storefront';

	public function __construct() {
		$this->overwriteFunctions();

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
			'label' => __( 'Replace Storefront search bar', 'ajax-search-for-woocommerce' ),
			'type'  => 'head',
			'class' => 'dgwt-wcas-sgs-header'
		);

		$settings[ $key ][52] = array(
			'name'  => $this->themeSlug . '_settings_head',
			'label' => __( 'Storefront Theme', 'ajax-search-for-woocommerce' ),
			'type'  => 'desc',
			'desc'  => Helpers::embeddingInThemeHtml(),
			'class' => 'dgwt-wcas-sgs-themes-label',
		);

		$img = DGWT_WCAS()->themeCompatibility->getThemeImageSrc();
		if ( ! empty( $img ) ) {
			$settings[ $key ][52]['label'] = '<img src="' . $img . '">';
		}

		$settings[ $key ][55] = array(
			'name'    => 'storefront_replace_search',
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
	 * Check if can replace the native Storefront search form with the FiboSearch form.
	 *
	 * @return bool
	 */
	private function canReplaceSearch() {
		$canIntegrate = false;

		if ( DGWT_WCAS()->settings->getOption( 'storefront_replace_search', 'off' ) === 'on' ) {
			$canIntegrate = true;
		}

		return $canIntegrate;
	}

	/**
	 * Overwrite funtions
	 *
	 * @return void
	 */
	private function overwriteFunctions() {
		if ( $this->canReplaceSearch() ) {

			// Force enable overlay for mobile search
			add_filter( 'dgwt/wcas/settings/load_value/key=enable_mobile_overlay', function () {
				return 'on';
			} );

			require_once DGWT_WCAS_DIR . 'partials/themes/storefront.php';
		}
	}


}
