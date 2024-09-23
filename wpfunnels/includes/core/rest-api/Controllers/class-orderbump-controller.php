<?php
/**
 * Orderbump controller
 * 
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;

use Wpfnl_Controller_Type_Factory;

class OrderBumpController extends Wpfnl_REST_Controller
{

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'order-bump';

    /**
     * Check if user has valid permission
     *
     * @param $request
     * 
     * @return bool|WP_Error
     * @since  1.0.0
     */
    public function update_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('steps', 'edit')) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), array('status' => rest_authorization_required_code()));
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * 
     * @return WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot list resources.', 'wpfnl'), array('status' => rest_authorization_required_code()));
        }
        return true;
    }


    /**
     * Register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base, array(
                'args' => array(),
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_settings'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                )
            )
        );
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<step_id>\d+)', array(
                'args' => array(
                    'step_id' => array(
                        'description' => __('Step ID.', 'wpfnl'),
                        'type' => 'string',
                    )
                ),
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_setting'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            )
        );
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/update-status/(?P<step_id>\d+)', array(
                'args' => array(
                    'step_id' => array(
                        'description' => __('Step ID.', 'wpfnl'),
                        'type' => 'string',
                    )
                ),
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_status'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            )
        );

        register_rest_route($this->namespace, '/getProducts/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_wc_products'
                ],
                'permission_callback' => [
                    $this,
                    'get_items_permissions_check'
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/getFluentForms', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_fluent_form_list'
                ],
                'permission_callback' => [
                    $this,
                    'get_items_permissions_check'
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/getOtherSteps', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_other_steps' ),
				'permission_callback' => array( $this,  'get_items_permissions_check'),
            ],
        ]);



        register_rest_route($this->namespace, '/updateSelectedProduct/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'update_selected_product'
                ],
                 'permission_callback' => [
                     $this,
                     'get_items_permissions_check'
                 ] ,
            ],
        ]);

    }



    /**
     * Update selected product
     *
     * @param string $existing_product.
     * 
     * @return array|WP_Error
     */
    public function update_selected_product($request)
    {
        $data = $request->get_params();
        $product_data = [
            'name' => '',
            'price' => '',
            'sale_price' => '',
            'html_price' => '',
            'title' => '',
            'img' => '',
        ];

        if (isset($data['product'])) {
            $product_id = $data['product'];
            $product_object = wc_get_product($product_id);
            if( $product_object ){
                $formatted_name = $product_object->get_formatted_name();
                if ($product_object->get_type() == 'variable-subscription') {
                    $sale_price = $product_object->get_sale_price();
                    if ($sale_price != "") {
                        $sale_price = wc_price($product_object->get_sale_price());
                    }
                    //=== Return updated product data===//
                    $product_data = [
                        'name' => rawurldecode($formatted_name),
                        'price' => $product_object->get_price_html(),
                        'sale_price' => $sale_price,
                        'html_price' => $product_object->get_price_html(),
                        'title' => $product_object->get_title(),
                        'img' => $product_object->get_image(),
                    ];

                } elseif ($product_object->get_type() == 'variable') {
                    $sale_price = $product_object->get_sale_price();
                    if ($sale_price != "") {
                        $sale_price = wc_price($product_object->get_sale_price());
                    }

                    //=== Return updated product data===//
                    $product_data = [
                        'name' => rawurldecode($formatted_name),
                        'price' => wc_price($product_object->get_regular_price()),
                        'sale_price' => $sale_price,
                        'html_price' => $product_object->get_price_html(),
                        'title' => $product_object->get_title(),
                        'img' => $product_object->get_image(),
                    ];
                } else {
                    $sale_price = $product_object->get_sale_price();
                    if ($sale_price != "") {
                        $sale_price = wc_price($product_object->get_sale_price());
                    }
                    //=== Return updated product data===//
                    $product_data = [
                        'name' => rawurldecode($formatted_name),
                        'price' => wc_price($product_object->get_regular_price()),
                        'sale_price' => $sale_price,
                        'html_price' => $product_object->get_price_html(),
                        'title' => $product_object->get_title(),
                        'img' => $product_object->get_image(),
                    ];
                }
            }
        }
        return $product_data;
    }



    /**
     * Get Other Steps.
     *
     * @param string $request data.
     * 
     * @return array|WP_Error
     */
    public function get_other_steps($request)
    {
        $step_id = $request['step_id'];
        if (isset($request['from']) && $request['from'] == 'plugin') {
            $funnel_id = get_post_meta($step_id, '_funnel_id', true);
            if (!$funnel_id) {
                return null;
            }
            $data = [];
            $data[] = [
                'value' => 'none',
                'name' => 'Default',
            ];
            $steps = get_post_meta($funnel_id, '_steps_order', true);
            foreach ($steps as $step_key => $step_value) {
                if ($step_value['step_type'] != 'landing' && $step_value['step_type'] != 'checkout') {
                    $step_data = [
                        'value' => $step_value['id'],
                        'name' => $step_value['name'],
                    ];
                    $data[] = $step_data;
                }
            }
            return $data;
        }

        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        if (!$funnel_id) {
            return null;
        }
        $data = [];
        $data[] = [
            'value' => 'none',
            'label' => 'Default',
        ];
        $steps = get_post_meta($funnel_id, '_steps_order', true);
        foreach ($steps as $step_key => $step_value) {
            if ($step_value['step_type'] != 'landing' && $step_value['step_type'] != 'checkout') {
                $step_data = [
                    'value' => $step_value['id'],
                    'label' => $step_value['name'],
                ];
                $data[] = $step_data;
            }
        }
        return $data;

    }



    /**
     * Get Fluent Forms Data
     *
     * @param string $request to get.
     * 
     * @return array|WP_Error
     */
    public function get_fluent_form_list($request)
    {
        $data = [];
        $default = [
            'value' => 'null',
            'label' => 'Select A Form',
        ];
        $data[] = $default;
        if (in_array('fluentform/fluentform.php', WPFNL_ACTIVE_PLUGINS)) {
            global $wpdb;
            $sql = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}fluentform_forms");
            $results = $wpdb->get_results($sql);
            foreach ($results as $value) {
                $sql = $wpdb->prepare("SELECT title FROM {$wpdb->prefix}fluentform_forms WHERE id=$value->id");
                $title = $wpdb->get_results($sql);
                $result = [
                    'value' => $value->id,
                    'label' => $title[0]->title,
                ];
                $data[] = $result;
            }
        }
        return $data;
    }



    /**
     * Get all Products.
     *
     * @param string $request Data.
     * 
     * @return array|WP_Error
     */
    public function get_wc_products($request)
    {
        $data = [];
        $default = [
            'value' => null,
            'label' => 'Select a Product'
        ];
        $data[] = $default;
        if (in_array('woocommerce/woocommerce.php', WPFNL_ACTIVE_PLUGINS)) {
            $all_ids = get_posts([
                'post_type' => 'product',
                'numberposts' => -1,
                'post_status' => 'publish',
                'fields' => 'ids',
            ]);
            foreach ($all_ids as $id) {
                $product = wc_get_product($id);
                if( $product ){
                    $type = $product->get_type();
                    if ($type == 'variable') {
                        $variations = $product->get_available_variations();
                        if( !empty($variations) ){
                            foreach ($variations as $variation) {
                                $product = wc_get_product($variation['variation_id']);
                                if( $product ){
                                    $value = $variation['variation_id'];
                                    $label = $product->get_name();
                                    $result = [
                                        'value' => $value,
                                        'label' => $label,
                                    ];
                                    $data[] = $result;
                                } 
                            }
                        }
                    } else {
                        $value = $id;
                        $label = $product->get_name();
                        $result = [
                            'value' => $value,
                            'label' => $label,
                        ];
                        $data[] = $result;
                    }
                }
            }
        }
        return $data;
    }


	/**
	 * Get order bump settings
	 *
	 * @param WP_REST_Request $request
     * 
	 * @return mixed
	 * @throws \Exception
	 */
    public function get_setting(WP_REST_Request $request)
    {
        
        $step_id 	= $request['step_id'];
        $all_settings = get_post_meta($step_id, 'order-bump-settings', true) ? get_post_meta($step_id, 'order-bump-settings', true) : array();
        $is_multiple = Wpfnl_functions::check_array_is_multidimentional( $all_settings );

        if( !$is_multiple && $all_settings ){
            $all_settings['name'] = 'Order bump';
            $all_settings = Wpfnl_functions::migrate_order_bump( $all_settings , $step_id );
        }

        if( is_array( $all_settings ) && count($all_settings) > 0 ) {
            $funnel_id = get_post_meta($step_id,'_funnel_id',true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
            $type = !$type ? 'wc' : $type;
            $class_object = Wpfnl_Controller_Type_Factory::build($type);
            if( $class_object ){
                $all_settings = $class_object->get_ob_settings( $all_settings );
            }
        }

        foreach($all_settings as $key=>$settings){
            if( !isset($settings['obTitleColor']) || !$settings['obTitleColor'] ){
                $all_settings[$key]['obTitleColor'] = '#363B4E';
            }
            if( !isset($settings['obHighlightColor']) || !$settings['obHighlightColor'] ){
                $all_settings[$key]['obHighlightColor'] = '#6E42D3';
            }
            if( !isset($settings['obCheckboxTitleColor']) || !$settings['obCheckboxTitleColor'] ){
                $all_settings[$key]['obCheckboxTitleColor'] = '#d9d9d9';
            }
            if( !isset($settings['obDescriptionColor']) || !$settings['obDescriptionColor'] ){
                $all_settings[$key]['obDescriptionColor'] = '#7A8B9A';
            }
            
            if( !isset($settings['replaceSettings']) || !$settings['replaceSettings'] ){
                $all_settings[$key]['replaceSettings'] = [
                    'isAllReplace' => 'yes',
                    'selectedProducts' => [],
                ];
            }
            
            if( !isset($settings['prePurchaseUpsell']) || !$settings['prePurchaseUpsell'] ){
                $all_settings[$key]['prePurchaseUpsell'] = 'no';
            }

            if( !empty($settings['product']) ){
                $product = wc_get_product($settings['product']);
                if( $product ){
                    $all_settings[$key]['numericSalePrice'] = wc_price(floatval($product->get_sale_price()) * intval($settings['quantity']));
                    $all_settings[$key]['numericRegularPrice'] = wc_price(floatval($product->get_regular_price()) * intval($settings['quantity']));
                }
            }
        }
       
        $response['success']     = true;
        $response['data']        = $all_settings;
        $response['ob_position'] = $step_id ? Wpfnl_functions::supported_orderbump_position( $step_id ) : [];
        return rest_ensure_response($response);
        
    }



    /**
     * Update a single setting in a group.
     *
     * @param WP_REST_Request $request Request data.
     * 
     * @return WP_Error|WP_REST_Response
     * @since  3.0.0
     */
    public function update_settings( $request )
    {
       

        $all_settings_value = $request->get_params();
        $response['success'] = false;
        if (isset($all_settings_value['value'])) {
            $all_settings = $all_settings_value['value'];

            $funnel_id = get_post_meta($all_settings_value['stepID'],'_funnel_id',true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
            $type = !$type ? 'wc' : $type;
            $class_object = Wpfnl_Controller_Type_Factory::build($type);
            if( $class_object ){
                $all_settings = $class_object->update_ob_settings( $all_settings );
            }
            $response = array();
            $step_id = $all_settings_value['stepID'];
            update_post_meta($step_id, 'order-bump-settings', $all_settings);
            // update_post_meta($step_id, 'order-bump', $settings['isEnabled']);
            do_action( 'wpfunnels/after_save_order_bump_data', $step_id, $all_settings );
            $response['success'] = true;

        }


        return rest_ensure_response($response);
    }


	/**
	 * Calculate Discount Price.
	 *
	 * @param $discount_type
	 * @param $discount_value
	 * @param $product_price
	 * @param string $apply_to
     * 
	 * @return string
	 */
	public function calculate_custom_price( $discount_type, $discount_value, $product_price, $apply_to = 'regular' )
	{
		$custom_price = $product_price;
		if (!empty($discount_type)) {
			if ('discount-percentage' === $discount_type) {
				if ($discount_value > 0) {
					$custom_price = $product_price - ( ( $product_price * $discount_value ) / 100);
				}
			} elseif ('discount-price' === $discount_type) {
				if ($discount_value < $product_price) {
					$custom_price = $product_price - $discount_value;
				}else{
                    $custom_price = $product_price;
                }
			}
		}

		return number_format((float)$custom_price, 2);
	}


    /**
     * Update orderbump status by index and step Id
     *
     * @param WP_REST_Request $request Request data.
     * 
     * @return WP_Error|WP_REST_Response
     */
    public function update_status( $request ){
        $payload = $request->get_params();
        $response['success'] = false;
        if( isset($payload['step_id'],$payload['index'],$payload['data']) ){
            $step_id = $payload['step_id'];
            $index = $payload['index'];
            $data = $payload['data'];
            
            if( $step_id && isset($settings[$index]['isEnabled']) ){
                $settings = get_post_meta($step_id, 'order-bump-settings', true);
                $settings[$index]['isEnabled'] = $data == 'true' ? true : false;
                update_post_meta($step_id, 'order-bump-settings', $settings);
                $response['success'] = true;
            }
            
        }
        return rest_ensure_response($response);
    }
}
