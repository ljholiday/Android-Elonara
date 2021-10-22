<?php

namespace DgoraWcas\Integrations\Themes\Konte;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Konte extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'konte';
		$this->themeName = 'Konte';

		parent::__construct();
	}
}
