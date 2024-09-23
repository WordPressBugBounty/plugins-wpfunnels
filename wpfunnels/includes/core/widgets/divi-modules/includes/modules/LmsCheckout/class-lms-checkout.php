<?php
/**
 * LMS checkout
 * 
 * @package
 */
namespace WPFunnels\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use ET_Builder_Module_Helper_Woocommerce_Modules;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Wpfnl_functions;

class WPFNL_Lms_Checkout extends ET_Builder_Module {

    public $slug       = 'wpfnl_lms_checkout';
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

		$this->name = esc_html__( 'WPF LMS Checkout', 'wpfnl' );

        $this->icon_path        =  plugin_dir_path( __FILE__ ) . 'checkout.svg';

        $this->settings_modal_toggles  = array(
            'general'  => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Layout', 'wpfnl' ),
                ),
            ),
			'advanced' => array(
				'toggles' => array(
					'form_field'  => array(
						'title'    => __( 'Header Style', 'wpfnl' ),
						'priority' => 60,
					),
					'checkout_body'  => array(
						'title'    => __( ' Checkout Body Style', 'wpfnl' ),
						'priority' => 65,
					),
					'body'         => array(
						'title'             => __( 'Checkout Body Title ', 'wpfnl' ),
						'priority'          => 75,
					),
					'payment_body' => array(
						'title'             => __( 'Checkout Body Content', 'wpfnl' ),
						'priority'          => 70,
					),
					'footer_section' => array(
						'title'             => __( 'Footer Content', 'wpfnl' ),
						'priority'          => 75,
					),
					'price_section' => array(
						'title'             => __( 'Price Content', 'wpfnl' ),
						'priority'          => 80,
					),
				),
			),

        );
        $this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'        => array(
				'payment_body' => array(
					'label'       => __( 'Body','wpfnl' ),
					'css'         => array(
						'main'      => implode(
							',',
							array(
								'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .course-title .no-of-lesson',
					 			'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .course-description',
					 			'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .course-plan'
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
					'toggle_slug' => 'payment_body',
					'sub_toggle'  => 'p',
				),
				'footer_section' => array(
					'label'       => __( 'Footer','wpfnl' ),
					'css'         => array(
						'main'      => implode(
							',',
							array(
								'%%order_class%% .wpfnl-lms-checkout .lms-checkout-footer .footer-title:not(.footer-price)'
							)
						),
						'important' => array( 'size', 'line-height' ),
					),
					'box_shadow' => false,
					'font_size'   => array(
						'default' => '14px',
					),
					'line_height' => array(
						'default' => '1.7em',
					),
					'toggle_slug' => 'footer_section',
					'sub_toggle'  => 'p',
				),
				'price_section' => array(
					'label'       => __( 'Price','wpfnl' ),
					'css'         => array(
						'main'      => implode(
							',',
							array(
								'%%order_class%% .wpfnl-lms-checkout .lms-checkout-footer .footer-price'
							)
						),
						'important' => array( 'size', 'line-height' ),
					),
					'margin_padding'  => array(
						'css' => array(
							'%%order_class%% .wpfnl-lms-checkout .lms-checkout-footer .footer-price',
							'important' => 'all',
						),
					),
					'font_size'   => array(
						'default' => '14px',
					),
					'line_height' => array(
						'default' => '1.7em',
					),
					'toggle_slug' => 'price_section',
					'sub_toggle'  => 'p',
				),
			),
			'text'         => false,
			'button'         => array(
				'button' => array(
					'label'           =>  __( ' Order Button', 'wpfnl' ),
					'css'             => array(
						'main' => '%%order_class%% .btn-join,%%order_class%% .btn-default',
						'important' => 'all',
					),
					'use_alignment'   => false,
					'border_width'    => array(
						'default' => '2px',
					),
					'box_shadow'      => array(
						'css' => array(
							'main' => '%%order_class%% .btn-join,,%%order_class%% .btn-default',
						),
					),
					'margin_padding'  => array(
						'css' => array(
							'important' => 'all',
						),
					),
					'toggle_priority' => 80,
				),
			),
			'link_options' => false,
			'form_field'   => array(
				'form_field'  => array(
					'label'           => __( 'Header', 'wpfnl' ),
					'css'         => array(
						'main'    => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header, %%order_class%% .wpfnl-lms-checkout .lms-checkout-header h4',
						'padding' => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
						'margin'  => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
						'important' => ['all'],
					),
					'box_shadow'      => array(
						'css' => array(
							'main' => implode(
								',',
								array(
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
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
					),

					'font_field'      => array(

						'css'         => array(
							'main'      => array(
								' %%order_class%% .wpfnl-lms-checkout .lms-checkout-header h4',
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
					'margin_padding'  => array(
						'css' => array(
							'main'    => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
							'padding' => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
							'margin'  => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
							'important' => 'all',
						),
					),
					'width'           => array(),
					'toggle_priority' => 55,
				),
				'checkout_body'  => array(
					'label'           => __( 'Body', 'wpfnl' ),
					'css'         	  => array(
						'main'    => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-body,
										%%order_class%%  .wpfnl-lms-checkout .lms-checkout-body .course-title,
										%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title,
										%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title',
						'padding' => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
						'margin'  => '%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
						'important' => ['all'],
					),
					'margin_padding' => array(
						'css' => array(
							'padding' => implode(
								', ',
								array(
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
								)
							),
							'margin' => implode(
								', ',
								array(
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
								)
							),
							'important' => ['all'],
						),

					),
					'font_field'      => array(
						'label'       => __( 'Checkout body', 'wpfnl' ),
						'css'         => array(
							'main'        => array(
									'%%order_class%%  .wpfnl-lms-checkout .lms-checkout-body .course-title',
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title',
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title'
								),

							'line_height' => array(
									'%%order_class%%  .wpfnl-lms-checkout .lms-checkout-body .course-title',
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title',
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title'
								),
						),
						'font_size'   => array(
							'default' => '14px',
						),
						'line_height' => array(
							'default' => '1.5em',
						),
					),
					'box_shadow'      => array(
						'css' => array(
							'main' => implode(
								',',
								array(
									'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
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
						'checkout_body'       => array(
							'label_prefix' => __( 'Fields', 'wpfnl' ),
							'css'          => array(
								'main' => array(
									'border_styles' => implode(
										',',
										array(
											'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
										)
									),
									'border_radii'  => implode(
										',',
										array(
											'%%order_class%% .wpfnl-lms-checkout .lms-checkout-body',
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
					'width'           => array(),
					'toggle_priority' => 55,
				),
			),
			'margin_padding' => array(
				'use_margin'  => true,
				'use_padding' => true,
				'css' => array(
					'main'  => "%%order_class%% h3",
					'important' => 'all',
				),
				'label_prefix'    => __( 'Heading', 'wpfnl' ),
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'title',
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

		$this->custom_css_fields = array(
			'title_text'  => array(
				'label'    => __( 'Heading Text', 'wpfnl' ),
				'selector' => '%%order_class%% h3',
			),
			'form_field'  => array(
				'label'    => __( 'Fields', 'wpfnl' ),
				'selector' => implode(
					',',
					array(
						'%%order_class%% .wpfnl-lms-checkout .lms-checkout-header',
					)
				),
			),
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
			'lms_order_header_title' => array(
				'label'           => __( 'Order Details Header Title', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => __( 'Order Details', 'wpfnl' ),
				'toggle_slug'     => 'main_content',
				'default'         => 'Order Details',
				'default_on_front' => 'Order Details',
				'computed_affects' => array(
					'__lmsCheckoutForm'
				),
			),
			'lms_order_course_details_title' => array(
				'label'           => __( 'Order Course Details Title', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => __( 'Order Details', 'wpfnl' ),
				'toggle_slug'     => 'main_content',
				'default'         => 'Course Details',
				'default_on_front' => 'Course Details',
				'computed_affects' => array(
					'__lmsCheckoutForm'
				),
			),
			'lms_order_course_description'       => array(
				'label'            => __( 'Course Description', 'wpfnl' ),
				'description'      => __( 'Course Description', 'wpfnl' ),
				'type'             => 'tiny_mce',
				'default'          => __('Briefly describe your course in 2-3 lines.','wpfnl'),
				'default_on_front' => __('Briefly describe your course in 2-3 lines.','wpfnl'),
				'toggle_slug'      => 'main_content',
				'computed_affects' => array(
					'__lmsCheckoutForm'
				),
			),
			'lms_order_plan_details_title'       => array(
				'label'            => __( 'Plan Details Title', 'wpfnl' ),
				'description'      => __( 'Plan Details Title', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Plan Details',
				'default_on_front' => 'Plan Details',
				'toggle_slug'      => 'main_content',
				'computed_affects' => array(
					'__lmsCheckoutForm'
				),
			),
            '__lmsCheckoutForm'        => array(
                'type'                => 'computed',
                'computed_callback'   => array(
                    'WPFunnels\Widgets\DiviModules\Modules\WPFNL_Lms_Checkout',
                    'get_lms_checkout_form',
                ),
                'computed_depends_on' => array(
                    'lms_order_header_title',
                    'lms_order_course_details_title',
                    'lms_order_course_description',
                    'lms_order_plan_details_title',
					'module_type'
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

    public static  function get_lms_checkout_form($props) {

        $step_id 	= isset($_POST['current_page']['id']) ? $_POST['current_page']['id'] : get_the_ID();
		$step_type = get_post_meta($step_id,'_step_type',true);
		$funnel_id = get_post_meta($step_id,'_funnel_id',true);
		$get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
		if ($step_type != 'checkout'){
			return __('Sorry, Please place the element in WPFunnels Checkout page','wpfnl');
		}else{
			if($get_learn_dash_setting == 'yes'){
				$course = Wpfnl_lms_learndash_functions::get_course_details($step_id);

				if (!empty($course)){
					ob_start();
					$course_title 		= $course['title'];
					$description 		= $course['description'];
					$no_of_lesson 		= $course['no_of_lesson'];
					$course_type 		= $course['type'];
					$billing_cycle 		= $course['billing_cycle'];
					$recurring_time 	= $course['recurring_time'];
					$price 				= $course['price'];
					$trial_price 		= $course['trial_price'];
					$trial_period 		= $course['trial_period'];
					$is_expire 			= $course['is_expire'];
					$expire_days 		= $course['expire_days'];
					$image 				= $course['image'];
					$billing_cycle_unit = $course['billing_cycle_unit'];
					$trial_period_unit  = $course['trial_period_unit'];
					$currency  = $course['currency'];
					$discount_price  	= isset($course['discount_price']) ? $course['discount_price'] : '';
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
					if( $course['billing_cycle_unit'] == 'Y' ) {
						$billing_cycle_unit_text = 'year';

					}else if( $course['billing_cycle_unit'] == 'M' ){
						$billing_cycle_unit_text = 'month';

					}else if( $course['billing_cycle_unit'] == 'W' ){
						$billing_cycle_unit_text = 'week';

					}else {
						$billing_cycle_unit_text = 'day';
					}

					//----trial period unit------
					if( $course['trial_period_unit'] == 'Y' ) {
						$trial_period_unit_text = 'year';

					}else if( $course['trial_period_unit'] == 'M' ){
						$trial_period_unit_text = 'month';

					}else if( $course['trial_period_unit'] == 'W' ){
						$trial_period_unit_text = 'week';

					}else {
						$trial_period_unit_text = 'day';
					}
					do_action( 'wpfunnels/before_checkout_form', $step_id );
					?>
					<div>
						<?php  do_action('wpfunnels/before_lms_order_deatils'); ?>
					</div>

					<div class="wpfnl-reset wpfnl-lms-checkout">
						<div class="lms-checkout-box">
							<div class="lms-checkout-header">
								<h4><?php echo $props['lms_order_header_title'] ?></h4>
							</div>
							<div class="lms-checkout-body">
								<div class="course-image">
									<img src="<?php echo $image; ?>" alt="course image" />
								</div>

								<div class="course-content">
									<h2 class="course-title">
										<?php echo $course_title ?>
										<span class="no-of-lesson">- <?php echo $no_of_lesson ?> Lessons</span>
									</h2>

									<div class="lms-single-block course-details-block">
										<h4 class="lms-block-title"><?php echo $props['lms_order_course_details_title'] ?></h4>
										<div class="course-description">
											<?php
												echo $props['lms_order_course_description'];
											?>
										</div>
									</div>

									<div class="lms-single-block course-plan-block">
										<h4 class="lms-block-title"><?php echo $props['lms_order_plan_details_title']; ?></h4>
										<div class="course-plan">
											<?php
											if( $course_type == 'subscribe' ){

												if( $discount_price ){
													echo $course_type_text .' - <span class="primary-color"><del>'.$currency.$price.'</del> <ins style="text-decoration:none">'.$currency.$discount_price.'</ins>/'.$billing_cycle_unit_text.'</span>';
												}else{
													echo $course_type_text .' - <span class="primary-color">'.$currency.$price.'/'.$billing_cycle_unit_text.'</span>';
												}

												if( !empty( $trial_period ) ){
													echo '<small> ('.$trial_period.' '.$trial_period_unit_text.' trial period)</small>';
												}

											} else if( $course_type == 'paynow' ){

												if( $discount_price ){
													echo $course_type_text .' - <span class="primary-color"><del>'.$currency.$price.'</del>  <ins style="text-decoration:none">'.$currency.$discount_price.'</ins></span>';
												}else{
													echo $course_type_text .' - <span class="primary-color">'.$currency.$price.'</span>';
												}

												if( $is_expire == 'on' ){
													echo '<small> (expires in '.$expire_days.' days)</small>';
												}
											}else{
												echo $course_type_text;

												if( $is_expire == 'on' ){
													echo '<small> (Login or register to enroll) (expires in '.$expire_days.' days)</small>';
												}
											}
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="lms-checkout-footer">
							<?php
							if( $course_type == 'subscribe' ){
								if( !empty( $trial_period ) ){
									?>

									<p class="footer-title">Course Trial Fees <span class="footer-price"><?php echo $currency.$trial_price; ?></span></p>

								<?php }else{ ?>

									<p class="footer-title">Course Subscription Fees <span class="footer-price"><?php echo $currency.$price; ?></span></p>
									<?php
								}
							}else if( $course_type == 'paynow' ){
								if( $discount_price ){ ?>
									<p class="footer-title">Course Fees <span class="footer-price"><del><?php echo $currency.$price; ?></del> <ins style="text-decoration:none"> <?php echo $currency.$discount_price; ?> </ins></span></p>
								<?php } else{ ?>
									<p class="footer-title">Course Fees <span class="footer-price"><?php echo $currency.$price; ?></span></p>
								<?php }
								?>

							<?php }else{ ?>
								<p class="footer-title">Course Fees <span class="footer-price">Free</span></p>
							<?php } ?>

							<!-- buy now button -->
							<?php
							if (is_user_logged_in()){
								$course_access = sfwd_lms_has_access( $course['id'], get_current_user_id() );
								$next_step_url = Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id).'?wpfnl_ld_payment=free';
								$lms_button_text 		= get_option( 'learndash_settings_custom_labels' );
								$button_text 			= !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : 'Take This Course';
								if ($course_access ){
									echo '<a class="btn-default" href="'.$next_step_url.'" id="wpfnl-lms-access-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-access-course-message"></span>';
								}else if($course_type == 'free'){
									$next_step_url = Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id);
									echo '<a class="btn-default" href="'.$next_step_url.'" user_id="'.get_current_user_id().'" step_id="'.$step_id.'" course_id="'.$course['id'].'" id="wpfnl-lms-free-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-free-course-message"></span>';
								}else{
									echo do_shortcode('[learndash_payment_buttons course_id='.$course['id'].']');
								}
							}else{
								echo do_shortcode('[learndash_login]');
							}
							?>
						</div>

					</div>

					<div>
						<?php  do_action('wpfunnels/after_lms_order_deatils'); ?>
					</div>
					<?php
					return ob_get_clean();


				}else{
					return __("Please Add Course in checkout","wpfnl");
				}
			}else{
				return __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		}
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
        $output = self::get_lms_checkout_form( $this->props );
        return $output;
    }
}

new WPFNL_Lms_Checkout;
