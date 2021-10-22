<?php

namespace DgoraWcas\Integrations\Themes\Restoration;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Restoration {

	private $themeSlug = 'restoration';

	private $themeName = 'Restoration';

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

			add_filter( 'wc_get_template', function ( $template, $templateName ) {

				if ( ! empty( $templateName ) && $templateName === 'product-searchform.php' ) {
					$template = DGWT_WCAS_DIR . 'partials/themes/restoration.php';
				}

				return $template;
			}, 10, 5 );


			add_action( 'init', function () {
				add_action( 'wp_head', array( $this, 'customCSS' ) );
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
			.thb-header-inline-search-inner .dgwt-wcas-sf-wrapp input[type=search].dgwt-wcas-search-input {
				border: none;
				background: transparent;
				color: #fff;
				text-align: center;
				padding-right: 40px;
			}
			.thb-header-inline-search-inner .dgwt-wcas-sf-wrapp input[type=search].dgwt-wcas-search-input:focus {
				box-shadow: none;
			}
			.thb-header-inline-search-inner .dgwt-wcas-sf-wrapp .dgwt-wcas-ico-magnifier {
				display: none;
			}
			.thb-header-inline-search-inner .dgwt-wcas-sf-wrapp input[type="search"].dgwt-wcas-search-input::placeholder {
				opacity: 0.8!important;
				color: #fff;
			}
		</style>
		<?php
	}

}
