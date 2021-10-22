<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Template {

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
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'printful_template_customize_button' ), 20 );
        // add a hidden input field
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'printful_customizer_hash_field' ), 11 );
        // Hook size guide button
        add_action( 'woocommerce_before_single_variation', array( $this, 'printful_size_guide_button' ), 20 );
    }

    /**
     * Hook callback for personalization button within product page
     */
    public static function printful_template_customize_button()
    {
        global $post;

        if ( $post && get_post_meta( $post->ID, 'pf_customizable', true ) ) {
            // load template for personalization button
            Printful_Admin::load_template( 'personalize-button', array(
                'site_url' => get_site_url(),
	            'pfc_button_color' => Printful_Integration::instance()->get_option( 'pfc_button_color' ) ?: Printful_Admin_Settings::DEFAULT_PERSONALIZE_BUTTON_COLOR,
	            'pfc_button_text' => Printful_Integration::instance()->get_option( 'pfc_button_text' ) ?: Printful_Admin_Settings::DEFAULT_PERSONALIZE_BUTTON_TEXT,
	            'pfc_modal_title' => Printful_Integration::instance()->get_option( 'pfc_modal_title' ) ?: Printful_Admin_Settings::DEFAULT_PERSONALIZE_MODAL_TITLE,
            ) );
        }
    }

    /**
     * Hook callback for size guide button render within product page
     */
    public static function printful_size_guide_button()
    {
        global $post;

        $sizeGuideData = Printful_Size_Guide::get_size_guide_for_product($post);
        if (!$sizeGuideData) {
            return;
        }

        // Load size guide button template
        Printful_Admin::load_template( 'size-guide-button', array(
            'size_guide_button_color' => Printful_Integration::instance()->get_option( 'pfsg_button_color' ) ?: Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_BUTTON_COLOR,
            'size_guide_button_text' => Printful_Integration::instance()->get_option( 'pfsg_button_text' ) ?: Printful_Admin_Settings::DEFAULT_SIZE_GUIDE_BUTTON_TEXT,
        ) );
    }

    /**
     * Add hidden customizer hash ID field to form
     */
    public static function printful_customizer_hash_field()
    {
        global $post;

        if ( $post && get_post_meta( $post->ID, 'pf_customizable', true ) ) {
            Printful_Admin::load_template('customizer-hidden-input');
        }
    }
}