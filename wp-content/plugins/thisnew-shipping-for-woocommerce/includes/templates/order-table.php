
<h2>ThisNew product orders</h2>
<?php  
if ( ! empty( $orders ) && $orders['count'] > 0): 
?>
    <table class="wp-list-table widefat fixed striped thisnew-latest-orders">
        <thead>
            <tr>
                <th class="col-order"><?php esc_html_e('ThisNew Order No.', 'thisnew'); ?></th>
                <th class="col-date"><?php esc_html_e('Customer Order No.', 'thisnew'); ?></th>
                <th class="col-from"><?php esc_html_e('Time', 'thisnew'); ?></th>
                <th class="col-status"><?php esc_html_e('Status', 'thisnew'); ?></th>
                <th class="col-total"><?php esc_html_e('Total', 'thisnew'); ?></th>
                <th class="col-actions"><?php esc_html_e('Action', 'thisnew'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $orders['results'] as $order ) : ?>
                <tr>
                    <td>
                        <?php
                        echo esc_html( $order['thisNewOrderNo']?$order['thisNewOrderNo']:'  -' ); 
                        ?>
                    </td>
                    <td>
                        <?php 
                        echo esc_html( $order['showCustomerOrderNo']?$order['showCustomerOrderNo']:'--') ;
                        //  echo esc_html( date('Y-m-d', $order['created']) ); ?>
                    </td>
                    <td>
                    
	                    <?php echo esc_html( $order['time']?$order['time']:'--' ); ?>
                    </td>
                    <td>
                        <?php
                            switch ($order['status'])
                            {
                            case 1:
                              echo 'Pending';
                              break;  
                            case 2:
                              echo 'Processed';
                              break;
                            case 3:
                              echo 'Processed';
                              break;
                            case 4:
                              echo 'Shipped';
                              break;
                            case 5:
                              echo 'Completed';
                              break;
                            case 7:
                              echo 'Partially Shipped';
                              break;
                            case 8:
                              echo 'Partially  Completed';
                              break;
                            case 21:
                              echo 'Needs Approval';
                              break;
                            case 22:
                              echo 'Not Synced';
                              break;
                            default:
                              echo 'Canceled';
                            }
                        ?>
                    </td>
                    <td>
	                    $<?php echo esc_html( $order['total']?number_format($order['total'],'2'):'0.00' ); ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(ThisNew_Base::get_thisnew_host()); ?>myAdmin/customerOrder?mainOrderId=<?php echo esc_attr($order['mainOrderId']); ?>" target="_blank"><?php esc_html_e('Open in ThisNew', 'thisnew'); ?></a>
                    </td>

                </tr>
                            
            <?php endforeach; ?>

        </tbody>
        <!-- <tfoot> -->
    </table>
    <ul class="pagination table-pagination" unselectable="unselectable">
        <li class="pagination-total-text">Total Record:<?php echo $orders['count'];?> </li>
        <li title="Previous Page" class="<?php echo $orders['page']==1?'pagination-disabled':''; ?> pagination-prev">
            <a class="pagination-item-link" onclick="gotoPage(<?php echo  $orders['page']-1;?>,<?php echo $orders['page']==1?'true':'false'; ?>)">
                <i aria-label="icon: left" class="anticon anticon-left">
                    <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="left" width="1em" height="1em" fill="currentColor" aria-hidden="true">
                        <path d="M724 218.3V141c0-6.7-7.7-10.4-12.9-6.3L260.3 486.8a31.86 31.86 0 0 0 0 50.3l450.8 352.1c5.3 4.1 12.9.4 12.9-6.3v-77.3c0-4.9-2.3-9.6-6.1-12.6l-360-281 360-281.1c3.8-3 6.1-7.7 6.1-12.6z"></path>
                    </svg>
                </i>
            </a>
        </li>
        <?php
            for($i=0;$i<$orders['end']-$orders['start']+1;$i++){  ?>
            <li   title="<?php echo $orders['start']+$i;?>" class="pagination-item <?php if($orders['start']+$i==$orders['page']){echo 'pagination-item-active ';};echo 'pagination-item-'.$i;?>" tabindex="0">
                <a href='<?php echo get_home_url();?>/wp-admin/admin.php?page=thisnew-dashboard&pagenum=<?php echo $orders['start']+$i;?>'>
                    <?php echo $orders['start']+$i; ?>
                </a>
            </li>
         <?php
            }
        ?>
        
        <li title="Next Page" tabindex="0" class="<?php echo $orders['page']==intval(ceil($orders['count']/10))?'pagination-disabled':'' ?> pagination-next">
            <a class="pagination-item-link" onclick="gotoPage(<?php echo  $orders['page']+1;?>,<?php echo $orders['page']==intval(ceil($orders['count']/10))?'true':'false';?>)">
                <i aria-label="icon: right" class="anticon anticon-right">
                    <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true">
                        <path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path>
                    </svg>
                </i>
            </a>
        </li>
        <li class="pagination-options">
            <div class="pagination-options-quick-jumper" ><span onclick="gotoPage(0)"> Goto</span><input oninput = "value=value.replace(/[^\d]/g,'')" id='order_table_pageNum' style='text-align: center;' type="text" value=""></div>
        </li>
    </ul>                          
<?php 
else: 
?>
    <div class="thisnew-latest-orders">
        <p><?php esc_html_e('Once your store gets some ThisNew product orders, they will be shown here!', 'thisnew'); ?></p>
    </div>
<?php 
endif;
 ?>

 <script>
    
    function gotoPage(num,boolval){
        var pageValue
        if(boolval){
            return false;
        }
        if(!num){
            pageValue= document.getElementById('order_table_pageNum').value;
            
            if(!(pageValue*1) && (pageValue!=0)){
                return false;
            }
            var maxPage = <?php echo intval(ceil($orders['count']/10));?>;
            if(pageValue*1>maxPage){
                pageValue = maxPage*1
                // return false;
            }
            if(pageValue==0){
                pageValue = 1;
                // return false;
            }
        }else{
            pageValue = num;
        }
        window.location.href= '<?php echo get_home_url().'/wp-admin/admin.php?page=thisnew-dashboard&pagenum='?>'+pageValue;
    }
    document.onkeydown = function (event) {

        var e = event || window.event;
        if (e && e.keyCode == 13) { //回车键的键值为13
            gotoPage(0);
        }
    }; 

 </script>