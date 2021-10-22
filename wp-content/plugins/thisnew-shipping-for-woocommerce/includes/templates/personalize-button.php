<?php
/**
 * @var string $pfc_button_color
 * @var string $site_url
 * @var string $pfc_button_text
 */
?>
<a class="button"
        style="background-color: <?php esc_attr_e($pfc_button_color); ?>"
        onclick="ThisNew_Product_Customizer.onCustomizeClick( '<?php echo esc_url($site_url); ?>')">
    <?php esc_html_e($pfc_button_text, 'thisnew'); ?>
</a>