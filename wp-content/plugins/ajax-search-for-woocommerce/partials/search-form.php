<?php

use DgoraWcas\Helpers;
use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

$layout = Helpers::getLayoutSettings();

$submitText = Helpers::getLabel( 'submit' );
$hasSubmit  = DGWT_WCAS()->settings->getOption( 'show_submit_button' );
$uniqueID   = ++ DGWT_WCAS()->searchInstances;
$layoutType = !empty($args['layout'])  ? $args['layout'] : $layout->layout;

$customParams = apply_filters( 'dgwt/wcas/search_bar/custom_params', array(), DGWT_WCAS()->searchInstances );

?>
<div class="dgwt-wcas-search-wrapp <?php echo Helpers::searchWrappClasses( $args ); ?>">
	<?php if(in_array($layoutType, array('icon', 'icon-flexible'))): ?>
	<div class="dgwt-wcas-search-icon js-dgwt-wcas-search-icon-handler"><?php echo Helpers::getMagnifierIco('dgwt-wcas-ico-magnifier-handler'); ?></div>
	<div class="dgwt-wcas-search-icon-arrow"></div>
	<?php endif; ?>
	<form class="dgwt-wcas-search-form" role="search" action="<?php echo Helpers::searchFormAction(); ?>" method="get">
		<div class="dgwt-wcas-sf-wrapp">
			<?php echo $hasSubmit !== 'on' ? Helpers::getMagnifierIco() : ''; ?>
			<label class="screen-reader-text"
			       for="dgwt-wcas-search-input-<?php echo $uniqueID; ?>"><?php _e( 'Products search',
					'ajax-search-for-woocommerce' ); ?></label>

			<input id="dgwt-wcas-search-input-<?php echo $uniqueID; ?>"
			       type="search"
			       class="dgwt-wcas-search-input"
			       name="<?php echo Helpers::getSearchInputName(); ?>"
			       value="<?php echo apply_filters( 'dgwt/wcas/search_bar/value', get_search_query(), DGWT_WCAS()->searchInstances); ?>"
			       placeholder="<?php echo Helpers::getLabel( 'search_placeholder' ); ?>"
			       autocomplete="off"
				   <?php echo ! empty( $customParams ) ? ' data-custom-params="' . htmlspecialchars(json_encode( (object) $customParams)) . '"' : ''; ?>
			/>
			<div class="dgwt-wcas-preloader"></div>

			<?php if ( $hasSubmit === 'on' ): ?>
				<button type="submit"
				        aria-label="<?php echo empty( $submitText ) ? __( 'Search','ajax-search-for-woocommerce' ) : esc_html( $submitText ); ?>"
				        class="dgwt-wcas-search-submit"><?php echo empty( $submitText ) ? Helpers::getMagnifierIco() : esc_html( $submitText ); ?></button>
			<?php endif; ?>

			<input type="hidden" name="post_type" value="product"/>
			<input type="hidden" name="dgwt_wcas" value="1"/>

			<?php if ( Multilingual::isWPML() ): ?>
				<input type="hidden" name="lang" value="<?php echo Multilingual::getCurrentLanguage(); ?>"/>
			<?php endif ?>

			<?php do_action( 'dgwt/wcas/form' ); ?>
		</div>
	</form>
</div>
