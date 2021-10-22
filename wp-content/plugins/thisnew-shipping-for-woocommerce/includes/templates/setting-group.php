<?php
/**
 * @var string $title
 * @var string $description
 * @var string $carrier_version
 * @var array $settings
 */
?>
<div class="thisnew-setting-group">

    <h2><?php echo esc_html($title); ?></h2>
    <p><?php _e( $description, 'thisnew' ); ?></p>

    <?php if ( !empty( $settings ) ) : ?>
        <table class="form-table">
            <tbody>
            <?php foreach($settings as $key => $setting) : ?>
                <?php if ( $setting['title'] == 'ThisNew store API key') : ?>
                    <tr valign="top">

                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($setting['title']); ?></label>
                        </th>

                        <td class="forminp">
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php echo esc_html($setting['title']); ?></span></legend>
                                <input class="input-text regular-input" type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_html($setting['value'] ?: $setting['default']); ?>" placeholder="">
                            </fieldset>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>
<script>
    jQuery(document).ready(function($){
        $('.pfc_button_color').wpColorPicker();
    });
</script>