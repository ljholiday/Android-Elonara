<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class VI_WOO_ALIDROPSHIP_Frontend_Product
 */
class VI_WOO_ALIDROPSHIP_Frontend_Product {
	private $settings;

	public function __construct() {
		$this->settings = VI_WOO_ALIDROPSHIP_DATA::get_instance();
		add_action( 'woocommerce_before_variations_form', array( $this, 'ignore_ship_from' ) );
	}

	public function ignore_ship_from() {
		if ( $this->settings->get_params( 'ignore_ship_from' ) ) {
			?>
            <script type="text/javascript">
                'use strict';
                jQuery(document).ready(function ($) {
                    let $options = $('#ships-from option');
                    for (let $option of $options) {
                        let optObj = $($option);
                        optObj.val().toLowerCase() === 'china' ? optObj.prop('selected', true) : optObj.remove();
                    }
                });
            </script>
			<?php
		}
	}
}
