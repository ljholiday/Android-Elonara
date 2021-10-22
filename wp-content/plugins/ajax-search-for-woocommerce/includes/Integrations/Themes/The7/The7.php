<?php

namespace DgoraWcas\Integrations\Themes\The7;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class The7 {

	private $themeSlug = 'the7';

	private $themeName = 'The7';

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
	 * Check if can replace the native Woodmart search form with the FiboSearch form.
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

			add_filter( 'presscore_template_manager_located_template', array( $this, 'changeTemplatePath' ), 10, 2 );

		}
	}

	/**
	 * Change template path
	 *
	 * @param string $templateName
	 * @param array $templateNames
	 */
	public function changeTemplatePath( $templateName, $templateNames ) {

		if ( strpos( $templateName, 'searchform.php' ) !== false ) {
			$templateName = DGWT_WCAS_DIR . 'partials/themes/the7-searchform.php';
		}

		return $templateName;
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
				.mini-widgets .dgwt-wcas-search-icon {
					width: 17px;
					margin-top: -2px;
				}
				.mini-widgets .dgwt-wcas-layout-icon-open .dgwt-wcas-search-icon-arrow {
					top: calc(100% + 5px);
				}
			</style>
			<?php
		} );

	}

}
