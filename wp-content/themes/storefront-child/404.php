<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package storefront
 */

get_header(); ?>

	<div id="primary" class="content-area">

		<main id="main" class="site-main" role="main">

			<div class="error-404 not-found">

				<div class="page-content">

					<header class="page-header">
						<h1 class="page-title"><?php esc_html_e( 'Hmmm... now where did I put that? I have no idea. Wanna take a shot at searching for it yourself.', 'storefront' ); ?></h1>
					</header><!-- .page-header -->

					<p><?php _e( 'I may have moved it, given it another name, or replaced it with something better. See if you can find it or jump to our <a href="https://ljholiday.com" title="homepage">storefront.</a>', 'storefront' ); ?></p>

					<?php
					echo '<section aria-label="' . esc_html__( 'Search', 'storefront' ) . '">';

					if ( storefront_is_woocommerce_activated() ) {
						the_widget( 'WC_Widget_Product_Search' );
					} else {
						get_search_form();
					}

					echo '</section>';

					if ( storefront_is_woocommerce_activated() ) {


						echo '<section aria-label="' . esc_html__( 'Popular Products', 'storefront' ) . '">';

							echo '<h2>' . esc_html__( 'Popular Products', 'storefront' ) . '</h2>';

							$shortcode_content = storefront_do_shortcode(
								'best_selling_products', array(
									'per_page' => 4,
									'columns'  => 4,
								)
							);

							echo $shortcode_content; // WPCS: XSS ok.

						echo '</section>';
					}
					?>

				</div><!-- .page-content -->
			</div><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
