<?php
/**
 * LMS order details
 * 
 * @package
 */
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;

use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;

/**
 * OrderDetails class.
 */
class LmsOrderDetails extends AbstractDynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'lms-order-details';

	public function __construct( $block_name = '' )
	{
		parent::__construct($block_name);
		add_action('wp_ajax_show_lms_order_details_markup', [$this, 'show_lms_order_details_markup']);
	}

	/**
	 * Render the Featured Product block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.0
	 * 
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		if( !isset($_GET['optin']) ) {
			$course_details_enable = isset($attributes['courseOverview']) ? $attributes['courseOverview'] : 'on';
			$payment_method_enable = isset($attributes['paymentOverview']) ? $attributes['paymentOverview']: 'on';
			$course_status_enable = isset($attributes['courseStatus']) ? $attributes['courseStatus']: 'on';

			$globalTextColor = isset($attributes['globalTextColor']) ? $attributes['globalTextColor'] : '';
			$lmsOrderDetailsHeaderBg = $attributes['lmsOrderDetailsHeaderBg'];
			$lmsOrderDetailsHeaderColor = $attributes['lmsOrderDetailsHeaderColor'];
			$lmsOrderDetailsHeaderBorderColor = $attributes['lmsOrderDetailsHeaderBorderColor'];

			$lmsOrderDetailsBodyBg = $attributes['lmsOrderDetailsBodyBg'];
			$lmsOrderDetailsBodyTextColor = $attributes['lmsOrderDetailsBodyTextColor'];
			$lmsOrderDetailsBodyLinkColor = $attributes['lmsOrderDetailsBodyLinkColor'];
			$lmsOrderDetailsBodyBorderColor = $attributes['lmsOrderDetailsBodyBorderColor'];

			ob_start();
			$step_id  = isset($_POST['post_id']) ? $_POST['post_id'] : get_the_ID();
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
								<?php if($course_details_enable == 'on') {?>
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

								<?php if($payment_method_enable == 'on') {?>
									<div class="order-item">
								<span class="course-name">
									Payment method
								</span>

										<span class="price-total">
									<?php echo $payment_method; ?>
								</span>
									</div>
								<?php } ?>

								<?php if($course_status_enable == 'on') {?>
									<div class="order-item">
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

						<style>
							.lms-order-details .order-details-table-footer .footer-list span,
							.lms-order-details .order-details-title,
							.lms-order-details .order-details-title span {
								color: <?php echo $globalTextColor; ?>;
							}
							.lms-order-details .order-details-table .order-tbl-header {
								background-color: <?php echo $lmsOrderDetailsHeaderBg; ?>;
								border-color: <?php echo $lmsOrderDetailsHeaderBorderColor; ?>
							}
							.lms-order-details .order-details-table .order-tbl-header > span {
								color: <?php echo $lmsOrderDetailsHeaderColor; ?>!important;
							}
							.lms-order-details .order-details-table {
								background-color: <?php echo $lmsOrderDetailsBodyBg; ?>;
							}
							.lms-order-details .order-details-table .order-item > span {
								color: <?php echo $lmsOrderDetailsBodyTextColor; ?>;
							}
							.lms-order-details .order-details-table .course-info > span a,
							.lms-order-details .order-details-table .order-item a {
								color: <?php echo $lmsOrderDetailsBodyLinkColor; ?>;
							}
							.lms-order-details .order-details-table,
							.lms-order-details .order-details-table .order-item {
								border-color: <?php echo $lmsOrderDetailsBodyBorderColor; ?>;
							}
						</style>
						<?php
					}
				}else{
					echo 'No Order Found';
				}
			}else{
				echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
			}
		}
		return ob_get_clean();
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
		global $post;
		$thankyou = new Wpfnl_Steps_Store_Data();
		$thankyou->read($post->ID);
		$order_overview     = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_order_overview');
		$order_details      = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_order_details');
		$billing_details    = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_billing_details');
		$shipping_details   = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_shipping_details');

		$classes = array(
			'wpfnl-block-' . $this->block_name,
			'wpfnl-gutenberg-display-order-overview-' . $order_overview,
			'wpfnl-gutenberg-display-order-details-' . $order_details,
			'wpfnl-gutenberg-display-billing-address-' . $billing_details,
			'wpfnl-gutenberg-display-shipping-address-' . $shipping_details,
		);
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
	 * Render order details markup
	 *
	 * @return string
	 *
	 * @since 2.0.3
	 */
	public function show_lms_order_details_markup() {

		add_filter('wpfunnels/show_dummy_order_details', function () {
			return true;
		});

		ob_start();
		$course_details_enable = isset($_POST['courseDetails']) ? $_POST['courseDetails'] : 'on';
		$payment_method_enable = isset($_POST['paymentDetails']) ? $_POST['paymentDetails']: 'on';
		$course_status_enable = isset($_POST['courseStatus']) ? $_POST['courseStatus']: 'on';

		$step_id  = isset($_POST['post_id']) ? $_POST['post_id'] : get_the_ID();
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


							<?php if($course_details_enable == 'on') {?>
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

							<?php if($payment_method_enable == 'on') {?>
								<div class="order-item">
								<span class="course-name">
									Payment method
								</span>

									<span class="price-total">
									<?php echo $payment_method; ?>
								</span>
								</div>
							<?php } ?>

							<?php if($course_status_enable == 'on') {?>
								<div class="order-item">
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
			}else{
				echo 'No Order Found';
			}
		}else{
			echo __('Sorry, Please place the element in WPFunnels when learnDash is active','wpfnl');
		}

		$data['html'] = ob_get_clean();

		wp_send_json_success($data);
		die();
	}

}
