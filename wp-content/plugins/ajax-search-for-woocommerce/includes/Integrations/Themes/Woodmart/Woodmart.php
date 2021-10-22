<?php

namespace DgoraWcas\Integrations\Themes\Woodmart;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woodmart {

	private $themeSlug = 'woodmart';

	private $themeName = 'Woodmart';

	public function __construct() {
		$this->overwriteFunctions();

		add_filter( 'dgwt/wcas/settings', array( $this, 'registerSettings' ) );
		add_filter( 'woodmart_shop_page_link', array( $this, 'shop_page_link' ), 10, 3 );
	}

	/**
	 * Add to the address of the shop a parameter informing that the search is done by our plugin
	 *
	 * @param string $link
	 * @param boolean $keep_query
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public function shop_page_link( $link, $keep_query, $taxonomy ) {
		if ( $keep_query && isset( $_GET['dgwt_wcas'] ) ) {
			$link = add_query_arg( 'dgwt_wcas', wc_clean( $_GET['dgwt_wcas'] ), $link );
		}

		return $link;
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
			'name'    => 'woodmart_replace_search',
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
	 * Check if can replace the native Woodmart search form with the FiboSearch form.
	 *
	 * @return bool
	 */
	private function canReplaceSearch() {
		$canIntegrate = false;

		if ( DGWT_WCAS()->settings->getOption( 'woodmart_replace_search', 'off' ) === 'on' ) {
			$canIntegrate = true;
		}

		return $canIntegrate;
	}

	/**
	 * Overwrite functions
	 *
	 * @return void
	 */
	private function overwriteFunctions() {
		if ( $this->canReplaceSearch() ) {
			require_once DGWT_WCAS_DIR . 'partials/themes/woodmart.php';
		}
	}
}
