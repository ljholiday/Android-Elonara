<?php

namespace DgoraWcas\Integrations\Themes\Salient;

use DgoraWcas\Abstracts\ThemeIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Salient extends ThemeIntegration {

	public function __construct() {
		$this->themeSlug = 'salient';
		$this->themeName = 'Salient';

		parent::__construct();
	}
}
