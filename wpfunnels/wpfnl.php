<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link    https://getwpfunnels.com
 * @since   1.0.0
 * @package Wpfnl
 *
 * @wordpress-plugin
 * Plugin Name:       WPFunnels
 * Plugin URI:        https://getwpfunnels.com
 * Description:       Easiest 💥 Sales Funnel Builder 💥 For WordPress & WooCommerce by WPFunnels - Generate Leads & Craft A Highly-Converting Sales Journey In Minutes
 * Version:           3.5.2
 * Author:            WPFunnels Team
 * Author URI:        https://getwpfunnels.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfnl
 * Domain Path:       /languages
 */

use wPFunnels\Wpfnl;
/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

if ( ! defined( 'WPFNL_VERSION' ) ) {
	define('WPFNL_VERSION', '3.5.2');
}

if ( ! defined( 'WPFNL_FILE' ) ) {
	define( 'WPFNL_FILE', __FILE__ );
}

if ( ! defined( 'WPFNL_PATH' ) ) {
	define( 'WPFNL_PATH', dirname( WPFNL_FILE ) );
}

if ( ! defined( 'WPFNL_BASE' ) ) {
	define( 'WPFNL_BASE', plugin_basename( WPFNL_FILE ) );
}

if ( ! defined( 'WPFNL_DIR' ) ) {
	define( 'WPFNL_DIR', plugin_dir_path( WPFNL_FILE ) );
}

if ( ! defined( 'WPFNL_URL' ) ) {
	define( 'WPFNL_URL', plugins_url( '/', WPFNL_FILE ) );
}

if ( ! defined( 'WPFNL_DIR_URL' ) ) {
	define( 'WPFNL_DIR_URL', plugin_dir_url( WPFNL_FILE ) );
}

if ( ! defined( 'GETWPFUNNEL_HOME_URL' ) ) {
	define( 'GETWPFUNNEL_HOME_URL', 'https://getwpfunnels.com/' );
}
if ( ! defined( 'GETWPFUNNEL_PRICING_URL' ) ) {
	define( 'GETWPFUNNEL_PRICING_URL', 'https://getwpfunnels.com/pricing/' );
}

define( 'WPFNL_ADMIN_DIR', WPFNL_DIR . 'admin/' );


define('WPFNL_TEMPLATE_URL', '');
define('WPFNL_MAIN_PAGE_SLUG', 'wpfunnels');
define('WPFNL_FUNNEL_PAGE_SLUG', 'wp_funnels');
define('WPFNL_TEMPLATE_PAGE_SLUG', 'wpf_templates');
define('WPFNL_SETTINGS_SLUG', 'wp_funnel_settings');
define('WPFNL_EDIT_FUNNEL_SLUG', 'edit_funnel');
define('WPFNL_TRASH_FUNNEL_SLUG', 'trash_funnels');
define('WPFNL_FUNNELS_POST_TYPE', 'wpfunnels');
define('WPFNL_STEPS_POST_TYPE', 'wpfunnel_steps');
define('WPFNL_CREATE_FUNNEL_SLUG', 'create_funnel');
define('WPFNL_GLOBAL_SETTINGS_SLUG', 'wpfnl_settings');
define('WPFNL_FUNNEL_PER_PAGE', 10);
define('WPFNL_TEMPLATES_OPTION_KEY', 'wpfunnels_remote_templates');
define('WPFNL_TESTS', false);
define('WPFNL_DOCUMENTATION_LINK', 'https://getwpfunnels.com/docs/wpfunnels-wordpress-funnel-builder/');


// Template middleman api url.
define( 'WPFNL_MIDDLEMAN_TEMPLATE_URL', 'https://license.getwpfunnels.com/api/v1/templates' );

// Single template middleman api url.
define( 'WPFNL_MIDDLEMAN_SINGLE_TEMPLATE_URL', 'https://license.getwpfunnels.com/api/v1/template' );

// Step middleman api url.
define( 'WPFNL_MIDDLEMAN_STEP_URL', 'https://license.getwpfunnels.com/api/v1/step' );

// Template type middleman api url.
define( 'WPFNL_MIDDLEMAN_TEMPLATE_TYPE_URL', 'https://license.getwpfunnels.com/api/v1/template/type/id' );

// Template category middleman api url.
define( 'WPFNL_MIDDLEMAN_TEMPLATE_CATEGORY_URL', 'https://license.getwpfunnels.com/api/v1/template/category' );

define( 'WPFNL_ACTIVE_PLUGINS', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );


define( 'WPFNL_TAXONOMY_TEMPLATES_BUILDER', 'template_builder' );
define( 'WPFNL_TAXONOMY_TEMPLATES_PROPERTY', 'template_type' );
define( 'WPFNL_TAXONOMY_TEMPLATES_INDUSTRIES', 'template_industries' );
define( 'WPFNL_TAXONOMY_STEP_TYPE', 'type' );
define( 'WPFNL_IS_REMOTE', false );
define( 'WPFNL_PRO', '/wpfunnels-pro/wpfnl-pro.php' );
define( 'WPFNL_PRO_REQUIRED_VERSION', '1.0.4' );
define( 'WPFNL_SLUG', 'wpfunnels' );
define( 'WPFNL_NAME', 'WPFunnels' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpfnl.php';


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpfnl-activator.php
 */
function activate_wpfnl() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/utils/class-wpfnl-activator.php';
	Wpfnl_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpfnl-deactivator.php
 */
function deactivate_wpfnl() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/utils/class-wpfnl-deactivator.php';
	Wpfnl_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpfnl' );
register_deactivation_hook( __FILE__, 'deactivate_wpfnl' );



/**
 * Function wpfnl()
 * It is responsible for returning the instance of the plugin.
 *
 * @return Wpfnl
 */
function wpfnl() {
	return Wpfnl::get_instance();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_wpfnl() {
	wpfnl()->run();
}
run_wpfnl();



/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_wpfunnels() {
	$client = new Appsero\Client( '6fb1e340-8276-4337-bca6-28a7cd186f06', 'WPFunnels', __FILE__ );
	$client->insights()->init();
}
appsero_init_tracker_wpfunnels();



add_filter( 'et_builder_third_party_post_types', 'wpfnl_third_party_post_type', 10, 1 );

/**
 * Create a new post type for the funnel steps
 *
 * @access public
 * @param string $post_types the post types.
 * @return string
 *
 * @since 1.0.0
 */
function wpfnl_third_party_post_type( $post_types ) {
	$post_types[] = WPFNL_STEPS_POST_TYPE;
	return $post_types;
}



