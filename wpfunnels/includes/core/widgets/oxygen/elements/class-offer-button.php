<?php
namespace WPFunnels\Widgets\Oxygen\Elements;

use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Offer Button Element for Oxygen Builder
 *
 * @since 3.0.0
 */
class OfferButton extends \OxyEl {

	public $css_added = false;

	/**
	 * Element name
	 */
	function name() {
		return __( 'Offer Button', 'wpfnl' );
	}

	/**
	 * Element slug
	 */
	function slug() {
		return 'wpfnl_offer_button';
	}

	/**
	 * Element icon
	 */
	function icon() {
		return plugin_dir_url( __FILE__ ) . 'icon.svg';
	}

	/**
	 * Button place
	 */
	function button_place() {
		return 'wpfunnels::helpers';
	}

	/**
	 * Register controls
	 */
	function controls() {
		// Settings Section
		$this->addOptionControl(
			array(
				'type'    => 'buttons-list',
				'name'    => __( 'Button Action', 'wpfnl' ),
				'slug'    => 'offer_action',
				'value'   => array(
					'accept' => __( 'Accept Offer', 'wpfnl' ),
					'reject' => __( 'Reject Offer', 'wpfnl' ),
				),
				'default' => 'accept',
			)
		)->rebuildElementOnChange();

		$this->addOptionControl(
			array(
				'type'    => 'textfield',
				'name'    => __( 'Button Text', 'wpfnl' ),
				'slug'    => 'button_text',
				'default' => __( 'Yes, Add to My Order!', 'wpfnl' ),
			)
		)->rebuildElementOnChange();

		$this->addOptionControl(
			array(
				'type'      => 'buttons-list',
				'name'      => __( 'Show Product Price', 'wpfnl' ),
				'slug'      => 'show_product_price',
				'value'     => array(
					'yes' => __( 'Yes', 'wpfnl' ),
					'no'  => __( 'No', 'wpfnl' ),
				),
				'default'   => 'no',
				'condition' => 'offer_action=accept',
			)
		)->rebuildElementOnChange();

		$this->addOptionControl(
			array(
				'type'    => 'buttons-list',
				'name'    => __( 'Button Alignment', 'wpfnl' ),
				'slug'    => 'button_alignment',
				'value'   => array(
					'left'   => __( 'Left', 'wpfnl' ),
					'center' => __( 'Center', 'wpfnl' ),
					'right'  => __( 'Right', 'wpfnl' ),
				),
				'default' => 'left',
			)
		)->rebuildElementOnChange();
	}

	/**
	 * Render element
	 */
	function render( $options, $defaults, $content ) {
		$offer_action = isset( $options['offer_action'] ) ? $options['offer_action'] : 'accept';
		$button_text = isset( $options['button_text'] ) ? $options['button_text'] : __( 'Yes, Add to My Order!', 'wpfnl' );
		$show_price = isset( $options['show_product_price'] ) ? $options['show_product_price'] : 'no';
		$button_align = isset( $options['button_alignment'] ) ? $options['button_alignment'] : 'left';

		// Get offer button type from settings or URL
		$offer_button_type = isset( $options['offer_type'] ) ? $options['offer_type'] : 'upsell';
		
		// Get product data
		$response = \WPFunnels\Wpfnl_functions::get_product_data_for_widget( get_the_ID() );
		$offer_product = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
		$get_product_type = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
		?>
		<div class="wp-block-wpfnl-offer-btn-<?php echo esc_attr( $button_align ); ?>">
			<div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper">
				<?php
				if( ('variable' === $get_product_type || 'variable-subscription' === $get_product_type) && 'accept' === $offer_action ) {
					echo '<div class="has-variation-product">';
					echo '<div class="wpfnl-product-variation">';
					if( 'yes' === $show_price ){
						echo '<span class="offer-btn-loader"></span>';
					}
					$post_id = get_the_ID();
					echo do_shortcode( '[wpf_variable_offer post_id="'.$post_id.'"]' );
					echo '</div>';
				}
				?>
				
				<div class="wpfnl-offerbtn-and-price-wrapper">
					<?php if ( $show_price === 'yes' && $offer_action === 'accept' ) : ?>
						<span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
							<?php
							if( ( 'variable' !== $get_product_type && 'variable-subscription' !== $get_product_type ) && 'accept' === $offer_action ){
								$step_id = get_the_ID();
								$step_type = get_post_meta( $step_id, '_step_type', true );
								$products = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );
								
								if ( ! empty( $products ) && is_array( $products ) ) {
									$product_id = isset( $products[0]['id'] ) ? $products[0]['id'] : 0;
									if ( $product_id ) {
										$product = wc_get_product( $product_id );
										if ( $product ) {
											echo $product->get_price_html();
										}
									}
								}
							}
							?>
						</span>
					<?php endif; ?>
					
					<a href="#"
						class="wpfunnels-oxygen-element wpfunnels_offer_button"
						id="wpfunnels_<?php echo esc_attr( $offer_button_type ); ?>_<?php echo esc_attr( $offer_action ); ?>"
						data-offertype="<?php echo esc_attr( $offer_button_type ); ?>">
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>
				
				<?php 
				if( ( 'variable' === $get_product_type || 'variable-subscription' === $get_product_type ) && 'accept' === $offer_action ) {
					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
		
		// Fire after offer button hook
		if ( $offer_action !== 'reject' && \WPFunnels\Wpfnl_functions::is_wc_active() ) {
			do_action( 'wpfunnels/after_offer_button' );
		}
	}
}

new OfferButton();
