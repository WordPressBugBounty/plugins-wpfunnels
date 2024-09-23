<?php
/**
 * Oxygen manager
 * 
 * @package
 */
namespace WPFunnels\Widgets\Oxygen;


use CT_Toolbar;
use WPFunnels\Wpfnl_functions;
use function cli\err;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}


final class Manager {

	private static $_instance = null;

	/**
	 * Section slug
	 * 
	 * @var string $section_slug
	 */
	public $section_slug = "wpf_section_slug";


	/**
	 * Tab slug
	 * 
	 * @var string $tab_slug
	 */
	public $tab_slug = "wpf_tab" ;


	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	public function __construct()
	{

		if ( !class_exists('OxygenElement') ) {
			return;
		}
		add_action('init', array($this, 'init_elements'));
		add_action('oxygen_add_plus_sections', [$this, 'add_plus_sections']);

		/* global_settings_tab */
		add_action('oxygen_vsb_global_styles_tabs', [$this, 'global_settings_tab']);

		/* oxygen_add_plus_{$id}_section_content */
		add_action("oxygen_add_plus_" . $this->section_slug . "_section_content", [$this, 'add_plus_subsections_content']);
	}

	/**
	 * Check Post type and step Type
	 * Display elements for indivisual step type
	 */
	public function wpfnl_oxygen_check_post_type() {
		global $wp_query;
		$step_id = $wp_query->post->ID;
		$post_type = get_post_type($step_id);
		
		if ( 'wpfunnel_steps' == $post_type){
			if (Wpfnl_functions::check_if_this_is_step_type('landing') || Wpfnl_functions::check_if_this_is_step_type('custom')){
				new NextStepButton();
				new Optin();
			}
			
			if (Wpfnl_functions::check_if_this_is_step_type('checkout')){
				new Checkout();
			}
			if (Wpfnl_functions::check_if_this_is_step_type('thankyou')){
				new OrderDetails();
			}
			add_action('oxygen_add_plus_sections', [$this, 'add_plus_sections']);
		}
	}


	public function init_elements() {
		
		new NextStepButton();
		new Checkout();
		new OrderDetails();
		new Optin();
	}


	/**
	 * Add plus section
	 */
	public function add_plus_sections() {
		/* show a section in +Add dropdown menu and name it "My Custom Elements" */
		CT_Toolbar::oxygen_add_plus_accordion_section($this->section_slug, __("WPFunnels","wpfnl"));
	}


	/**
	 * Add settings tab
	 */
	public function global_settings_tab(){
		global $oxygen_toolbar;
		$oxygen_toolbar->settings_tab(__("Tab Label", 'wpfnl'), $this->tab_slug, "panelsection-icons/styles.svg");
	}


	/**
	 * Add plus subsections content
	 */
	public function add_plus_subsections_content() {
		do_action("oxygen_add_plus_" . $this->tab_slug . "_dynamic");
		do_action("oxygen_add_plus_" . $this->tab_slug . "_other");
	}
}
