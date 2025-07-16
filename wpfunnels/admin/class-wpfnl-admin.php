<?php

/**
 * Admin class
 *
 * @package
 */

namespace WPFunnels\Admin;

use WPFunnels\Admin\BatchProcessing\BatchProcessingController;
use WPFunnels\Admin\Migrations\MigrationManager;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use MRM\Common\MrmCommon;


/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://rextheme.com
 * @since 1.0.0
 *
 * @package    WPFunnels
 * @subpackage WPFunnels/Admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpfnl
 * @subpackage Wpfnl/admin
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Admin
{
	/**
	 * Wpfunnels feed option key.
	 */
	const WPFNL_FEED_OPTION_KEY = 'wpfnl_remote_info_feed_data';

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $version The current version of this plugin.
	 */
	private $version;


	private $dependency_plugins;


	/**
	 * Page hooks
	 *
	 * @var array
	 */
	private $page_hooks = [
		'toplevel_page_wpf_templates',
		'toplevel_page_wpfunnels',
		'wpfunnels_page_wpf_templates',
		'wpfunnels_page_trash_funnels',
		'wpfunnels_page_wpf_feature_comparison',
		'wpfunnels_page_wp_funnel_settings',
		'wpfunnels_page_wp_global_store',
		'wpfunnels_page_edit_funnel',
		'wpfunnels_page_create_funnel',
		'wpfunnels_page_wpfnl_settings',
		'wpfunnels_page_wpf-license',
		'wpfunnels_page_email-builder',
		'wpfunnels_page_wp_funnels',
		'wpfunnels_page_wpf_templates'
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_filter('plugin_action_links_' . WPFNL_BASE, [$this, 'add_funnel_action_links']);
		add_filter('locale', array($this, 'change_local_for_funnel_canvas'));
		if (is_admin()) {
			add_filter('wp_dropdown_pages', array($this, 'show_funnel_steps_on_reading'));
			add_filter('gettext', [$this, 'wpfnl_customizing_checkout_text'], 10, 3);
			add_action('admin_init', [$this, 'wpfnl_add_type_meta']);
			add_action('admin_init', array($this, 'init_actions'));
			add_action('deactivated_plugin', [$this, 'wpfnl_deactivated_plugin']);
			add_action('activated_plugin', [$this, 'wpfnl_activated_plugin']);

			// Register Dashboard Widgets.
			add_action('wp_dashboard_setup', [$this, 'wpfnl_register_dashboard_widgets']);

			if (isset($_GET['page']) && 'edit_funnel' === $_GET['page']) {
				wp_cache_flush();
			}
		}

		add_action('admin_head', function () {
			remove_submenu_page(WPFNL_MAIN_PAGE_SLUG, 'email-builder');
		});


		// Remove all admin notices from WPFunnels Dashboard
		if ($this->is_current_page('wpfunnels')) {
			remove_all_actions('admin_notices');
		}


		/**
		 * @todo This piece of code will be removed after releasing 3.2.0. We need to introduced this because we have changed the funnel template creation strategy.
		 *
		 */
		add_action('admin_init', function () {
			$current_version 	= get_option('wpfunnels_version');
			$is_already_synced 	= get_option('_is_wpfunnels_new_templates_synced', 'no');
			if (version_compare($current_version, '3.1.2', '>') && 'yes' != $is_already_synced) {
				delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_wc');
				delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_lms');
				delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
				delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
				delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
				update_option('_is_wpfunnels_new_templates_synced', 'yes');
			}
		});

		add_action('admin_init', array($this, 'attach_admin_capabilities'));

		add_action('wpfunnels_tracker_optin', array($this, 'wpfunnels_tracker_optin'), 10);
		add_action('mint_before_sending_sms', array($this, 'create_mrm_contact'), 10);
	}


	public function init_actions()
	{
		$batch_processor = new BatchProcessingController();
		new MigrationManager($batch_processor);
	}

	/**
	 * Register dashboard widgets.
	 *
	 * Adds a new WPFunnels widgets to WordPress dashboard.
	 *
	 * Fired by `wp_dashboard_setup` action.
	 *
	 * @since 2.6.2
	 */
	public function wpfnl_register_dashboard_widgets()
	{
		wp_add_dashboard_widget('wpfnl-dashboard-overview', esc_html__('WPFunnels Overview', 'wpfnl'), [$this, 'wpfnl_dashboard_overview_widget']);
		global $wp_meta_boxes;

		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$ours = [
			'wpfnl-dashboard-overview' => $dashboard['wpfnl-dashboard-overview'],
		];

		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge($ours, $dashboard);
	}


	/**
	 * WPFunnels dashboard widget.
	 *
	 * Displays the wpfunnels dashboard widget.
	 *
	 * @since 2.6.2
	 */
	public function wpfnl_dashboard_overview_widget()
	{
		$upgrade_to_pro      = __('Upgrade to Pro', 'wpfnl');
		$install_activate    = __('Install & Activate', 'wpfnl');
		$is_mail_mint_active = WPFnl_functions::is_mail_mint_free_active();

		$response = $this->get_wpfnl_feed_data();

		$icons = [
			'Total Orders'            => '<svg width="16" height="17" fill="none" viewBox="0 0 16 17" xmlns="http://www.w3.org/2000/svg"><path fill="#B7A6E9" d="M12.061 8.04a4.015 4.015 0 01-3.478-6.03h-5.74l-.029-.236A2.01 2.01 0 00.82 0H.67a.67.67 0 000 1.34h.15a.67.67 0 01.665.592l.922 7.84a3.35 3.35 0 003.328 2.96h6.997a.67.67 0 000-1.341H5.734a2.01 2.01 0 01-1.89-1.34h7.987a3.35 3.35 0 003.298-2.756l.16-.884a4.01 4.01 0 01-3.228 1.63z"/><path fill="#B7A6E9" d="M10.542 5.994a1.254 1.254 0 00.901.402h.023a1.255 1.255 0 00.894-.37l2.863-2.863a.672.672 0 10-.95-.952l-2.806 2.81-.827-.887a.67.67 0 00-.98.915l.882.945zM4.69 16.082a1.34 1.34 0 100-2.68 1.34 1.34 0 000 2.68zm6.701 0a1.34 1.34 0 100-2.68 1.34 1.34 0 000 2.68z"/></svg>',
			'Total Revenue'           => '<svg width="15" height="15" fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path fill="#B7A6E9" d="M9.75 15h-4.5C1.5 15 0 13.5 0 9.75v-4.5C0 1.5 1.5 0 5.25 0h4.5C13.5 0 15 1.5 15 5.25v4.5C15 13.5 13.5 15 9.75 15z"/><path fill="#B7A6E9" d="M5.004 9.247c0 .968.742 1.748 1.665 1.748H8.55c.803 0 1.455-.683 1.455-1.523 0-.915-.397-1.237-.99-1.447l-3.022-1.05c-.593-.21-.99-.533-.99-1.448 0-.84.652-1.522 1.455-1.522H8.34c.923 0 1.665.78 1.665 1.747"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" d="M5.004 9.247c0 .968.742 1.748 1.665 1.748H8.55c.803 0 1.455-.683 1.455-1.523 0-.915-.397-1.237-.99-1.447l-3.022-1.05c-.593-.21-.99-.533-.99-1.448 0-.84.652-1.522 1.455-1.522H8.34c.923 0 1.665.78 1.665 1.747"/><path fill="#B7A6E9" d="M7.5 3v9-9z"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" d="M7.5 3v9"/></svg>',
			'Order Bump Revenue'      => '<svg width="15" height="15" fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path stroke="#B7A6E9" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5" d="M5.376 1L3.05 3.333M9.475 1l2.327 2.333"/><path fill="#fff" stroke="#B7A6E9" stroke-width="1.5" d="M1 4.76c0-1.19.636-1.286 1.427-1.286h10c.791 0 1.428.097 1.428 1.286 0 1.382-.637 1.285-1.427 1.285H2.427C1.637 6.045 1 6.142 1 4.76z"/><path fill="#B7A6E9" d="M3.273 13.064L1.79 5.944h10.975l-.89 6.526c0 .712-1.582 1.286-2.373 1.484H5.35c-.237.237-1.483-.495-2.076-.89z"/><path stroke="#B7A6E9" stroke-linecap="round" stroke-width="1.5" d="M1.964 6.142l.906 5.553c.206 1.247.7 2.16 2.54 2.16h3.875c1.999 0 2.294-.874 2.526-2.082l1.08-5.63"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" d="M8.91 9.504L7.425 8.021 5.943 9.504M7.426 8.02v3.56"/></svg>',
			'Upsell/Downsell Revenue' => '<svg width="15" height="15" fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path fill="#B7A6E9" d="M5.25 15h4.5C13.5 15 15 13.5 15 9.75v-4.5C15 1.5 13.5 0 9.75 0h-4.5C1.5 0 0 1.5 0 5.25v4.5C0 13.5 1.5 15 5.25 15z"/><path fill="#B7A6E9" d="M6.379 5.25h3.37v3.371"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.379 5.25h3.37v3.371M9.75 5.25l-4.5 4.5"/></svg>',
			'Total Leads'             => '<svg width="13" height="13" fill="none" viewBox="0 0 13 13" xmlns="http://www.w3.org/2000/svg"><path fill="#B7A6E9" d="M8.45 0h-3.9C1.3 0 0 1.3 0 4.55v3.9c0 2.457.741 3.803 2.509 4.303.143-1.69 1.879-3.023 3.991-3.023 2.113 0 3.848 1.333 3.991 3.023C12.259 12.253 13 10.907 13 8.45v-3.9C13 1.3 11.7 0 8.45 0zM6.5 7.91a2.33 2.33 0 010-4.66 2.33 2.33 0 010 4.66z"/></svg>',
		];


		$pro_icon_svg = '<div class="pro-icon"><svg width="14" height="14" fill="none" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="7" fill="#66CB64"/><path fill="#fff" fill-rule="evenodd" d="M7.623 4.465a.69.69 0 00-1.15 0l-1.36 2.08-1.04-.59c-.52-.294-1.146.168-1.005.743l.706 2.88c.032.13.15.222.285.222h5.977a.292.292 0 00.285-.221l.706-2.88c.141-.576-.485-1.038-1.005-.743l-1.04.588-1.36-2.08zm-.493.312a.098.098 0 00-.165 0l-1.51 2.311a.296.296 0 01-.393.094l-1.28-.725a.097.097 0 00-.144.106l.652 2.659h5.515l.652-2.66a.096.096 0 00-.143-.105l-1.281.725a.296.296 0 01-.393-.094l-1.51-2.31z" clip-rule="evenodd"/><path fill="#fff" d="M4.312 10.377a.291.291 0 00-.293.29.29.29 0 00.293.288h5.472c.161 0 .293-.13.293-.289a.291.291 0 00-.293-.289H4.312z"/><circle cx="10.739" cy="4.987" r=".514" fill="#fff"/><circle cx="3.139" cy="4.987" r=".514" fill="#fff"/><circle cx="7.042" cy="3.139" r=".514" fill="#fff"/><path fill="#fff" d="M9.918 9.404h-5.65L3.241 6.22l1.952.616 1.849-2.465L8.993 6.94l1.746-.822-.821 3.287z"/></svg></div>';

?>
		<div class="wpfnl-dashboard-widget">
			<div class="wpfnl-overview__header">
				<div class="wpfnl-overview__logo">
					<div class="wpfnl-logo-wrapper">
						<svg width="38" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"></path>
							<path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"></path>
							<path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"></path>
						</svg>
					</div>
				</div>

				<div class="wpfnl-overview__upgrade">
					<span><?php echo !Wpfnl_functions::is_pro_license_activated() ? __('Upgrade to Pro to get premium features such as one-click upsells, A/B testing and more!', 'wpfnl') : __('Start creating high converting landing pages, sales funnels, and checkout flows in minutes!', 'wpfnl'); ?></span>
				</div>

				<?php if (! Wpfnl_functions::is_pro_license_activated()) { ?>
					<div class="wpfnl-overview__create">
						<a href="https://getwpfunnels.com/pricing/" target="_blank" class="button"><span aria-hidden="true"></span> <?php echo esc_html($upgrade_to_pro); ?></a>
					</div>
				<?php } ?>

			</div>

			<!-- overview report -->
			<div class="wpfnl-overview__report">
				<?php foreach ($response as $report) :
					$icon_html = $icons[$report['title']] ?? '';
				?>
					<div class="report-item">
						<div class="icon"><?php echo $icon_html; ?></div>
						<div class="text-wrapper">
							<p class="title"><?php echo esc_html($report['title']); ?></p>
							<p class="amount"><?php echo $report['amount']; ?></p>
						</div>

						<?php if ($report['show_pro'] && ! Wpfnl_functions::is_pro_license_activated()) : ?>
							<?php echo $pro_icon_svg; ?>
						<?php endif; ?>

					</div>
				<?php endforeach; ?>
			</div>

			<?php if (!Wpfnl_functions::is_mint_mrm_active()) : ?>
				<!-- install mail mint -->
				<div class="wpfnl-overview__mailmint <?php echo Wpfnl_functions::is_mint_mrm_active() ? 'is-active' : 'is-disabled'; ?>">
					<div class="icon-wrapper">
						<div class="icon">
							<svg width="34" height="34" fill="none" viewBox="0 0 34 34" xmlns="http://www.w3.org/2000/svg">
								<circle cx="17" cy="17" r="16.5" fill="#fff" stroke="#573BFF" />
								<path fill="#573BFF" d="M24.084 14.876l-.634 8.778.905-1.267.996-10.225-9.23 6.153-2.082-2.172-5.972-5.067 7.601 8.868c.272.271.634.362.996.09l7.42-5.158z" />
								<path fill="#02C4FB" d="M21.822 11.62c-3.891.814-7.24.904-8.416.814l.995 1.086c4.525-.272 6.697-.905 7.24-1.267.362-.272.271-.543.18-.634z" />
								<path fill="#573BFF" d="M20.374 13.61c-2.444.724-4.525.905-5.249.905l.724.633c2.805-.362 4.163-.905 4.434-1.176.272-.181.181-.362.09-.362z" />
								<path fill="#573BFF" d="M25.947 11.345c-.212-.033-.418.064-.596.184l-9.05 6.153-4.162-4.525-.749-.793-.79-.835c1.72.18 8.778-.09 12.67-.996 1.81-.452 1.447-1.267 1.266-1.176-8.415 1.176-15.745.452-15.745.452-.403-.034-.617.066-.815.362-.18.272-.09.634.09.905l.453.453 2.082 2.08 5.158 5.43c.271.272.633.272.995.09l8.054-5.52c-.453 4.073-.996 8.145-1.267 9.14-1.81.363-12.397.453-13.845.091-.453-.724-.996-4.253-1.358-7.963-.09-.543-.633-.905-1.176-.634 0 0-.362.177-.362.724.905 8.597 1.81 9.05 2.081 9.23.543.272 4.163.453 7.873.453s7.33-.181 7.873-.634c.18-.18.814-.723 1.9-11.401v-.091c.074-.736-.152-1.113-.58-1.18z" />
								<path fill="url(#paint0_linear_129_34)" d="M8.474 11.538h2.135l1.687 1.79H9.984l-1.51-1.79z" />
								<defs>
									<linearGradient id="paint0_linear_129_34" x1="9.613" x2="10.654" y1="11.538" y2="13.37" gradientUnits="userSpaceOnUse">
										<stop stop-color="#0C0632" />
										<stop offset="1" stop-color="#573BFF" />
									</linearGradient>
								</defs>
							</svg>
						</div>
						<div class="text-wrapper">
							<p class="description">
								<?php echo $is_mail_mint_active ? __('Use email marketing automation for your funnels easily with <b>Mail Mint</b>!', 'wpfnl') : __('Use email marketing automation for your funnels easily with <b>Mail Mint</b>!', 'wpfnl'); ?>
							</p>
						</div>
					</div>
					<div class="wpfnl-overview__install-mailmint">
						<?php if (Wpfnl_functions::is_mail_mint_free_active()) : ?>
							<a href="https://getwpfunnels.com/pricing/#mail-mint" target="_blank" class="button">
								<?php echo $upgrade_to_pro; ?>
							</a>
						<?php else : ?>
							<button id="pro-modal-dropdown-btn" class="button">
								<span class="loader" style="display:none;"></span>
								<span class="btn-text"><?php echo $install_activate; ?></span>
							</button>
						<?php endif; ?>
					</div>

				</div>
			<?php endif; ?>

			<div class="wpfnl-overview__footer wpfnl-divider_top">
				<ul>
					<?php foreach ($this->get_wpfunnels_dashboard_overview_widget_footer_actions() as $action_id => $action) : ?>
						<li class="wpfnl-overview__<?php echo esc_attr($action_id); ?>"><a href="<?php echo esc_attr($action['link']); ?>" target="_blank"><?php echo esc_html($action['title']); ?> <span class="screen-reader-text"><?php echo esc_html__('(opens in a new window)', 'wpfnl'); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<script>
				(function($) {

					const apiService = (path = "", method = "GET", data, content_type = "text/plain") => {
						return new Promise((resolve, reject) => {
							jQuery.ajax({
								url: wpApiSettings.root + path,
								type: method,
								data: data,
								beforeSend: function(xhr) {
									xhr.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
								},
								dataType: "json",
								contentType: content_type,
								success: resolve,
								error: reject
							});
						});
					};

					$(document).ready(function() {
						jQuery("#pro-modal-dropdown-btn").click((e) => {
							e.preventDefault();
							var btn = $('#pro-modal-dropdown-btn');
							var loader = btn.find('.loader');
							console.log(btn);
							var btnText = btn.find('.btn-text');
							loader.show();
							btn.prop('disabled', true);
							btnText.text('Processing...');
							apiService("wpfunnels/v1/activate-plugin", 'POST', JSON.stringify({
								basename: 'mail-mint/mail-mint.php',
								slug: 'mail-mint',
							}), 'application/json').then((res) => {
								var upgradeBtn = $(
									'<a href="https://getwpfunnels.com/pricing/#mail-mint" target="_blank" class="button" style="margin-left:0;">' +
									'Upgrade to Pro' +
									'</a>'
								);
								btn.closest('.wpfnl-overview__install-mailmint').html(upgradeBtn);
								$('.text-wrapper .title').text('Get Mail Mint Pro');
								$('.text-wrapper .description').html('Use email marketing automation for your funnels easily with <b>Mail Mint</b>!');
							}).catch((e) => {
								btn.replaceWith(btnPrevState);
								addClickEvent();
								console.log(e.responseJSON);
							})
						});
					});
				})(jQuery);
			</script>

			<style>
				.wpfnl-dashboard-widget {
					margin-top: -11px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint {
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 16px 20px;
					border: 1px solid #EEEAF8;
					margin-bottom: 15px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint.is-active .wpfnl-overview__install-mailmint button {
					pointer-events: none;
					cursor: not-allowed;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .icon-wrapper {
					display: flex;
					gap: 12px;
					align-items: center;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .wpfnl-overview__install-mailmint .button {
					border-radius: 7px;
					background: #573BFF;
					color: #FFF;
					text-align: center;
					font-family: Inter;
					font-size: 12px;
					font-style: normal;
					font-weight: 500;
					line-height: 2.6;
					border: none;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .wpfnl-overview__install-mailmint .button:focus {
					box-shadow: unset;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .wpfnl-overview__install-mailmint .loader {
					border: 2px solid #f3f3f3;
					border-top: 2px solid #573BFF;
					border-radius: 50%;
					width: 12px;
					height: 12px;
					animation: spin 0.8s linear infinite;
					display: inline-block;
					vertical-align: middle;
					margin-right: 4px;
				}

				@keyframes spin {
					0% {
						transform: rotate(0deg);
					}

					100% {
						transform: rotate(360deg);
					}
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .wpfnl-overview__install-mailmint .button:disabled {
					cursor: not-allowed;
					opacity: 0.7;
					background: #573BFF !important;
					color: #FFF !important;
				}


				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .icon-wrapper .text-wrapper .title {
					color: #2E3148;
					font-size: 16px;
					font-weight: 600;
					line-height: 1;
					letter-spacing: -0.32px;
					margin: 0 0 8px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__mailmint .icon-wrapper .text-wrapper .description {
					margin: 0;
					line-height: 1;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__report {
					display: grid;
					grid-template-columns: repeat(2, 1fr);
					border: 1px solid #EEEAF8;
					margin: 15px 0;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__report .report-item {
					display: flex;
					gap: 15px;
					padding: 20px;
					position: relative;

				}

				.wpfnl-dashboard-widget .wpfnl-overview__report .report-item:nth-child(odd)::before {
					content: "";
					position: absolute;
					right: 15%;
					height: 36px;
					width: 1px;
					background: linear-gradient(180deg, rgba(238, 234, 248, 0.00) -2.78%, #EEEAF8 47.1%, rgba(238, 234, 248, 0.00) 104.17%);
				}

				.wpfnl-dashboard-widget .wpfnl-overview__report .report-item .text-wrapper p {
					margin: 0;
					line-height: 1;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__report .report-item .text-wrapper p.title {
					color: #2E3148;
					font-size: 12px;
					font-weight: 400;
					margin-bottom: 8px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__report .report-item .text-wrapper p.amount {
					color: #2E3148;
					font-size: 15px;
					font-weight: 600;
					line-height: normal;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__header {
					display: flex;
					align-items: center;
					justify-content: space-between;
					border-bottom: 1px solid #eee;
					gap: 15px;
					padding: 15px 15px;
					margin: 0 -12px;

				}

				.wpfnl-dashboard-widget .wpfnl-overview__header .wpfnl-overview__upgrade {
					padding: 0px 15px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__versions {
					font-size: 0.9em;
					line-height: 1.5;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__version {
					display: block;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__create a {
					border-radius: 7px;
					background: #6E42D3;
					border: 1px solid #6E42D3;
					font-style: normal;
					line-height: 2.28;
					color: #FFF;
					text-align: center;
					font-family: Inter;
					font-size: 14px;
					font-weight: 500;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__create a:hover {
					background: #6E42D3;
					color: #FFF;
					box-shadow: unset;
					border-color: transparent;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__create a:focus {
					background: #6E42D3;
					color: #FFF;
					outline: none;
					box-shadow: unset;
					border-color: transparent;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__create .dashicons {
					font-size: 15px;
					line-height: 1;
					position: relative;
					top: 8px;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__recently-edited .wpfnl-heading {
					font-weight: 600 !important;
					border-bottom: 1px solid #eee;
					margin: 0 -12px !important;
					padding: 6px 12px !important;
					margin-bottom: 13px !important;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__post {
					margin-top: 10px;
					font-weight: 500;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__post-link {
					display: inline-block;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__post-description {
					margin: 0 0 1.5em;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer {
					border-top: 1px solid #eee;
					margin: 0 -12px;
					padding: 6px 12px;
					padding-top: 12px;
					padding-bottom: 0;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer ul {
					display: -webkit-box;
					display: -ms-flexbox;
					display: flex;
					list-style: none;
					margin: 0;
					padding: 0;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer ul li {
					padding: 0 10px;
					margin: 0;
					position: relative;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__blog a,
				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__changelog a,
				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__help a {
					color: #667085;
					font-size: 12px;
					font-weight: 400;
					position: relative;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__blog a:hover,
				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__changelog a:hover,
				.wpfnl-dashboard-widget .wpfnl-overview__footer .wpfnl-overview__help a:hover {
					color: #6846CE;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer ul li:not(:last-child)::before {
					content: "";
					position: absolute;
					right: 0;
					top: 3px;
					height: 16px;
					width: 1px;
					background: linear-gradient(180deg, rgba(238, 234, 248, 0.00) -2.78%, #EEEAF8 47.1%, rgba(238, 234, 248, 0.00) 104.17%);
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer ul li:first-child {
					padding-left: 0;
					border: none;
				}

				.wpfnl-dashboard-widget .wpfnl-overview__footer ul li.wpfnl-overview__go-pro a {
					color: #DF8740;
					font-size: 12px;
					font-weight: 400;
					line-height: normal;
				}
			</style>
		</div>
<?php
	}

	/**
	 * Get wpfunnels feed data.
	 *
	 * Retrieve the feed info data from remote wpfunnels server.
	 *
	 * @access public
	 *
	 * @param bool $force_update Optional. Whether to force the data update or
	 *                                     not. Default is false.
	 *
	 * @return array Feed data.
	 * @since 2.7.16
	 */
	public function get_wpfnl_feed_data($force_update = false)
	{
		return $this->get_wpfnl_info_data($force_update);
	}

	/**
	 * Get wpfunnles info data.
	 *
	 * This function retrieves post from wpfunnels remote API.
	 *
	 * @access private
	 *
	 * @param bool $force_update Optional. Whether to force the data retrieval or
	 *                                     not. Default is false.
	 *
	 * @return array|false Info data, or false.
	 * @since 2.7.16
	 */
	private function get_wpfnl_info_data($force_update)
	{
		global $wpdb;
		$response = [
			[
				'title'    => 'Total Orders',
				'amount'   => $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}wpfnl_stats WHERE status = %s", 'completed')),
				'show_pro' => false
			],
			[
				'title'    => 'Total Revenue',
				'amount'   => Wpfnl_functions::is_wc_active() ? wc_price((int)$wpdb->get_var($wpdb->prepare("SELECT SUM(total_sales) FROM {$wpdb->prefix}wpfnl_stats WHERE status = %s", 'completed'))) : number_format(0, 2),
				'show_pro' => false
			],
			[
				'title'    => 'Order Bump Revenue',
				'amount'   => Wpfnl_functions::is_wc_active() ? wc_price((int)$wpdb->get_var($wpdb->prepare("SELECT SUM(orderbump_sales) FROM {$wpdb->prefix}wpfnl_stats WHERE status = %s", 'completed'))) : number_format(0, 2),
				'show_pro' => false
			],
			[
				'title'    => 'Upsell/Downsell Revenue',
				'amount'   => ( Wpfnl_functions::is_pro_license_activated() && Wpfnl_functions::is_wc_active() ) ? wc_price((int)$wpdb->get_var($wpdb->prepare("SELECT SUM(upsell_sales + downsell_sales) FROM {$wpdb->prefix}wpfnl_stats WHERE status = %s", 'completed'))) : number_format(0, 2),
				'show_pro' => true
			],
			[
				'title'    => 'Total Leads',
				'amount'   => $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}wpfnl_optin_entries",)),
				'show_pro' => false
			],
		];

		return $response;
	}

	/**
	 * Get all post from url
	 *
	 * @param $url
	 * @param $args
	 *
	 * @return array
	 */
	private function remote_get($url, $args = [])
	{
		$response = wp_remote_get($url, $args);

		if (is_wp_error($response) || ! is_array($response) || ! isset($response['body'])) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
				'data'    => $response,
			];
		}

		// Decode the results.
		$results = json_decode($response['body'], true);

		if (! is_array($results)) {
			return new \WP_Error('unexpected_data_format', 'Data was not returned in the expected format.');
		}

		return [
			'success' => true,
			'message' => 'Data successfully retrieved',
			'data'    => json_decode(wp_remote_retrieve_body($response), true),
		];
	}


	/**
	 * Get wpfunnels dashboard overview widget footer actions.
	 *
	 * Retrieves the footer action links displayed in wpfunnels dashboard widget.
	 *
	 * @since  1.9.0
	 * @access private
	 */
	private function get_wpfunnels_dashboard_overview_widget_footer_actions()
	{
		$actions = [
			'go-pro' => [
				'title' => esc_html__('Upgrade to Pro', 'wpfnl'),
				'link' => 'https://getwpfunnels.com/pricing/',
			],
			'blog' => [
				'title' => esc_html__('Blogs', 'wpfnl'),
				'link' => 'https://getwpfunnels.com/blog/',
			],
			'changelog' => [
				'title' => esc_html__('Changelogs', 'wpfnl'),
				'link' => 'https://getwpfunnels.com/changelog/',
			],
			'help' => [
				'title' => esc_html__('Get Help', 'wpfnl'),
				'link' => 'https://getwpfunnels.com/docs/getting-started-with-wpfunnels/',
			],

		];

		if (Wpfnl_functions::is_pro_license_activated()) {
			unset($actions['go-pro']);
		}

		return $actions;
	}



	/**
	 * Funnel action links
	 *
	 * @since 1.0.0
	 */
	public function add_funnel_action_links($actions)
	{
		$documentation = array(
			'<a href="https://getwpfunnels.com/docs/wpfunnels-wordpress-funnel-builder/" target="_blank" >Documentation</a>',
		);
		$is_pro_installed = Wpfnl_functions::is_plugin_installed('wpfunnels-pro/wpfnl-pro.php');
		if (!defined('WPFNL_PRO_VERSION') && !$is_pro_installed) {
			$documentation[] = '<a href="https://getwpfunnels.com/pricing/" target="_blank" style="color:#6E42D3;font-weight:bold" >Upgrade to Pro</a>';
		}
		$actions = array_merge($actions, $documentation);
		return $actions;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles($hook)
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ('wpfunnels_page_email-builder' === $hook) {
			wp_enqueue_style($this->plugin_name . '-email-builder', plugin_dir_url(__FILE__) . 'assets/dist/email-builder/email-builder.css', [], $this->version, 'all');
		}

		if ($this->is_current_page('wpfunnels')) {
			wp_enqueue_style('wpfnl-admin-app', plugin_dir_url(__FILE__) . 'assets/css/admin-app.css', [], $this->version, 'all');
		}


		if (in_array($hook, $this->page_hooks)) {
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_style($this->plugin_name . '-jquery-ui', plugin_dir_url(__FILE__) . 'assets/css/jquery-ui.min.css', [], $this->version, 'all');
			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'assets/css/wpfnl-admin.css', [], $this->version, 'all');
			do_action('wpfunnels_after_styles_loaded');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts($hook)
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */


		$screen = get_current_screen();


		if ('dashboard' === $screen->id || 'plugins' === $screen->id) {
			wp_enqueue_script($this->plugin_name . '-global', plugin_dir_url(__FILE__) . 'assets/js/wpfnl-global.js', ['jquery', 'jquery-ui-sortable'], $this->version, true);
		}

		if ('wpfunnels_page_email-builder' === $hook) {
			$is_wc_installed = 'no';
			$is_wpfunnels_installed = 'no';
			if (is_plugin_active('woocommerce/woocommerce.php')) {
				$is_wc_installed = 'yes';
			}
			if (is_plugin_active('wpfunnels/wpfunnels.php')) {
				$is_wpfunnels_installed = 'yes';
			}
			$dependency = require_once WPFNL_PATH . '/admin/assets/dist/email-builder/main.min.asset.php';
			wp_enqueue_script(
				$this->plugin_name . '-email-builder',
				plugin_dir_url(__FILE__) . 'assets/dist/email-builder/main.min.js',
				$dependency['dependencies'],
				$this->version,
				true
			);
			wp_localize_script(
				$this->plugin_name . '-email-builder',
				'WPFEmailBuilderVars',
				array(
					'editor_data_source' => $this->get_editor_source(),
					'post_types'		 => MrmCommon::get_all_post_types(),
					'is_wc_installed'    => $is_wc_installed,
					'is_mint_active'     => Wpfnl_functions::is_mint_mrm_active(),
				)
			);
		}

		if (in_array($hook, $this->page_hooks)) {

			$is_wc_installed = 'no';
			$is_wpfunnels_installed = 'no';
			if (is_plugin_active('woocommerce/woocommerce.php')) {
				$is_wc_installed = 'yes';
			}
			if (is_plugin_active('wpfunnels/wpfunnels.php')) {
				$is_wpfunnels_installed = 'yes';
			}

			wp_enqueue_script($this->plugin_name . '-select2', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', ['jquery'], $this->version, true);
			$products = array();
			if (isset($_GET['step_id'])) {
				$step_id = sanitize_text_field($_GET['step_id']);
				if ($step_id) {
					if (Wpfnl_functions::check_if_this_is_step_type_by_id($step_id, 'checkout')) {
						$products = get_post_meta($step_id, '_wpfnl_checkout_products', true);
						if (empty($products)) {
							$products = [];
						}
					}
				}
			}

			$funnel_id 		= '';
			$funnel_title 	= '';
			$step_id 		= '';
			if (isset($_GET['id'])) {
				$funnel_id 		= $_GET['id'];
				$funnel_title 	= html_entity_decode(get_the_title($funnel_id));
				if (isset($_GET['step_id'])) {
					$step_id = filter_input(INPUT_GET, 'step_id', FILTER_VALIDATE_INT);
				}
			}

			/**
			 * Get funnel preview link
			 */
			$steps 					= get_post_meta($funnel_id, '_steps_order', true);
			$funnel_preview_link 	= '#';
			$response['success'] = false;
			if ($steps) {
				if (isset($steps[0]) && $steps[0]['id']) {
					$funnel_preview_link = get_post_permalink($steps[0]['id']);
				}
			}


			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_editor();
			wp_enqueue_script('wp-color-picker');
			wp_enqueue_media();

			wp_enqueue_script($this->plugin_name . '-jed', plugin_dir_url(__FILE__) . 'assets/js/jed.js', ['jquery'], $this->version, true);
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'assets/js/wpfnl-admin.js', ['jquery', 'jquery-ui-sortable'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-runtime', plugin_dir_url(__FILE__) . 'assets/dist/runtime/index.min.js', ['jquery', 'wp-i18n', 'wp-util', 'updates', 'wp-color-picker', $this->plugin_name . '-jed'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-funnel-window', plugin_dir_url(__FILE__) . 'assets/dist/js/funnel-components.min.js', ['jquery', 'wp-i18n', 'wp-util', 'updates', 'wp-color-picker', $this->plugin_name . '-jed', $this->plugin_name . '-runtime'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-backbone-marionette', plugin_dir_url(__FILE__) . 'assets/lib/backbone/backbone.marionette.min.js', ['backbone'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-backbone-radio', plugin_dir_url(__FILE__) . 'assets/lib/backbone/backbone.radio.min.js', ['backbone'], $this->version, true);

			$general_settings 	= Wpfnl_functions::get_general_settings();
			$builder 			= $general_settings['builder'];

			/**
			 * This code snippet will check if pro addons is activated or not. if not activated
			 * Total number of funnels will be maximum 3, otherwise customer can add as more funnels
			 * As they want
			 */
			$is_pro_active = apply_filters('wpfunnels/is_pro_license_activated', false);
			$count_funnels = wp_count_posts('wpfunnels')->publish + wp_count_posts('wpfunnels')->draft + wp_count_posts('wpfunnels')->trash;
			$count_active_funnels = wp_count_posts('wpfunnels')->publish + wp_count_posts('wpfunnels')->draft;
			$total_allowed_funnels = 3;
			if ($is_pro_active) {
				$total_allowed_funnels = -1;
			}

			$wc_currency_symbol = '$';
			if (function_exists('get_woocommerce_currency_symbol')) {
				$wc_currency_symbol = html_entity_decode(get_woocommerce_currency_symbol());
			}
			$ld_currency_symbol = '$';
			if (function_exists('learndash_get_currency_symbol')) {
				$ld_currency_symbol = html_entity_decode(learndash_get_currency_symbol());
			}

			$product_url = esc_url_raw(
				add_query_arg(
					array(
						'post_type'      => 'product',
						'wpfunnels' => 'yes',
					),
					admin_url('post-new.php')
				)
			);

			wp_localize_script($this->plugin_name, 'WPFunnelVars', array(
				'ajaxurl' 					=> admin_url('admin-ajax.php'),
				'rest_api_url' 				=> get_rest_url(),
				'security' 					=> wp_create_nonce('wpfnl-admin'),
				'admin_url' 				=> admin_url(),
				'edit_funnel_url' 			=> admin_url('admin.php?page=edit_funnel'),
				'i18n'                      => array('wpfnl' => $this->get_jed_locale_data('wpfnl')),
				'current_user_id'           => get_current_user_id(),
				'is_wc_installed' 			=> $is_wc_installed,
				'is_wpfunnels_installed'	=> $is_wpfunnels_installed,
				'products' 					=> $products,
				'funnel_id' 				=> $funnel_id,
				'step_id' 					=> $step_id,
				'funnel_title' 				=> $funnel_title,
				'funnel_preview_link' 		=> $funnel_preview_link,
				'site_url'	 				=> site_url(),
				'image_path' 				=> WPFNL_URL . 'admin/assets/images',
				'placeholder_image_path' 	=> WPFNL_URL . 'admin/assets/images/ob_placeholder.png',
				'nonce' 					=> wp_create_nonce('wp_rest'),
				'isNewFunnel' 				=> \Wpfnl_Activator::is_new_install() ? true : false,
				'isProActivated' 			=> $is_pro_active,
				'totalFunnels' 				=> $count_funnels,
				'count_active_funnels' 		=> $count_active_funnels,
				'product_url' 				=> $product_url,
				'totalAllowedFunnels' 		=> $total_allowed_funnels,
				'builder' 					=> $builder,
				'dependencyPlugins' 		=> Wpfnl_functions::get_dependency_plugins_status(),
				'isAnyPluginMissing' 		=> Wpfnl_functions::is_any_plugin_missing(),
				'isGlobalFunnelActivated' 	=> Wpfnl_functions::is_global_funnel_activated(),
				'isGlobalFunnel' 			=> Wpfnl_functions::is_global_funnel($funnel_id),
				'GBFVersion' 			    => defined('WPFNL_PRO_GB_VERSION') ? WPFNL_PRO_GB_VERSION : '',
				'isSkipOffer' 			    => $this->is_skip_offer(),
				'paymentMethod' 			=> $this->get_payment_method(),
				'shippingMethod' 			=> $this->get_shipping_method(),
				'isGbf' 					=> $this->maybe_gbf($funnel_id),
				'isLms' 					=> $this->maybe_lms($funnel_id),
				'isLmsActivated' 			=> Wpfnl_functions::is_lms_addon_active(),
				'isLmsSettings' 			=> Wpfnl_functions::is_enable_lms_settings(),
				'isLmsDisbaled' 	        => $this->maybe_lms_settings_disbaled(),
				'lmsVersion'				=> defined('WPFNL_PRO_LMS_VERSION') || defined('WPFUNNELS_PRO_LMS_VERSION') ? version_compare(LEARNDASH_VERSION, '4.2.1.1', '>=') : false,
				'global_funnel_type' 		=> Wpfnl_functions::get_global_funnel_type(),
				'individual_funnel_type' 	=> get_post_meta($funnel_id, '_wpfnl_funnel_type', true),
				'gbf_set_condition_steps'	=> $this->get_gbf_steps($funnel_id),
				'reconfigurable_condition_data'	=> $this->get_reconfigurable_condition_data($funnel_id),
				'mint_steps'				=> $this->mint_step_settings($funnel_id),
				'mint_tags'					=> Wpfnl_functions::get_mint_contact_groups('tags'),
				'mint_lists'				=> Wpfnl_functions::get_mint_contact_groups('lists'),
				'mint_sequences'			=> Wpfnl_functions::get_sequences(),
				'obList'					=> Wpfnl_functions::get_all_ob($funnel_id),
				'upsellSteps'				=> Wpfnl_functions::get_selected_steps('upsell', $funnel_id),
				'downsellSteps'				=> Wpfnl_functions::get_selected_steps('downsell', $funnel_id),
				'landingAndCustomSteps'		=> Wpfnl_functions::get_selected_steps('landing', $funnel_id),
				'customSteps'				=> Wpfnl_functions::get_selected_steps('custom', $funnel_id),
				'onlySettingsSteps'			=> Wpfnl_functions::get_only_settings_steps(),
				'isWCML'					=> defined('WCML_VERSION'),
				'cfeDefault'			    => Wpfnl_functions::get_cfe_default_fields(),
				'adminEmail'				=> get_bloginfo('admin_email'),
				'siteName'					=> get_option('blogname'),
				'mailMintEmail'				=> Wpfnl_functions::get_mailmint_email(),
				'email_settings'			=> Wpfnl_functions::get_mailmint_email_settings(),
				'mint_twillo_settings'		=> Wpfnl_functions::get_mailmint_twillo_settings(),
				'emailBuilderUrl'			=> admin_url() . 'admin.php?page=email-builder',
				'currentUrl'				=> (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
				'wc_currency'				=> $wc_currency_symbol,
				'ld_currency'				=> $ld_currency_symbol,
				'getText'					=> Wpfnl_functions::get_text(),
				'user_role_manager_data'	=> Wpfnl_functions::get_user_role_settings(),
				'availableGateways'			=> Wpfnl_functions::get_enabled_payment_gateways(),
				'guidedTour'				=> Wpfnl_functions::get_guided_tour(),
			));
			wp_localize_script(
				$this->plugin_name,
				'template_library_object',
				array(
					'ajaxurl' 				=> esc_url_raw(admin_url('admin-ajax.php')),
					'rest_api_url' 			=> esc_url_raw(get_rest_url()),
					'dashboard_url' 		=> esc_url_raw(admin_url('admin.php?page=' . WPFNL_FUNNEL_PAGE_SLUG)),
					'settings_url' 			=> esc_url_raw(admin_url('admin.php?page=settings')),
					'home_url' 				=> esc_url_raw(home_url()),
					'funnel_id' 			=> $funnel_id,
					'isTemplatePage' 		=> 'wpfunnels_page_wpf_templates' === $hook,
					'is_pro' 				=> Wpfnl_functions::is_pro_license_activated(),
					'is_ab_tesing_available' => defined('WPFNL_PRO_VERSION') ? version_compare(WPFNL_PRO_VERSION, "1.7.3", ">=") : false,
					'is_webhook_licensed'   => Wpfnl_functions::is_webhook_license_activated(),
					'pro_url' 				=> add_query_arg('wpfnl-dashboard', '1', GETWPFUNNEL_PRICING_URL),
					'nonce' 				=> wp_create_nonce('wp_rest'),
					'image_path' 			=> WPFNL_URL . 'admin/assets/images',
					'template_type' 	    => Wpfnl_functions::get_template_types(),
					'countries' 	        => Wpfnl_functions::get_countries(),
					'supported_steps' 		=> Wpfnl_functions::get_supported_step_type(),
					'is_mint_active' 		=> Wpfnl_functions::is_mint_mrm_active(),
					'settingsSteps' 		=> Wpfnl_functions::get_settings_steps(),
					'isRemote' 				=> Wpfnl_functions::maybe_remote_funnel(),
				)
			);

			wp_localize_script(
				$this->plugin_name,
				'CheckoutStep',
				array(
					'ajaxurl' 			=> esc_url_raw(admin_url('admin-ajax.php')),
					'rest_api_url' 		=> esc_url_raw(get_rest_url()),
					'wc_currency' 		=> $wc_currency_symbol,
					'nonce' 			=> wp_create_nonce('wp_rest'),
					'security' 			=> wp_create_nonce('wpfnl-admin'),
					'image_path' 		=> WPFNL_URL . 'admin/assets/images',
					'tooltipIcon' 		=> WPFNL_URL . 'admin/partials/icons/question-tooltip-icon.php',
					'imageUploadIcon' 	=> WPFNL_URL . 'admin/partials/icons/image-upload-icon.php',
					'step_id' 			=> $step_id,
					'back' => add_query_arg(
						array(
							'page'      => WPFNL_EDIT_FUNNEL_SLUG,
							'id'        => filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT),
							'step_id'   => $step_id,
						),
						admin_url('admin.php')
					),
					'priceConfig'       => Wpfnl_functions::get_wc_price_config(),
				)
			);

			wp_localize_script(
				$this->plugin_name,
				'wpfnl_addons_vars',
				array(
					'addons' 		 => Wpfnl_functions::get_supported_addons(),
					'gbf_conditions' => Wpfnl_functions::get_supported_gbf_offer_condition(),
					'categories'	 => Wpfnl_functions::get_categories_gbf_offer_condition(),
					'role'			 => Wpfnl_functions::get_all_user_roles(),
					'tags'			 => Wpfnl_functions::get_all_tags(),
					'shippingClasses' => Wpfnl_functions::get_shipping_classes(),
				)
			);

			// Enqueue the admin dashboard script only on WPFunnels Main Page

			if ($this->is_current_page('wpfunnels')) {
				wp_enqueue_script('wpfnl-admin-app', plugin_dir_url(__FILE__) . 'assets/dist/js/wpfnl-admin-app.min.js', array(), $this->version, true);
				wp_localize_script('wpfnl-admin-app', 'WPFAdminObj', array(
					'rest_api_url'		=> get_rest_url(),
					'security'			=> wp_create_nonce('wpfnl-admin'),
					'nonce'				=> wp_create_nonce('wp_rest'),
					'priceConfig'   	=> Wpfnl_functions::get_wc_price_config(),
					'isProActivated' 	=> apply_filters('wpfunnels/is_wpfnl_pro_active', false),
					'isWPFIntegrationActive' 	=> Wpfnl_functions::is_integrations_addon_active(),
					'getText'					=> Wpfnl_functions::get_text(),
				));
			}


			/**
			 * Localize scripts for funnel window
			 *
			 * @var $localize
			 */

			$notices = $this->get_builder_notice();

			$localize = apply_filters('wpfunnels/funnel_window_admin_localize', array());
			wp_localize_script($this->plugin_name . '-funnel-window', 'wpfunnels_funnel_localize', array($localize));


			do_action('wpfunnels_after_scripts_loaded');
		}
	}


	/**
	 * Get the reconfigurable condition data for a funnel
	 */
	public function get_reconfigurable_condition_data($funnel_id)
	{
		if (!$funnel_id) {
			return [];
		}
		$data = get_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', true);
		if (!is_array($data) || empty($data)) {
			return [];
		}
		return $data;
	}


	/**
	 * Get all setps where GBF conditions is already set
	 *
	 * @param Integer $funnel_id
	 *
	 * @since 2.6.1
	 */
	private function get_gbf_steps($funnel_id)
	{
		$gbf_steps = [];
		if ($funnel_id) {
			$is_gbf = get_post_meta($funnel_id, 'is_global_funnel', true);
			if ('yes' === $is_gbf) {
				$steps = Wpfnl_functions::get_steps($funnel_id);
				if (is_array($steps)) {
					foreach ($steps as $step) {
						if (isset($step['id'], $step['step_type'])) {
							if ('checkout' === $step['step_type']) {
								$conditions = get_post_meta($funnel_id, 'global_funnel_start_condition', true);
								if (is_array($conditions)) {
									array_push($gbf_steps, $step['id']);
								}
							} elseif ('upsell' === $step['step_type'] || 'downsell' === $step['step_type']) {

								$conditions = get_post_meta($step['id'], 'global_funnel_' . $step['step_type'] . '_rules', true);
								if (is_array($conditions)) {
									array_push($gbf_steps, $step['id']);
								}
							}
						}
					}
				}
			}
		}

		return $gbf_steps;
	}


	/**
	 * Returns Jed-formatted localization data.
	 *
	 * @param string $domain Translation domain.
	 *
	 * @return array
	 */
	public function get_jed_locale_data($domain, $language_dir = null)
	{
		$plugin_translations = $this->get_translations_for_plugin_domain($domain, $language_dir);
		$translations = get_translations_for_domain($domain);

		$locale = array(
			'domain'      => $domain,
			'locale_data' => array(
				$domain => array(
					'' => array(
						'domain' => $domain,
						'lang'   => is_admin() ? get_user_locale() : get_locale(),
					),
				),
			),
		);

		if (! empty($translations->headers['Plural-Forms'])) {
			$locale['locale_data'][$domain]['']['plural_forms'] = $translations->headers['Plural-Forms'];
		} else if (! empty($plugin_translations['header'])) {
			$locale['locale_data'][$domain]['']['plural_forms'] = $plugin_translations['header']['Plural-Forms'];
		}

		$entries = array_merge($plugin_translations['translations'], $translations->entries);

		foreach ($entries as $msgid => $entry) {
			$locale['locale_data'][$domain][$msgid] = $entry->translations;
		}

		return $locale;
	}


	/**
	 * Get translactions for WePos plugin
	 *
	 * @param string $domain
	 * @param string $language_dir
	 *
	 * @return array
	 */
	public function get_translations_for_plugin_domain($domain, $language_dir = null)
	{
		if ($language_dir == null) {
			$language_dir      = WPFNL_PATH . '/languages/';
		}
		$languages     = get_available_languages($language_dir);
		$get_site_lang = is_admin() ? get_user_locale() : get_locale();
		$mo_file_name  = $domain . '-' . $get_site_lang;
		$translations  = [];

		if (in_array($mo_file_name, $languages) && file_exists($language_dir . $mo_file_name . '.mo')) {
			$mo = new \MO();
			if ($mo->import_from_file($language_dir . $mo_file_name . '.mo')) {
				$translations = $mo->entries;
			}
		}

		return [
			'header'       => isset($mo) ? $mo->headers : '',
			'translations' => $translations,
		];
	}


	/**
	 * Check GBF version for skip offer
	 */
	public function is_skip_offer()
	{
		if (defined('WPFNL_PRO_GB_VERSION')) {
			return version_compare(WPFNL_PRO_GB_VERSION, "1.0.9", ">=");
		}
		return false;
	}

	public function mint_step_settings($funnel_id)
	{
		$steps = get_post_meta($funnel_id, '_steps', true);
		$mint_steps = [];
		if (is_array($steps)) {
			foreach ($steps as $step) {
				$mint_settings['stepID'] = $step['id'];
				$value = get_post_meta($step['id'], '_wpfnl_automation_steps', true);
				$mint_settings['value'] = is_array($value) ? $value : [];
				array_push($mint_steps, $mint_settings);
			}
		}
		return $mint_steps;
	}


	/**
	 * Define w2cloud routes
	 *
	 * @return array
	 */
	public function get_wpfnl_routes()
	{
		$routes = array(
			array(
				'path' => '/',
				'name' => 'home',
				'component' => 'Home'
			)
		);
		return apply_filters('wpfnl_routes', $routes);
	}

	/**
	 * Get all active Payment Method
	 *
	 * @return array
	 */
	public function get_payment_method()
	{
		if (Wpfnl_functions::is_wc_active()) {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			$enabled_gateways = [];

			if ($gateways) {
				foreach ($gateways as $key =>  $gateway) {
					if ($gateway->enabled == 'yes') {
						$enabled_gateways[$key] = $gateway->method_title;
					}
				}
			}
			return $enabled_gateways;
		}
		return [];
	}

	/**
	 * Get all shipping methods
	 *
	 * @return array
	 */
	public function get_shipping_method()
	{
		if (Wpfnl_functions::is_wc_active()) {
			$available_shipping = WC()->shipping()->get_shipping_methods();
			$enabled_shipping = [];
			if ($available_shipping) {
				foreach ($available_shipping as $key =>  $shipping) {
					$enabled_shipping[$key] = $shipping->method_title;
				}
			}
			return $enabled_shipping;
		}
		return [];
	}


	/**
	 * Show funnel steps on reading
	 *
	 * @param $output
	 *
	 * @return mixed
	 */
	public function show_funnel_steps_on_reading($output)
	{
		global $pagenow;
		if (('options-reading.php' === $pagenow || 'customize.php' === $pagenow) && preg_match('#page_on_front#', $output)) {
			$output = $this->show_steps_options($output);
		}
		return $output;
	}


	/**
	 * Show steps pages on reading
	 *
	 * @param $output
	 *
	 * @return string
	 */
	private function show_steps_options($output)
	{
		$options 	= '';

		$steps = get_posts(
			array(
				'post_type'      => WPFNL_STEPS_POST_TYPE,
				'posts_per_page' => -1,
				'numberposts'    => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'   => '_step_type',
						'value' => 'landing',
					),
					array(
						'key'   => '_step_type',
						'value' => 'checkout',
					)
				),
			)
		);

		if ($steps && is_array($steps)) {
			$front_page_id 	= get_option('page_on_front');
			foreach ($steps as $step) {
				$selected      	= selected($front_page_id, $step->ID, false);
				$post_type_obj 	= get_post_type_object($step->post_type);
				$options 		.= "<option value=\"{$step->ID}\"{$selected}>{$step->post_title} (WPFunnels {$post_type_obj->labels->singular_name})</option>";
			}
			$options 	.= '</select>';
			$output 	= str_replace('</select>', $options, $output);
		}
		return $output;
	}


	/**
	 * Get notice for builder
	 *
	 * @return array
	 */
	public function get_builder_notice()
	{
		$builder = Wpfnl_functions::get_builder_type();
		$notices = array();
		if ('divi-builder' === $builder) {
			$notices['notices'][] = array();
		}
		return $notices;
	}


	/**
	 * Change text in order details page
	 */
	public function wpfnl_customizing_checkout_text($translated_text, $untranslated_text, $domain)
	{

		if (is_admin()) {
			if ($translated_text == 'Coupon(s):') {
				$translated_text = __('Discount:', 'wpfnl');
			}
		}

		return $translated_text;
	}


	/**
	 * Change local for if this is funnel canvas window
	 *
	 * @param $local
	 *
	 * @return string
	 *
	 * @since 2.3.8
	 */
	public function change_local_for_funnel_canvas($local)
	{
		if (Wpfnl_functions::is_funnel_canvas_window()) {
			return 'en_US';
		}
		return $local;
	}


	/**
	 * Add type meta
	 *
	 * @since 2.4.4
	 */
	public function wpfnl_add_type_meta()
	{
		Wpfnl_functions::add_type_meta();
	}


	/**
	 * Maybe Global funnel
	 *
	 * @param String
	 *
	 * @return Bool
	 * @since  2.4.11
	 */
	public function maybe_gbf($funnel_id)
	{

		if (is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php')) {
			if ($funnel_id) {
				$is_gbf = get_post_meta($funnel_id, 'is_global_funnel', true);
				if ('yes' == $is_gbf) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check funnel type is lms or not by funnel_id
	 *
	 * @return Bool
	 */
	public function maybe_lms($funnel_id)
	{

		$type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
		if ('lms' === $type) {
			return true;
		}
		return false;
	}

	/**
	 * May be lms settings is disbaled
	 */
	public function maybe_lms_settings_disbaled()
	{
		return Wpfnl_functions::is_wc_active() && Wpfnl_functions::is_lms_addon_active();
	}


	/**
	 * Trigger when any plugin is deactivated
	 *
	 * @param $filename
	 *
	 * @since 2.5.1
	 */
	public function wpfnl_deactivated_plugin($filename)
	{
		if ('woocommerce/woocommerce.php' === $filename) {
			if (!Wpfnl_functions::is_lms_addon_active()) {
				Wpfnl_functions::update_funnel_type_to_lead();
			}
			Wpfnl_functions::wpfnl_delete_transient();
		} elseif ('sfwd-lms/sfwd_lms.php' === $filename) {
			if (!Wpfnl_functions::is_wc_active()) {
				Wpfnl_functions::update_funnel_type_to_lead();
			}
			Wpfnl_functions::wpfnl_delete_transient();
		} elseif ('wpfunnels-pro-lms/wpfunnels-pro-lms.php' === $filename) {
			Wpfnl_functions::wpfnl_delete_transient();
		} elseif ('qubely/qubely.php' === $filename) {
			Wpfnl_functions::wpfnl_delete_transient();
			delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_wc');
			delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_lms');
			delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
			delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
			delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
		}
	}


	/**
	 * Trigger when any plugin is activated
	 *
	 * @param $filename
	 *
	 * @since 2.5.1
	 */
	public function wpfnl_activated_plugin($filename)
	{
		if ('woocommerce/woocommerce.php' === $filename) {
			Wpfnl_functions::wpfnl_delete_transient();
		} elseif ('sfwd-lms/sfwd_lms.php' === $filename) {
			if (Wpfnl_functions::is_lms_addon_active()) {
				Wpfnl_functions::wpfnl_delete_transient();
			}
		} elseif ('wpfunnels-pro-lms/wpfunnels-pro-lms.php' === $filename) {
			Wpfnl_functions::wpfnl_delete_transient();
		} elseif ('qubely/qubely.php' === $filename) {
			Wpfnl_functions::wpfnl_delete_transient();
			delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_wc');
			delete_option(WPFNL_TEMPLATES_OPTION_KEY . '_lms');
			delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
			delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
			delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
		}
	}



	/**
	 * Get editor source data
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_editor_source()
	{
		// get product categories for email builder.
		$wc_categories = $this->get_formatted_wc_categories();
		$wp_categories = $this->get_formatted_wp_post_categories();

		return apply_filters(
			'plugin_hook_name',
			array(
				'product_categories' => $wc_categories,
				'post_categories'    => $wp_categories,
				'placeholder_image'  => ''
			)
		);
	}



	/**
	 * Get the WooCommerce product categories
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_formatted_wc_categories()
	{
		$taxonomy     = 'product_cat';
		$orderby      = 'name';
		$show_count   = 0;
		$pad_counts   = 0;
		$hierarchical = 1;
		$title        = '';
		$empty        = 0;

		$args               = array(
			'taxonomy'     => $taxonomy,
			'orderby'      => $orderby,
			'show_count'   => $show_count,
			'pad_counts'   => $pad_counts,
			'hierarchical' => $hierarchical,
			'title_li'     => $title,
			'hide_empty'   => $empty,
		);
		$product_categories = get_categories($args);
		$wc_categories      = array();
		foreach ($product_categories as $product_cat) {
			$wc_categories[] = array(
				'value' => $product_cat->term_id,
				'label' => $product_cat->name,
			);
		}

		return $wc_categories;
	}


	/**
	 * Get the WordPress post categories
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_formatted_wp_post_categories()
	{
		$taxonomy     = 'category';
		$orderby      = 'name';
		$show_count   = 0;
		$pad_counts   = 0;
		$hierarchical = 1;
		$title        = '';
		$empty        = 0;

		$args               = array(
			'taxonomy'     => $taxonomy,
			'orderby'      => $orderby,
			'show_count'   => $show_count,
			'pad_counts'   => $pad_counts,
			'hierarchical' => $hierarchical,
			'title_li'     => $title,
			'hide_empty'   => $empty,
		);
		$post_categories = get_categories($args);
		$categories      = array();
		foreach ($post_categories as $post_cat) {
			$categories[] = array(
				'value' => $post_cat->term_id,
				'label' => $post_cat->name,
			);
		}
		return $categories;
	}


	/**
	 * Add custom capabilities to administrator role
	 *
	 * @since 3.1.3
	 */
	public function attach_admin_capabilities()
	{
		global $wp_roles;
		$capabilities = array(
			'wpf_manage_funnels',
		);
		foreach ($capabilities as $cap) {
			$wp_roles->add_cap('administrator', $cap);
		}
	}

	/**
	 * Checks if the current page slug matches the provided slug.
	 *
	 * @param $slug
	 * @return bool
	 * @since 3.5.0
	 */
	public function is_current_page($slug)
	{
		if (empty($slug)) {
			return false;
		}
		$current_page_slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
		if ($slug === $current_page_slug) {
			return true;
		}
		return false;
	}


	/**
	 * Create contact on mailmint when user allows to track data
	 *
	 * @param array $data
	 *
	 * @since 3.3.1
	 */
	public function wpfunnels_tracker_optin($data)
	{
		if (isset($data['admin_email'], $data['first_name'], $data['last_name'])) {
			$json_body_data = json_encode([
				'email'         => $data['admin_email'],
				'first_name'    => $data['first_name'],
				'last_name'     => $data['last_name'],
			]);

			$webHookUrl = [
				'https://staging-useraccount.kinsta.cloud/?mailmint=1&route=webhook&topic=contact&hash=b258136d-6759-4e91-bbab-0b7397af6dc7'
			];

			try {
				if (!empty($webHookUrl)) {
					foreach ($webHookUrl as $url) {
						wp_remote_request($url, [
							'method'    => 'POST',
							'headers' => [
								'Content-Type' => 'application/json',
							],
							'body' => $json_body_data
						]);
					}
				}
			} catch (\Exception $e) {
			}
		}
	}

	/**
	 * Creates a new MRM contact.
	 *
	 * @param array $data The data for the contact.
	 * @return void
	 */
	public function create_mrm_contact($data)
	{
		if (!isset($data['data']['user_email'])) {
			return;
		}
		// Get double opt-in settings.
		$default  =  \MRM\Common\MrmCommon::double_optin_default_configuration();
		$settings = get_option('_mrm_optin_settings', $default);
		$enable   = isset($settings['enable']) ? $settings['enable'] : false;

		$user_data     = array(
			'email' 	  => $data['data']['user_email'],
			'first_name'  => isset($data['data']['first_name']) ? $data['data']['first_name'] : '',
			'last_name'   => isset($data['data']['last_name']) ? $data['data']['last_name'] : '',
			'meta_fields' => array(
				'phone_number' => isset($data['data']['phone_number']) ? $data['data']['phone_number'] : '',
			),
			'status'      => $enable ? 'pending' : 'subscribed',
			'source'      => 'WPFunnels',
		);

		$mail_mint_object = new \WPFunnels\Integrations\MailMint($user_data);
		$mail_mint_object->create_or_update_contact();
	}
}
