<div class="thisnew-stats">
	<div class="thisnew-stats-item">
		<h4><?php echo esc_html($stats['countNum']?$stats['countNum']:'0'); ?></h4>
        <b>
            <?php
            echo 'Orders';
            ?>
        </b>
	</div>
    <div class="thisnew-stats-item">
		<h4>$<?php echo esc_html($stats['totalPay']?number_format($stats['totalPay'],'2'):'0.00'); ?></h4>
        <b>
            <?php
            echo 'Amount';
            ?>
        </b>
	</div>
	
</div>