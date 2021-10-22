<?php

namespace DgoraWcas\Integrations\Themes\Shopical;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shopical extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'shopical';
		$this->themeName = 'Shopical';

		parent::__construct();
	}
}
