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
	public $css_selector = '.wpfnl-offerbtn-wrapper .wpfunnels_offer_button';
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
	 * Get WordPress user roles for display condition control.
	 *
	 * @return array
	 */
	protected function get_wp_user_roles() {
		$roles = [ 'none' => esc_html__( 'None', 'wpfnl' ) ];

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		foreach ( get_editable_roles() as $role_key => $role_info ) {
			$roles[ $role_key ] = $role_info['name'];
		}

		return $roles;
	}

	/**
	 * Set control groups
	 */
	public function set_control_groups() {
		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'wpfnl' ),
			'tab'   => 'content',
		];

		$this->control_groups['subtitle_style'] = [
			'title'    => esc_html__( 'Subtitle Style', 'wpfnl' ),
			'tab'      => 'content',
			'required' => [ 'enable_subtitle', '=', true ],
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

		// ---- Icon ----
		$this->controls['iconSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'type'  => 'separator',
			'label' => esc_html__( 'Icon', 'wpfnl' ),
		];

		$this->controls['icon'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'icon',
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Icon Position', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		// ---- Subtitle ----
		$this->controls['subtitleSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'type'  => 'separator',
			'label' => esc_html__( 'Subtitle', 'wpfnl' ),
		];

		$this->controls['enable_subtitle'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Enable Subtitle', 'wpfnl' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['subtitle_text'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Subtitle Text', 'wpfnl' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Click to continue', 'wpfnl' ),
			'placeholder' => esc_html__( 'Enter subtitle text', 'wpfnl' ),
			'required'    => [ 'enable_subtitle', '=', true ],
		];

		// ---- Subtitle Style (group) ----
		$this->controls['subtitleTypography'] = [
			'tab'      => 'content',
			'group'    => 'subtitle_style',
			'label'    => esc_html__( 'Subtitle Typography', 'wpfnl' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-button-subtitle',
				],
			],
			'exclude'  => [ 'text-align', 'text-decoration', 'line-height' ],
		];

		$this->controls['subtitleSpacing'] = [
			'tab'      => 'content',
			'group'    => 'subtitle_style',
			'label'    => esc_html__( 'Subtitle Spacing Top', 'wpfnl' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'margin-top',
					'selector' => '.wpfnl-button-subtitle',
				],
			],
			'default'  => '2px',
		];

		// ---- Responsive Display ----
		$this->controls['responsiveSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Responsive Display', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['hide_on_desktop'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Hide on Desktop (≥ 1200px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on desktop devices (window width >= 1200px)', 'wpfnl' ),
		];

		$this->controls['hide_on_tablet'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Hide on Tablet (768px – 1199px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on tablet devices (window width 768px - 1199px)', 'wpfnl' ),
		];

		$this->controls['hide_on_mobile'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Hide on Mobile (≤ 767px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on mobile devices (window width <= 767px)', 'wpfnl' ),
		];

		// ---- Display Conditions ----
		$this->controls['displayConditionSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Display Conditions', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['display_condition_type'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Display Condition', 'wpfnl' ),
			'type'    => 'select',
			'options' => [
				'none'             => esc_html__( 'None', 'wpfnl' ),
				'user_state'       => esc_html__( 'User State', 'wpfnl' ),
				'user_role'        => esc_html__( 'User Role', 'wpfnl' ),
				'browser'          => esc_html__( 'Browser', 'wpfnl' ),
				'operating_system' => esc_html__( 'Operating System', 'wpfnl' ),
				'day'              => esc_html__( 'Day', 'wpfnl' ),
			],
			'inline'  => false,
			'default' => 'none',
		];

		$this->controls['hide_from_logged_in'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Hide From Logged In User', 'wpfnl' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'display_condition_type', '=', 'user_state' ],
		];

		$this->controls['hide_from_logged_out'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Hide From Logged Out User', 'wpfnl' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'display_condition_type', '=', 'user_state' ],
		];

		$this->controls['hide_for_user_role'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Hide For User Role', 'wpfnl' ),
			'type'     => 'select',
			'options'  => $this->get_wp_user_roles(),
			'default'  => 'none',
			'required' => [ 'display_condition_type', '=', 'user_role' ],
		];

		$this->controls['hide_on_browser'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Hide On Browser', 'wpfnl' ),
			'type'     => 'select',
			'options'  => [
				'none'       => esc_html__( 'None', 'wpfnl' ),
				'mozilla'    => esc_html__( 'Mozilla Firefox', 'wpfnl' ),
				'chrome'     => esc_html__( 'Google Chrome', 'wpfnl' ),
				'opera_mini' => esc_html__( 'Opera Mini', 'wpfnl' ),
				'safari'     => esc_html__( 'Safari', 'wpfnl' ),
				'edge'       => esc_html__( 'Microsoft Edge', 'wpfnl' ),
			],
			'default'  => 'none',
			'required' => [ 'display_condition_type', '=', 'browser' ],
		];

		$this->controls['hide_on_os'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Hide On Operating System', 'wpfnl' ),
			'type'     => 'select',
			'options'  => [
				'none'    => esc_html__( 'None', 'wpfnl' ),
				'ios'     => esc_html__( 'iOS', 'wpfnl' ),
				'android' => esc_html__( 'Android', 'wpfnl' ),
				'windows' => esc_html__( 'Windows', 'wpfnl' ),
				'macos'   => esc_html__( 'MacOS', 'wpfnl' ),
				'linux'   => esc_html__( 'Linux', 'wpfnl' ),
				'sunos'   => esc_html__( 'SunOS', 'wpfnl' ),
				'openbsd' => esc_html__( 'OpenBSD', 'wpfnl' ),
			],
			'default'  => 'none',
			'required' => [ 'display_condition_type', '=', 'operating_system' ],
		];

		$this->controls['disable_on_days'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Select Days You Want To Disable', 'wpfnl' ),
			'type'     => 'select',
			'multiple' => true,
			'options'  => [
				'monday'    => esc_html__( 'Monday', 'wpfnl' ),
				'tuesday'   => esc_html__( 'Tuesday', 'wpfnl' ),
				'wednesday' => esc_html__( 'Wednesday', 'wpfnl' ),
				'thursday'  => esc_html__( 'Thursday', 'wpfnl' ),
				'friday'    => esc_html__( 'Friday', 'wpfnl' ),
				'saturday'  => esc_html__( 'Saturday', 'wpfnl' ),
				'sunday'    => esc_html__( 'Sunday', 'wpfnl' ),
			],
			'default'  => [],
			'required' => [ 'display_condition_type', '=', 'day' ],
		];

		// ---- Animation ----
		$this->controls['animationSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Animation', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['entrance_animation'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Entrance Animation', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				''            => esc_html__( 'None', 'wpfnl' ),
				'fadeIn'      => esc_html__( 'Fade In', 'wpfnl' ),
				'fadeInUp'    => esc_html__( 'Fade In Up', 'wpfnl' ),
				'fadeInDown'  => esc_html__( 'Fade In Down', 'wpfnl' ),
				'fadeInLeft'  => esc_html__( 'Fade In Left', 'wpfnl' ),
				'fadeInRight' => esc_html__( 'Fade In Right', 'wpfnl' ),
				'zoomIn'      => esc_html__( 'Zoom In', 'wpfnl' ),
				'zoomInUp'    => esc_html__( 'Zoom In Up', 'wpfnl' ),
				'bounceIn'    => esc_html__( 'Bounce In', 'wpfnl' ),
				'bounceInUp'  => esc_html__( 'Bounce In Up', 'wpfnl' ),
				'slideInUp'   => esc_html__( 'Slide In Up', 'wpfnl' ),
				'slideInLeft' => esc_html__( 'Slide In Left', 'wpfnl' ),
				'slideInRight'=> esc_html__( 'Slide In Right', 'wpfnl' ),
				'flipInX'     => esc_html__( 'Flip In X', 'wpfnl' ),
				'flipInY'     => esc_html__( 'Flip In Y', 'wpfnl' ),
				'pulse'       => esc_html__( 'Pulse', 'wpfnl' ),
				'tada'        => esc_html__( 'Tada', 'wpfnl' ),
				'wobble'      => esc_html__( 'Wobble', 'wpfnl' ),
			],
			'inline'      => false,
			'placeholder' => esc_html__( 'None', 'wpfnl' ),
			'default'     => '',
			'description' => esc_html__( 'Animation plays when the button enters the viewport on the live page.', 'wpfnl' ),
		];
	}

	/**
	 * Render element
	 */
	public function render() {
		$settings = $this->settings;

		// ---- Display Conditions ----
		$display_condition = ! empty( $settings['display_condition_type'] ) ? $settings['display_condition_type'] : 'none';

		if ( $display_condition === 'user_state' ) {
			$hide_logged_in  = ! empty( $settings['hide_from_logged_in'] );
			$hide_logged_out = ! empty( $settings['hide_from_logged_out'] );
			if ( $hide_logged_in && is_user_logged_in() ) { return; }
			if ( $hide_logged_out && ! is_user_logged_in() ) { return; }

		} elseif ( $display_condition === 'user_role' ) {
			$hide_for_user_role = ! empty( $settings['hide_for_user_role'] ) ? $settings['hide_for_user_role'] : 'none';
			if ( $hide_for_user_role !== 'none' && is_user_logged_in() ) {
				$user = wp_get_current_user();
				if ( in_array( $hide_for_user_role, $user->roles ) ) { return; }
			}

		} elseif ( $display_condition === 'day' ) {
			$disable_on_days = ! empty( $settings['disable_on_days'] ) ? $settings['disable_on_days'] : [];
			if ( ! empty( $disable_on_days ) && is_array( $disable_on_days ) ) {
				if ( in_array( strtolower( date( 'l' ) ), $disable_on_days ) ) { return; }
			}

		} elseif ( $display_condition === 'browser' ) {
			$hide_on_browser = ! empty( $settings['hide_on_browser'] ) ? $settings['hide_on_browser'] : 'none';
			if ( $hide_on_browser !== 'none' ) {
				$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				$cb = '';
				if ( strpos( $ua, 'edg' ) !== false )                                          { $cb = 'edge'; }
				elseif ( strpos( $ua, 'opr' ) !== false || strpos( $ua, 'opera' ) !== false )  { $cb = 'opera_mini'; }
				elseif ( strpos( $ua, 'chrome' ) !== false )                                   { $cb = 'chrome'; }
				elseif ( strpos( $ua, 'safari' ) !== false )                                   { $cb = 'safari'; }
				elseif ( strpos( $ua, 'firefox' ) !== false )                                  { $cb = 'mozilla'; }
				if ( $cb === $hide_on_browser ) { return; }
			}

		} elseif ( $display_condition === 'operating_system' ) {
			$hide_on_os = ! empty( $settings['hide_on_os'] ) ? $settings['hide_on_os'] : 'none';
			if ( $hide_on_os !== 'none' ) {
				$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				$co = '';
				if ( strpos( $ua, 'windows' ) !== false || strpos( $ua, 'win32' ) !== false || strpos( $ua, 'win64' ) !== false ) { $co = 'windows'; }
				elseif ( strpos( $ua, 'macintosh' ) !== false || strpos( $ua, 'mac os x' ) !== false )                           { $co = 'macos'; }
				elseif ( strpos( $ua, 'linux' ) !== false && strpos( $ua, 'android' ) === false )                                { $co = 'linux'; }
				elseif ( strpos( $ua, 'android' ) !== false )                                                                    { $co = 'android'; }
				elseif ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false || strpos( $ua, 'ipod' ) !== false ) { $co = 'ios'; }
				elseif ( strpos( $ua, 'sunos' ) !== false )                                                                      { $co = 'sunos'; }
				elseif ( strpos( $ua, 'openbsd' ) !== false )                                                                    { $co = 'openbsd'; }
				if ( $co === $hide_on_os ) { return; }
			}
		}
		// ---- End Display Conditions ----

		$offer_action      = isset( $settings['offerAction'] ) ? $settings['offerAction'] : 'accept';
		$button_text       = isset( $settings['buttonText'] ) ? $settings['buttonText'] : __( 'Yes, Add to My Order!', 'wpfnl' );
		$show_price        = isset( $settings['showProductPrice'] ) ? $settings['showProductPrice'] : 'no';
		$button_align      = isset( $settings['buttonAlign'] ) ? $settings['buttonAlign'] : 'left';
		$offer_button_type = isset( $settings['offerType'] ) ? $settings['offerType'] : 'upsell';

		// Animation
		$animation       = ! empty( $settings['entrance_animation'] ) ? $settings['entrance_animation'] : '';
		$animation_class = $animation ? ' wpfnl-animation ' . esc_attr( $animation ) : '';

		$is_bricks_editor    = isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'];
		$bricks_editor_class = $is_bricks_editor ? 'bricks-editor-active' : '';

		$this->set_attribute( '_root', 'class', 'wp-block-wpfnl-offer-btn-' . esc_attr( $button_align ) );
		if ( $animation ) {
			$this->set_attribute( '_root', 'class', 'wpfnl-animation ' . esc_attr( $animation ) );
		}
		if ( $is_bricks_editor ) {
			$this->set_attribute( '_root', 'class', 'bricks-editor-active' );
		}

		// Responsive display classes
		if ( ! empty( $settings['hide_on_desktop'] ) ) {
			$this->set_attribute( '_root', 'class', 'wpfnl-hide-desktop' );
		}
		if ( ! empty( $settings['hide_on_tablet'] ) ) {
			$this->set_attribute( '_root', 'class', 'wpfnl-hide-tablet' );
		}
		if ( ! empty( $settings['hide_on_mobile'] ) ) {
			$this->set_attribute( '_root', 'class', 'wpfnl-hide-mobile' );
		}
		
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
					class="wpfunnels-bricks-widget wpfunnels_offer_button btn<?php echo ( ! empty( $settings['enable_subtitle'] ) && ! empty( $settings['subtitle_text'] ) ) ? ' wpfnl-has-subtitle' : ''; ?>"
					id="wpfunnels_<?php echo esc_attr( $offer_button_type ); ?>_<?php echo esc_attr( $offer_action ); ?>"
					data-offertype="<?php echo esc_attr( $offer_button_type ); ?>">
					<?php
					$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
					$icon_position = ! empty( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'right';
					$enable_subtitle = ! empty( $settings['enable_subtitle'] );
					$subtitle_text   = ! empty( $settings['subtitle_text'] ) ? $settings['subtitle_text'] : '';

					if ( $enable_subtitle && $subtitle_text ) {
						echo "<span class='icon-wrapper'>";
						if ( $icon && $icon_position === 'left' ) {
							echo $icon;
						}
						echo esc_html( $button_text );
						if ( $icon && $icon_position === 'right' ) {
							echo $icon;
						}
						echo "</span>";
						echo '<small class="wpfnl-button-subtitle">' . esc_html( $subtitle_text ) . '</small>';
					} else {
						if ( $icon && $icon_position === 'left' ) {
							echo $icon;
						}
						echo esc_html( $button_text );
						if ( $icon && $icon_position === 'right' ) {
							echo $icon;
						}
					}
					?>
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
