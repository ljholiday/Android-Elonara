<?php

namespace DgoraWcas;

use  DgoraWcas\Integrations\Solver ;
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Scripts
{
    public function __construct()
    {
        add_action( 'wp_enqueue_scripts', array( $this, 'loadScripts' ) );
    }
    
    /**
     * Loads scripts and styles
     * Uses a WP hook wp_enqueue_scripts
     *
     * @return void
     */
    public function loadScripts()
    {
        $min = ( SCRIPT_DEBUG ? '' : '.min' );
        //Register
        wp_register_style(
            'dgwt-wcas-style',
            apply_filters( 'dgwt/wcas/scripts/css_style_url', DGWT_WCAS_URL . 'assets/css/style' . $min . '.css' ),
            array(),
            DGWT_WCAS_VERSION
        );
        wp_register_script(
            'jquery-dgwt-wcas',
            apply_filters( 'dgwt/wcas/scripts/js_url', DGWT_WCAS_URL . 'assets/js/search' . $min . '.js' ),
            array( 'jquery' ),
            DGWT_WCAS_VERSION,
            true
        );
        // Enqueue
        wp_enqueue_style( 'dgwt-wcas-style' );
        $layout = Helpers::getLayoutSettings();
        // Localize
        $localize = array(
            'labels'                       => Helpers::getLabels(),
            'ajax_search_endpoint'         => \WC_AJAX::get_endpoint( DGWT_WCAS_SEARCH_ACTION ),
            'ajax_details_endpoint'        => \WC_AJAX::get_endpoint( DGWT_WCAS_RESULT_DETAILS_ACTION ),
            'ajax_prices_endpoint'         => \WC_AJAX::get_endpoint( DGWT_WCAS_GET_PRICES_ACTION ),
            'action_search'                => DGWT_WCAS_SEARCH_ACTION,
            'action_result_details'        => DGWT_WCAS_RESULT_DETAILS_ACTION,
            'action_get_prices'            => DGWT_WCAS_GET_PRICES_ACTION,
            'min_chars'                    => 3,
            'width'                        => 'auto',
            'show_details_box'             => false,
            'show_images'                  => false,
            'show_price'                   => false,
            'show_desc'                    => false,
            'show_sale_badge'              => false,
            'show_featured_badge'          => false,
            'dynamic_prices'               => false,
            'is_rtl'                       => ( is_rtl() == true ? true : false ),
            'show_preloader'               => false,
            'show_headings'                => false,
            'preloader_url'                => '',
            'taxonomy_brands'              => '',
            'img_url'                      => DGWT_WCAS_URL . 'assets/img/',
            'is_premium'                   => ( dgoraAsfwFs()->is_premium() ? true : false ),
            'mobile_breakpoint'            => $layout->breakpoint,
            'mobile_overlay_wrapper'       => $layout->mobile_overlay_wrapper,
            'debounce_wait_ms'             => apply_filters( 'dgwt/wcas/scripts/debounce_wait_ms', 400 ),
            'send_ga_events'               => apply_filters( 'dgwt/wcas/scripts/send_ga_events', true ),
            'enable_ga_site_search_module' => apply_filters( 'dgwt/wcas/scripts/enable_ga_site_search_module', false ),
            'magnifier_icon'               => Helpers::getMagnifierIco( '' ),
            'close_icon'                   => Helpers::getIcon( 'close' ),
            'back_icon'                    => Helpers::getIcon( 'arrow-left' ),
            'preloader_icon'               => Helpers::getIcon( 'preloader' ),
            'custom_params'                => (object) apply_filters( 'dgwt/wcas/scripts/custom_params', array() ),
            'convert_html'                 => true,
            'suggestions_wrapper'          => apply_filters( 'dgwt/wcas/scripts/suggestions_wrapper', 'body' ),
            'show_product_vendor'          => dgoraAsfwFs()->is_premium() && class_exists( 'DgoraWcas\\Integrations\\Marketplace\\Marketplace' ) && DGWT_WCAS()->marketplace->showProductVendor(),
            'disable_hits'                 => apply_filters( 'dgwt/wcas/scripts/disable_hits', false ),
            'disable_submit'               => apply_filters( 'dgwt/wcas/scripts/disable_submit', false ),
        );
        if ( Multilingual::isMultilingual() ) {
            $localize['current_lang'] = Multilingual::getCurrentLanguage();
        }
        // Min characters
        $min_chars = DGWT_WCAS()->settings->getOption( 'min_chars' );
        if ( !empty($min_chars) && is_numeric( $min_chars ) ) {
            $localize['min_chars'] = absint( $min_chars );
        }
        $sug_width = DGWT_WCAS()->settings->getOption( 'sug_width' );
        if ( !empty($sug_width) && is_numeric( $sug_width ) && $sug_width > 100 ) {
            $localize['sug_width'] = absint( $sug_width );
        }
        // Show/hide Details panel
        if ( DGWT_WCAS()->settings->getOption( 'show_details_box' ) === 'on' ) {
            $localize['show_details_box'] = true;
        }
        // Show/hide images
        if ( DGWT_WCAS()->settings->getOption( 'show_product_image' ) === 'on' ) {
            $localize['show_images'] = true;
        }
        // Show/hide price
        if ( DGWT_WCAS()->settings->getOption( 'show_product_price' ) === 'on' ) {
            $localize['show_price'] = true;
        }
        // Show/hide description
        if ( DGWT_WCAS()->settings->getOption( 'show_product_desc' ) === 'on' ) {
            $localize['show_desc'] = true;
        }
        // Show/hide description
        if ( DGWT_WCAS()->settings->getOption( 'show_product_sku' ) === 'on' ) {
            $localize['show_sku'] = true;
        }
        // Show/hide sale badge
        if ( DGWT_WCAS()->settings->getOption( 'show_sale_badge' ) === 'on' ) {
            $localize['show_sale_badge'] = true;
        }
        // Show/hide featured badge
        if ( DGWT_WCAS()->settings->getOption( 'show_featured_badge' ) === 'on' ) {
            $localize['show_featured_badge'] = true;
        }
        // Set preloader
        
        if ( DGWT_WCAS()->settings->getOption( 'show_preloader' ) === 'on' ) {
            $localize['show_preloader'] = true;
            $localize['preloader_url'] = esc_url( trim( DGWT_WCAS()->settings->getOption( 'preloader_url' ) ) );
        }
        
        // Show/hide autocomplete headings
        if ( DGWT_WCAS()->settings->getOption( 'show_grouped_results' ) === 'on' ) {
            $localize['show_headings'] = true;
        }
        $localize = apply_filters( 'dgwt/wcas/scripts/localize', $localize );
        wp_localize_script( 'jquery-dgwt-wcas', 'dgwt_wcas', $localize );
    }

}