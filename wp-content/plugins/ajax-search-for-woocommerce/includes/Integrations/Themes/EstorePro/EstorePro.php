<?php

namespace DgoraWcas\Integrations\Themes\EstorePro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EstorePro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'estore-pro';
		$this->themeName = 'eStore Pro';

		parent::__construct();
	}

	/**
	 * Overwrite search
	 *
	 * @return void
	 */
	protected function maybeOverwriteSearch() {
		// We load partial from free version of theme
		$partialPath = DGWT_WCAS_DIR . 'partials/themes/estore.php';
		if ( $this->canReplaceSearch() && file_exists( $partialPath ) ) {
			require_once( $partialPath );
		}
	}
}
