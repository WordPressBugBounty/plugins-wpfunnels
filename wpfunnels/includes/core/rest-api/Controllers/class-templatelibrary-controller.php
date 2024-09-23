<?php
/**
 * Template library controller
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;

/**
 * Class TemplateLibraryController
 * This class represents the controller for the template library in the WP Funnels plugin
 * It extends the Wpfnl_REST_Controller class
 * 
 * @package WPFunnels\Rest\Controllers
 * @since 	1.0.0
 */
class TemplateLibraryController extends Wpfnl_REST_Controller {

	/**
	 * The URL for the WP Funnels API.
	 *
	 * This variable holds the URL for the WP Funnels API, which is used to retrieve templates.
	 *
	 * @var string
	 */
	public static $funnel_api_url = 'https://templates.getwpfunnels.com/wp-json/wp/v2/wpfunnels/';

	/**
	 * The URL for the WP Funnels API.
	 *
	 * This variable holds the URL for the WP Funnels API, which is used to retrieve templates.
	 *
	 * @var string
	 */
	public static $funnel_categories_api_url = 'https://templates.getwpfunnels.com/wp-json/wp/v2/template_industries/';

	/**
	 * The URL for the WP Funnels API.
	 *
	 * This variable holds the URL for the WP Funnels API, which is used to retrieve templates.
	 *
	 * @var string
	 */
	public static $funnel_steps_api_url = 'https://templates.getwpfunnels.com/wp-json/wp/v2/wpfunnel_steps/';

	/**
	 * The URL for the WP Funnels API.
	 *
	 * This variable holds the URL for the WP Funnels API, which is used to retrieve templates.
	 *
	 * @var string
	 */
	public static $all_funnels_api_url = 'https://templates.getwpfunnels.com/wp-json/wpfunnels/v1/get_all_funnels/';


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
	protected $rest_base = 'templates/';


	/**
	 * Get remote funnel api url
	 *
	 * @return string
	 * @since  2.2.8
	 */
	private static function get_remote_funnel_api_url() {
		if ( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnels/';
		} elseif ( 'bricks' === Wpfnl_functions::get_builder_type() ) {
			return 'https://brickstemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnels/';
		}
		return self::$funnel_api_url;
	}


	/**
	 * Get remote funnel builders categories url
	 *
	 * @return string
	 *
	 * @since 2.2.8
	 */
	private static function get_remote_funnel_categories_api_url() {
		if ( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/template_industries/';
		} elseif ( 'bricks' === Wpfnl_functions::get_builder_type() ) {
			return 'https://brickstemplates.getwpfunnels.com/wp-json/wp/v2/template_industries/';
		}
		return self::$funnel_categories_api_url;
	}


	/**
	 * Get remote funnel steps categories url
	 *
	 * @return string
	 *
	 * @since 2.2.8
	 */
	private static function get_remote_funnel_steps_api_url() {
		if ( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnel_steps/';
		} elseif ( 'bricks' === Wpfnl_functions::get_builder_type() ) {
			return 'https://brickstemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnel_steps/';
		}
		return self::$funnel_steps_api_url;
	}


	/**
	 * Get all templates API url
	 *
	 * @return string
	 * @since  2.2.8
	 */
	private static function get_all_templates_api_url() {
		if ( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wpfunnels/v1/get_all_funnels/';
		} elseif ( 'bricks' === Wpfnl_functions::get_builder_type() ) {
			return 'https://brickstemplates.getwpfunnels.com/wp-json/wpfunnels/v1/get_all_funnels/';
		}
		return self::$all_funnels_api_url;
	}


	/**
	 * Check user permission
	 *
	 * @param $request
	 *
	 * @return bool|WP_Error
	 */
	public function update_items_permissions_check( $request ) {
		$permission = current_user_can( 'wpf_manage_funnels' );
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'templates' ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
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
	public function get_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'templates' ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}



	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . 'get_templates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . 'get_template_type_id',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_template_type_id' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),
			)
		);

	}


	/**
	 * Prepare a single setting object for response.
	 *
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_templates( $request ) {
		$funnel_template_type = isset( $_GET['type'] ) ? $_GET['type'] : 'wc';
		$step                 = isset( $_GET['step'] ) ? $_GET['step'] : false;
		$templates            = $this->get_funnels_data( $funnel_template_type, $step, array(), false );
		$templates			  = $this->prepare_custom_step($templates);
		$templates['success'] = true;
		return $this->prepare_item_for_response( $templates, $request );
	}


	/**
	 * Prepares custom steps.
	 * Allow user to create custom step from landing page.
	 * 
	 * @param array $templates The templates to prepare.
	 * @return array
	 * 
	 * @since  3.4.15
	 */
	public function prepare_custom_step($templates){
		if( is_array( $templates ) ){
			if( isset( $templates['steps'] ) && is_array( $templates['steps'] ) ){
				foreach( $templates['steps'] as $key => $step ){
					if( is_array( $step ) && isset( $step['step_type'] ) && 'landing' === $step['step_type'] ){
						$custom_step = $step;
						$custom_step['step_type'] = 'custom';
						if( isset( $custom_step['title'] ) ){
							$custom_step['title'] = str_ireplace('Landing', '', $custom_step['title']);
						}
						$templates['steps'][] = $custom_step;
					}
				}
			}
		}
		return $templates;
	}



	/**
	 * Send http request
	 *
	 * @param $url
	 * @param $args
	 *
	 * @return array
	 */
	public static function remote_get( $url, $args ) {
		$response = wp_remote_get( $url, $args );
		// bail if there is an error
		if ( is_wp_error( $response ) || ! is_array( $response ) || ! isset( $response['body'] ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
				'data'    => $response,
			);
		}

		// Decode the results.
		$results = json_decode( $response['body'], true );

		// bail if there is no result found
		if ( ! is_array( $results ) ) {
			return new \WP_Error( 'unexpected_data_format', 'Data was not returned in the expected format.' );
		}

		return array(
			'success' => true,
			'message' => 'Data successfully retrieved',
			'data'    => json_decode( wp_remote_retrieve_body( $response ), true ),
		);
	}


	/**
	 * Get all funnels of the specific builder
	 *
	 * @param bool  $type
	 * @param bool  $isStep
	 * @param array $args
	 * @param bool  $force_update
	 *
	 * @return bool|mixed|void
	 */
	public static function get_funnels( $type = false, $isStep = false, $args = array(), $force_update = false ) {
		$builder_type = Wpfnl_functions::get_builder_type();
		$cache_key    = 'wpfunnels_remote_template_data_' . $type . '_' . WPFNL_VERSION;
		$data         = get_transient( $cache_key );
		if ( $data ) {
			return;
		}
		if ( $type && ( $force_update || false === $data ) ) {
			if ( false === $data ) {
				$data = array();
			}
			$timeout = ( $force_update ) ? 40 : 55;
			// get all templates
			$params = array(
				'per_page'      => 100,
				'offset'        => 0,
				'builder'       => $builder_type,
				'template_type' => $type,
				'template_url'  => self::get_all_templates_api_url(),
			);
			if( !defined('QUBELY_VERSION') ){
				$params['gutenberg_type'] = 'wpf_native_gutenberg';
			}
			$url    = add_query_arg( $params, self::get_all_templates_api_url() );

			$template_data = self::remote_get(
				$url,
				array(
					'timeout' => $timeout,
				)
			);
			
			if ( ! is_array( $template_data ) || ! $template_data['success'] ) {
				$url    = add_query_arg( $params, WPFNL_MIDDLEMAN_TEMPLATE_URL );
				$template_data = self::remote_get(
					$url,
					array(
						'timeout' => $timeout,
					)
				);

				if ( ! is_array( $template_data ) || ! $template_data['success'] ) {
					set_transient( $cache_key, array(), 24 * HOUR_IN_SECONDS );
					return false;
				}
			}
			
			// get all steps
			$steps = array();
			$templates = array();
			if ( $template_data['data'] ) {
				foreach ( $template_data['data'] as $key => $template ) {
					$i              = 0;
					$thankyou_count = 0;
					if( isset($template['page_builder']) && 'gutenberg' === $template['page_builder'] ){
						if( !defined('QUBELY_VERSION') && 'wpf_native_gutenberg' !== $template['gutenberg_type'] ){
							continue;
						}
					}
					
					foreach ( $template['steps'] as $_step ) {
						if ( $thankyou_count && 'thankyou' === $_step['step_type'] ) {
							continue;
						}
						if ( 0 == $i ) {
							$template_data['data'][ $key ]['link'] = $_step['link'];
						}
						$_step['funnel_name']   = $template['title'];
						$_step['template_type'] = isset( $template['templateType'] ) ? ( 'free' === $template['templateType'] ? 'free' : 'pro' ) : 'free';
						$steps[]                = $_step;
						if ( 'thankyou' === $_step['step_type'] ) {
							$thankyou_count++;
						}
						$i++;
					}
					array_push($templates, $template_data['data'][ $key ]);
				}
			}

			// fetch the funnel categories from the remote server
			$params          = array(
				'per_page' => 100,
				'category_url' => self::get_remote_funnel_categories_api_url(),
			);

			$url             = add_query_arg( $params, self::get_remote_funnel_categories_api_url() );
			$categories_data = self::remote_get(
				$url,
				array(
					'timeout' => $timeout,
				)
			);
			
			if ( ! is_array( $categories_data ) || ! $categories_data['success'] ) {
				$url    = add_query_arg( $params, WPFNL_MIDDLEMAN_TEMPLATE_CATEGORY_URL );
				$categories_data = self::remote_get(
					$url,
					array(
						'timeout' => $timeout,
					)
				);
				
				if ( ! is_array( $categories_data ) || ! $categories_data['success'] ) {
					set_transient( $cache_key, array(), 24 * HOUR_IN_SECONDS );
					return false;
				}
			}
			
			$data['templates']  = $templates;
			$data['steps']      = $steps;
			$data['categories'] = $categories_data['data'];
			update_option( WPFNL_TEMPLATES_OPTION_KEY . '_' . $type, $data, 'no' );
			set_transient( $cache_key, $data, 24 * HOUR_IN_SECONDS );
			return false;
		}
	}



	/**
	 * Get funnel templates data
	 *
	 * @param array $args
	 * @param bool  $force_update
	 *
	 * @return array|mixed|void
	 * @since  1.0.0
	 */
	public function get_funnels_data( $type = false, $step = false, $args = array(), $force_update = false ) {

		self::get_funnels( $type, $step, $args, $force_update );
		$template_data = get_option( WPFNL_TEMPLATES_OPTION_KEY . '_' . $type );

		if ( empty( $template_data ) ) {
			return array();
		}
		return $template_data;
	}


	/**
	 * Get step from template library site by step ID
	 *
	 * @param $step_id
	 * @param bool    $force_update
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_step( $step_id, $force_update = false ) {
		$timeout = ( $force_update ) ? 25 : 8;

		$params = array(
			'_fields' => 'id,title,link,slug,featured_media,post_meta,rawData,steps,divi_content,featured_image,steps_order,builder,industry,is_pro,type,step_type,funnel_id,funnel_name,_qubely_css,_qubely_interaction_json,__qubely_available_blocks',
			'url'     => self::get_remote_funnel_steps_api_url() . $step_id,
		);

		$url      = add_query_arg( $params, self::get_remote_funnel_steps_api_url() . $step_id );
		$api_args = array(
			'timeout' => $timeout,
		);
		$response = ( new TemplateLibraryController() )->remote_get( $url, $api_args );

		if ( is_array($response) && isset( $response['success'] ) ) {
			$step = $response['data'];
			return array(
				'title'        => ( isset( $step['title']['rendered'] ) ) ? $step['title']['rendered'] : '',
				'post_meta'    => ( isset( $step['post_meta'] ) ) ? $step['post_meta'] : '',
				'data'         => $step,
				'content'      => isset( $response['data']['content']['rendered'] ) ? $response['data']['content']['rendered'] : '',
				'rawData'      => isset( $response['data']['rawData'] ) ? $response['data']['rawData'] : '',
				'divi_content' => isset( $response['data']['divi_content'] ) ? $response['data']['divi_content'] : '',
				'message'      => $response['message'],
				'success'      => $response['success'],
			);
		}

		$url    = add_query_arg( $params, WPFNL_MIDDLEMAN_STEP_URL );
		$response = ( new TemplateLibraryController() )->remote_get( $url, $api_args );
	
		if ( is_array($response) && isset( $response['success'] ) ) {
			$step = $response['data'];
			return array(
				'title'        => ( isset( $step['title']['rendered'] ) ) ? $step['title']['rendered'] : '',
				'post_meta'    => ( isset( $step['post_meta'] ) ) ? $step['post_meta'] : '',
				'data'         => $step,
				'content'      => isset( $response['data']['content']['rendered'] ) ? $response['data']['content']['rendered'] : '',
				'rawData'      => isset( $response['data']['rawData'] ) ? $response['data']['rawData'] : '',
				'divi_content' => isset( $response['data']['divi_content'] ) ? $response['data']['divi_content'] : '',
				'message'      => $response['message'],
				'success'      => $response['success'],
			);
		}


		return array(
			'title'     => '',
			'post_meta' => array(),
			'message'   => $response['message'],
			'data'      => $response['data'],
			'success'   => $response['success'],
			'content'   => '',
		);
	}




	public static function get_funnel( $funnel_id, $force_update = false ) {
		$timeout = ( $force_update ) ? 25 : 8;
		$params  = array(
			'_fields' => 'id,title,link,slug,featured_media,funnel_data,_funnel_data',
			'url' 	  => self::get_remote_funnel_api_url().$funnel_id,
		);

		$url = add_query_arg( $params, self::get_remote_funnel_api_url() . $funnel_id );

		$api_args = array(
			'timeout' => $timeout,
		);
		$response = ( new TemplateLibraryController() )->remote_get( $url, $api_args );
		if (is_array($response) && isset( $response['success'] )) {
			$funnel = $response['data'];
			return array(
				'funnel_id'    => $funnel_id,
				'funnel_data'  => $funnel['funnel_data'] ?? '',
				'_funnel_data' => $funnel['_funnel_data'] ?? '',
				'title'        => ( isset( $funnel['title']['rendered'] ) ) ? $funnel['title']['rendered'] : '',
				'success'      => $response['success'],
			);
		}

		$url    = add_query_arg( $params, WPFNL_MIDDLEMAN_SINGLE_TEMPLATE_URL );
		$response = ( new TemplateLibraryController() )->remote_get( $url, $api_args );
		
		if ( is_array($response) && isset( $response['success'] ) ) {
			$funnel = $response['data'];
			return array(
				'funnel_id'    => $funnel_id,
				'funnel_data'  => $funnel['funnel_data'] ?? '',
				'_funnel_data' => $funnel['_funnel_data'] ?? '',
				'title'        => ( isset( $funnel['title']['rendered'] ) ) ? $funnel['title']['rendered'] : '',
				'success'      => $response['success'],
			);
		}

		return array(
			'title'     => '',
			'post_meta' => array(),
			'message'   => $response['message'],
			'data'      => $response['data'],
			'success'   => $response['success'],
			'content'   => '',
		);
	}


	/**
	 * Get funnel type id
	 */
	public function get_template_type_id( $request ) {

		$response = array(
			'success' => false,
			'type_id' => '',
		);

		if ( $request['type'] ) {
			$type             = $request['type'];
			$force_update     = false;
			$timeout          = ( $force_update ) ? 40 : 55;
			$teplate_type_url = self::get_remote_funnel_template_type_api_url();
			$response         = self::remote_get(
				$teplate_type_url,
				array(
					'timeout' => $timeout,
				)
			);

			if ( isset( $response['data'] ) && is_array( $response['data'] ) && !empty($response['data'])) {
				foreach ( $response['data'] as $types ) {
					if ( $type === $types['name'] ) {
						$response['success'] = true;
						$response['type_id'] = $types['id'];
					}
				}
			}

			$params  = array(
				'url' 	  => self::get_remote_funnel_template_type_api_url()
			);
			
			$url    = add_query_arg( $params, WPFNL_MIDDLEMAN_TEMPLATE_TYPE_URL );
			$response = ( new TemplateLibraryController() )->remote_get( $url , array(
				'timeout' => $timeout,
			) );

			if ( isset( $response['data'] ) && is_array( $response['data'] ) && !empty($response['data'])) {
				foreach ( $response['data'] as $types ) {
					if ( $type === $types['name'] ) {
						$response['success'] = true;
						$response['type_id'] = $types['id'];
					}
				}
			}


		}
		return rest_ensure_response( $response );
	}


	/**
	 * Prepare a single setting object for response.
	 *
	 * @param object          $item Setting object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $response Response data.
	 * @since  1.0.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = $this->add_additional_fields_to_object( $item, $request );
		return rest_ensure_response( $data );
	}
}
