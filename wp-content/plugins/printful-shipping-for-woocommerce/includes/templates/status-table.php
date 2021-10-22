<?php if ( $checklist['overall_status'] ) {
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e('Looks like the everything is set up correctly and Printful integration should work as intended.', 'printful'); ?></p>
	</div>
	<?php
} else {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e('There are errors with your store setup that may cause the Printful integration to not work as intended!', 'printful'); ?></p>
	</div>
	<?php
}
?>

<table class="wp-list-table widefat fixed striped printful-status">
	<thead>
	<tr>
		<td class="col-name"><?php esc_html_e('Name', 'printful'); ?></td>
		<td class="col-desc"><?php esc_html_e('Description', 'printful'); ?></td>
		<td class="col-status"><?php esc_html_e('Status', 'printful'); ?></td>
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
					echo '<span class="pass">' . esc_html__('OK', 'printful') .'</span>';
				} else if ( $item['status'] == 0 ) {
					echo '<span class="warning">' . esc_html__('WARNING', 'printful') .'&#42;</span>';
				} else if ( $item['status'] == 2 ) {
                    echo '<span class="fail">' . esc_html__('NOT CONNECTED', 'printful') .'</span>';
                } else {
					echo '<span class="fail">' . esc_html__('FAIL', 'printful') .'</span>';
				}
				?>
            </td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
	<tr>
        <td class="col-name"><?php esc_html_e('Name', 'printful'); ?></td>
        <td class="col-desc"><?php esc_html_e('Description', 'printful'); ?></td>
        <td class="col-status"><?php esc_html_e('Status', 'printful'); ?></td>
	</tr>
	</tfoot>
</table>

<p class="asterisk">&#42; <?php esc_html_e('Warnings are issued when the test was unable to come to a definite conclusion or if the result was passable, but not ideal.', 'printful'); ?></p>
