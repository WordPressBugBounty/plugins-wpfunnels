<?php

/**
 * Ajax handler
 *
 * @package
 */

namespace WPFunnels\Ajax_Handler;

use Elementor\Plugin;
use Elementor\Utils;
use WPFunnels\Frontend\Recaptcha\Google_Recaptcha_Handler;
use WPFunnels\Optin\Optin_Record;
use WPFunnels\Wpfnl_functions;
use function Mollie\WooCommerce\errorNotice;
use WPFunnels\Conditions\Wpfnl_Condition_Checker;

/**
 * Class Ajax_Handler
 *
 * @package WPFunnels\Widgets\Elementor
 */
class Ajax_Handler {

	/**
	 * Initializes AJAX actions and filters for handling different types of form submissions.
	 * Sets up AJAX hooks for various form submission scenarios, including shortcode and Gutenberg submissions.
	 * Also hooks into the 'mailmint_after_form_submit_response' filter for processing form submissions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpfnl_optin_submission', [ $this, 'optin_form_submission' ] );
		add_action( 'wp_ajax_nopriv_wpfnl_optin_submission', [ $this, 'optin_form_submission' ] );

		add_action( 'wp_ajax_wpfnl_shortcode_optin_submission', [ $this, 'wpfnl_shortcode_optin_submission' ] );
		add_action( 'wp_ajax_nopriv_wpfnl_shortcode_optin_submission', [ $this, 'wpfnl_shortcode_optin_submission' ] );

		add_action( 'wp_ajax_wpfnl_gutenberg_optin_submission', [ $this, 'gutenberg_optin_form_submission' ] );
		add_action( 'wp_ajax_nopriv_wpfnl_gutenberg_optin_submission', [ $this, 'gutenberg_optin_form_submission' ] );

	}


	/**
	 * Optin form submission handler for elementor
	 *
	 * @return void
	 * @since  2.5.7
	 */
	public function optin_form_submission() {
		// check_ajax_referer( 'optin_form_nonce', 'security' );

		$step_id  = isset( $_POST[ 'step_id' ] ) ? $_POST[ 'step_id' ] : '';
		$postData = isset( $_POST[ 'postData' ] ) ? $_POST[ 'postData' ] : '';

		$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
		$post_data = array();
		wp_parse_str( $postData, $post_data );

		if ( isset( $post_data[ 'email' ] ) ) {
			$post_data[ 'email' ] = strtolower( $post_data[ 'email' ] );
		}

		$url   = isset( $_POST[ 'url' ] ) ? $_POST[ 'url' ] : '';
		$parts = parse_url( $url );
		$query = [];
		if ( isset( $parts[ 'query' ] ) ) {
			parse_str( $parts[ 'query' ], $query );
		}

		$elementor = Plugin::instance();
		$document  = $elementor->documents->get( $step_id );
		$form      = null;
		if ( $document ) {
			$form = Utils::find_element_recursive( $document->get_elements_data(), $post_data[ 'form_id' ] );
		}

		if ( empty( $form ) ) {
			$results = array(
				'message' => 'invalid_form',
				'success' => false
			);
			echo json_encode( $results );
			die();
		}

		$widget             = $elementor->elements_manager->create_element_instance( $form );
		$form[ 'settings' ] = $widget->get_settings_for_display();
		unset( $post_data[ 'post_id' ] );
		unset( $post_data[ 'form_id' ] );

		$record = new Optin_Record( $post_data, $form );
		$fields = $record->get_fields();
		$name   = '';
		if ( $fields ) {
			foreach( $fields as $key => $value ) {
				if ( 'email' === $key ) {
					$name = strstr( $value, '@', true );;
				}
				elseif ( 'last_name' === $key ) {
					$name = $value;
				}
				elseif ( 'first_name' === $key ) {
					$name = $value;
				}
			}
		}

		$response = array(
			'success'           => true,
			'post_action'       => 'notification',
			'notification_text' => '',
			'redirect_url'      => '#'
		);

		$post_action                     = $form[ 'settings' ][ 'post_action' ];
		$response[ 'post_action' ]       = $post_action;
		$action_type                     = '';
		$response[ 'notification_text' ] = isset( $form[ 'settings' ][ 'notification_text' ] ) && $form[ 'settings' ][ 'notification_text' ] ? $form[ 'settings' ][ 'notification_text' ] : '';
		$next_step                       = [];
		switch( $post_action ) {
			case 'notification':
				$response[ 'redirect' ] = false;
				break;
			case 'redirect_to':
				$action_type            = 'redirect_to_url';
				$response[ 'redirect' ] = true;
				if ( !empty( $form[ 'settings' ][ 'redirect_url' ] ) ) {
					$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), $form[ 'settings' ][ 'redirect_url' ][ 'url' ] );
				}
				else {
					$response[ 'redirect_url' ] = '#';
				}
				break;
			default:
				$action_type                = 'next_step';
				$next_step                  = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
				$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), get_the_permalink( $next_step[ 'step_id' ] ) );
				$response[ 'redirect' ]     = true;
		}

		$admin_email         = isset( $form[ 'settings' ][ 'admin_email' ] ) ? $form[ 'settings' ][ 'admin_email' ] : false;
		$admin_email_subject = isset( $form[ 'settings' ][ 'admin_email_subject' ] ) ? $form[ 'settings' ][ 'admin_email_subject' ] : 'Opt-in form Submission';

		$user_info = $record->form_data;

		/**
		 * User registration
		 */
		if ( !empty( $user_info[ 'optin_allow_registration' ] ) && $user_info[ 'optin_allow_registration' ] == 'yes' ) {

			$this->create_user_optin_allow_registration( $name, $user_info );
		}


		/**
		 * Recaptcha Check
		 */
		$is_recaptcha           = isset( $post_data[ 'wpf-is-recapcha' ] ) ? $post_data[ 'wpf-is-recapcha' ] : 'no';
		$recaptcha_site_key     = isset( $post_data[ 'wpf-optin-g-token' ] ) ? $post_data[ 'wpf-optin-g-token' ] : '';
		$recaptcha_site_secrect = isset( $post_data[ 'wpf-optin-g-secret-key' ] ) ? $post_data[ 'wpf-optin-g-secret-key' ] : '';

		$response = $this->send_mail_after_checking_google_recaptcha_response( $is_recaptcha, $recaptcha_site_key, $recaptcha_site_secrect, $admin_email, $admin_email_subject, $user_info, $response );

		if( \WPFunnels\Integrations\Helper::maybe_enabled() && !empty($form[ 'settings' ]['enable_mm_contact']) && 'yes' === $form[ 'settings' ]['enable_mm_contact'] ){
			$lists = [];
			$tags = [];
			if ( !empty($form[ 'settings' ][ 'mm_lists' ]) ){
				$data = \WPFunnels\Integrations\Helper::get_list_by_id($form[ 'settings' ][ 'mm_lists' ]);
				if( isset($data['id'],$data['title'])){
					$lists = [
						[
							'id' => $data['id'],
							'title' => $data['title']
						]
					];
				}
			}
			if ( !empty($form[ 'settings' ][ 'mm_tags' ]) ){
				$data = \WPFunnels\Integrations\Helper::get_list_by_id($form[ 'settings' ][ 'mm_tags' ]);
				if( isset($data['id'],$data['title'])){
					$tags = [
						[
							'id' => $data['id'],
							'title' => $data['title']
						]
					];
				}
			}

			if ( !empty($form[ 'settings' ][ 'mm_contact_status' ]) ){
				$user_info['status'] = $form[ 'settings' ][ 'mm_contact_status' ];
			}

			$user_info['meta_fields']['phone_number'] = isset( $user_info['phone'] ) ?$user_info['phone'] : '';

			$mail_mint_object = new \WPFunnels\Integrations\MailMint( $user_info, $lists, $tags );
			$mail_mint_object->create_or_update_contact();
			$mail_mint_object->add_contact_to_lists();
			$mail_mint_object->add_contact_to_tags();
		}


		/**
		 * Submit & process form data
		 */
		ob_start();
		do_action( 'wpfunnels/after_optin_submit', $step_id, $post_action, $action_type, $record, $post_data );
		ob_get_clean();

		if ( !empty( $next_step[ 'step_type' ] ) && 'conditional' == $next_step[ 'step_type' ] ) {
			$next_step                  = $this->match_conditional_step( $next_step, $funnel_id, $step_id );
			$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), get_the_permalink( $next_step[ 'step_id' ] ) );
		}

		if ( isset( $next_step[ 'step_type' ] ) && 'thankyou' === $next_step[ 'step_type' ] ) {
			$custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );
			if ( $custom_url ) {
				$response[ 'redirect_url' ] = $custom_url;
			}
		}


		echo json_encode( $response, true );
		die();
	}


	/**
	 * Prepare query param for redirect url
	 *
	 * @param String $name
	 * @param array $query
	 *
	 * @return array
	 * @since  2.5.9
	 */
	private function prepare_query_param( $name, $query ) {
		$query_param = [ 'optin' => true, 'uname' => $name, ];

		$required_keys = [ 'wpfnl-order', 'wpfnl-key', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' ];

		foreach( $required_keys as $key ) {
			if ( !empty( $query[ $key ] ) ) {
				$query_param[ $key ] = $query[ $key ];
			}
		}

		return apply_filters('wpfunnels/update_query_param', $query_param);
	}


	/**
	 * Optin submission handling for shortcode
	 *
	 * @return void
	 * @since  @since 2.5.7
	 */
	public function wpfnl_shortcode_optin_submission( $response = [] ) {
		$post = filter_input_array( INPUT_POST );
		// if ( !wp_verify_nonce( htmlspecialchars( $post[ 'security' ] ?? '' ), 'optin_form_nonce' )
		// 	&& !wp_verify_nonce( htmlspecialchars( $post[ 'wp_nonce' ] ?? '' ), 'wp_rest' ) ) {
		// 	die( '-1' );
		// }

		$return = true;
		if ( empty( $response ) ) {
			$response = [];
			$return   = false;
		}

		$response[ 'success' ]           = true;
		$response[ 'post_action' ]       = 'notification';
		$response[ 'notification_text' ] = '';
		$response[ 'redirect_url' ]      = '#';

		$post_data = htmlspecialchars( $post[ 'postData' ] ?? '' );
		$post_data = empty( $post_data ) ? htmlspecialchars( $post[ 'post_data' ] ?? '' ) : $post_data;
		$post_data = !empty( $post_data ) ? str_replace( 'amp;', '&', $post_data ) : $post_data;
		wp_parse_str( $post_data, $post_data );

		if ( !empty( $post_data ) ) {
			$post_data[ 'email' ] = !empty( $post_data[ 'email' ] ) && is_email( $post_data[ 'email' ] ) ? strtolower( sanitize_email( $post_data[ 'email' ] ) ) : '';
			$parts                = wp_parse_url( filter_input( INPUT_POST, 'url', FILTER_VALIDATE_URL ) );
			$parts_query          = $parts[ 'query' ] ?? '';
			wp_parse_str( $parts_query, $query );
			$step_id                         = $post_data[ 'step_id' ] ?? null;
			if( isset($post_data[ 'post_id' ]) ){
				$step_id 						 = $step_id ? $step_id : $post_data[ 'post_id' ] ?? null;
			}

			$funnel_id                       = $step_id ? Wpfnl_functions::get_funnel_id_from_step( $step_id ) : null;
			$post_action                     = $post_data[ 'post_action' ] ?? 'notification';
			$response[ 'post_action' ]       = $post_action;
			$response[ 'notification_text' ] = $post_data[ 'notification_text' ] ?? '';
			$user_info                       = $this->extract_user_info( $post_data );
			$name                            = $user_info[ 'email' ] ? strstr( $user_info[ 'email' ], '@', true ) : '';
			$query_params                    = $this->prepare_query_param( $name, $query );
			$actions                         = $this->process_post_action( $post_action, $post_data, $response, $query_params, $step_id, $funnel_id );
			$response                        = $actions[ 'response' ] ?? $response;
			$next_step                       = $actions[ 'next_step' ] ?? [];
			$action_type                     = $actions[ 'action_type' ] ?? '';
			/**
			 * Filters the response after opt-in for submission.
			 *
			 * This filter hook allows developers to modify or override the response generated
			 * for an opt-in form submission.
			 *
			 * @param array $response The response generated after opt-in for submission.
			 * @param array $post_data The post data received from the opt-in form submission.
			 * @param array $user_info The user information extracted from the form submission.
			 * @return array Modified response array.
			 *
			 * @since 2.8.2
			 */
			$response          = apply_filters( 'wpfunnels/optin_submission_response', $this->process_recaptcha( $post_data, $response, $user_info ), $funnel_id, $step_id, $next_step, $query_params );
			$record            = new \stdClass();
			$record->form_data = $user_info;

			if ( isset( $next_step[ 'step_type' ] ) && 'thankyou' === $next_step[ 'step_type' ] ) {
				$custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );
				if ( $custom_url ) {
					$response[ 'redirect_url' ] = $custom_url;
				}
			}

			if( \WPFunnels\Integrations\Helper::maybe_enabled() && !empty($post_data['enable_mm_contact']) && 'yes' === $post_data['enable_mm_contact'] ){
				$lists = [];
				$tags = [];
				if ( !empty($post_data[ 'mm_lists' ]) ){
					$data = \WPFunnels\Integrations\Helper::get_list_by_id($post_data[ 'mm_lists' ]);
					if( isset($data['id'],$data['title'])){
						$lists = [
							[
								'id' => $data['id'],
								'title' => $data['title']
							]
						];
					}
				}
				if ( !empty($post_data[ 'mm_tags' ]) ){
					$data = \WPFunnels\Integrations\Helper::get_list_by_id($post_data[ 'mm_tags' ]);
					if( isset($data['id'],$data['title'])){
						$tags = [
							[
								'id' => $data['id'],
								'title' => $data['title']
							]
						];
					}
				}
	
				if ( !empty($post_data[ 'mm_contact_status' ]) ){
					$user_info['status'] = $post_data[ 'mm_contact_status' ];
				}

				$user_info['meta_fields']['phone_number'] = isset( $user_info['phone'] ) ?$user_info['phone'] : '';

				$mail_mint_object = new \WPFunnels\Integrations\MailMint( $user_info, $lists, $tags );
				$mail_mint_object->create_or_update_contact();
				$mail_mint_object->add_contact_to_lists();
				$mail_mint_object->add_contact_to_tags();
			}




			/**
			 * Fires after an opt-in form submission in a funnel step.
			 *
			 * This action hook is triggered after the submission of an opt-in form in a funnel step.
			 * It provides the opportunity to perform additional actions or processes after the opt-in form is submitted.
			 *
			 * @param int $step_id The ID of the funnel step where the opt-in form was submitted.
			 * @param string $post_action The type of action taken after form submission (e.g., 'notification', 'redirect_to', 'next_step').
			 * @param string $action_type The type of action that determined the redirection or next step behavior.
			 * @param object $record An object containing data related to the form submission.
			 * @param array $post_data The post data received from the form submission.
			 *
			 * @since  @since 2.5.7
			 */
			ob_start();
			do_action( 'wpfunnels/after_optin_submit', $step_id, $post_action, $action_type, $record, $post_data );
			ob_get_clean();

			if ( $return ) return $response;

			echo json_encode( $response, true );
		}
		die();
	}

	/**
	 * Extracts user information from the provided post data.
	 *
	 * This function extracts specific user information fields from the given post data.
	 * The function iterates through a predefined list of user fields and extracts
	 * their values from the post data.
	 *
	 * @param array $post_data The post data containing user information.
	 *
	 * @return array The extracted user information as an associative array.
	 * @since 2.8.2
	 */
	private function extract_user_info( $post_data ) {
		$user_info   = [];
		$user_fields = [ 'email', 'last_name', 'first_name', 'phone', 'web-url', 'message', 'data_to_checkout' ];

		// Iterate through user fields and extract their values from post data
		foreach( $user_fields as $key ) {
			if ( !empty( $post_data[ $key ] ) ) {
				$user_info[ $key ] = $post_data[ $key ];
			}
		}

		return $user_info;
	}

	/**
	 * Processes the post action based on the provided data and returns relevant response information.
	 *
	 * This function takes the post action, post data, response, query parameters, step ID, and funnel ID
	 * to determine the appropriate action and response details. It handles different post action cases,
	 * such as 'notification', 'redirect_to', and the default case. It prepares the necessary response data
	 * including whether a redirect is required, the action type, and the next step information.
	 *
	 * @param string $post_action The post action type.
	 * @param array $post_data The post data containing user inputs.
	 * @param array $response The current response data.
	 * @param array $query_params The query parameters to add to the URL.
	 * @param int $step_id The ID of the current step.
	 * @param int $funnel_id The ID of the current funnel.
	 *
	 * @return array An associative array containing the updated response, next step information, and action type.
	 * @since 2.8.2
	 */
	private function process_post_action( $post_action, $post_data, $response, $query_params, $step_id, $funnel_id ) {
		switch( $post_action ) {
			case 'notification':
				$response[ 'redirect' ] = false;
				break;
			case 'redirect_to':
				$response[ 'redirect' ]          = true;
				$response[ 'confirmation_type' ] = 'to_a_custom_url';
				$action_type                     = 'redirect_to_url';
				if ( !empty( $post_data[ 'redirect_url' ] ) ) {
					if ( 'next_step' !== $post_data[ 'redirect_url' ] ) {
						$redirect_url               = sanitize_url( add_query_arg( $query_params, $post_data[ 'redirect_url' ] ) );
						$response[ 'redirect_url' ] = $redirect_url;
						$response[ 'custom_url' ]   = $redirect_url;
						break;
					}
					$action_type = 'next_step';
					$next_step   = $funnel_id ? Wpfnl_functions::get_next_step( $funnel_id, $step_id ) : '';
					if ( $next_step ) {
						$redirect_url               = sanitize_url( add_query_arg( $query_params, get_the_permalink( $next_step[ 'step_id' ] ) ) );
						$response[ 'redirect_url' ] = $redirect_url;
						$response[ 'custom_url' ]   = $redirect_url;
						break;
					}
					$response[ 'redirect_url' ] = '#';
					break;
				}
				$response[ 'redirect_url' ] = '#';
				break;
			default:
				$action_type = 'next_step';
				$next_step   = $funnel_id ? Wpfnl_functions::get_next_step( $funnel_id, $step_id ) : '';

				if ( $next_step ) {
					$redirect_url               = sanitize_url( add_query_arg( $query_params, get_the_permalink( $next_step[ 'step_id' ] ) ) );
					$response[ 'redirect_url' ] = $redirect_url;
					$response[ 'custom_url' ]   = $redirect_url;
				}
				else {
					$response[ 'redirect_url' ] = '#';
				}
				$response[ 'redirect' ]          = true;
				$response[ 'confirmation_type' ] = 'to_a_custom_url';
		}

		return [
			'response'    => $response,
			'next_step'   => $next_step ?? [],
			'action_type' => $action_type ?? ''
		];
	}

	/**
	 * Processes reCAPTCHA and sends an email after checking Google reCAPTCHA response.
	 *
	 * This function takes the post data, response, and user information, and processes reCAPTCHA
	 * by checking the Google reCAPTCHA response. It then sends an email with the provided user information.
	 *
	 * @param array $post_data The post data containing user inputs and reCAPTCHA details.
	 * @param array $response The current response data.
	 * @param array $user_info The user information to include in the email.
	 *
	 * @return array The updated response after processing reCAPTCHA and sending the email.
	 * @since 2.8.2
	 */
	private function process_recaptcha( $post_data, $response, $user_info ) {
		$admin_email            = $post_data[ 'admin_email' ] ?? false;
		$admin_email_subject    = $post_data[ 'admin_email_subject' ] ?? 'Opt-in form Submission';
		$is_recaptcha           = $post_data[ 'wpf-is-recapcha' ] ?? 'false';
		$recaptcha_site_key     = $post_data[ 'wpf-optin-g-token' ] ?? '';
		$recaptcha_site_secrect = $post_data[ 'wpf-optin-g-secret-key' ] ?? '';

		return $this->send_mail_after_checking_google_recaptcha_response( $is_recaptcha, $recaptcha_site_key, $recaptcha_site_secrect, $admin_email, $admin_email_subject, $user_info, $response );
	}

	/**
	 * Gutenberg opt-in form handling
	 *
	 * @return void
	 * @since  2.5.9
	 */
	public function gutenberg_optin_form_submission() {
		check_ajax_referer( 'optin_form_nonce', 'security' );
		$step_id   = isset( $_POST[ 'step_id' ] ) ? $_POST[ 'step_id' ] : '';
		$postData  = isset( $_POST[ 'postData' ] ) ? $_POST[ 'postData' ] : '';
		$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
		$post_data = array();
		parse_str( $postData, $post_data );

		if ( isset( $post_data[ 'email' ] ) ) {
			$post_data[ 'email' ] = strtolower( $post_data[ 'email' ] );
		}

		$post_id    = $step_id;
		$post       = get_post( $post_id );
		$all_blocks = parse_blocks( $post->post_content );

		$url   = isset( $_POST[ 'url' ] ) ? $_POST[ 'url' ] : '';
		$parts = parse_url( $url );
		$query = [];
		if ( isset( $parts[ 'query' ] ) ) {
			parse_str( $parts[ 'query' ], $query );
		}

		$admin_email         = '';
		$admin_email_subject = '';
		$post_action         = '';
		$block_attr          = array();

		$record = new Optin_Record( $post_data );
		$fields = $record->get_fields();
		$name   = '';
		if ( $fields ) {
			foreach( $fields as $key => $value ) {
				if ( 'email' === $key ) {
					$name = strstr( $value, '@', true );
				}
				elseif ( 'last_name' === $key ) {
					$name = $value;
				}
				elseif ( 'first_name' === $key ) {
					$name = $value;
				}
			}
		}

		$response   = array(
			'success'           => true,
			'post_action'       => 'notification',
			'notification_text' => '',
			'redirect_url'      => '#'
		);
		$custom_url = '';
		$blocks     = $this->search_items_by_key( $all_blocks, 'blockName' );
		foreach( $blocks as $block ) {
			if ( $block[ 'blockName' ] == 'wpfunnels/optin-form' ) {
				$block_attr = $block[ 'attrs' ];

				$admin_email         = isset( $block_attr[ 'adminEmail' ] ) ? $block_attr[ 'adminEmail' ] : '';
				$admin_email_subject = isset( $block_attr[ 'emailSubject' ] ) ? $block_attr[ 'emailSubject' ] : 'Opt-in form Submission';

				$post_action = isset( $block_attr[ 'postAction' ] ) ? $block_attr[ 'postAction' ] : 'notification';
				$custom_url  = isset( $block_attr[ 'redirect_url' ] ) ? $block_attr[ 'redirect_url' ] : '#';
			}
		}

		$response[ 'notification_text' ] = isset( $block_attr[ 'notification' ] ) ? $block_attr[ 'notification' ] : '';
		$next_step                       = [];
		switch( $post_action ) {
			case 'notification':
				$action_type            = 'notification';
				$response[ 'redirect' ] = false;
				break;
			case 'redirect_to':
				$action_type               = 'redirect_to_url';
				$response[ 'redirect' ]    = true;
				$response[ 'post_action' ] = $post_action;
				if ( $custom_url ) {
					$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), $custom_url );
				}
				else {
					$response[ 'redirect_url' ] = '#';
				}
				break;
			case 'next_step':
				$response[ 'post_action' ] = $post_action;
				$action_type               = 'next_step';
				$next_step                 = Wpfnl_functions::get_next_step( $funnel_id, $step_id );

				$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), get_the_permalink( $next_step[ 'step_id' ] ) );
				$response[ 'redirect' ]     = true;
				break;
			default:
				$action_type                = 'next_step';
				$next_step                  = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
				$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), get_the_permalink( $next_step[ 'step_id' ] ) );
				$response[ 'redirect' ]     = true;
		}
		/**
		 * User registration
		 */
		if ( !empty( $post_data[ 'optin_allow_registration' ] ) && $post_data[ 'optin_allow_registration' ] == 'yes' ) {

			$this->create_user_optin_allow_registration( $name, $post_data );
		}
		/**
		 * Check google Recaptcha V3
		 */
		$is_recaptcha           = isset( $post_data[ 'wpf-is-recapcha' ] ) ? $post_data[ 'wpf-is-recapcha' ] : 'false';
		$recaptcha_site_key     = isset( $post_data[ 'wpf-optin-g-token' ] ) ? $post_data[ 'wpf-optin-g-token' ] : '';
		$recaptcha_site_secrect = isset( $post_data[ 'wpf-optin-g-secret-key' ] ) ? $post_data[ 'wpf-optin-g-secret-key' ] : '';

		$response = $this->send_mail_after_checking_google_recaptcha_response( $is_recaptcha, $recaptcha_site_key, $recaptcha_site_secrect, $admin_email, $admin_email_subject, $post_data, $response );

		
		$user_data = [
			'first_name' => $post_data['first_name'] ?? '',
			'last_name' => $post_data['last_name'] ?? '',
			'email' => $post_data['email'] ?? '',
			'phone' => $post_data['phone'] ?? '',
			'web-url' => $post_data['web-url'] ?? '',
		];
		
		if( \WPFunnels\Integrations\Helper::maybe_enabled() && !empty($post_data['enable_mm_contact']) && 'yes' === $post_data['enable_mm_contact'] ){
			
			$lists = [];
			$tags = [];
			if ( !empty($post_data[ 'mm_lists' ]) ){
				$data = \WPFunnels\Integrations\Helper::get_list_by_id($post_data[ 'mm_lists' ]);
				if( isset($data['id'],$data['title'])){
					$lists = [
						[
							'id' => $data['id'],
							'title' => $data['title']
						]
					];
				}
			}
			if ( !empty($post_data[ 'mm_tags' ]) ){
				$data = \WPFunnels\Integrations\Helper::get_list_by_id($post_data[ 'mm_tags' ]);
				if( isset($data['id'],$data['title'])){
					$tags = [
						[
							'id' => $data['id'],
							'title' => $data['title']
						]
					];
				}
			}

			if ( !empty($post_data[ 'mm_contact_status' ]) ){
				$user_data['status'] = $post_data[ 'mm_contact_status' ];
			}
			
			$user_data['meta_fields']['phone_number'] = isset( $user_data['phone'] ) ?$user_data['phone'] : '';

			$mail_mint_object = new \WPFunnels\Integrations\MailMint( $user_data, $lists, $tags );
			$mail_mint_object->create_or_update_contact();
			$mail_mint_object->add_contact_to_lists();
			$mail_mint_object->add_contact_to_tags();
		}

		ob_start();
		do_action( 'wpfunnels/after_optin_submit', $step_id, $post_action, $action_type, $record, $post_data );
		ob_get_clean();

		if ( !empty( $next_step[ 'step_type' ] ) && 'conditional' == $next_step[ 'step_type' ] ) {
			$next_step                  = $this->match_conditional_step( $next_step, $funnel_id, $step_id );
			$response[ 'redirect_url' ] = add_query_arg( $this->prepare_query_param( $name, $query ), get_the_permalink( $next_step[ 'step_id' ] ) );
		}

		if ( isset( $next_step[ 'step_type' ] ) && 'thankyou' === $next_step[ 'step_type' ] ) {
			$custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );
			if ( $custom_url ) {
				$response[ 'redirect_url' ] = $custom_url;
			}
		}


		echo json_encode( $response, true );
		die();
	}


	/**
	 * Search array item by key
	 *
	 * @param Array $array
	 * @param String $key
	 *
	 * @return Array
	 * @since  2.5.9
	 */
	private function search_items_by_key( $array, $key ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && key( $array ) == $key )
				if ( $array[ $key ] == 'wpfunnels/optin-form' ) {
					$results[] = $array;
				}

			foreach( $array as $sub_array )
				$results = array_merge( $results, $this->search_items_by_key( $sub_array, $key ) );
		}

		return $results;
	}


	/**
	 * Send email to admin
	 *
	 * @param String $email
	 * @param String $subject
	 * @param Array $user_info
	 *
	 * @return void
	 * @since  2.5.9
	 */
	private function send_email_to_admin( $emails = '', $subject = '', $user_info = [] ) {
		$email_array = explode( ",", $emails );
		if ( is_array( $email_array ) ) {
			foreach( $email_array as $email ) {

				if ( $email ) {
					$email        = trim( $email );
					$current_date = date( "d M Y" );
					$time         = new \DateTimeImmutable( 'now', wp_timezone() );
					$current_time = $time->format( "h:i A" );
					$poweredBy    = __( 'WPFunnels', 'wpfnl' );
					$info         = '';
					if ( isset( $user_info[ 'first_name' ] ) && $user_info[ 'first_name' ] ) {
						$info .= "First Name : {$user_info['first_name']}<br>";
					}
					if ( isset( $user_info[ 'last_name' ] ) && $user_info[ 'last_name' ] ) {
						$info .= "Last Name : {$user_info['last_name']}<br>";
					}
					if ( isset( $user_info[ 'email' ] ) && $user_info[ 'email' ] ) {
						$info .= "Email : {$user_info['email']}<br><br>";
					}
					if ( isset( $user_info[ 'phone' ] ) && $user_info[ 'phone' ] ) {
						$info .= "Phone : {$user_info['phone']}<br><br>";
					}
					if ( isset( $user_info[ 'web-url' ] ) && $user_info[ 'web-url' ] ) {
						$esc_url = esc_url( $user_info[ 'web-url' ] );
						$info    .= "Website URL : {$esc_url}<br><br>";
					}
					if ( isset( $user_info[ 'message' ] ) && $user_info[ 'message' ] ) {
						$esc_html = esc_html( $user_info[ 'message' ] );
						$info     .= "Message : {$esc_html}<br><br>";
					}
					$info .= "----<br><br>Date : {$current_date} <br>Time : {$current_time} <br>Powered by : {$poweredBy} <br>";

					$email_body = $info;
					if ( !$subject ) {
						$subject = 'New Submission';
					}
					$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
					wp_mail( $email, $subject, $email_body, $headers );
				}
			}
		}
	}


	/**
	 * Create User as a subscriber
	 * When allow the subscriber option for registration from builder
	 *
	 * @param $name
	 * @param $post_data
	 *
	 * @since 2.5.9
	 */
	public static function create_user_optin_allow_registration( $name, $post_data ) {
		$exist_user_by_username = get_user_by( 'login', $name );
		$exist_user_by_email    = get_user_by( 'email', $post_data[ 'email' ] );

		if ( empty( $exist_user_by_email ) || empty( $exist_user_by_username ) ) {
			$data = array(
				'user_login' => $name,
				'user_email' => isset( $post_data[ 'email' ] ) ? $post_data[ 'email' ] : '',
				'first_name' => isset( $post_data[ 'first_name' ] ) ? $post_data[ 'first_name' ] : '',
				'last_name'  => isset( $post_data[ 'last_name' ] ) ? $post_data[ 'last_name' ] : '',
				'user_pass'  => wp_generate_password( 6, false ),
				'role'       => 'subscriber',
			);
			if ( isset( $post_data[ 'web-url' ] ) ) {
				$data[ 'user_url' ] = $post_data[ 'web-url' ];
			}
			$user_id = wp_insert_user( $data );
			if ( isset( $post_data[ 'phone' ] ) ) {
				update_user_meta( $user_id, 'phone', $post_data[ 'phone' ] );
			}
			if ( !is_wp_error( $user_id ) ) {
				wp_send_new_user_notifications( $user_id, $notify = 'both' );
			}
		}
	}


	/**
	 * Check Google recaptcha Response and Send mail
	 *
	 * @param $is_recaptcha
	 * @param $recaptcha_site_key
	 * @param $recaptcha_site_secrect
	 *
	 * @since 2.5.9
	 */

	private function send_mail_after_checking_google_recaptcha_response( $is_recaptcha, $recaptcha_site_key, $recaptcha_site_secrect, $admin_email, $admin_email_subject, $post_data, $response ) {
		$recaptcha_response = [];
		if ( $recaptcha_site_key && $recaptcha_site_secrect && 'on' === $is_recaptcha ) {
			$recaptcha_response = Google_Recaptcha_Handler::get_response_recaptcha( $recaptcha_site_secrect, $recaptcha_site_key );
		}
		if ( 'on' === $is_recaptcha ) {
			if ( $admin_email && $admin_email_subject && isset( $post_data[ 'email' ] ) && $post_data[ 'email' ] && isset( $recaptcha_response->success ) && $recaptcha_response->success ) {
				$this->send_email_to_admin( $admin_email, $admin_email_subject, $post_data );
			}
			else {
				$response[ 'notification_text' ] = __( "reCAPTCHA is not Correct", 'wpfnl' );
				$response[ 'success' ]           = false;
				return $response;
			}
		}
		else {
			if ( $admin_email && $admin_email_subject && isset( $post_data[ 'email' ] ) && $post_data[ 'email' ] ) {
				$this->send_email_to_admin( $admin_email, $admin_email_subject, $post_data );
			}
		}
		return $response;
	}


	/**
	 * Match ption submit condition
	 *
	 * @param Array $next_step
	 * @param Number $funnel_id
	 *
	 * @return Array $next_step
	 * @since  2.5.7
	 */
	public static function match_conditional_step( $next_step, $funnel_id, $step_id ) {
		$condition            = Wpfnl_Condition_Checker::getInstance();
		$condition_identifier = strval( $next_step[ 'step_id' ] );
		$data[ 'step_id' ]    = $step_id;
		$condition_matched    = $condition->check_condition( $funnel_id, $data, $condition_identifier );
		$next_node            = Wpfnl_functions::get_next_step( $funnel_id, $condition_identifier, $condition_matched );
		$next_node            = apply_filters( 'wpfunnels/next_step_data', $next_node );
		return $next_node;
	}


}
