<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VI_WOO_ALIDROPSHIP_Admin_Product
 */
class VI_WOO_ALIDROPSHIP_Admin_Product {
	private $settings;

	public function __construct() {
		$this->settings = VI_WOO_ALIDROPSHIP_DATA::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'deleted_post' ) );
		add_action( 'edit_form_top', array( $this, 'link_to_imported_page' ) );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 20, 2 );
	}

	/**
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function post_row_actions( $actions, $post ) {
		if ( $post && $post->post_type === 'product' && $post->post_status !== 'trash' ) {
			$ali_sku = get_post_meta( $post->ID, '_vi_wad_aliexpress_product_id', true );
			if ( $ali_sku ) {
				$actions['vi_wad_view_on_aliexpress']    = '<a href="' . VI_WOO_ALIDROPSHIP_DATA::get_aliexpress_product_url( $ali_sku ) . '" title="' . esc_attr__( 'View product on AliExpress', 'woo-alidropship' ) . '" target="_blank">' . esc_html__( 'View on AliExpress', 'woo-alidropship' ) . '</a>';
				$href                                    = admin_url( "admin.php?page=woo-alidropship-imported-list&vi_wad_search_woo_id={$post->ID}" );
				$actions['vi_wad_view_on_imported_page'] = '<a href="' . $href . '" title="' . esc_attr__( 'View product on Imported page', 'woo-alidropship' ) . '" target="_blank">' . esc_html__( 'View on Imported', 'woo-alidropship' ) . '</a>';
			}
		}

		return $actions;
	}

	public function link_to_imported_page( $post ) {
		if ( $post->post_type === 'product' && get_post_meta( $post->ID, '_vi_wad_aliexpress_product_id', true ) ) {
			$href = admin_url( "admin.php?page=woo-alidropship-imported-list&post_status=publish&vi_wad_search_woo_id={$post->ID}" );
			$link = "<a href='{$href}' target='_blank' class='page-title-action' style='margin-top:10px '>" . __( 'View on Imported page', 'woo-alidropship' ) . "</a>";
			?>
            <script type="text/javascript">
                'use strict';
                jQuery(document).ready(function ($) {
                    let html = `<?php echo $link?>`;
                    $('.wp-header-end').before(html);
                });
            </script>
			<?php
		}
	}
	/**Set a product status
	 *
	 * @param $product_id
	 * @param string $status
	 */
	public static function set_status( $product_id, $status = 'trash' ) {
		$ali_sku = get_post_meta( $product_id, '_vi_wad_aliexpress_product_id', true );
		if ( $ali_sku ) {
			if ( $status === 'publish' ) {
				$id = VI_WOO_ALIDROPSHIP_DATA::product_get_id_by_woo_id( $product_id, false, false, 'trash' );
			} else {
				$id = VI_WOO_ALIDROPSHIP_DATA::product_get_id_by_woo_id( $product_id );
			}
			if ( $id ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );
			}
		}
	}

	/**Set a product status to trash when a WC product is deleted
	 *
	 * @param $product_id
	 */
	public function deleted_post( $product_id ) {
		self::set_status( $product_id, 'trash' );
	}

	/**Set a product status to trash when a WC product is trashed and set to publish when a trashed product is restored
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'product' === $post->post_type ) {
			$product_id = $post->ID;
			if ( 'trash' === $new_status ) {
				self::set_status( $product_id );
			} elseif ( $old_status === 'trash' ) {
				self::set_status( $product_id, 'publish' );
			}
		}
	}

	public function admin_enqueue_scripts( $page ) {
		global $post_type;
		if ( $page === 'post.php' && $post_type === 'product' ) {
			wp_enqueue_style( 'woo-alidropship-admin-edit-product', VI_WOO_ALIDROPSHIP_CSS . 'admin-product.css', '', VI_WOO_ALIDROPSHIP_VERSION );
			add_action( 'post_submitbox_start', array( $this, 'post_submitbox_start' ) );
		}
	}

	public function post_submitbox_start( $post ) {
		if ( $post ) {
			$product_id     = $post->ID;
			$ali_product_id = get_post_meta( $product_id, '_vi_wad_aliexpress_product_id', true );
			if ( $ali_product_id ) {
				?>
                <p class="vi-wad-view-original-product-button">
                    <a href="<?php echo esc_url( "https://www.aliexpress.com/item/{$ali_product_id}.html" ); ?>"
                       target="_blank"
                       class="button"><?php esc_html_e( 'View product on AliExpress', 'woo-alidropship' ); ?></a>
                </p>
				<?php
			}
		}
	}
}
