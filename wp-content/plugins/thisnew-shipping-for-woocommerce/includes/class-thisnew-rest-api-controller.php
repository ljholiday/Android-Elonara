<?php

/**
 * API class
 */
class ThisNew_REST_API_Controller extends WC_REST_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'thisnew';

    /**
     * Register the REST API routes.
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/access', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array( $this, 'set_thisnew_access' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'show_in_index' => false,
                'args' => array(
                    'accessKey' => array(
                        'required' => false,
                        'type' => 'string',
                        'description' => __( 'ThisNew access key', 'thisnew' ),
                    ),
                    'storeId' => array(
                        'required' => false,
                        'type' => 'integer',
                        'description' => __( 'Store Identifier', 'thisnew' ),
                    ),
                ),
            )
        ) );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/products/(?P<product_id>\d+)/size-chart', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array( $this, 'post_size_guide' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
                'show_in_index' => false,
                'args' => array(
                    'product_id' => array(
                        'description' => __( 'Unique identifier for the resource.', 'thisnew' ),
                        'type'        => 'integer',
                    ),
                    'size_chart' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => __( 'ThisNew size guide', 'thisnew' ),
                    )
                )
            )
        ) );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/version', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'callback' => array( $this, 'get_version' ),
                'show_in_index' => false,
            )
        ) );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/store_data', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'callback' => array( $this, 'get_store_data' ),
                'show_in_index' => true,
            )
        ) );
    }

    /**
     * @param WP_REST_Request $request
     * @return array
     */
    public static function set_thisnew_access( $request )
    {
        $error = false;

	    $options = get_option( 'woocommerce_thisnew_settings', array() );

        $api_key  = $request->get_param('accessKey');
        $store_id = $request->get_param('storeId');
        $store_id = intval( $store_id );

        if ( ! is_string( $api_key ) || strlen( $api_key ) == 0 || $store_id == 0 ) {
            $error = 'Failed to update access data';
        }

	    $options['thisnew_key']      = $api_key;
	    $options['thisnew_store_id'] = $store_id;

        ThisNew_Integration::instance()->update_settings( $options );

        return array(
            'error' => $error,
        );
    }

    /**
     * Submit size guide
     * @param array $data
     * @return array|WP_Error
     */
    public static function post_size_guide( $data )
    {
        if ( empty( $data['size_chart'] ) ) {
            return new WP_Error( 'thisnew_api_size_chart_empty', __( 'No size chart was provided', 'thisnew' ), array('status' => 400));
        }

        //product id is valid
        $product_id = intval( $data['product_id'] );
        if ( $product_id < 1 ) {
            return new WP_Error( 'thisnew_api_product_not_found', __( 'The product ID is invalid', 'thisnew' ), array('status' => 400));
        }

        //product exists
        $product = get_post( $product_id );
        if ( empty( $product ) || $product->post_type != 'product' ) {
            return new WP_Error( 'thisnew_api_product_not_found', __( 'The product is not found', 'thisnew' ), array('status' => 400));
        }

        //how about permissions?
        $post_type = get_post_type_object( $product->post_type );
        if ( ! current_user_can( $post_type->cap->edit_post, $product->ID ) ) {
            return new WP_Error( 'thisnew_api_user_cannot_edit_product_size_chart', __( 'You do not have permission to edit the size chart', 'thisnew' ), array('status' => 401));
        }

        //lets do this
        update_post_meta( $product_id, 'pf_size_chart', htmlspecialchars( $data['size_chart'] ) );

        return array(
            'product'    => $product,
            'size_chart' => $data['size_chart'],
        );
    }

    /**
     * Allow remotely get plugin version for debug purposes
     */
    public static function get_version() {
        $error = false;

        try {
            $client     = ThisNew_Integration::instance()->get_client();
            $store_data = $client->get( 'store' );
        } catch ( Exception $exception ) {
            $error = $exception->getMessage();
        }

        $checklist = ThisNew_Admin_Status::get_checklist();
        $checklist['overall_status'] = ( $checklist['overall_status'] ? 'OK' : 'FAIL' );

        foreach ( $checklist['items'] as $checklist_key => $checklist_item ) {

            if ( $checklist_item['status'] == ThisNew_Admin_Status::PF_STATUS_OK ) {
                $checklist_item['status'] = 'OK';
            } elseif ( $checklist_item['status'] == ThisNew_Admin_Status::PF_STATUS_WARNING ) {
                $checklist_item['status'] = 'WARNING';
            } elseif ( $checklist_item['status'] == ThisNew_Admin_Status::PF_STATUS_NOT_CONNECTED ) {
                $checklist_item['status'] = 'NOT CONNECTED';
            } else {
                $checklist_item['status'] = 'FAIL';
            }

            $checklist['items'][ $checklist_key ] = $checklist_item;
        }

        return array(
            'version'          => ThisNew_Base::VERSION,
            'store_id'         => ! empty( $store_data['id'] ) ? $store_data['id'] : false,
            'error'            => $error,
            'status_checklist' => $checklist,
        );
    }

    /**
     * Get necessary store data
     * @return array
     */
    public static function get_store_data() {
        return array(
            'website'   => get_site_url(),
            'version'   => WC()->version,
            'name'      => get_bloginfo( 'title', 'display' )
        );
    }

    /**
     * Check whether a given request has permission to read thisnew endpoints.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        if ( ! wc_rest_check_user_permissions( 'read' ) ) {
            return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }

    /**
     * Check if a given request has access to update a product.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check( $request ) {
        $params = $request->get_url_params();
        $post = get_post( (int) $params['product_id'] );

        if ( $post && ! wc_rest_check_post_permissions( 'product', 'edit', $post->ID ) ) {
            return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }
}
