<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

if ( ! function_exists( 'avia_append_search_nav' ) ) {
	add_filter( 'wp_nav_menu_items', 'avia_append_search_nav', 9997, 2 );
	add_filter( 'avf_fallback_menu_items', 'avia_append_search_nav', 9997, 2 );

	function avia_append_search_nav( $items, $args ) {
		if ( avia_get_option( 'header_searchicon', 'header_searchicon' ) != "header_searchicon" ) {
			return $items;
		}
		if ( avia_get_option( 'header_position', 'header_top' ) != "header_top" ) {
			return $items;
		}

		if ( ( is_object( $args ) && $args->theme_location == 'avia' ) || ( is_string( $args ) && $args = "fallback_menu" ) ) {
			ob_start();
			echo do_shortcode( '[wcas-search-form layout="icon"]' );
			$search = ob_get_clean();
			$items  .= '<li class="noMobile menu-item menu-item-search-dropdown menu-item-avia-special"><a class="dgwt-wcas-search-enfold-wrapper" href="#">' . $search . '</a></li>';
		}

		return $items;
	}
}

add_action( 'wp_footer', function () {
	?>
	<script>
		(function ($) {
			$(window).on('load', function () {
				$('.dgwt-wcas-search-enfold-wrapper').on('click', function () {
					return false;
				});
			});
		}(jQuery));
	</script>
	<?php
} );

add_action( 'wp_head', function () {
	?>
	<style>
		.dgwt-wcas-search-enfold-wrapper {
			cursor: default;
		}

		.dgwt-wcas-search-wrapp {
			margin: 0;
			position: absolute;
			top: 48%;
			-ms-transform: translateY(-48%);
			transform: translateY(-48%);
		}

		.dgwt-wcas-overlay-mobile .dgwt-wcas-search-wrapp {
			position: relative;
			top: 0;
			-ms-transform: none;
			transform: none;
		}

		.dgwt-wcas-ico-magnifier-handler {
			max-width: 14px;
		}

		.dgwt-wcas-layout-icon-open .dgwt-wcas-search-icon-arrow {
			top: calc(100% + 5px);
		}

		html:not(.dgwt-wcas-overlay-mobile-on) .dgwt-wcas-search-wrapp.dgwt-wcas-layout-icon .dgwt-wcas-search-form {
			top: calc(100% + 11px);
		}

		@media (max-width: 767px) {
			.menu-item-search-dropdown {
				z-index: 100;
				padding-right: 25px;
			}

			.dgwt-wcas-ico-magnifier-handler {
				max-width: 20px;
			}
		}
	</style>
	<?php
} );
