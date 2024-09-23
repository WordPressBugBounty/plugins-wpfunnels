<?php
/**
 * ElementorPro Compatibility for form
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Optin\Optin_Record;

/**
 * Elementor Pro Compatibility for form
 * 
 * @package WPFunnels\Compatibility\ElementorPro
 */
class ElementorPro extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Elementor Pro
	 * to initiate the necessary updates.
	 *
	 * @since 3.4.9
	 */
	public function init() {
		add_action( 'elementor_pro/forms/new_record', array( $this, 'after_submit_elementor_form'), 10,2 );
	}


	/**
	 * This function is triggered after submitting an Elementor Pro form.
	 * It retrieves the form fields, maps them to the required format,
	 * Creates a new Optin Record, and triggers an action to process the form data.
	 *
	 * @since 3.4.9
	 *
	 * @param object $record The Elementor Pro form record object.
	 * @param object $handler The Elementor Pro form handler object.
	 *
	 * @return void
	 */
	public function after_submit_elementor_form( $record, $handler ) {
		// Get the ID of the current post or page
		$step_id = get_the_ID();

		if( !$step_id ){
			return;
		}

		$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );

		if( !$funnel_id ){
			return;
		}

		// Get the 'fields' data from the form submission record
		$fields = $record->get( 'fields' );
	
		// Map the fields using the map_fields method
		$mapped_value = $this->map_fields( $fields );
	
		// Create a new instance of the Optin_Record class with the mapped values
		$record = new Optin_Record( $mapped_value);
		
		/**
		 * Submit & process form data
		 */
		// Start output buffering
		ob_start();
	
		// Trigger the 'wpfunnels/after_optin_submit' action, passing along the step ID, a 'notification' string, an empty string, the record, and the mapped values
		do_action( 'wpfunnels/after_optin_submit', $step_id, 'redirect_to', 'next_step', $record, $mapped_value );
	
		// Clean (erase) the output buffer and turn off output buffering
		ob_get_clean();
	}


	/**
	 * Maps Elementor form fields to a standardized format.
	 *
	 * This function takes an array of Elementor form fields and maps them to a standardized
	 * format for further processing. It handles specific field types such as name, email,
	 * message, telephone, and URL.
	 *
	 * @param array $fields An array of Elementor form fields.
	 *
	 * @return array An array of mapped fields in a standardized format. Returns an empty array if input is invalid.
	 * 
	 * @since 3.4.9
	 */
	private function map_fields( $fields ) {
		// Check if $fields is an array and not empty
		if( is_array( $fields ) && !empty( $fields ) ){
			// Initialize an empty array to store the mapped fields
			$map_fields = array();
	
			// Iterate over each field in the $fields array
			foreach( $fields as $key=>$field ){
				// If the key is 'name' and the 'value' is set, map the 'value' to 'first_name' and 'last_name' in the $map_fields array
				if( 'name' === $key && isset( $field['value'] ) ){
					$map_fields['first_name'] = $field['value'];
					$map_fields['last_name'] = '';
				}
				// If the key is 'email' and the 'value' is set, map the 'value' to 'email' in the $map_fields array
				if( 'email' === $key && isset( $field['value'] ) ){
					$map_fields['email'] = $field['value'];
				}
				// If the key is 'message' and the 'value' is set, map the 'value' to 'message' in the $map_fields array
				if( 'message' === $key && isset( $field['value'] ) ){
					$map_fields['message'] = $field['value'];
				}
				// If the 'type' is set and is 'tel', map the 'value' to 'phone' in the $map_fields array
				if( isset( $field['type'] ) && 'tel' === $field['type'] ){
					$map_fields['phone'] = $field['value'];
				}
				// If the 'type' is set and is 'url', map the 'value' to 'web-url' in the $map_fields array
				if( isset( $field['type'] ) && 'url' === $field['type'] ){
					$map_fields['web-url'] = $field['value'];
				}
			}
			
			// Return the mapped fields
			return $map_fields;
		}
		// If $fields is not an array or is empty, return an empty array
		return [];
	}


	/**
	 * Check if Elementor Pro is activated
	 *
	 * @return bool
	 * @since 3.4.9
	 */
	public function maybe_activate()
	{
		// Check if the constant 'ELEMENTOR_PRO_VERSION' is defined
		// Return true if it is defined, false otherwise
		return defined('ELEMENTOR_PRO_VERSION');
	}
}
