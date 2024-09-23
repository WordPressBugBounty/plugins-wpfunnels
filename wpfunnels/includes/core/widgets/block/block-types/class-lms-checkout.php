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
			$get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
			if($get_learn_dash_setting == 'yes'){
				$course = Wpfnl_lms_learndash_functions::get_course_details($step_id);
				if (!empty($course)){

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
							if (is_user_logged_in() ){
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
			$get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
			if($get_learn_dash_setting == 'yes'){
				$course = Wpfnl_lms_learndash_functions::get_course_details($step_id);
				if (!empty($course)){

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
								if (is_user_logged_in() ){
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


				}
			}else{
				echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		wp_send_json_success(ob_get_clean());
	}
}
