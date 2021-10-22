<?php

namespace DgoraWcas\Integrations\Themes\TopStore;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TopStore extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'top-store';
		$this->themeName = 'Top Store';

		parent::__construct();
	}

	/**
	 * Overwrite search
	 *
	 * @return void
	 */
	protected function maybeOverwriteSearch() {
		// We load partial from Pro version
		$partialPath = DGWT_WCAS_DIR . 'partials/themes/' . $this->themeSlug . '-pro.php';
		if ( $this->canReplaceSearch() && file_exists( $partialPath ) ) {
			require_once( $partialPath );
		}
	}
}
