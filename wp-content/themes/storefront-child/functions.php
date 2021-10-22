<?php
add_action( 'wp_enqueue_scripts', function() {
  wp_enqueue_style( 'storefront-parent-style', get_template_directory_uri() . '/style.css' );
});

/* Google fonts */
function google_fonts() {
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=EB+Garamond:wght@615&family=Montserrat&family=Open+Sans&display=swap', false );
}
add_action( 'wp_enqueue_scripts', 'google_fonts' );




/**
  * Display the theme credit
  * Normally set by /wp-content/themes/storefront/inc/storefront-template-functions.php on line 125
  * @since  1.0.0
  * @return void
  */

 function storefront_credit() {

	 ?>

	 <div class="site-info">

		 <?php echo esc_html( apply_filters( 'storefront_copyright_text', $content = '&copy; ' . get_bloginfo( 'name' ) . ' ' . date( 'Y' ) ) ); ?>


		 <?php if ( apply_filters( 'storefront_credit_link', true ) ) { 

		 } ?>
	 </div>
	 <?php
 }

/**
  * END Display the theme credit
  */




/**
* @snippet       Remove "Default Sorting" Dropdown @ WooCommerce Shop & Archive Pages
* @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
* @source        https://businessbloomer.com/?p=401
* @author        Rodolfo Melogli
* @compatible    Woo 3.5.2
* @donate $9     https://businessbloomer.com/bloomer-armada/
*/
 
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

add_action( 'init', 'bbloomer_delay_remove' );
 
function bbloomer_delay_remove() {
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
}

/** Rich Snippet Data
 * Add missing data not handled by WooCommerce yet
 * adds the values of custom product attributes brand and sku to the schema meta data sent to the users browser. This is where google gets the "brand" and "mpn" values.
 * this solves the missing fields error in search console.
 * from https://shanerutter.co.uk/fix-for-woocommerce-schema-data-missing-brand-and-mpn/
 */
function custom_woocommerce_structured_data_product ($data) {
        global $product;
//      $data['brand'] = $product->get_attribute('pa_manufacturer') ?? null;
        $data['brand'] = $product->get_attribute('brand') ?? null;
//      $data['mpn'] = $product->get_sku() ?? null;
        $data['mpn'] = $product->get_attribute('mpn') ?? null;
        $data['review'] = $product->get_attribute('review') ?? null;
        return $data;
}
add_filter( 'woocommerce_structured_data_product', 'custom_woocommerce_structured_data_product' );
/* End of the Rich Snippet Data Code.


/**
 * QREUZ SNIPPET FOR WOOCOMMERCE / WORDPRESS
 *
 * @TITLE       Google Customer Reviews Snippet for WooCommerce
 * @VERSION     1.4.2
 * @DESCRIPTION Implements the necessary snippets to WooCommerce for collecting Google Customer Reviews from customers after purchase
 * @FOR         Google Customer Reviews, https://support.google.com/merchants/answer/7124319
 * @LINK        https://qreuz.com/snippets/google-customer-reviews-snippet-for-woocommerce/

 * @AUTHOR      Qreuz GmbH <hello@qreuz.com>

 * @LICENSE     GNU GPL v3 https://www.gnu.org/licenses/gpl-3.0
 */
/**
 * This function will set the language for your GCR opt-in (and your GCR badge if you integrate it).
 * replace the lang code with your store´s language; available languages can be found at https://support.google.com/merchants/answer/7106244
 *
 */
function qreuz_google_customer_reviews_language() {
    $qreuz_customer_reviews_language_script = 'window.___gcfg = {lang: \'en_US\'};';
        wp_register_script( 'qreuz_customer_reviews_language_script', '', '', 'false', 'true' );
        wp_enqueue_script( 'qreuz_customer_reviews_language_script' );
        wp_add_inline_script( 'qreuz_customer_reviews_language_script', $qreuz_customer_reviews_language_script );
}
add_action( 'wp_enqueue_scripts', 'qreuz_google_customer_reviews_language', 20 );
/**
 * Adds the Google Customer Reviews opt-in form to the checkout confirmation page
 * Add your merchant ID where the placeholder is.
 *
 * @param int|string $order_id WooCommerce order id
 *
 */
function qreuz_google_customer_reviews_optin( $order_id ) {
    $order = new WC_Order( $order_id );
    ?>
    <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
    <script>            
              window.renderOptIn = function() {
                window.gapi.load('surveyoptin', function() {
                  window.gapi.surveyoptin.render(
                    {
                      // REQUIRED FIELDS
                      "merchant_id": 246899873, // place your merchant ID here, get it from your Merchant Center at https://merchants.google.com/mc/merchantdashboard
                      "order_id": "<?php echo esc_attr( $order->get_order_number() ); ?>",
                      "email": "<?php echo esc_attr( $order->get_billing_email() ); ?>",
                      "delivery_country": "<?php echo esc_attr( $order->get_billing_country() ); ?>",
                      "estimated_delivery_date": "<?php echo esc_attr( date( 'Y-m-d', strtotime( '+5 day', strtotime( $order->get_date_created() ) ) ) ); ?>",  // replace "5 day" with the estimated delivery time of your orders
                      "opt_in_style": "CENTER_DIALOG"
                    });
                });
              }</script>
    <?php
}
add_action( 'woocommerce_thankyou', 'qreuz_google_customer_reviews_optin' );
/**
 * Adds the GCR rating badge to your storefront.
 * Add your merchant ID and set the positioning of the rating badge as you like.
 */
function qreuz_google_customer_reviews_badge() {
    $qreuz_google_customer_reviews_script = '<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>';
    echo $qreuz_google_customer_reviews_script;
    $qreuz_google_customer_reviews_badge_script = '
              window.renderBadge = function() {
                var ratingBadgeContainer = document.createElement("div");
                document.body.appendChild(ratingBadgeContainer);
                window.gapi.load(\'ratingbadge\', function() {
                  window.gapi.ratingbadge.render(ratingBadgeContainer, {
                    // REQUIRED
                     "merchant_id": 246899873, // place your merchant ID here, get it from your Merchant Center at https://merchants.google.com/mc/merchantdashboard
                    // OPTIONAL
                    "position": "BOTTOM_RIGHT" // find out more about positioning at https://support.google.com/merchants/answer/7105655
                    });
                });
              }';
    wp_register_script( 'qreuz_google_customer_reviews_badge_script', '', '', 'false', 'true' );
    wp_enqueue_script( 'qreuz_google_customer_reviews_badge_script' );
    wp_add_inline_script( 'qreuz_google_customer_reviews_badge_script', $qreuz_google_customer_reviews_badge_script );
}
add_action( 'wp_enqueue_scripts', 'qreuz_google_customer_reviews_badge', 20 );
/* end of the customer review thing. I'm not sure this isn't broken. */
/* end of the customer review thing. I'm not sure this isn't broken. */
?>







<?php
/* Maybe remove this? Changes the destination of the return to shop button on the cart page. It was pointed to shop. We don't use the shop page. */
function store_mall_wc_empty_cart_redirect_url() {
        $url = 'https://ljholiday.com/ljholiday'; // change this link to your need
        return esc_url( $url );
    }
    add_filter( 'woocommerce_return_to_shop_redirect', 'store_mall_wc_empty_cart_redirect_url' );
?>

<?php
/* Remove Storefront search box */
add_action( 'init', 'nl_remove_storefront_header_search' );
function nl_remove_storefront_header_search() {
  remove_action( 'storefront_header', 'storefront_product_search', 40 ); 
}
?>