<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

add_action( 'wp_footer', 'dgwt_wcas_sober_search', 10 );

function dgwt_wcas_sober_search() {
	echo '<div id="wcas-sober-mobile-search" style="display: none;">' . do_shortcode( '[wcas-search-form]' ) . '</div>';
	echo '<div id="wcas-sober-search" style="display: block;">' . do_shortcode( '[wcas-search-form layout="icon"]' ) . '</div>';
	?>
	<script>
		var soberSearch = document.querySelector('.menu-item-search a');
		if (soberSearch !== null) {
			soberSearch.replaceWith(document.querySelector('#wcas-sober-search > div'));
		}
		(function ($) {
			$(window).on('load', function () {
				var soberSearchMobile = $('#mobile-menu .search-form');
				if (soberSearchMobile.eq(0)) {
					soberSearchMobile.replaceWith($('#wcas-sober-mobile-search > div'));
				}
			});
		}(jQuery));
	</script>
	<?php
}

if ( ! function_exists( 'sober_search_modal' ) ) {
	function sober_search_modal() {
	}
}
