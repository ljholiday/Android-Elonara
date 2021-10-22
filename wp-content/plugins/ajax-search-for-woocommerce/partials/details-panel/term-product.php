<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

$rewCount = $product->getReviewCount();
?>

<a class="dgwt-wcas-tax-product-details" href="<?php echo esc_url( $product->getPermalink() ); ?>" title="<?php echo esc_attr( $product->getName() ); ?>">

	<div class="dgwt-wcas-tpd-image">
		<?php echo $product->getThumbnail(); ?>
	</div>

	<div class="dgwt-wcas-tpd-rest">

		<span class="dgwt-wcas-tpd-rest-title"><?php echo esc_attr( $product->getName() ); ?></span>

		<?php if ( $rewCount > 0 ): ?>

			<div class="dgwt-wcas-pd-rating">
				<?php echo $product->getRatingHtml() . ' <span class="dgwt-wcas-pd-review">(' . $rewCount . ')</span>'; ?>
			</div>

		<?php endif; ?>

		<div class="dgwt-wcas-tpd-price">
			<?php echo $product->getPriceHTML(); ?>
		</div>

	</div>
</a>
