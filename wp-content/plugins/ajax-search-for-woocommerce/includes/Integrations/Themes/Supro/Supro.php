<?php

namespace DgoraWcas\Integrations\Themes\Supro;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Supro extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'supro';
		$this->themeName = 'Supro';

		parent::__construct();
	}
}
