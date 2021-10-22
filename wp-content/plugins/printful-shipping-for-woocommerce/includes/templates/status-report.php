<div class="support-report-wrap">
	<p>
        <?php esc_html_e('Copy the box content below and add it to your support message', 'printful'); ?>
        <br/>
        <?php esc_html_e('Note: this status report may not include an error log. Contact your hosting provider if you need help with acquiring error logs.'); ?>
    </p>
	<textarea class="support-report"><?php echo esc_html($status_report); ?></textarea>
	<button class="button button-primary button-large support-report-btn"><?php esc_html_e('Copy', 'printful'); ?></button>
	<script type="text/javascript">
        var copyTextareaBtn = document.querySelector('.support-report-btn');

        copyTextareaBtn.addEventListener('click', function() {
            var copyTextarea = document.querySelector('.support-report');
            copyTextarea.select();

            try {
                document.execCommand('copy');
            } catch (err) {
                //do nothing
            }
        });
	</script>
</div>