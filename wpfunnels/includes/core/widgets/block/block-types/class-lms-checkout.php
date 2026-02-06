<?php
/**
 * LMS checkout
 * 
 * @package
 */
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;


use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

use function \CodeRex\Ecommerce\ecommerce;

/**
 * FeaturedProduct class.
 */
class LmsCheckout extends AbstractDynamicBlock {

	protected $defaults = array(

	);


	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'lms-checkout';

	public function __construct( $block_name = '' )
	{
		parent::__construct($block_name);
		add_action('wp_ajax_show_lms_checkout_markup', [$this, 'show_checkout_markup']);
		add_action( 'wpfunnels/gutenberg_checkout_dynamic_filters', array($this, 'dynamic_filters') );
		
	}



	/**
	 * Render the Featured Product block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * 
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {


		global $post;
		$lmsCheckoutHeaderTitle 	= isset($attributes['lmsCheckoutHeaderTitle']) ? $attributes['lmsCheckoutHeaderTitle'] : __("Order Details","wpfnl");
		$lmsCourseDetailsTitle 		= isset($attributes['lmsCourseDetailsTitle']) ? $attributes['lmsCourseDetailsTitle'] : __("Course Details","wpfnl");
		$lmsCourseDescriptionTitle 	= isset($attributes['lmsCourseDescriptionTitle']) ? $attributes['lmsCourseDescriptionTitle'] : '';
		$lmsPlanDetailsTitle 		= isset($attributes['lmsPlanDetailsTitle']) ? $attributes['lmsPlanDetailsTitle'] : __("Plan Details","wpfnl");
		$step_id  = $post->ID;
		ob_start();
		if (!Wpfnl_functions::check_if_this_is_step_type('checkout')){
			echo __('Sorry, Please place the element in WPFunnels Checkout page','wpfnl');
		}else{
			$funnel_id = get_post_meta($step_id,'_funnel_id',true);
			$funnel_type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
			
			// Check if this is an LMS funnel
			if($funnel_type === 'lms'){
				// Get course from step and determine provider
				$course = $this->get_course_from_step($step_id);
				$provider_id = isset($course['provider']) ? $course['provider'] : 'learndash';
				$course_id = isset($course['id']) ? $course['id'] : 0;
				$user_id = get_current_user_id();
				
				// Check if user is already enrolled in the course
				$is_enrolled = false;
				if ($user_id && $course_id) {
					if ($provider_id === 'creatorlms') {
						// Check CreatorLMS enrollment
						if (class_exists('\WPFunnels\lms\providers\CreatorLMS_Provider')) {
							$provider = new \WPFunnels\lms\providers\CreatorLMS_Provider();
							$is_enrolled = $provider->is_enrolled($user_id, $course_id);
						}
					} else {
						// Check LearnDash enrollment
						$is_enrolled = $this->has_course_access($course_id, $user_id, $provider_id);
					}
				}
				
				// If already enrolled, show message and next step button
				if ($is_enrolled) {
					$next_step_url = $this->get_next_step_url($funnel_id, $step_id);
					?>
					<div class="wpfnl-already-enrolled-message" style="padding: 40px 20px; text-align: center; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; margin: 20px 0;">
						<svg style="width: 64px; height: 64px; margin: 0 auto 20px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
						<h3 style="margin: 0 0 10px; color: #1e40af; font-size: 24px;"><?php echo __('Already Enrolled', 'wpfnl'); ?></h3>
						<p style="margin: 0 0 20px; color: #475569; font-size: 16px;">
							<?php echo sprintf(__('You are already enrolled in "%s". No need to purchase again.', 'wpfnl'), esc_html($course['title'])); ?>
						</p>
						<?php if ($next_step_url) : ?>
							<a href="<?php echo esc_url($next_step_url); ?>" class="btn-default" style="display: inline-block; padding: 12px 32px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
								<?php echo __('Continue', 'wpfnl'); ?>
							</a>
						<?php endif; ?>
					</div>
					<?php
					return ob_get_clean();
				}
				
				// For CreatorLMS, render their native checkout form
				// Course is added to cart in maybe_add_course_to_cart() hook
				if ($provider_id === 'creatorlms' && !empty($course)) {
					// Enqueue CreatorLMS checkout scripts and styles
					
					try {
						$cart = ecommerce()->cart;
						if ($cart) {
							// Empty cart first (CreatorLMS only allows 1 item)
							// $cart->empty_cart(true);
						
							// Add course to cart
							$added = $cart->add_to_cart($course_id, 1, array());
							$cart->calculate_totals();
						
						}
					} catch (\Exception $e) {
						error_log( 'Error adding course to cart: ' . $e->getMessage() );
					}
					set_transient( 'creatorlms_step_id', $step_id );
					// Render CreatorLMS checkout shortcode which shows the full checkout form
					echo do_shortcode('[creator_lms_checkout]');
					
				} elseif (!empty($course)){
					// For LearnDash, use custom checkout layout

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
					$currency  			= $course['currency'];
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
								<h4><?php echo $lmsCheckoutHeaderTitle ?></h4>
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
										<h4 class="lms-block-title"><?php echo $lmsCourseDetailsTitle ?></h4>
										<div class="course-description">
											<?php
												echo $lmsCourseDescriptionTitle;
											?>
										</div>
									</div>

									<div class="lms-single-block course-plan-block">
										<h4 class="lms-block-title"><?php echo $lmsPlanDetailsTitle ?></h4>
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
							$provider_id = isset($course['provider']) ? $course['provider'] : 'learndash';
							
							if (is_user_logged_in() ){
								$course_access = $this->has_course_access($course['id'], get_current_user_id(), $provider_id);
								$next_step_url = $this->get_next_step_url($funnel_id,$step_id);
								
								// Get button text based on provider
								if ($provider_id === 'learndash') {
									$lms_button_text = get_option( 'learndash_settings_custom_labels' );
									$button_text = !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : 'Take This Course';
								} else {
									$button_text = 'Enroll Now';
								}
								
								if ($course_access ){
									echo '<a class="btn-default" href="'.$next_step_url.'?wpfnl_lms_payment=free" id="wpfnl-lms-access-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-access-course-message"></span>';
								}else if($course_type == 'free' || $course_type == 'open'){
									echo '<a class="btn-default" href="'.$next_step_url.'" user_id="'.get_current_user_id().'" step_id="'.$step_id.'" course_id="'.$course['id'].'" id="wpfnl-lms-free-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-free-course-message"></span>';
								}else{
									// Provider-specific payment buttons
									if ($provider_id === 'learndash') {
										echo do_shortcode('[learndash_payment_buttons course_id='.$course['id'].']');
									} else {
										// CreatorLMS and other providers - show WooCommerce add to cart
										if (function_exists('crlms_get_course')) {
											$course_obj = crlms_get_course($course['id']);
											if ($course_obj && method_exists($course_obj, 'get_product_id')) {
												$product_id = $course_obj->get_product_id();
												if ($product_id) {
													echo do_shortcode('[add_to_cart id="'.$product_id.'" show_price="false"]');
												}
											}
										}
									}
								}
							}else{
								// Provider-specific login
								if ($provider_id === 'learndash') {
									echo do_shortcode('[learndash_login]');
								} else {
									echo '<p>Please <a href="'.wp_login_url(get_permalink()).'">login</a> to enroll in this course.</p>';
								}
							}
							?>
						</div>

					</div>

					<div>
						<?php  do_action('wpfunnels/after_lms_order_deatils'); ?>
					</div>

					<style>
						<?php
							$lmsCheckoutHeaderBg = isset($attributes['lmsCheckoutHeaderBg']) ? $attributes['lmsCheckoutHeaderBg'] : '';
							$lmsCheckoutHeaderColor = isset($attributes['lmsCheckoutHeaderColor']) ? $attributes['lmsCheckoutHeaderColor'] : '';
							$lmsCheckoutHeaderBorderColor = isset($attributes['lmsCheckoutHeaderBorderColor']) ? $attributes['lmsCheckoutHeaderBorderColor'] : '';
							$lmsCheckoutBodyBg = isset($attributes['lmsCheckoutBodyBg']) ? $attributes['lmsCheckoutBodyBg'] : '';
							$lmsCheckoutBodyTitleColor = isset($attributes['lmsCheckoutBodyTitleColor']) ? $attributes['lmsCheckoutBodyTitleColor'] : '';
							$lmsCheckoutBodyContentColor = isset($attributes['lmsCheckoutBodyContentColor']) ? $attributes['lmsCheckoutBodyContentColor'] : '';
							$lmsCheckoutHighlightedTextColor = isset($attributes['lmsCheckoutHighlightedTextColor']) ? $attributes['lmsCheckoutHighlightedTextColor'] : '';
							$lmsCheckoutBodyBorderColor = isset($attributes['lmsCheckoutBodyBorderColor']) ? $attributes['lmsCheckoutBodyBorderColor'] : '';
							$lmsCheckoutFooterTextColor = isset($attributes['lmsCheckoutFooterTextColor']) ? $attributes['lmsCheckoutFooterTextColor'] : '';
							$lmsCheckoutFooterHighlightedTextColor = isset($attributes['lmsCheckoutFooterHighlightedTextColor']) ? $attributes['lmsCheckoutFooterHighlightedTextColor'] : '';
							$lmsCheckoutButtonColor = isset($attributes['lmsCheckoutButtonColor']) ? $attributes['lmsCheckoutButtonColor'] : '';
							$lmsCheckoutButtonHover = isset($attributes['lmsCheckoutButtonHover']) ? $attributes['lmsCheckoutButtonHover'] : '';
							$lmsCheckoutButtonBg = isset($attributes['lmsCheckoutButtonBg']) ? $attributes['lmsCheckoutButtonBg'] : '';
							$lmsCheckoutButtonHvrBg = isset($attributes['lmsCheckoutButtonHvrBg']) ? $attributes['lmsCheckoutButtonHvrBg'] : '';
						?>

						.wpfnl-lms-checkout .lms-checkout-header {
							background-color: <?php echo $lmsCheckoutHeaderBg; ?>;
							border-color: <?php echo $lmsCheckoutHeaderBorderColor; ?>;
						}
						.wpfnl-lms-checkout .lms-checkout-header h4 {
							color: <?php echo $lmsCheckoutHeaderColor; ?>;
						}

						.wpfnl-lms-checkout .lms-checkout-body {
							background-color: <?php echo $lmsCheckoutBodyBg; ?>;
							border-color: <?php echo $lmsCheckoutBodyBorderColor; ?>;
						}
						.wpfnl-lms-checkout .lms-checkout-body .course-title,
						.wpfnl-lms-checkout .lms-checkout-body .lms-single-block .lms-block-title {
							color: <?php echo $lmsCheckoutBodyTitleColor; ?>;
						}
						.wpfnl-lms-checkout .lms-checkout-body .course-title .no-of-lesson,
						.wpfnl-lms-checkout .lms-checkout-body .lms-single-block .course-description,
						.wpfnl-lms-checkout .lms-checkout-body .lms-single-block .course-plan {
							color: <?php echo $lmsCheckoutBodyContentColor; ?>;
						}
						.wpfnl-lms-checkout .lms-checkout-body .lms-single-block .course-plan .primary-color {
							color: <?php echo $lmsCheckoutHighlightedTextColor; ?>;
						}


						.wpfnl-lms-checkout .lms-checkout-footer .footer-title {
							color: <?php echo $lmsCheckoutFooterTextColor; ?>;
						}
						.wpfnl-lms-checkout .lms-checkout-footer .footer-price {
							color: <?php echo $lmsCheckoutFooterHighlightedTextColor; ?>;
						}


						.wpfnl-lms-checkout .lms-checkout-footer .btn-default,
						.wpfnl-lms-checkout .lms-checkout-footer .ld-login-button,
						.wpfnl-lms-checkout .lms-checkout-footer .btn-join {
							color: <?php echo $lmsCheckoutButtonColor; ?>;
							background-color: <?php echo $lmsCheckoutButtonBg; ?> !important;
						}
						.wpfnl-lms-checkout .lms-checkout-footer .btn-default:hover,
						.wpfnl-lms-checkout .lms-checkout-footer .ld-login-button:hover,
						.wpfnl-lms-checkout .lms-checkout-footer .btn-join:hover {
							color: <?php echo $lmsCheckoutButtonHover; ?>;
							background-color: <?php echo $lmsCheckoutButtonHvrBg; ?> !important;
						}

					</style>
					<?php

				}
			}else{
				echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		}
		return ob_get_clean();
	}


	/**
	 * Dynamic filters for checkout form
	 *
	 * @param $attributes
	 *
	 * @since 2.0.3
	 */
	public function dynamic_filters( $attributes ) {
		$checkout_meta = array(
			array(
				'name'      => 'layout',
				'meta_key'  => 'wpfnl_checkout_layout'
			)
		);
		foreach ( $checkout_meta as $key => $meta ) {
			$meta_key = $meta['meta_key'];
			$meta_name = $meta['name'];
			add_filter(
				"wpfunnels/checkout_meta_{$meta_key}",
				function ( $value ) use ( $attributes, $meta_name ) {
					$value = sanitize_text_field( wp_unslash( $attributes[$meta_name] ) );
					return $value;
				},
				10, 1
			);
		}
	}


	/**
	 * Get generated dynamic styles from $attributes
	 *
	 * @param $attributes
	 * @param $post
	 * 
	 * @return array|string
	 */
	protected function get_generated_dynamic_styles( $attributes, $post ) {
		$selectors = array(

		);
		return $this->generate_css($selectors);
	}


	/**
	 * Get the styles for the wrapper element (background image, color).
	 *
	 * @param array       $attributes Block attributes. Default empty array.
	 * 
	 * @return string
	 */
	public function get_styles( $attributes ) {
		$style      = '';
		return $style;
	}


	/**
	 * Get class names for the block container.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * 
	 * @return string
	 */
	public function get_classes( $attributes ) {
		$classes = array( 'wpfnl-block-' . $this->block_name );
		return implode( ' ', $classes );
	}


	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
	}


	/**
	 * Show checkout markup by ajax response
	 *
	 * @throws \Exception
	 */
	public function show_checkout_markup() {

		$step_id  = isset($_POST['post_id']) ? $_POST['post_id'] : 0;;
		$lmsCheckoutHeaderTitle 	= $_POST['lmsCheckoutHeaderTitle'];
		$lmsCourseDetailsTitle 		= $_POST['lmsCourseDetailsTitle'];
		$lmsCourseDescriptionTitle 	= $_POST['lmsCourseDescriptionTitle'];
		$lmsPlanDetailsTitle 		= $_POST['lmsPlanDetailsTitle'];
		ob_start();

			$funnel_id = get_post_meta($step_id,'_funnel_id',true);
			$funnel_type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
			
			// Check if this is an LMS funnel
			if($funnel_type === 'lms'){
				$course = $this->get_course_from_step($step_id);
				$provider_id = isset($course['provider']) ? $course['provider'] : 'learndash';
				
				// For CreatorLMS, use their native checkout
				if ($provider_id === 'creatorlms' && !empty($course)) {
					$course_id = $course['id'];
					// Store step info for redirect after checkout
					set_transient('wpfnl_creatorlms_checkout_' . $course_id, array(
						'funnel_id' => $funnel_id,
						'step_id' => $step_id,
					), HOUR_IN_SECONDS);
					
					// Render CreatorLMS checkout shortcode
					echo do_shortcode('[creator_lms_checkout course_id="' . $course_id . '"]');
					
				} elseif (!empty($course)){
					// For LearnDash, use custom checkout layout

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
					$currency  			= $course['currency'];


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
					// do_action( 'wpfunnels/gutenberg_checkout_dynamic_filters', $attributes );
					do_action( 'wpfunnels/before_gb_checkout_form_ajax', $step_id, $_POST );

					?>

					<div>
						<?php  do_action('wpfunnels/before_lms_order_deatils'); ?>
					</div>

					<div class="wpfnl-reset wpfnl-lms-checkout">
						<div class="lms-checkout-box">
							<div class="lms-checkout-header">
								<h4><?php echo $lmsCheckoutHeaderTitle ?></h4>
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
										<h4 class="lms-block-title"><?php echo $lmsCourseDetailsTitle ?></h4>
										<div class="course-description">
											<?php
												echo $lmsCourseDescriptionTitle;
											 ?>
										</div>
									</div>

									<div class="lms-single-block course-plan-block">
										<h4 class="lms-block-title"><?php echo $lmsPlanDetailsTitle ?></h4>
										<div class="course-plan">
											<?php
											if( $course_type == 'subscribe' ){
												echo $course_type_text .' - <span class="primary-color">'.$currency.$price.'/'.$billing_cycle_unit_text.'</span>';

												if( !empty( $trial_period ) ){
													echo '<small> ('.$trial_period.' '.$trial_period_unit_text.' trial period)</small>';
												}

											} else if( $course_type == 'paynow' ){
												echo $course_type_text .' - <span class="primary-color">'.$currency.$price.'</span>';

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
								?>

								<p class="footer-title">Course Fees <span class="footer-price"><?php echo $currency.$price; ?></span></p>

							<?php }else{ ?>
								<p class="footer-title">Course Fees <span class="footer-price">Free</span></p>
							<?php } ?>

							<!-- buy now button -->
							<?php
							$provider_id = isset($course['provider']) ? $course['provider'] : 'learndash';
							
							if (is_user_logged_in() ){
								$course_access = $this->has_course_access($course['id'], get_current_user_id(), $provider_id);
								$next_step_url = $this->get_next_step_url($funnel_id,$step_id);
								
								// Get button text based on provider
								if ($provider_id === 'learndash') {
									$lms_button_text = get_option( 'learndash_settings_custom_labels' );
									$button_text = !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : 'Take This Course';
								} else {
									$button_text = 'Enroll Now';
								}
								
								if ($course_access ){
									echo '<a class="btn-default" href="'.$next_step_url.'?wpfnl_lms_payment=free" id="wpfnl-lms-access-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-access-course-message"></span>';
								}else if($course_type == 'free' || $course_type == 'open'){
									echo '<a class="btn-default" href="'.$next_step_url.'" user_id="'.get_current_user_id().'" step_id="'.$step_id.'" course_id="'.$course['id'].'" id="wpfnl-lms-free-course">'.$button_text.'</a>';
									echo '<span class="wpfnl-lms-free-course-message"></span>';
								}else{
									// Provider-specific payment buttons
									if ($provider_id === 'learndash') {
										echo do_shortcode('[learndash_payment_buttons course_id='.$course['id'].']');
									} else {
										// CreatorLMS and other providers - show WooCommerce add to cart
										if (function_exists('crlms_get_course')) {
											$course_obj = crlms_get_course($course['id']);
											if ($course_obj && method_exists($course_obj, 'get_product_id')) {
												$product_id = $course_obj->get_product_id();
												if ($product_id) {
													echo do_shortcode('[add_to_cart id="'.$product_id.'" show_price="false"]');
												}
											}
										}
									}
								}
							}else{
								// Provider-specific login
								if ($provider_id === 'learndash') {
									echo do_shortcode('[learndash_login]');
								} else {
									echo '<p>Please <a href="'.wp_login_url(get_permalink()).'">login</a> to enroll in this course.</p>';
								}
							}
							?>
						</div>

					</div>

					<div>
						<?php  do_action('wpfunnels/after_lms_order_deatils'); ?>
					</div>
					<?php


				}
			}else{
				echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		wp_send_json_success(ob_get_clean());
	}

	/**
	 * Get course details from step using provider system
	 *
	 * @param int $step_id Step ID
	 * @return array|false Course details array or false
	 * @since 2.0.0
	 */
	private function get_course_from_step( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		// Get products from step meta
		$step_type = get_post_meta( $step_id, '_step_type', true );
		$products  = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );

		if ( empty( $products ) || ! isset( $products[0]['id'] ) ) {
			return false;
		}

		$course_id   = $products[0]['id'];
		$provider_id = isset( $products[0]['lms_provider'] ) ? $products[0]['lms_provider'] : null;

		// If no provider ID in product, try to detect from funnel settings
		if ( ! $provider_id ) {
			$funnel_id   = get_post_meta( $step_id, '_funnel_id', true );
			$lms_settings = get_option( '_wpfunnels_lms_settings', array() );
			$provider_id  = isset( $lms_settings['lms_provider'] ) ? $lms_settings['lms_provider'] : 'learndash';
		}

		// Get provider instance
		if ( ! function_exists( 'wpfunnels_lms' ) ) {
			// Fallback to old LearnDash method if provider system not available
			if ( class_exists( 'WPFunnels\lms\helper\Wpfnl_lms_learndash_functions' ) ) {
				return Wpfnl_lms_learndash_functions::get_course_details( $step_id );
			}
			return false;
		}

		$lms_manager = wpfunnels_lms();
		$provider    = $lms_manager->get_provider( $provider_id );

		if ( ! $provider ) {
			return false;
		}

		// Get course details from provider
		$course_details = $provider->get_course_details( $course_id );
		
		// Add discount price if applicable
		if ( ! empty( $course_details ) ) {
			$discount_price = $this->get_discounted_course_price( $step_id, $provider_id );
			if ( $discount_price ) {
				$course_details['discount_price'] = $discount_price;
			}
			
			// Add provider ID to course details
			$course_details['provider'] = $provider_id;
		}

		return $course_details;
	}

	/**
	 * Get discounted price for a course
	 *
	 * @param int    $step_id     Step ID
	 * @param string $provider_id Provider ID
	 * @return string|false Discounted price or false
	 * @since 2.0.0
	 */
	private function get_discounted_course_price( $step_id, $provider_id = 'learndash' ) {
		if ( ! $step_id ) {
			return false;
		}

		$step_type = get_post_meta( $step_id, '_step_type', true );
		
		// For LearnDash, use the existing helper
		if ( $provider_id === 'learndash' && class_exists( 'WPFunnels\lms\helper\Wpfnl_lms_learndash_functions' ) ) {
			return Wpfnl_lms_learndash_functions::get_discounted_course_price( $step_id );
		}

		// Generic discount calculation for other providers
		$discount = get_post_meta( $step_id, '_wpfnl_checkout_discount_main_product', true );
		
		if ( ! is_array( $discount ) ) {
			return false;
		}

		if ( isset( $discount['discountOptions'] ) && 
			 isset( $discount['mutedDiscountValue'] ) && 
			 'original' !== $discount['discountOptions'] && 
			 $discount['mutedDiscountValue'] ) {
			
			if ( isset( $discount['discountedPrice'] ) ) {
				return $discount['discountedPrice'];
			}
		}

		return false;
	}

	/**
	 * Check if user has access to course
	 *
	 * @param int    $course_id   Course ID
	 * @param int    $user_id     User ID
	 * @param string $provider_id Provider ID
	 * @return bool
	 * @since 2.0.0
	 */
	private function has_course_access( $course_id, $user_id, $provider_id ) {
		if ( ! $course_id || ! $user_id ) {
			return false;
		}

		// For LearnDash
		if ( $provider_id === 'learndash' && function_exists( 'sfwd_lms_has_access' ) ) {
			return sfwd_lms_has_access( $course_id, $user_id );
		}

		// Use provider system
		if ( function_exists( 'wpfunnels_lms' ) ) {
			$lms_manager = wpfunnels_lms();
			$provider    = $lms_manager->get_provider( $provider_id );
			
			if ( $provider && method_exists( $provider, 'is_enrolled' ) ) {
				return $provider->is_enrolled( $user_id, $course_id );
			}
		}

		return false;
	}

	/**
	 * Get next step URL
	 *
	 * @param int $funnel_id Funnel ID
	 * @param int $step_id   Current step ID
	 * @return string Next step URL
	 * @since 2.0.0
	 */
	private function get_next_step_url( $funnel_id, $step_id ) {
		if ( class_exists( 'WPFunnels\lms\helper\Wpfnl_lms_learndash_functions' ) ) {
			return Wpfnl_lms_learndash_functions::get_next_step_url( $funnel_id, $step_id );
		}
		
		// Fallback - get next step in funnel
		$steps = get_post_meta( $funnel_id, '_steps', true );
		if ( ! is_array( $steps ) ) {
			return home_url();
		}

		$current_index = array_search( $step_id, $steps );
		if ( $current_index !== false && isset( $steps[ $current_index + 1 ] ) ) {
			return get_permalink( $steps[ $current_index + 1 ] );
		}

		return home_url();
	}
	
	
}
