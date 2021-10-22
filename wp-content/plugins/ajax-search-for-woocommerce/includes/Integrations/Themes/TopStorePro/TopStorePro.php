<?php

namespace DgoraWcas\Integrations\Themes\TopStorePro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TopStorePro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'top-store-pro';
		$this->themeName = 'Top Store Pro';

		parent::__construct();
	}
}
