<?php
/**
 * @var bool $disabled
 */
?>
<p class="printful-submit">
	<input name="save" class="button-primary woocommerce-save-button <?php if($disabled) { echo 'disabled'; } ?>" type="submit" value="<?php esc_attr_e('Save changes', 'printful'); ?>" <?php if($disabled) { echo 'disabled'; } ?>/>
	<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
    <?php wp_referer_field(true); ?>
    <span class="loader-wrap">
        <img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ) ?>" class="loader" width="20px" height="20px" alt="loader"/>
        <span class="pass">
            <span class="dashicons dashicons-yes"></span>
            <?php esc_html_e('Saved successfully', 'printful'); ?>
        </span>
        <span class="fail">
        </span>
    </span>
</p>
<script type="text/javascript">
    jQuery(document).ready(function () {
        Printful_Settings.init_submit();
    });
</script>