<?php

namespace DgoraWcas\Integrations\Themes\BigCart;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BigCart extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'bigcart';
		$this->themeName = 'BigCart';

		parent::__construct();
	}
}
