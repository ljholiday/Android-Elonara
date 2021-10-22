<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

if ( ! function_exists( 'storefront_product_search' ) ) {
	function storefront_product_search() {
		if ( storefront_is_woocommerce_activated() ) { ?>
			<div class="site-search">
				<?php echo do_shortcode( '[wcas-search-form]' ); ?>
			</div>
			<?php
		}
	}
}

add_action( 'wp_footer', 'dgwt_wcas_storefront_inverse_orientation', 100 );

function dgwt_wcas_storefront_inverse_orientation() {
	?>
	<script>
		(function ($) {
			$(window).on('load', function () {
				$(document).on('click', '.storefront-handheld-footer-bar .search > a', function (e) {
					var $wrapper = $(this).parent();
					$wrapper.removeClass('active');

					setTimeout(function () {
						$wrapper.find('.js-dgwt-wcas-enable-mobile-form')[0].click();
					}, 200);
					e.preventDefault();
				});
			});
		}(jQuery));
	</script>
	<?php
}
