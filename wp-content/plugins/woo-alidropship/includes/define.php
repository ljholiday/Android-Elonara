<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VI_WOO_ALIDROPSHIP_ADMIN', VI_WOO_ALIDROPSHIP_DIR . "admin" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_FRONTEND', VI_WOO_ALIDROPSHIP_DIR . "frontend" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_LANGUAGES', VI_WOO_ALIDROPSHIP_DIR . "languages" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_TEMPLATES', VI_WOO_ALIDROPSHIP_DIR . "templates" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_CACHE', VI_WOO_ALIDROPSHIP_DIR . "cache" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_COOKIE_FILE', VI_WOO_ALIDROPSHIP_CACHE . 'my_cookies.txt' );
$plugin_url = plugins_url( '', __FILE__ );
$plugin_url = str_replace( '/includes', '', $plugin_url );
define( 'VI_WOO_ALIDROPSHIP_CSS', $plugin_url . "/css/" );
define( 'VI_WOO_ALIDROPSHIP_CSS_DIR', VI_WOO_ALIDROPSHIP_DIR . "css" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_JS', $plugin_url . "/js/" );
define( 'VI_WOO_ALIDROPSHIP_JS_DIR', VI_WOO_ALIDROPSHIP_DIR . "js" . DIRECTORY_SEPARATOR );
define( 'VI_WOO_ALIDROPSHIP_IMAGES', $plugin_url . "/images/" );
define( 'VI_WOO_ALIDROPSHIP_EXTENSION_VERSION', '1.0' );


/*Include functions file*/
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "functions.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "functions.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "support.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "support.php";
}
/*Include functions file*/
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "wp-async-request.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "wp-async-request.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "wp-background-process.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "wp-background-process.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "data.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "data.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-draft-product.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-draft-product.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_ADMIN . "class-villatheme-admin-show-message.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_ADMIN . "class-villatheme-admin-show-message.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-error-images-table.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-error-images-table.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-download-images.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-download-images.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-import.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-import.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-download-description.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "class-vi-wad-background-download-description.php";
}
if ( is_file( VI_WOO_ALIDROPSHIP_INCLUDES . "setup-wizard.php" ) ) {
	require_once VI_WOO_ALIDROPSHIP_INCLUDES . "setup-wizard.php";
}
vi_include_folder( VI_WOO_ALIDROPSHIP_ADMIN, 'VI_WOO_ALIDROPSHIP_Admin_' );
vi_include_folder( VI_WOO_ALIDROPSHIP_FRONTEND, 'VI_WOO_ALIDROPSHIP_Frontend_' );
