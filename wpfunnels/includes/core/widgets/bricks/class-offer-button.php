<?php
namespace WPFunnels\Widgets\Bricks;

use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Offer Button Element for Bricks Builder
 *
 * @since 3.0.0
 */
class OfferButton extends \Bricks\Element {

	public $category = 'wpfunnels';
	public $name = 'wpfnl-offer-button';
	public $icon = 'ti-mouse';
	public $css_selector = '.wpfnl-offerbtn-wrapper';
	public $scripts = ['wpfnlOffer'];

	/**
	 * Get element label
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Offer Button', 'wpfnl' );
	}

	/**
	 * Set control groups
	 */
	public function set_control_groups() {
		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'wpfnl' ),
			'tab'   => 'content',
		];

		$this->control_groups['style'] = [
			'title' => esc_html__( 'Style', 'wpfnl' ),
			'tab'   => 'content',
		];
	}

	/**
	 * Set element controls
	 */
	public function set_controls() {
		// Settings
		$this->controls['offerAction'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Button Action', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				'accept' => esc_html__( 'Accept Offer', 'wpfnl' ),
				'reject' => esc_html__( 'Reject Offer', 'wpfnl' ),
			],
			'inline'      => true,
			'default'     => 'accept',
		];

		$this->controls['buttonText'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Button Text', 'wpfnl' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Yes, Add to My Order!', 'wpfnl' ),
		];

		$this->controls['showProductPrice'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Show Product Price', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				'no'  => esc_html__( 'No', 'wpfnl' ),
				'yes' => esc_html__( 'Yes', 'wpfnl' ),
			],
			'inline'      => true,
			'default'     => 'no',
			'required'    => [ 'offerAction', '=', 'accept' ],
		];

		$this->controls['buttonAlign'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Button Alignment', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				'left'   => esc_html__( 'Left', 'wpfnl' ),
				'center' => esc_html__( 'Center', 'wpfnl' ),
				'right'  => esc_html__( 'Right', 'wpfnl' ),
			],
			'inline'      => true,
			'default'     => 'left',
		];
	}

	/**
	 * Render element
	 */
	public function render() {
		$settings = $this->settings;
		
		$offer_action = isset( $settings['offerAction'] ) ? $settings['offerAction'] : 'accept';
		$button_text = isset( $settings['buttonText'] ) ? $settings['buttonText'] : __( 'Yes, Add to My Order!', 'wpfnl' );
		$show_price = isset( $settings['showProductPrice'] ) ? $settings['showProductPrice'] : 'no';
		$button_align = isset( $settings['buttonAlign'] ) ? $settings['buttonAlign'] : 'left';
		
		// Get offer button type from settings or URL
		$offer_button_type = isset( $settings['offerType'] ) ? $settings['offerType'] : 'upsell';
		
		$this->set_attribute( '_root', 'class', 'wp-block-wpfnl-offer-btn-' . esc_attr( $button_align ) );
		
		// Get product data
		$response = \WPFunnels\Wpfnl_functions::get_product_data_for_widget( get_the_ID() );
		$offer_product = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
		$get_product_type = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
		
		echo "<div {$this->render_attributes( '_root' )}>";
		?>
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
					class="wpfunnels-bricks-widget wpfunnels_offer_button btn"
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
		<?php
		echo '</div>';
		
		// Fire after offer button hook
		if ( $offer_action !== 'reject' && \WPFunnels\Wpfnl_functions::is_wc_active() ) {
			do_action( 'wpfunnels/after_offer_button' );
		}
	}
}
