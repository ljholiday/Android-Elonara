<?php if ( $checklist['overall_status'] ) {
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e('Looks like the everything is set up correctly and ThisNew integration should work as intended.', 'thisnew'); ?></p>
	</div>
	<?php
} else {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e('There are errors with your store setup that may cause the ThisNew integration to not work as intended!', 'thisnew'); ?></p>
	</div>
	<?php
}
?>

<table class="wp-list-table widefat fixed striped thisnew-status">
	<thead>
	<tr>
		<td class="col-name"><?php esc_html_e('Name', 'thisnew'); ?></td>
		<td class="col-desc"><?php esc_html_e('Description', 'thisnew'); ?></td>
		<td class="col-status"><?php esc_html_e('Status', 'thisnew'); ?></td>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $checklist['items'] as $item ) : ?>
		<tr>
			<td><?php echo esc_html( $item['name'] ); ?></td>
			<td><?php echo esc_html( $item['description'] ); ?></td>
			<td>
				<?php
				$status = 'OK';
				if ( $item['status'] == 1 ) {
					echo '<span class="pass">' . esc_html__('OK', 'thisnew') .'</span>';
				} else if ( $item['status'] == 0 ) {
					echo '<span class="warning">' . esc_html__('WARNING', 'thisnew') .'&#42;</span>';
				} else if ( $item['status'] == 2 ) {
                    echo '<span class="fail">' . esc_html__('NOT CONNECTED', 'thisnew') .'</span>';
                } else {
					echo '<span class="fail">' . esc_html__('FAIL', 'thisnew') .'</span>';
				}
				?>
            </td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
	<tr>
        <td class="col-name"><?php esc_html_e('Name', 'thisnew'); ?></td>
        <td class="col-desc"><?php esc_html_e('Description', 'thisnew'); ?></td>
        <td class="col-status"><?php esc_html_e('Status', 'thisnew'); ?></td>
	</tr>
	</tfoot>
</table>

<p class="asterisk">&#42; <?php esc_html_e('Warnings are issued when the test was unable to come to a definite conclusion or if the result was passable, but not ideal.', 'thisnew'); ?></p>