<?php           
/**                     
 * Plugin Name:       LJH Fix Search
 * Plugin URI:        https://ljholiday.com/plugins/ljh-fix-search/
 * Description:       This plugin should add a search box to the main menu
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Lonn Holiday
 * Author URI:        https://ljholiday.com/about
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ljh-fix-search
 * Domain Path:       /languages
 */     

function add_last_nav_item($items, $args) {
  if ('header_menu' === $args->menu_id) {
        $homelink = get_search_form(true);
        $items .= '<li>'.$homelink.'</li>';
        return $items;
  }
  return $items;
}
add_filter( 'wp_nav_menu_items', 'add_last_nav_item', 10, 2 );


?>
