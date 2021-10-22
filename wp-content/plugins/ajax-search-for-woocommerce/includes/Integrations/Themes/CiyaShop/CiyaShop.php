<?php

namespace DgoraWcas\Integrations\Themes\CiyaShop;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiyaShop extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'ciyashop';
		$this->themeName = 'CiyaShop';

		parent::__construct();
	}
}
