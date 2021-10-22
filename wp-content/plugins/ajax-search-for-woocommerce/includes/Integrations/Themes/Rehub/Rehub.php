<?php

namespace DgoraWcas\Integrations\Themes\Rehub;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rehub extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'rehub-theme';
		$this->themeName = 'Rehub';

		parent::__construct();
	}
}
