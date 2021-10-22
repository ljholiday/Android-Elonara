<?php
/**
 * @var string $title
 * @var string $description
 * @var string $carrier_version
 * @var array $settings
 */
?>
<div class="printful-setting-group">

    <h2><?php echo esc_html($title); ?></h2>
    <p><?php _e( $description, 'printful' ); ?></p>

	<?php if ( !empty( $settings ) ) : ?>

        <table class="form-table">
            <tbody>
                <?php foreach($settings as $key => $setting) : ?>

                <?php
                    if($setting['type'] == 'title') {
                        continue;
                    }
                ?>

                <tr valign="top">

                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($setting['title']); ?></label>
                    </th>

                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo esc_html($setting['title']); ?></span></legend>

                            <?php if ( $setting['type'] == 'text' ) : ?>

                                <input class="input-text regular-input" type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_html($setting['value'] ?: $setting['default']); ?>" placeholder="">

                            <?php elseif ($setting['type'] == 'checkbox') : ?>

                                <label for="<?php echo esc_attr($key); ?>">
                                <input class="" type="checkbox" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="1" <?php if($setting['value'] == 'yes') { echo 'checked="checked"'; } ?>>
                                    <?php _e( $setting['label'] , 'printful' ); ?>
                                </label>
                                <br>

                            <?php elseif ($setting['type'] == 'checkbox-group') : ?>

                                <?php foreach ( $setting['items'] as $checkbox_key => $item ) : ?>

                                    <label class="carrier-type"><?php echo $item['subtitle']; ?></label>
                                    <?php foreach ( $item['carriers'] as $carrier_name => $carrier ): ?>

                                        <label for="<?php echo esc_attr( $key ) . '_' . esc_attr( $checkbox_key ).'_'.esc_attr( $carrier_name ); ?>" class="checkbox-group-item">
                                            <input class="" type="checkbox" name="<?php echo esc_attr( $key ) . '[' . esc_attr( $checkbox_key ) . '][]'; ?>"
                                                   id="<?php echo esc_attr( $key ) . '_' .esc_attr( $checkbox_key ) . '_' . esc_attr( $carrier_name ); ?>"
                                                   value="<?php echo esc_attr( $key ) . '_' . esc_attr( $checkbox_key ) . '_' . esc_attr( $carrier_name ); ?>"
                                                <?php if ( $carrier['value'] == 'yes' ) {
                                                    echo 'checked="checked"';
                                                } ?>
                                                <?php if ( $carrier['isDisabled'] ) {
                                                    echo 'disabled="disabled"';
                                                } ?>
                                            >
                                            <?php echo wp_kses_post( $carrier['label'] ); ?>
                                        </label>
                                        <br>

                                    <?php endforeach; ?>

                                <?php endforeach; ?>

                            <?php elseif ($setting['type'] == 'color-picker') : ?>
                                <label for="<?php esc_attr_e($key); ?>">
                                <input type="text" name="<?php esc_attr_e($key); ?>" class="<?php esc_attr_e($key); ?> pf-color-picker" value="<?php esc_attr_e($setting['value'] ?: $setting['default']); ?>" data-default-color="<?php esc_attr_e($setting['default']); ?>" />

                            <?php elseif ($setting['type'] == 'dropdown') : ?>
                            <label for="<?php esc_attr_e($key); ?>">
                                <select name="<?php esc_attr_e($key); ?>" id="<?php esc_attr_e($key); ?>">
                                    <?php foreach ( $setting['items'] as $selectionKey => $selectionValue ) : ?>
                                        <option <?php echo $setting['selected'] === $selectionKey ? 'selected' : ''; ?>
                                                value="<?php esc_attr_e($selectionKey); ?>">
                                            <?php esc_attr_e($selectionValue); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                        </fieldset>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>

	<?php endif; ?>

</div>
<script>
    jQuery(document).ready(function($){
        $('.pf-color-picker').wpColorPicker();
    });
</script>