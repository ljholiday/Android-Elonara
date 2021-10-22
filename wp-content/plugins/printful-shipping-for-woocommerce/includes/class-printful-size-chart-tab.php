<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Size_Chart_Tab {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Printful_Size_Chart_Tab constructor.
	 */
	public static function init() {
		$size_chart = new self;
		add_filter( 'woocommerce_product_tabs', array( $size_chart, 'init_size_chart_tab' ) );
		add_action( 'add_meta_boxes', array( $size_chart, 'init_metabox' ) );
		add_action( 'save_post', array( $size_chart, 'save_size_chart' ), 1, 2 );
	}

	/**
	 * Initialize meta boxes
	 */
	public function init_metabox() {
		global $post;
		// If product has advanced size chart we don't show basic size chart metabox at all
		if (Printful_Size_Guide::get_size_guide_for_product($post)) {
			return;
		}
		add_meta_box( 'pf_size_chart', __( 'Size chart', 'printful' ), array( $this, 'size_chart_metabox' ), 'product', 'normal' );
	}

	/**
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function init_size_chart_tab( $tabs ) {
		if ( strlen( $this->get_size_chart_content() ) > 0 ) {
			$tabs['size_chart'] = array(
				'title'    => __( 'Size Chart', 'printful' ),
				'priority' => 50,
				'callback' => array( $this, 'size_chart_tab_content' ),
			);
		}

		return $tabs;
	}

	/**
	 * Display the size chart content
	 */
	public function size_chart_tab_content() {
		echo '<h2>' . esc_html__( 'Size Chart', 'printful' ) . '</h2>';
		echo $this->get_size_chart_content();
	}

	/**
	 * @return mixed
	 */
	public function get_size_chart_content() {
		global $post;

		return htmlspecialchars_decode(get_post_meta( $post->ID, 'pf_size_chart', true ));
	}

	/**
	 * @param $meta_id
	 */
	public function size_chart_metabox( $meta_id ) {
		$settings = array(
			'textarea_name' => 'pf_size_chart',
			'tinymce'       => array(
				'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
				'theme_advanced_buttons2' => '',
			),
			'editor_css'    => '<style>#wp-pf_size_chart-editor-container .wp-editor-area{height:175px; width:100%;} .wp-editor-area{height:175px; width:100%;}</style>',
		);

		$content = get_post_meta( $meta_id->ID, 'pf_size_chart', true );

		wp_editor( htmlspecialchars_decode( $content ), 'pf_size_chart_editor', apply_filters( 'woocommerce_product_short_description_editor_settings', $settings ) );
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public function save_size_chart( $post_id, $post ) {

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check the post type
		if ( $post->post_type != 'product' ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		self::$saved_meta_boxes = true;

		//save
		update_post_meta($post_id, 'pf_size_chart', htmlspecialchars($_POST['pf_size_chart']));
	}
}