<div id="loader-block-<?php echo esc_attr($action); ?>">
	<div class="block-loader loader-wrap">
		<img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ) ?>" class="loader" width="20px" height="20px" alt="loader"/>
		<span class="message"><?php echo esc_html($message); ?></span>
	</div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        Printful_Block_Loader.load('<?php echo esc_url(admin_url( 'admin-ajax.php?action=' . $action )); ?>', 'loader-block-<?php echo esc_attr($action); ?>');
    });
</script>