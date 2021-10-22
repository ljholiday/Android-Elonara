<?php
/**
 * @var string $size_guide_button_color
 * @var string $size_guide_button_text
 */
?>
<a href="javascript:" style="color: <?php esc_attr_e($size_guide_button_color); ?>"
        onclick="Printful_Product_Size_Guide.onSizeGuideClick()">
    <?php esc_html_e($size_guide_button_text, 'printful'); ?>
</a>