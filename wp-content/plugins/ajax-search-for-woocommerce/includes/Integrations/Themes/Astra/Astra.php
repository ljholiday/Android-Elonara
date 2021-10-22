<?php

namespace DgoraWcas\Integrations\Themes\Astra;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Astra extends ThemeIntegration {
	public function __construct() {
		$this->themeSlug = 'astra';
		$this->themeName = 'Astra';

		if ( defined( 'ASTRA_EXT_VER' ) ) {
			add_filter( 'dgwt/wcas/suggestion_details/show_quantity', '__return_false' );
		}

		parent::__construct();
	}
}
