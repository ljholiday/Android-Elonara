<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ThisNew_Template {

    /**
     * Initialize the values, hooks and actions
     */
    public static function init()
    {
        $template = new self;

        $template->hook_templates();
    }

    /**
     * Hook custom modifications in template files
     */
    public function hook_templates()
    {
        // hook templates, 29 indicates position, right before variation selection
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'thisnew_template_customize_button' ), 20 );
        // add a hidden input field
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'thisnew_customizer_hash_field' ), 11 );
    }

    /**
     * Hook callback for personalization button within product page
     */
    public static function thisnew_template_customize_button()
    {
        global $post;

        if ( $post && get_post_meta( $post->ID, 'pf_customizable', true ) ) {
            // load template for personalization button
            ThisNew_Admin::load_template( 'personalize-button', array(
                'site_url' => get_site_url(),
	            'pfc_button_color' => ThisNew_Integration::instance()->get_option( 'pfc_button_color' ) ?: ThisNew_Admin_Settings::DEFAULT_PERSONALIZE_BUTTON_COLOR,
	            'pfc_button_text' => ThisNew_Integration::instance()->get_option( 'pfc_button_text' ) ?: ThisNew_Admin_Settings::DEFAULT_PERSONALIZE_BUTTON_TEXT,
	            'pfc_modal_title' => ThisNew_Integration::instance()->get_option( 'pfc_modal_title' ) ?: ThisNew_Admin_Settings::DEFAULT_PERSONALIZE_MODAL_TITLE,
            ) );
        }
    }

    /**
     * Add hidden customizer hash ID field to form
     */
    public static function thisnew_customizer_hash_field()
    {
        ThisNew_Admin::load_template( 'customizer-hidden-input' );
    }
}