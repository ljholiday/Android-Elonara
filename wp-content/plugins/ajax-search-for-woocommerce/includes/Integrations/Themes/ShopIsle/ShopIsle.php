<?php

namespace DgoraWcas\Integrations\Themes\ShopIsle;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShopIsle extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'shop-isle';
		$this->themeName = 'Shop Isle';

		parent::__construct();
	}
}
