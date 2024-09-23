<?php
/**
 * LMS order details
 * 
 * @package
 */
namespace WPFunnels\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Wpfnl_functions;

class WPFNL_Lms_Order_details extends ET_Builder_Module {

    public $slug       = 'wpfnl_lms_order_details';
    public $vb_support = 'on';

    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );
    /**
     * Module properties initialization
     */
    public function init() {

        $this->name = __( 'WPF LMS Order Details', 'wpfnl' );

        $this->icon_path        =  plugin_dir_path( __FILE__ ) . 'order_details.svg';

        $this->settings_modal_toggles  = array(
            'general'  => array(
                'toggles' => array(
                    'order_details'     => __( 'Order Details', 'wpfnl' ),
                ),
            ),
			'advanced' => array(
				'toggles' => array(
					'global_section' => array(
						'title'             => __( 'Footer Style', 'wpfnl' ),
						'priority'          => 75,
					),
					'form_field'  => array(
						'title'    => __( 'Header Style', 'wpfnl' ),
						'priority' => 60,
					),
					'order_body'  => array(
						'title'    => __( ' Order Body Style', 'wpfnl' ),
						'priority' => 65,
					),
					'body'         => array(
						'title'             => __( 'Order Body Title ', 'wpfnl' ),
						'priority'          => 70,
					),
				),
			),
        );
		$this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'        => array(
				'body'         => array(
					'label'       => __( 'Order body', 'wpfnl' ),
					'css'         => array(

						// Accepts only string and not array. Hence using `implode`.
						'main'        => implode(
							', ',
							array(
								'%%order_class%%  .lms-order-details .order-details-table .order-item.course-info > span',
								'%%order_class%%  .lms-order-details .order-details-table .order-item.payment-info > span',
								'%%order_class%%  .lms-order-details .order-details-table .order-item.status-info > span'
							)
						),
						'important' => array('all'),

						// Accepts only string and not array. Hence using `implode`.
						'line_height' => implode(
							', ',
							array(
								'%%order_class%%  .lms-order-details .order-details-table .order-item.course-info > span',
								'%%order_class%%  .lms-order-details .order-details-table .order-item.payment-info > span',
								'%%order_class%%  .lms-order-details .order-details-table .order-item.status-info > span'
							)
						),
					),
					'font_size'   => array(
						'default' => '14px',
					),
					'line_height' => array(
						'default' => '1.5em',

					),
					'toggle_slug' => 'body',
					'sub_toggle'  => 'p',
				),
				'global_section' => array(
					'label'       => __( 'Footer','wpfnl' ),
					'css'         => array(
						'main'      => implode(
							',',
							array(
								'%%order_class%% .lms-order-details .order-details-table-footer .footer-list span',
								' %%order_class%% .lms-order-details .order-details-title',
								' %%order_class%% .lms-order-details .order-details-title span',
							)
						),
						'important' => array( 'size', 'line-height' ),
					),
					'font_size'   => array(
						'default' => '14px',
					),
					'line_height' => array(
						'default' => '1.7em',
					),
					'toggle_slug' => 'global_section',
					'sub_toggle'  => 'p',
				),
			),
			'text'         => false,
			'link_options' => false,
			'form_field'   => array(
				'form_field'  => array(
					'label'           => __( 'Header', 'wpfnl' ),
					'css'         => array(
						'main'    => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header,%%order_class%% .lms-order-details .order-details-table .order-tbl-header span',
						'padding' => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
						'margin'  => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
						'important' => ['all'],
					),
					'box_shadow'      => array(
						'css' => array(
							'main' => implode(
								',',
								array(
									'%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
								)
							),
							'important' => array('all'),
						),
					),
					'border_styles'   => array(
						'form_field'       => array(
							'label_prefix' => __( 'Fields', 'wpfnl' ),
							'css'          => array(
								'main' => array(
									'border_styles' => implode(
										',',
										array(
											'%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
										)
									),
									'border_radii'  => implode(
										',',
										array(
											'%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
										)
									),
								),
							),
							'defaults'     => array(
								'border_radii'  => 'on|0px|0px|0px|0px',
								'border_styles' => array(
									'width' => '0px',
									'style' => 'solid',
								),
							),
						),
					),
					'margin_padding'  => array(
						'css' => array(
							'main'    => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
							'padding' => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
							'margin'  => '%%order_class%% .lms-order-details .order-details-table .order-tbl-header',
							'important' => 'all',
						),
					),
					'font_field'      => array(
						'css'         => array(
							'main'      => array(
								'%%order_class%% .lms-order-details .order-details-table .order-tbl-header > span',
							),
							// Required to override default WooCommerce styles.
							'important' => array( 'line-height', 'size', 'font' ),
						),
						'font_size'   => array(
							'default' => '14px',
						),
						'line_height' => array(
							'default' => '1.7em',
						),
					),
					'width'           => array(),
					'toggle_priority' => 55,
				),
				'order_body'  => array(
					'label'           => __( 'Body', 'wpfnl' ),
					'css'         => array(
						'main'    => '%%order_class%% .lms-order-details .order-details-table',
						'padding' => '%%order_class%% .lms-order-details .order-details-table .order-item',
						'margin'  => '%%order_class%% .lms-order-details .order-details-table',
						'important' => ['all'],
					),
					'margin_padding' => array(
						'css' => array(
							'padding'        => implode(
								', ',
								array(
									'%%order_class%%  .lms-order-details .order-details-table .order-item.course-info',
									'%%order_class%%  .lms-order-details .order-details-table .order-item.payment-info',
									'%%order_class%%  .lms-order-details .order-details-table .order-item.status-info',
								)
							),
							'important' => ['all'],
						),

					),
					'font_field'  => array(
						'css'         => array(
							'%%order_class%%  .lms-order-details .order-details-table .order-item.course-info > span',
							'%%order_class%%  .lms-order-details .order-details-table .order-item.payment-info > span',
							'%%order_class%%  .lms-order-details .order-details-table .order-item.status-info > span'
						),
						'font_size'   => array(
							'default' => '22px',
						),
						'line_height' => array(
							'default' => '1em',
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title',
					),
					'box_shadow'      => array(
						'css' => array(
							'main' => implode(
								',',
								array(
									'%%order_class%% .lms-order-details .order-details-table',
								)
							),
							'important' => 'all',
						),
					),
					'border_styles'   => array(
						'form_field'       => array(
							'label_prefix' => __( 'Fields', 'wpfnl' ),
							'css'          => array(
								'main' => array(
									'border_styles' => implode(
										',',
										array(
											'%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
										)
									),
									'border_radii'  => implode(
										',',
										array(
											'%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
										)
									),
								),
							),
							'defaults'     => array(
								'border_radii'  => 'on|0px|0px|0px|0px',
								'border_styles' => array(
									'width' => '0px',
									'style' => 'solid',
								),
							),
						),
						'order_body'       => array(
							'label_prefix' => __( 'Fields', 'wpfnl' ),
							'css'          => array(
								'main' => array(
									'border_styles' => implode(
										',',
										array(
											'%%order_class%% .lms-order-details .order-details-table .order-item.course-info',
											'%%order_class%%  .lms-order-details .order-details-table .order-item.payment-info ',
											'%%order_class%%  .lms-order-details .order-details-table .order-item.status-info '
										)
									),
									'border_radii'  => implode(
										',',
										array(
											'%%order_class%% .lms-order-details .order-details-table',
										)
									),
								),
							),
							'defaults'     => array(
								'border_radii'  => 'on|0px|0px|0px|0px',
								'border_styles' => array(
									'width' => '1px',
									'style' => 'solid',
								),
							),
						),
					),
					'width'           => array(),
					'toggle_priority' => 55,
				),
			),
			'borders' =>array(
				'title' =>	array(
					'css'             => array(
						'main' => array(
							'border_radii' => "%%order_class%% h3",
							'border_styles' => "%%order_class%% h3",
						)
					),
					'label_prefix'    => __( 'Heading', 'wpfnl' ),
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'title',
				)
			),
			'animation' => false,
			'box_shadow' => false,

		);

    }

    /**
     * Module's specific fields
     *
     * The following modules are automatically added regardless being defined or not:
     *   Tabs     | Toggles          | Fields
     *   --------- ------------------ -------------
     *   Content  | Admin Label      | Admin Label
     *   Advanced | CSS ID & Classes | CSS ID
     *   Advanced | CSS ID & Classes | CSS Class
     *   Advanced | Custom CSS       | Before
     *   Advanced | Custom CSS       | Main Element
     *   Advanced | Custom CSS       | After
     *   Advanced | Visibility       | Disable On
     *
     * @return array
     */

    public function get_fields() {

        return array(
            'enable_course_review'       => array(
                'label'            => __( 'Enable Course Overview', 'wpfnl' ),
                'description'      => __( 'Enable Course Overview', 'wpfnl' ),
                'type'             => 'yes_no_button',
                'option_category'  => 'configuration',
                'options'          => array(
                    'off' => __( 'No','wpfnl' ),
                    'on'  => __( 'Yes','wpfnl' ),
                ),
                'default'          => 'on',
                'default_on_front' => 'on',
                'toggle_slug'      => 'order_details',
                'computed_affects' => array(
                    '__lmsOrderDetails',
                ),
            ),
            'enable_payment_method'       => array(
                'label'            => __( 'Enable Payment Method', 'wpfnl' ),
                'description'      => __( 'Enable Payment Method', 'wpfnl' ),
                'type'             => 'yes_no_button',
                'option_category'  => 'configuration',
                'options'          => array(
                    'off' => __( 'No','wpfnl' ),
                    'on'  => __( 'Yes','wpfnl' ),
                ),
                'default'          => 'on',
                'default_on_front' => 'on',
                'toggle_slug'      => 'order_details',
                'computed_affects' => array(
                    '__lmsOrderDetails',
                ),
            ),
            'enable_course_details'       => array(
                'label'            => __( 'Enable Course Status', 'wpfnl' ),
                'description'      => __( 'Enable Course Status', 'wpfnl' ),
                'type'             => 'yes_no_button',
                'option_category'  => 'configuration',
                'options'          => array(
                    'off' => __( 'No','wpfnl' ),
                    'on'  => __( 'Yes','wpfnl' ),
                ),
                'default'          => 'on',
                'default_on_front' => 'on',
                'toggle_slug'      => 'order_details',
                'computed_affects' => array(
                    '__lmsOrderDetails',
                ),
            ),
            '__lmsOrderDetails'        => array(
                'type'                => 'computed',
                'computed_callback'   => array(
                    'WPFunnels\Widgets\DiviModules\Modules\WPFNL_Lms_Order_details',
                    'get_lms_order_details',
                ),
                'computed_depends_on' => array(
                    'enable_course_review',
                    'enable_payment_method',
                    'enable_course_details',
                )
            ),
        );

    }

    /**
     * Computed checkout form
     *
     * @param $props
	 * 
     * @return string
     */

    public static  function get_lms_order_details($props) {

        $step_id = isset($_POST['current_page']['id']) ? $_POST['current_page']['id'] : get_the_ID();
		$step_type = get_post_meta($step_id,'_step_type',true);
		ob_start();
		if (!$step_type){
			echo __('Sorry, Please place the element in WPFunnels Thank You page','wpfnl');
		}else{
			$funnel_id = get_post_meta($step_id,'_funnel_id',true);
			$get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
			if($get_learn_dash_setting == 'yes'){
				$order_details = Wpfnl_lms_learndash_functions::get_lms_order_details( get_current_user_id() ,$funnel_id );
				if (!empty($order_details)){
					foreach($order_details as $details){
						$course_details =  $details['course_details'];
						$payment_method =  $details['payment_method'];

						$course_type 		= $course_details['type'];
						$billing_cycle 		= $course_details['billing_cycle'];
						$recurring_time 	= $course_details['recurring_time'];
						$price 				= $course_details['price'];
						$trial_price 		= $course_details['trial_price'];
						$trial_period 		= $course_details['trial_period'];
						$is_expire 			= $course_details['is_expire'];
						$expire_days 		= $course_details['expire_days'];
						$currency  = $course_details['currency'];
						$price  			= isset($details['amount']) ? $details['amount'] : $price;

						//----course type------
						if( $course_type == 'subscribe' ) {
							$course_type_text = 'Subscription';

						}else if( $course_type == 'open' || $course_type == 'free' ){
							$course_type_text = 'Free';

						}else if( $course_type == 'paynow' ){
							$course_type_text = 'One-time Payment';

						}else {
							$course_type_text = 'Closed';
						}

						//----billing cycle unit------
						if( $course_details['billing_cycle_unit'] == 'Y' ) {
							$billing_cycle_unit_text = 'year';

						}else if( $course_details['billing_cycle_unit'] == 'M' ){
							$billing_cycle_unit_text = 'month';

						}else if( $course_details['billing_cycle_unit'] == 'W' ){
							$billing_cycle_unit_text = 'week';

						}else {
							$billing_cycle_unit_text = 'day';
						}

						//----trial period unit------
						if( $course_details['trial_period_unit'] == 'Y' ) {
							$trial_period_unit_text = 'year';

						}else if( $course_details['trial_period_unit'] == 'M' ){
							$trial_period_unit_text = 'month';

						}else if( $course_details['trial_period_unit'] == 'W' ){
							$trial_period_unit_text = 'week';

						}else {
							$trial_period_unit_text = 'day';
						}

						?>
						<div class="lms-order-details">
							<!-- <h2 class="order-details-title"><span>Thank you.</span> You have been enrolled to your courses.</h2> -->

							<div class="order-details-table">
								<div class="order-item order-tbl-header">
									<?php
									if( $details['step_type'] == 'upsell' || $details['step_type'] == 'downsell' ){
										?>
										<span class="course-name">Course Offer</span>
										<?php
									}else{
										?>
										<span class="course-name">Course</span>
										<?php
									}
									?>


									<span class="price-total">Total</span>
								</div>

								<?php if($props['enable_course_review'] == 'on') {?>
									<div class="order-item course-info">
									<span class="course-name">
										<span><?php echo $course_details['title']; ?></span>
										<a href="<?php echo get_post_permalink($course_details['id']) ?>">View Course</a>
									</span>

										<span class="price-total">

									<?php
									if( $course_type == 'subscribe' ){
										echo  $currency.$price.'/'.$billing_cycle_unit_text;

									} else if( $course_type == 'paynow' ){
										echo $currency.$price;
									}else{
										echo $course_type_text;
									}
									?>


									<?php //echo $course_details['currency'].$course_details['price']; ?>
								</span>
									</div>
								<?php } ?>

								<?php if($props['enable_payment_method'] == 'on') {?>
									<div class="order-item payment-info">
								<span class="course-name">
									Payment method
								</span>

										<span class="price-total">
									<?php echo $payment_method; ?>
								</span>
									</div>
								<?php } ?>

								<?php if($props['enable_course_details'] == 'on') {?>
									<div class="order-item status-info">
								<span class="course-name">
									Status
								</span>

										<span class="price-total">
									<?php
									if( $course_type == 'subscribe' ){
										if( !empty( $trial_period ) ){
											echo $trial_period.' '.strtolower($trial_period_unit_text).' trial '.'('.$currency.$trial_price.')';
										}else{
											echo $currency.$price;
										}

									}else{
										echo __('Enrolled','wpfnl');

										if( $is_expire == 'on' ){
											echo '<small> (expires in '.$expire_days.' days)</small>';
										}
									}
									?>

								</span>
									</div>
								<?php } ?>
							</div>

							<div class="order-details-table-footer">
								<div class="footer-list">
									<span class="total-title">Total Payment</span>
									<span class="total-price"><?php
										if($payment_method == 'free'){
											echo $course_details['currency']."0";
										}elseif( !empty( $trial_period ) ){
											echo $currency.$trial_price;
										}else{
											echo $course_details['currency'].$price;
										}

										?>
									</span>
								</div>
							</div>
						</div>
						<?php
					}
				}elseif (isset($_GET['wpfnl_course_status']) && $_GET['wpfnl_course_status'] == 'enrolled' ){
					echo __('Thank you.You are already enrolled','wpfnl');
				}else{
					echo __('No Order Found','wpfnl');
				}
			}else{
				echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		}
        return ob_get_clean();
    }

    /**
     * Get Custom  Woocommerce template
     *
     * @param $template
     * @param $template_name
     * @param $template_path
	 * 
     * @return mixed|string
     */

    public static function wpfunnels_woocommerce_locate_template($template, $template_name, $template_path)
    {
		/***
		 * Fires when change the wc template
		 *
		 * @since 2.8.21
		 */
		if( apply_filters( 'wpfunnels/maybe_locate_template', true ) ){
			global $woocommerce;
			$_template 		= $template;
			$plugin_path 	= WPFNL_DIR . '/woocommerce/templates/';

			if (file_exists($plugin_path . $template_name)) {
				$template = $plugin_path . $template_name;
			}

			if ( ! $template ) {
				$template = $_template;
			}
		}
        return $template;
    }

    /**
     * Render Checkout form
     *
     * @param array $attrs
     * @param null $content
     * @param string $render_slug
	 * 
     * @return bool|string|null
     */

    public function render( $attrs, $content, $render_slug ) {
        $output = self::get_lms_order_details( $this->props );
        return $output;
    }


}

if( Wpfnl_functions::is_lms_addon_active()){
	new WPFNL_Lms_Order_details;
}

