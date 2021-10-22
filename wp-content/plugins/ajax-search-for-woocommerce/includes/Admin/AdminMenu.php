<?php

namespace DgoraWcas\Admin;

// Exit if accessed directly
use  DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder ;
use  DgoraWcas\Settings ;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class AdminMenu
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'addMenu' ), 20 );
    }
    
    /**
     * Add meun items
     *
     * @return void
     */
    public function addMenu()
    {
        $menuSuffix = '';
        add_submenu_page(
            'woocommerce',
            __( 'FiboSearch', 'ajax-search-for-woocommerce' ),
            __( 'FiboSearch', 'ajax-search-for-woocommerce' ) . $menuSuffix,
            'manage_options',
            'dgwt_wcas_settings',
            array( $this, 'settingsPage' )
        );
        add_submenu_page(
            'dgwt_wcas_settings',
            'FiboSearch Debug',
            'FiboSearch [Hidden]',
            'manage_options',
            'dgwt_wcas_debug',
            array( $this, 'debugPage' )
        );
    }
    
    /**
     * Settings page
     *
     * @return void
     */
    public function settingsPage()
    {
        Settings::output();
    }
    
    /**
     * Debug page
     *
     * @return void
     */
    public function debugPage()
    {
    }

}