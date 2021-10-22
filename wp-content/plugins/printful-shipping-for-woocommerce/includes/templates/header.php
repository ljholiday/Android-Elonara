<div class="wrap">

    <?php
    $base_url = '?page=printful-dashboard';
    ?>

    <h2 class="nav-tab-wrapper printful-tabs">
        <?php foreach ($tabs as $tab) : ?>
            <?php
	        $active = '';
	        if ( ! empty( $_GET['tab'] ) && $_GET['tab'] == $tab['tab_url'] ) {
		        $active = 'nav-tab-active';
	        }
	        if ( empty( $_GET['tab'] ) && $tab['tab_url'] == '' ) {
		        $active = 'nav-tab-active';
	        }
            ?>
            <a href="<?php echo esc_url($base_url . ($tab['tab_url'] ? '&tab=' . $tab['tab_url'] : '') ); ?>" class="nav-tab <?php echo esc_attr($active);?>"><?php echo esc_html($tab['name']); ?></a>
        <?php endforeach; ?>
    </h2>