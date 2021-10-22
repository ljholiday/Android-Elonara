<?php

namespace DgoraWcas\Integrations\Themes\Goya;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Goya extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'goya';
		$this->themeName = 'Goya';

		parent::__construct();
	}
}
