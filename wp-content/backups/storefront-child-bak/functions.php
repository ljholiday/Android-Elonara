<?php
function my_theme_enqueue_styles() {

    $parent_style = 'storefront-style'; // This is 'storefront-style' for the Storefront theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

add_action( 'get_header', 'remove_storefront_sidebar' );
function remove_storefront_sidebar() {
	if ( is_woocommerce() ) {
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}
}


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
		 <?php if ( apply_filters( 'storefront_credit_link', true ) ) { ?>
		 <br /> <?php echo '<a href="https://woocommerce.com" target="_blank" title="' . esc_attr__( 'WooCommerce - The Best eCommerce Platform for WordPress', 'storefront' ) . '" rel="author">' . esc_html__( 'Built with Storefront &amp; WooCommerce', 'storefront' ) . '</a>' ?> by <a href="https://ljholiday.com" title="Your Company Name"><!-- begin moniker -->

      <span style="font-family:times new roman,times,baskerville,georgia,serif; font-size: 1em; display: inline-block;"> = </span><span style="font-family:times new roman,times,baskerville,georgia,serif; font-size: 1.2em; padding:0; display: inline-block;"> LJH </span><span style="font-family:times new roman,times,baskerville,georgia,serif; font-size: 1em; display: inline-block;"> OLIDAY= </span>

<!-- end moniker --></a>
		 <?php } ?>
	 </div><!-- .site-info -->
	 <?php
 }
/**
  * END Display the theme credit
  */

/* from https://nicola.blog/2017/07/28/showing-products-when-no-products-found/ */
add_action( 'woocommerce_no_products_found', 'show_products_on_no_products_found', 20 );
function show_products_on_no_products_found() {
	echo '<h2>' . __( 'You may be interested in...', 'domain' ) . '</h2>';
	echo do_shortcode( '[recent_products per_page="4"]' );
}



/**
* @snippet       Remove "Default Sorting" Dropdown @ WooCommerce Shop & Archive Pages
* @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
* @source        https://businessbloomer.com/?p=401
* @author        Rodolfo Melogli
* @compatible    Woo 3.5.2
* @donate $9     https://businessbloomer.com/bloomer-armada/
*/
 
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

/**
* @snippet       Remove "Default Sorting" Dropdown @ StoreFront Shop & Archive Pages
* @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
* @source        https://businessbloomer.com/?p=401
* @author        Rodolfo Melogli
* @compatible    Woo 3.5.2
* @donate $9     https://businessbloomer.com/bloomer-armada/
*/
 
add_action( 'init', 'bbloomer_delay_remove' );
 
function bbloomer_delay_remove() {
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
}



?>


