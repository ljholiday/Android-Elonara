<?php

namespace DgoraWcas\Integrations\Themes\OpenShop;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OpenShop extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'open-shop';
		$this->themeName = 'Open Shop';

		parent::__construct();
	}
}
