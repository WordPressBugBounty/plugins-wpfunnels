<?php
/**
 * REST controller for the Integrations screen (`?page=wpfunnels#/integrations`).
 *
 * The free plugin owns the upsell grid: the 18 locked cards, the banner and its
 * two copy variants. Those used to be hard-coded in
 * admin/modules/integration/view.php; they live in get_catalog() and
 * get_banner() now, and the legacy view reads the very same data, so the PHP
 * page and the React screen cannot drift apart.
 *
 * WPFunnels Pro on an integration plan adds its working CRM forms through the
 * `wpfunnels/integrations_page_data` filter (mode, providers) and serves the
 * credential actions from its own routes.
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IntegrationsPageController
 *
 * Named after the screen rather than `IntegrationsController`: Pro's REST
 * controllers share this namespace and Pro ships its own integrations
 * controller, and two classes with one name would shadow each other in the
 * classmap autoloaders.
 *
 * @package WPFunnels\Rest\Controllers
 */
class IntegrationsPageController extends Wpfnl_REST_Controller {

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
	protected $rest_base = 'integrations';


	/**
	 * Register the route the Integrations screen loads itself from.
	 *
	 * @return void
	 * @since  3.13.9
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page_data' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}


	/**
	 * Whether the current user may open the Integrations screen.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 * @since  3.13.9
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_view',
				__( 'Sorry, you cannot list resources.', 'wpfnl' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}


	/**
	 * GET integrations — everything the screen renders.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @since  3.13.9
	 */
	public function get_page_data( $request ) {
		return rest_ensure_response( self::get_page_data_array() );
	}


	/**
	 * The screen's data, before and after Pro adds to it.
	 *
	 * @return array {
	 *     @type string $mode          'locked' (upsell grid) or 'active' (Pro's CRM forms).
	 *     @type bool   $is_pro_active Whether a Pro licence is active.
	 *     @type string $heading       Section title above the banner.
	 *     @type array  $banner        title, description, cta_label, cta_title, cta_url.
	 *     @type array  $items         The locked cards, see get_catalog().
	 *     @type array  $providers     Pro's CRM forms; empty in the free plugin.
	 * }
	 * @since 3.13.9
	 */
	public static function get_page_data_array() {
		$is_pro_active = (bool) apply_filters( 'wpfunnels/is_pro_license_activated', false );

		$data = array(
			'mode'          => 'locked',
			'is_pro_active' => $is_pro_active,
			'heading'       => __( 'Integrations', 'wpfnl' ),
			'banner'        => self::get_banner( $is_pro_active ),
			'items'         => self::get_catalog(),
			'providers'     => array(),
		);

		/**
		 * Filters what the Integrations screen renders.
		 *
		 * WPFunnels Pro on an integration plan sets `mode` to 'active' and
		 * fills `providers` with its CRM forms.
		 *
		 * @param array $data See get_page_data_array().
		 *
		 * @since 3.13.9
		 */
		$filtered = apply_filters( 'wpfunnels/integrations_page_data', $data );

		return is_array( $filtered ) ? $filtered : $data;
	}


	/**
	 * Upgrade banner copy.
	 *
	 * Two variants: with no Pro licence it sells Pro, with a Pro licence on a
	 * plan that has no integrations it sells the higher plan. The CTA's `title`
	 * attribute and its label differ in the no-Pro variant, as they always did.
	 *
	 * @param bool $is_pro_active Whether a Pro licence is active.
	 * @return array title, description, cta_label, cta_title, cta_url.
	 * @since  3.13.9
	 */
	public static function get_banner( $is_pro_active ) {
		if ( $is_pro_active ) {
			return array(
				'title'       => __( 'Unlock Every Integration with a Higher Plan', 'wpfnl' ),
				'description' => __( 'You are on the Starter plan. Upgrade to the Medium Annual or Large Annual plan to connect ActiveCampaign, Mailchimp, HubSpot, and 15 more CRM & email tools.', 'wpfnl' ),
				'cta_label'   => __( 'Upgrade Plan', 'wpfnl' ),
				'cta_title'   => __( 'Upgrade Plan', 'wpfnl' ),
				'cta_url'     => 'https://getwpfunnels.com/pricing/',
			);
		}

		return array(
			'title'       => __( 'Unlock Every Integration with WPFunnels Pro', 'wpfnl' ),
			'description' => __( 'Connect your favorite marketing tools and automate your sales funnel effortlessly. Unlock all integrations with a single upgrade and streamline your business operations.', 'wpfnl' ),
			'cta_label'   => __( 'Get WPFunnels Pro', 'wpfnl' ),
			'cta_title'   => __( 'Upgrade to Pro', 'wpfnl' ),
			'cta_url'     => 'https://getwpfunnels.com/pricing/',
		);
	}


	/**
	 * The integrations the upsell grid lists, in display order.
	 *
	 * `slug` is the provider's key in `_wpfnl_addons_cred` (the same key Pro's
	 * routes take), `icon` an absolute URL. Titles and descriptions are kept
	 * exactly as the legacy page printed them, untranslated as they were.
	 *
	 * @return array[] slug, title, description, icon, is_new, is_locked.
	 * @since  3.13.9
	 */
	public static function get_catalog() {
		$icons = WPFNL_URL . 'admin/assets/images/';

		$catalog = array(
			array( 'activecamp', 'ActiveCampaign', 'Create advanced automated sequences and tag customers based on funnel behavior.', 'integration-active-campaign-icon.svg', true ),
			array( 'mailchimp', 'Mailchimp', 'Sync your contacts instantly and trigger personalized email campaigns for every new lead.', 'integration-mailchimp-icon.svg', false ),
			array( 'mailpoet', 'MailPoet', 'Manage your newsletter and automated emails directly within your WordPress dashboard.', 'integration-mailpoet-icon.svg', false ),
			array( 'cc', 'Constant Contact', 'Seamlessly move your funnel leads into professional email marketing workflows.', 'integration-constant-contact-icon.svg', false ),
			array( 'mailerlite', 'MailerLite', 'A simple yet powerful way to capture leads and grow your email subscriber list.', 'integration-mailerlite-icon.svg', false ),
			array( 'sendinblue', 'Breo (Sendinblue)', 'Send transactional emails and SMS alerts to your customers in real-time.', 'integration-sendinblue-icon.svg', false ),
			array( 'encharge', 'Encharge', 'Automate your customer journey with behavior-based emails and deep CRM synchronization.', 'integration-encharge-icon.svg', false ),
			array( 'aweber', 'AWeber', 'Automate your lead generation and build long-term relationships with your audience.', 'integration-aweber-icon.svg', false ),
			array( 'convertkit', 'ConvertKit', 'Designed for creators—trigger tailored automations based on specific funnel actions.', 'integration-convertkit-icon.svg', false ),
			array( 'hubspot', 'HubSpot', 'Automatically sync funnel data with your CRM to manage your entire sales pipeline.', 'integration-hubspot-icon.svg', false ),
			array( 'zoho', 'Zoho CRM', 'Track every customer interaction and organize your leads for better conversion.', 'integration-zoho-icon.svg', false ),
			array( 'zapier', 'Zapier', 'Automate workflows by connecting your favorite apps and services.', 'integration-zapier-icon.svg', false ),
			array( 'getresponse', 'GetResponse', 'Manage your email marketing campaigns and automate your customer journeys.', 'integration-getresponse-icon.svg', false ),
			array( 'omnisend', 'Omnisend', 'Enhance your e-commerce marketing with automated workflows and personalized messaging.', 'integration-omnisend-icon.svg', false ),
			array( 'wpfusion', 'WPFusion', 'Integrate your WordPress site with your CRM to automate marketing and sales processes.', 'integration-wpfusion-icon.svg', false ),
			array( 'drip', 'Drip', 'Automate your email marketing campaigns and nurture your leads effectively.', 'integration-drip-icon.svg', false ),
			array( 'pabbly', 'Pabbly Connect', 'Connect your apps and automate workflows seamlessly.', 'integration-pabblyemail-icon.svg', false ),
			array( 'pabbly_email', 'Pabbly Email Marketing', 'Manage your email marketing campaigns and automate your customer journeys.', 'integration-pabblyemail-icon.svg', false ),
		);

		return array_map(
			static function ( $row ) use ( $icons ) {
				return array(
					'slug'        => $row[0],
					'title'       => $row[1],
					'description' => $row[2],
					'icon'        => $icons . $row[3],
					'is_new'      => $row[4],
					'is_locked'   => true,
				);
			},
			$catalog
		);
	}
}
