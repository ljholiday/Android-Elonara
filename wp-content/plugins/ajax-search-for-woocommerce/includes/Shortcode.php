<?php

namespace DgoraWcas;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	public static function register() {

		add_shortcode( 'wcas-search-form', array( __CLASS__, 'addBody' ) );
		add_shortcode( 'fibosearch', array( __CLASS__, 'addBody' ) );

	}

	/**
	 * Register Woo Ajax Search shortcode
	 *
	 * @param array $atts bool show_details_box
	 */
	public static function addBody( $atts, $content, $tag ) {
		$layout = Helpers::getLayoutSettings();

		$searchArgs = shortcode_atts( array(
			'class'          => '',
			'layout'         => $layout->layout,
			'mobile_overlay' => $layout->mobile_overlay,
			'details_box'    => 'hide'
		), $atts, $tag );

		$searchArgs['class'] .= empty( $search_args['class'] ) ? 'woocommerce' : ' woocommerce';

		$args = apply_filters( 'dgwt/wcas/shortcode/args', $searchArgs );

		return self::getForm( $args );
	}

	/**
	 * Display search form
	 *
	 * @param array args
	 *
	 * @return string
	 */

	public static function getForm( $args ) {

		// Enqueue required scripts
		wp_enqueue_script( 'jquery-dgwt-wcas' );
		if ( DGWT_WCAS()->settings->getOption( 'show_details_box' ) === 'on' ) {
			wp_enqueue_script( 'woocommerce-general' );
		}

		ob_start();
		$filename = apply_filters( 'dgwt/wcas/form/partial_path', DGWT_WCAS_DIR . 'partials/search-form.php' );
		if ( file_exists( $filename ) ) {
			include $filename;

			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $filename, true );
			}
		}
		$html = ob_get_clean();

		return apply_filters( 'dgwt/wcas/form/html', $html, $args );
	}

}
