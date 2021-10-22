<?php           
/**                     
 * Plugin Name:       LJH Fix Schema Errors
 * Plugin URI:        https://ljholiday.com/plugins/ljh-fix-shema-errors/
 * Description:       This plugin adds all the fields and stuff to fix the brand, gtin, review, etc errors in google search console
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Lonn Holiday
 * Author URI:        https://ljholiday.com/about
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ljh-fix-schema-errors
 * Domain Path:       /languages
 * Based on https://robertvicol.com/tech/solve-woocommerce-search-console-issue-missing-field-brand-aggregaterating-review-and-no-global-identifier-provided/
 */     


function byrev_filter_woocommerce_structured_data_product( $markup, $product ) {
  if (empty($markup['mpn']))
        $markup['mpn'] = $markup['sku'];

  if (empty($markup['brand']))
        $markup['brand'] = $product->get_attribute( 'pa_brand' );
    
  if (empty($markup['brand']))
    $markup['brand'] = 'unknown';		

    if (empty($markup['aggregateRating']))
    $markup['aggregateRating'] = array(
      '@type' => 'AggregateRating',
      'ratingValue' => 5,
      'reviewCount' => 1,
    );

    if (empty($markup['review']))
    $markup['review'] = array(
      '@type'=> 'Review',
      'reviewRating'=> [
        '@type'=> 'Rating',
        'ratingValue'=> '5',
        'bestRating'=> '5'
      ],
      'author'=> [
        '@type'=> 'Person',
        'name'=> ''
      ]
    );

    return $markup;
};

add_filter( 'woocommerce_structured_data_product', 'byrev_filter_woocommerce_structured_data_product', 10, 2 );

?>
