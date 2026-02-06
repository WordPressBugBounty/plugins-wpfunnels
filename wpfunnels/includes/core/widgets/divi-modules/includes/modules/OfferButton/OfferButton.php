<?php
namespace WPFunnels\Widgets\DiviModules\Includes\Modules\OfferButton;

use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Offer Button Module for Divi Builder
 *
 * @since 3.0.0
 */
class OfferButton extends \ET_Builder_Module {

	public $slug       = 'wpfnl_offer_button';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://getwpfunnels.com',
		'author'     => 'WPFunnels',
		'author_uri' => 'https://getwpfunnels.com',
	);

	/**
	 * Module properties initialization
	 */
	public function init() {
		$this->name = esc_html__( 'Offer Button', 'wpfnl' );
		$this->icon_path = plugin_dir_path( __FILE__ ) . 'icon.svg';
	}

	/**
	 * Get module fields
	 */
	public function get_fields() {
		return array(
			'offer_action' => array(
				'label'           => esc_html__( 'Button Action', 'wpfnl' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'accept' => esc_html__( 'Accept Offer', 'wpfnl' ),
					'reject' => esc_html__( 'Reject Offer', 'wpfnl' ),
				),
				'default'         => 'accept',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Choose whether this button accepts or rejects the offer.', 'wpfnl' ),
			),
			'button_text' => array(
				'label'           => esc_html__( 'Button Text', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Yes, Add to My Order!', 'wpfnl' ),
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Enter the text for the button.', 'wpfnl' ),
			),
			'show_product_price' => array(
				'label'           => esc_html__( 'Show Product Price', 'wpfnl' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'wpfnl' ),
					'off' => esc_html__( 'No', 'wpfnl' ),
				),
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Show product price above the button (only for accept button).', 'wpfnl' ),
				'show_if'         => array(
					'offer_action' => 'accept',
				),
			),
			'button_alignment' => array(
				'label'           => esc_html__( 'Button Alignment', 'wpfnl' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => array(
					'left'   => esc_html__( 'Left', 'wpfnl' ),
					'center' => esc_html__( 'Center', 'wpfnl' ),
					'right'  => esc_html__( 'Right', 'wpfnl' ),
				),
				'default'         => 'left',
				'toggle_slug'     => 'alignment',
				'description'     => esc_html__( 'Choose button alignment.', 'wpfnl' ),
			),
		);
	}

	/**
	 * Get advanced fields configuration
	 */
	public function get_advanced_fields_config() {
		return array(
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
			'borders'        => array(),
			'box_shadow'     => array(),
			'filters'        => array(),
			'margin_padding' => array(),
		);
	}

	/**
	 * Render module output
	 *
	 * @param array  $attrs       List of unprocessed attributes
	 * @param string $content     Content being processed
	 * @param string $render_slug Slug of module that is used for rendering output
	 *
	 * @return string Module's rendered output
	 */
	public function render( $attrs, $content, $render_slug ) {
		$offer_action = $this->props['offer_action'];
		$button_text = $this->props['button_text'];
		$show_price = $this->props['show_product_price'];
		$button_align = $this->props['button_alignment'];

		// Get offer button type from settings or URL
		$offer_button_type = isset( $this->props['offer_type'] ) ? $this->props['offer_type'] : 'upsell';
		
		// Get product data
		$response = \WPFunnels\Wpfnl_functions::get_product_data_for_widget( get_the_ID() );
		$offer_product = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
		$get_product_type = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';

		ob_start();
		?>
		<div class="wp-block-wpfnl-offer-btn-<?php echo esc_attr( $button_align ); ?>">
			<div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper">
				<?php
				if( ('variable' === $get_product_type || 'variable-subscription' === $get_product_type) && 'accept' === $offer_action ) {
					echo '<div class="has-variation-product">';
					echo '<div class="wpfnl-product-variation">';
					if( 'on' === $show_price ){
						echo '<span class="offer-btn-loader"></span>';
					}
					$post_id = get_the_ID();
					echo do_shortcode( '[wpf_variable_offer post_id="'.$post_id.'"]' );
					echo '</div>';
				}
				?>
				
				<div class="wpfnl-offerbtn-and-price-wrapper">
					<?php if ( $show_price === 'on' && $offer_action === 'accept' ) : ?>
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
						class="wpfunnels-divi-module wpfunnels_offer_button"
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

		return ob_get_clean();
	}
}

new OfferButton();
