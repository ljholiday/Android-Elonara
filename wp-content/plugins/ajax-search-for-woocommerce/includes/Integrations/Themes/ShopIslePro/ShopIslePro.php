<?php

namespace DgoraWcas\Integrations\Themes\ShopIslePro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShopIslePro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'shop-isle-pro';
		$this->themeName = 'ShopIsle PRO';

		parent::__construct();
	}

	/**
	 * Overwrite search
	 *
	 * @return void
	 */
	protected function maybeOverwriteSearch() {
		// We load partial from free version of theme
		$partialPath = DGWT_WCAS_DIR . 'partials/themes/shop-isle.php';
		if ( $this->canReplaceSearch() && file_exists( $partialPath ) ) {
			require_once( $partialPath );
		}
	}
}
