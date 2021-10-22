<?php

namespace DgoraWcas\Integrations\Themes\Estore;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Estore extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'estore';
		$this->themeName = 'eStore';

		parent::__construct();
	}
}
