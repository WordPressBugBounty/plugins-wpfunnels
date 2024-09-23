<?php
/**
 * Widgets manager
 * 
 * @package
 */
namespace WPFunnels\Widgets;

use WPFunnels\Base_Manager;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Widgets\Gutenberg\BlockTypes\Gutenberg_Editor;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Widgets_Manager extends Base_Manager
{

	use SingletonTrait;

	private $widgets = [];

	/**
	 * Initializes modules based on available builders.
	 *
	 * @since Unknown
	 */
	public function init()
	{
		$modules_namespace_prefix 	= $this->get_namespace_prefix();
		$builders 					= $this->get_builders();
		
		foreach ($builders as $builder) {
			$class_name = str_replace('-', ' ', $builder);
			$class_name = str_replace(' ', '', ucwords($class_name));
			$class_name = $modules_namespace_prefix . '\\Widgets\\' . $class_name . '\Manager';
			$this->widgets[$builder] = $class_name::instance();
		}
		
	}

	/**
	 * Retrieves a list of builders available within WordPress, including Oxygen if enabled.
	 *
	 * @return array An array containing the list of available builders.
	 * @since Unknown
	 */
	public function get_builders()
	{
		$selected_builder = Wpfnl_functions::get_builder_type();
		$builder = array();

		$oxygen_capability = Wpfnl_functions::oxygen_builder_version_capability();
		
		if ('oxygen' === $selected_builder && $oxygen_capability){
			array_push($builder,'oxygen');
		}elseif ('divi-builder' === $selected_builder ){
			array_push($builder,'diviModules');
		}elseif ('elementor' === $selected_builder ){
			array_push($builder,'elementor');
		}elseif ( 'bricks' === $selected_builder && 'bricks' === wp_get_theme()->template ){
			array_push($builder,'bricks');
		}elseif ('gutenberg' === $selected_builder ){
			array_push($builder,'gutenberg');
		}

		return apply_filters('wpfunnels/builders',$builder);
	}

	/**
	 * Retrieves MailMint forms' IDs and titles.
	 *
	 * @return array An array containing MailMint forms' IDs and titles.
	 * @since 2.8.15
	 */
	public static function get_mailmint_forms() {
		$mailmint_forms = method_exists( 'Mint\MRM\DataBase\Models\FormModel', 'get_all_id_title' ) ? \Mint\MRM\DataBase\Models\FormModel::get_all_id_title() : [];

		if ( is_array( $mailmint_forms ) && !empty( $mailmint_forms[ 'data' ] ) ) {
			foreach( $mailmint_forms[ 'data' ] as $form ) {
				if ( !empty( $form[ 'id' ] ) && !empty( $form[ 'title' ] ) ) {
					$forms[ $form[ 'id' ] ] = $form[ 'title' ];
				}
			}
		}
		return $forms ?? [];
	}

	/**
	 * Renders a Mail Mint form based on the provided form ID.
	 *
	 * This protected function checks for the existence of the \Mint\MRM\Internal\ShortCode\ContactForm class and utilizes it to render the content of a Mail Mint form specified by the provided form ID.
	 *
	 * @param string|int $form_id Represents the unique identifier of the Mail Mint form to be rendered.
	 * @param array $options Represents widget settings.
	 *
	 * @return void Outputs the rendered content of the Mail Mint form or an empty string if rendering fails.
	 * @since 2.8.15
	 */
	public static function render_mailmint_form( $form_id, $options = [] ) {
		if ( class_exists( '\Mint\MRM\Internal\ShortCode\ContactForm' ) ) {
			$mailmint_contact_form = new \Mint\MRM\Internal\ShortCode\ContactForm( [] );
			ob_start();
			echo method_exists( $mailmint_contact_form, 'render_content' ) ? $mailmint_contact_form->render_content( $form_id ) : '';
			$mailmint_form = ob_get_clean();
			$replace = 'id="mrm-form">';
			$replace .= '<input type="hidden" name="step_id" value="'.get_the_ID().'"/>';

			$replace .= !empty( $options[ 'admin_email' ] ) ? '<input type="hidden" name="admin_email" value="'.$options[ 'admin_email' ].'"/>' : '';
			$replace .= !empty( $options[ 'admin_email_subject' ] ) ? '<input type="hidden" name="admin_email_subject" value="'.$options[ 'admin_email_subject' ].'"/>' : '';
			$replace .= !empty( $options[ 'notification_text' ] ) ? '<input type="hidden" name="notification_text" value="'.$options[ 'notification_text' ].'"/>' : '';
			$replace .= !empty( $options[ 'other_action' ] ) ? '<input type="hidden" name="post_action" value="'.$options[ 'other_action' ].'"/>' : '';
			$replace .= !empty( $options[ 'redirect_url' ] ) ? '<input type="hidden" name="redirect_url" value="'.$options[ 'redirect_url' ].'"/>' : '';
			$replace .= !empty( $options[ 'data_to_checkout' ] ) && 'on' === $options[ 'data_to_checkout' ] ? '<input type="hidden" name="data_to_checkout" value="yes"/>' : '';
			$replace .= !empty( $options[ 'register_as_subscriber' ] ) && 'on' === $options[ 'register_as_subscriber' ] ? '<input type="hidden" name="optin_allow_registration" value="yes"/>' : '';

			echo str_replace( 'id="mrm-form">', $replace, $mailmint_form );
		}
	}
}
