<?php

namespace DgoraWcas\Integrations\Themes\ShopicalPro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShopicalPro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'shopical-pro';
		$this->themeName = 'Shopical Pro';

		parent::__construct();
	}

	/**
	 * Overwrite search
	 *
	 * @return void
	 */
	protected function maybeOverwriteSearch() {
		// We load partial from free version of theme
		$partialPath = DGWT_WCAS_DIR . 'partials/themes/shopical.php';
		if ( $this->canReplaceSearch() && file_exists( $partialPath ) ) {
			require_once( $partialPath );
		}
	}
}
