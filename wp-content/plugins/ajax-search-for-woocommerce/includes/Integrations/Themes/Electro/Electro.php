<?php

namespace DgoraWcas\Integrations\Themes\Electro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Electro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'electro';
		$this->themeName = 'Electro';

		parent::__construct();
	}
}
