<div class="thisnew-connect">

    <div class="thisnew-connect-inner">


        <img src=" <?php echo esc_url(ThisNew_Base::get_asset_url() . 'images/connect.png'); ?>" class="connect-image" alt="connect to thisnew">

        <?php
        if ( ! empty( $issues ) ) {
            ?>
            <p><?php esc_html_e('To connect your store to ThisNew, fix the following errors:', 'thisnew'); ?></p>
            <div class="thisnew-notice">
                <ul>
                    <?php
                    foreach ( $issues as $issue ) {
                        echo '<li>' . wp_kses_post( $issue ) . '</li>';
                    }
                    ?>
                </ul>
            </div>
            <?php
            $url = '#';
        } else {
            ?><p class="connect-description"><?php esc_html_e('You\'re almost done! Just 2 more steps to have your WooCommerce store connected to ThisNew for automatic order fulfillment.', 'thisnew'); ?></p><?php
            $url = ThisNew_Base::get_thisnew_host() . '/index.php?route=account/login&website=' . urlencode( trailingslashit( get_home_url() ) ) . '&key=' . urlencode( $consumer_key ) . '&returnUrl=' . urlencode( get_admin_url( null,'admin.php?page=' . ThisNew_Admin::MENU_SLUG_DASHBOARD ) );
        }

        echo '<a href="' . esc_url($url) . '" class="button button-primary thisnew-connect-button ' . ( ! empty( $issues ) ? 'disabled' : '' ) . '" target="_blank">' . esc_html__('Connect', 'thisnew') . '</a>';
        ?>

        <img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ) ?>" class="loader hidden" width="20px" height="20px" alt="loader"/>

        <script type="text/javascript">
            jQuery(document).ready(function () {
                ThisNew_Connect.init('<?php echo esc_url( admin_url( 'admin-ajax.php?action=ajax_force_check_connect_status' ) ); ?>');
            });
        </script>
    </div>
</div>