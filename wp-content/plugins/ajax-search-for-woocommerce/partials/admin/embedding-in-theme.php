<?php

use DgoraWcas\Admin\Promo\Upgrade;

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

if ( DGWT_WCAS()->themeCompatibility->isCurrentThemeSupported() ):

	$theme = DGWT_WCAS()->themeCompatibility->getTheme();
	$name = ! empty( $theme->name ) ? $theme->name : '';
	$parentName = ! empty( $theme->parent_theme ) ? $theme->parent_theme : '';

	$parentLabel = ! empty( $parentName ) ? ', ' . sprintf( __( 'child theme of <b>%s</b>', 'ajax-search-for-woocommerce' ), $parentName ) : '';

	?>
	<h2><?php printf( __( 'You use the <b>%s</b> theme%s. Fantastic!', 'ajax-search-for-woocommerce' ), $name, $parentLabel ); ?></h2>
	<p><?php _e( 'We support this theme and you can easily replace all default search bars.', 'ajax-search-for-woocommerce' ); ?></p>
<?php endif; ?>
