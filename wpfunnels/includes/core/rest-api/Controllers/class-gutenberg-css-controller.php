<?php
/**
 * Gutenberg CSS controller
 * 
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;

class GutenbergCSSController extends Wpfnl_REST_Controller {

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
	protected $rest_base = 'gutenberg';

	/**
	 * Check if user has valid permission
	 *
	 * @param $request
	 * 
	 * @return bool|WP_Error
	 * @since  1.0.0
	 */
	public function update_items_permissions_check($request) {
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
	public function get_items_permissions_check($request) {
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('steps')) {
			return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot list resources.', 'wpfnl'), array('status' => rest_authorization_required_code()));
		}
		return true;
	}


	/**
	 * Register rest routes
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base ,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'append_gutenberg_css_callback' ),
					'permission_callback' => array( $this, 'can_edit_target_post' ),
					'args'                => array(),
				),
			)
		);

		// For css file save
		register_rest_route(
			$this->namespace,
			'/'.$this->rest_base.'/save_block_css/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_block_css' ),
					'permission_callback' => array( $this, 'can_edit_target_post' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Permission check for both CSS-write routes: `current_user_can('edit_posts')`
	 * alone only proves the caller can edit *some* post type, not this
	 * specific `post_id` — any Contributor could target any published step.
	 * This requires `edit_post` capability on the exact post the request
	 * names.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 * @since 3.13.7
	 */
	public function can_edit_target_post( $request ) {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'wpfunnels_rest_invalid_post', __( 'Invalid post.', 'wpfnl' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Add block css
	 *
	 * @param $request
	 * 
	 * @throws
	 */
	public function append_gutenberg_css_callback( $request ) {
		try {
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$params  = $request->get_params();
			$css     = $this->sanitize_css_content( $params['css'] );
			$post_id = (int) sanitize_text_field( $params['post_id'] );
			if ( $post_id ) {
				$filename   = "wpfnl-gb-css-{$post_id}.css";
				$upload_dir = wp_upload_dir();
				$dir        = trailingslashit( $upload_dir['basedir'] ) . 'wpfunnels/css/';
				if ( file_exists( $dir . $filename ) ) {
					$file = fopen( $dir . $filename, 'a' );
					fwrite( $file, $css );
					fclose( $file );
				}
				$get_data = get_post_meta( $post_id, '_wpfunnels_gb_css', true );
				update_post_meta( $post_id, '_wpfunnels_gb_css', $get_data . $css );

				wp_send_json_success(
					array(
						'success' => true,
						'message' => 'Update done' . $get_data,
					)
				);
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}


	/**
	 * Save block css
	 * Save block css for each post in a css file and enqueue the file to the post page
	 * 
	 * @since 1.0.0
	 * 
	 * @throws \Exception
	 */
	public function save_block_css($request){
		try {
			global $wp_filesystem;
			if (!$wp_filesystem) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$params  = $request->get_params();
			$post_id = (int) sanitize_text_field($params['post_id']);
			$is_previewing = $params['isPreviewing'];

			if ($params['is_remain']) {
				$qubely_block_css = $this->sanitize_css_content($params['block_css']);
				$filename         = "wpfnl-css-{$post_id}.css";

				$qubely_block_json = $this->sanitize_json_content($params['interaction']);
				$jsonfilename      = "wpfnl-json-{$post_id}.json";

				$upload_dir = wp_upload_dir();
				$dir        = trailingslashit($upload_dir['basedir']) . 'wpfunnels/css/';

				// Add Import in first
				$import_first = $this->set_import_url_to_top_css($qubely_block_css);

				if ($is_previewing==true) {
					$filename         = "wpfnl-preview.css";
					$jsonfilename      = "wpfnl-preview.json";
				} else {
					update_post_meta($post_id, '_wpfunnels_gb_css', $import_first);
				}

				WP_Filesystem(false, $upload_dir['basedir'], true);
				if (!$wp_filesystem->is_dir($dir)) {
					wp_mkdir_p( $dir );
				}
				// If fail to save css in directory, then it will show a message to user
				if (!$wp_filesystem->put_contents($dir . $filename, $import_first)) {
					throw new \Exception(__('CSS can not be saved due to permission!!!', 'wpfnl'));
				}

				// If fail to save css in directory, then it will show a message to user
				if (!$wp_filesystem->put_contents($dir . $jsonfilename, $qubely_block_json)) {
					throw new \Exception(__('JSON can not be saved due to permission!!!', 'wpfnl'));
				}
			} else {
				if($is_previewing==false ){
					delete_post_meta($post_id, '_wpfunnels_gb_css');
					$this->delete_post_resource($post_id);
				}
			}

			$success_message = 'WPFunnels preview css file has been updated.';

			return array(
				'success' => true,
				'message' => $success_message,
				'data'    => $params,
			);
		} catch (\Exception $e) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}


	/**
	 * Delete post releated data
	 *
	 * @delete post css file
	 */
	private function delete_post_resource( $post_id = '' ) {
		$post_id = get_the_ID();
		if ( $post_id ) {
			$upload_dir     = wp_get_upload_dir();
			$upload_css_dir = trailingslashit( $upload_dir['basedir'] );
			$css_path       = $upload_css_dir . "wpfunnels/css/wpfnl-css-{$post_id}.css";
			$json_path      = $upload_css_dir . "wpfunnels/css/wpfnl-json-{$post_id}.json";
			if ( file_exists( $css_path ) ) {
				unlink( $css_path );
			}
			if ( file_exists( $json_path ) ) {
				unlink( $json_path );
			}
		}
	}


	/**
	 * Set font import to the top of the CSS file
	 * 
	 * @since 1.0.2
	 */
	public function set_import_url_to_top_css( $get_css = '' ) {
		$css_url            = "@import url('https://fonts.googleapis.com/css?family=";
		$google_font_exists = substr_count( $get_css, $css_url );

		if ( $google_font_exists ) {
			$pattern = sprintf(
				'/%s(.+?)%s/ims',
				preg_quote( $css_url, '/' ),
				preg_quote( "');", '/' )
			);

			if ( preg_match_all( $pattern, $get_css, $matches ) ) {
				$fonts   = $matches[0];
				$get_css = str_replace( $fonts, '', $get_css );
				if ( preg_match_all( '/font-weight[ ]?:[ ]?[\d]{3}[ ]?;/', $get_css, $matche_weight ) ) { // short out font weight
					$weight = array_map(
						function ( $val ) {
							$process = trim( str_replace( array( 'font-weight', ':', ';' ), '', $val ) );
							if ( is_numeric( $process ) ) {
								return $process;
							}
						},
						$matche_weight[0]
					);
					foreach ( $fonts as $key => $val ) {
						$fonts[ $key ] = str_replace( "');", '', $val ) . ':' . implode( ',', $weight ) . "');";
					}
				}

				// Multiple same fonts to single font
				$fonts   = array_unique( $fonts );
				$get_css = implode( '', $fonts ) . $get_css;
			}
		}
		return $get_css;
	}

	/**
	 * Sanitize CSS content to prevent XSS attacks
	 *
	 * The stored value is echoed raw inside a `<style>` tag
	 * (class-manager.php::add_block_inline_css()), so this must guarantee no
	 * `<` or `>` survives — a literal or entity-encoded `</style>` is enough
	 * to break out of the style block and run as markup. Entities are
	 * decoded FIRST so `&lt;/style&gt;` is caught by the same pass that
	 * catches `</style>`, then every `<`/`>` is stripped outright: CSS never
	 * needs them, so this can't legitimately break real stylesheets.
	 *
	 * @param string $css The CSS content to sanitize
	 * @return string Sanitized CSS content
	 * @since 3.5.27
	 */
	public function sanitize_css_content($css){
		if (!is_string($css)) {
			return '';
		}

		// Decode entities first so an encoded breakout (`&lt;/style&gt;`,
		// `&lt;script&gt;`) is sanitized the same as a literal one, instead
		// of surviving this function and only decoding back to life later.
		$css = html_entity_decode($css, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// Remove potentially dangerous CSS functions and properties
		$dangerous_patterns = array(
			'/javascript\s*:/i',           // Remove javascript: URLs
			'/expression\s*\(/i',          // Remove CSS expressions
			'/behavior\s*:/i',             // Remove IE behavior property
			'/@import\s+(?!url\()/i',      // Remove @import except url() imports
			'/binding\s*:/i',              // Remove binding property
			'/-moz-binding\s*:/i',         // Remove -moz-binding
			'/data\s*:\s*text\/html/i',    // Remove data:text/html
			'/vbscript\s*:/i',             // Remove vbscript: URLs
			'/on\w+\s*=/i',                // Remove event handlers
		);

		// Apply sanitization patterns
		$sanitized_css = preg_replace($dangerous_patterns, '', $css);

		// Strip any HTML/XML tags, then every remaining angle bracket, so no
		// `<style`/`<script`/`</style>` variant — tagged or bare — can survive
		// to be echoed into the page's `<style>` block.
		$sanitized_css = strip_tags($sanitized_css);
		$sanitized_css = str_replace(array('<', '>'), '', $sanitized_css);

		return $sanitized_css;
	}

	/**
	 * Sanitize JSON content to prevent XSS attacks
	 * 
	 * @param mixed $json The JSON content to sanitize
	 * @return string Sanitized JSON content
	 * @since 3.5.27
	 */
	private function sanitize_json_content($json){
		if (is_string($json)) {
			// Decode JSON to validate structure
			$decoded = json_decode($json, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				// Recursively sanitize the decoded data
				$sanitized = $this->sanitize_json_data($decoded);
				return wp_json_encode($sanitized);
			}
		} elseif (is_array($json) || is_object($json)) {
			// Sanitize array/object data
			$sanitized = $this->sanitize_json_data($json);
			return wp_json_encode($sanitized);
		}

		return '{}'; // Return empty JSON object if invalid
	}

	/**
	 * Recursively sanitize JSON data
	 * 
	 * @param mixed $data The data to sanitize
	 * @return mixed Sanitized data
	 * @since 3.5.27
	 */
	private function sanitize_json_data($data){
		if (is_array($data)) {
			$sanitized = array();
			foreach ($data as $key => $value) {
				$sanitized_key = sanitize_text_field($key);
				$sanitized[$sanitized_key] = $this->sanitize_json_data($value);
			}
			return $sanitized;
		} elseif (is_object($data)) {
			$sanitized = new \stdClass();
			foreach ($data as $key => $value) {
				$sanitized_key = sanitize_text_field($key);
				$sanitized->$sanitized_key = $this->sanitize_json_data($value);
			}
			return $sanitized;
		} elseif (is_string($data)) {
			// For strings, remove potential XSS vectors
			$data = wp_kses($data, array());
			$data = sanitize_text_field($data);
			return $data;
		}

		return $data;
	}
}
