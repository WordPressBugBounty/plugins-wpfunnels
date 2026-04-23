<?php
/**
 * Namespace for the NextStep class.
 * This class is part of the WPFunnels\Widgets\Bricks namespace.
 */
namespace WPFunnels\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use WPFunnels\Wpfnl_functions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Class NextStep
 * 
 * Represents a NextStep element in the WP Funnels plugin.
 * This class extends the Element class.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class NextStep extends Element {

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_next_step'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder
    public $tag         = 'button';


     /**
     * Return localised element label
     * 
     * @return string
     * @since 3.1.0
     */
    public function get_label()
    {
        return esc_html__('Next Step', 'wpfnl');
    }

    /**
     * Get WordPress user roles for display condition control.
     *
     * @return array
     * @since 3.1.0
     */
    protected function get_wp_user_roles()
    {
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
     * Set builder controls
     * 
     * @since 3.1.0
     */
    public function set_controls() {
		$this->controls['button_type_selector'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Select Button Type', 'wpfnl' ),
			'type' => 'select',
			'options' => [
			  'checkout' => 'Next Step',
			  'url-path' => 'Go To URL Path',
			  'another-funnel' => 'Another Funnel',
			],
			'inline' => false,
			'placeholder' => esc_html__( 'Select Type', 'wpfnl' ),
			'default' => '',
		];

		$this->controls['url_path_field'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'URL Path', 'wpfnl' ),
			'type'        => 'link',
			'pasteStyles' => false,
			'placeholder' => esc_html__( 'http://yoursite.com', 'wpfnl' ),
			'required'    => [ 'button_type_selector', '=', 'url-path' ],
		];

		$this->controls['another_funnel_field'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Choose Funnel', 'wpfnl' ),
			'type' => 'select',
			'options' => Wpfnl_functions::get_funnel_list(),
			'inline' => false,
			'placeholder' => esc_html__( 'Select a Funnel', 'wpfnl' ),
			'default' => '',
			'required'    => [ 'button_type_selector', '=', 'another-funnel' ],
		];


		$this->controls['buttonTypeSeparator'] = [
			'type'  => 'separator',
		];
		  
		//---text---
		$this->controls['text'] = [
			'label'       => esc_html__( 'Button Title', 'wpfnl' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Next Step', 'wpfnl' ),
			'placeholder' => esc_html__( 'Next Step', 'wpfnl' ),
		];

		// Subtitle
		$this->controls['subtitleSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Subtitle', 'wpfnl' ),
		];

		$this->controls['enable_subtitle'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Enable Subtitle', 'wpfnl' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['subtitle_text'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Subtitle Text', 'wpfnl' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Click to continue', 'wpfnl' ),
			'placeholder' => esc_html__( 'Enter subtitle text', 'wpfnl' ),
			'required'    => [ 'enable_subtitle', '=', true ],
		];

		$this->controls['subtitleTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Subtitle Typography', 'wpfnl' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-button-subtitle',
				],
			],
			'exclude'  => [ 'text-align', 'text-decoration', 'line-height' ],
			'required' => [ 'enable_subtitle', '=', true ],
		];

		$this->controls['subtitleSpacing'] = [
			'tab'      => 'content',
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
			'required' => [ 'enable_subtitle', '=', true ],
		];

		$this->controls['size'] = [
			'label'       => esc_html__( 'Size', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'Default', 'wpfnl' ),
		];

		$this->controls['style'] = [
			'label'       => esc_html__( 'Style', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'reset'       => true,
			'default'     => 'primary',
			'placeholder' => esc_html__( 'None', 'wpfnl' ),
		];

		$this->controls['circle'] = [
			'label' => esc_html__( 'Circle', 'wpfnl' ),
			'type'  => 'checkbox',
			'reset' => true,
		];

		$this->controls['outline'] = [
			'label' => esc_html__( 'Outline', 'wpfnl' ),
			'type'  => 'checkbox',
			'reset' => true,
		];

		// Icon
		$this->controls['iconSeparator'] = [
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['icon'] = [
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'icon',
		];

		$this->controls['iconTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'label'       => esc_html__( 'Position', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		// Responsive Display
		$this->controls['responsiveSeparator'] = [
			'label' => esc_html__( 'Responsive Display', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['hide_on_desktop'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hide on Desktop (≥ 1200px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on desktop devices (window width >= 1200px)', 'wpfnl' ),
		];

		$this->controls['hide_on_tablet'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hide on Tablet (768px – 1199px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on tablet devices (window width 768px - 1199px)', 'wpfnl' ),
		];

		$this->controls['hide_on_mobile'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hide on Mobile (≤ 767px)', 'wpfnl' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Hide button on mobile devices (window width <= 767px)', 'wpfnl' ),
		];

		// Display Conditions
		$this->controls['displayConditionSeparator'] = [
			'label' => esc_html__( 'Display Conditions', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['display_condition_type'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Display Condition', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				'none'             => esc_html__( 'None', 'wpfnl' ),
				'user_state'       => esc_html__( 'User State', 'wpfnl' ),
				'user_role'        => esc_html__( 'User Role', 'wpfnl' ),
				'browser'          => esc_html__( 'Browser', 'wpfnl' ),
				'operating_system' => esc_html__( 'Operating System', 'wpfnl' ),
				'day'              => esc_html__( 'Day', 'wpfnl' ),
			],
			'inline'      => false,
			'default'     => 'none',
		];

		// User State
		$this->controls['hide_from_logged_in'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide From Logged In User', 'wpfnl' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'display_condition_type', '=', 'user_state' ],
		];

		$this->controls['hide_from_logged_out'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide From Logged Out User', 'wpfnl' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'display_condition_type', '=', 'user_state' ],
		];

		// User Role
		$this->controls['hide_for_user_role'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Hide For User Role', 'wpfnl' ),
			'type'     => 'select',
			'options'  => $this->get_wp_user_roles(),
			'default'  => 'none',
			'required' => [ 'display_condition_type', '=', 'user_role' ],
		];

		// Browser
		$this->controls['hide_on_browser'] = [
			'tab'      => 'content',
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

		// Operating System
		$this->controls['hide_on_os'] = [
			'tab'      => 'content',
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

		// Day
		$this->controls['disable_on_days'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Select Days You Want To Disable', 'wpfnl' ),
			'type'        => 'select',
			'multiple'    => true,
			'options'     => [
				'monday'    => esc_html__( 'Monday', 'wpfnl' ),
				'tuesday'   => esc_html__( 'Tuesday', 'wpfnl' ),
				'wednesday' => esc_html__( 'Wednesday', 'wpfnl' ),
				'thursday'  => esc_html__( 'Thursday', 'wpfnl' ),
				'friday'    => esc_html__( 'Friday', 'wpfnl' ),
				'saturday'  => esc_html__( 'Saturday', 'wpfnl' ),
				'sunday'    => esc_html__( 'Sunday', 'wpfnl' ),
			],
			'default'     => [],
			'required'    => [ 'display_condition_type', '=', 'day' ],
		];

		// Animation
		$this->controls['animationSeparator'] = [
			'label' => esc_html__( 'Animation', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['entrance_animation'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Entrance Animation', 'wpfnl' ),
			'type'        => 'select',
			'options'     => [
				''           => esc_html__( 'None', 'wpfnl' ),
				'fadeIn'     => esc_html__( 'Fade In', 'wpfnl' ),
				'fadeInUp'   => esc_html__( 'Fade In Up', 'wpfnl' ),
				'fadeInDown' => esc_html__( 'Fade In Down', 'wpfnl' ),
				'fadeInLeft' => esc_html__( 'Fade In Left', 'wpfnl' ),
				'fadeInRight'=> esc_html__( 'Fade In Right', 'wpfnl' ),
				'zoomIn'     => esc_html__( 'Zoom In', 'wpfnl' ),
				'zoomInUp'   => esc_html__( 'Zoom In Up', 'wpfnl' ),
				'bounceIn'   => esc_html__( 'Bounce In', 'wpfnl' ),
				'bounceInUp' => esc_html__( 'Bounce In Up', 'wpfnl' ),
				'slideInUp'  => esc_html__( 'Slide In Up', 'wpfnl' ),
				'slideInLeft'=> esc_html__( 'Slide In Left', 'wpfnl' ),
				'slideInRight'=> esc_html__( 'Slide In Right', 'wpfnl' ),
				'flipInX'    => esc_html__( 'Flip In X', 'wpfnl' ),
				'flipInY'    => esc_html__( 'Flip In Y', 'wpfnl' ),
				'pulse'      => esc_html__( 'Pulse', 'wpfnl' ),
				'tada'       => esc_html__( 'Tada', 'wpfnl' ),
				'wobble'     => esc_html__( 'Wobble', 'wpfnl' ),
			],
			'inline'      => false,
			'placeholder' => esc_html__( 'None', 'wpfnl' ),
			'default'     => '',
			'description' => esc_html__( 'Animation plays when the button enters the viewport on the live page.', 'wpfnl' ),
		];

		// $this->controls['iconGap'] = [
		// 	'label'    => esc_html__( 'Gap', 'wpfnl' ),
		// 	'type'     => 'number',
		// 	'units'    => true,
		// 	'css'      => [
		// 		[
		// 			'property' => 'gap',
		// 		],
		// 	],
		// 	'required' => [ 'icon', '!=', '' ],
		// ];

		// $this->controls['iconSpace'] = [
		// 	'label'    => esc_html__( 'Space between', 'wpfnl' ),
		// 	'type'     => 'checkbox',
		// 	'css'      => [
		// 		[
		// 			'property' => 'justify-content',
		// 			'value'    => 'space-between',
		// 		],
		// 	],
		// 	'required' => [ 'icon', '!=', '' ],
		// ];
	}


    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function render() {
		$settings = $this->settings;

		// ---- Display Conditions ----------------------------------------
		$display_condition = ! empty( $settings['display_condition_type'] ) ? $settings['display_condition_type'] : 'none';

		if ( $display_condition === 'user_state' ) {
			$hide_logged_in  = ! empty( $settings['hide_from_logged_in'] );
			$hide_logged_out = ! empty( $settings['hide_from_logged_out'] );

			if ( $hide_logged_in && is_user_logged_in() ) {
				return;
			}
			if ( $hide_logged_out && ! is_user_logged_in() ) {
				return;
			}

		} elseif ( $display_condition === 'user_role' ) {
			$hide_for_user_role = ! empty( $settings['hide_for_user_role'] ) ? $settings['hide_for_user_role'] : 'none';

			if ( $hide_for_user_role !== 'none' && is_user_logged_in() ) {
				$user = wp_get_current_user();
				if ( in_array( $hide_for_user_role, $user->roles ) ) {
					return;
				}
			}

		} elseif ( $display_condition === 'day' ) {
			$disable_on_days = ! empty( $settings['disable_on_days'] ) ? $settings['disable_on_days'] : [];

			if ( ! empty( $disable_on_days ) && is_array( $disable_on_days ) ) {
				$current_day = strtolower( date( 'l' ) ); // e.g., 'monday'
				if ( in_array( $current_day, $disable_on_days ) ) {
					return;
				}
			}

		} elseif ( $display_condition === 'browser' ) {
			$hide_on_browser = ! empty( $settings['hide_on_browser'] ) ? $settings['hide_on_browser'] : 'none';

			if ( $hide_on_browser !== 'none' ) {
				$user_agent      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				$current_browser = '';

				if ( strpos( $user_agent, 'edg' ) !== false ) {
					$current_browser = 'edge';
				} elseif ( strpos( $user_agent, 'opr' ) !== false || strpos( $user_agent, 'opera' ) !== false ) {
					$current_browser = 'opera_mini';
				} elseif ( strpos( $user_agent, 'chrome' ) !== false ) {
					$current_browser = 'chrome';
				} elseif ( strpos( $user_agent, 'safari' ) !== false ) {
					$current_browser = 'safari';
				} elseif ( strpos( $user_agent, 'firefox' ) !== false ) {
					$current_browser = 'mozilla';
				}

				if ( $current_browser === $hide_on_browser ) {
					return;
				}
			}

		} elseif ( $display_condition === 'operating_system' ) {
			$hide_on_os = ! empty( $settings['hide_on_os'] ) ? $settings['hide_on_os'] : 'none';

			if ( $hide_on_os !== 'none' ) {
				$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				$current_os = '';

				if ( strpos( $user_agent, 'windows' ) !== false || strpos( $user_agent, 'win32' ) !== false || strpos( $user_agent, 'win64' ) !== false ) {
					$current_os = 'windows';
				} elseif ( strpos( $user_agent, 'macintosh' ) !== false || strpos( $user_agent, 'mac os x' ) !== false ) {
					$current_os = 'macos';
				} elseif ( strpos( $user_agent, 'linux' ) !== false && strpos( $user_agent, 'android' ) === false ) {
					$current_os = 'linux';
				} elseif ( strpos( $user_agent, 'android' ) !== false ) {
					$current_os = 'android';
				} elseif ( strpos( $user_agent, 'iphone' ) !== false || strpos( $user_agent, 'ipad' ) !== false || strpos( $user_agent, 'ipod' ) !== false ) {
					$current_os = 'ios';
				} elseif ( strpos( $user_agent, 'sunos' ) !== false ) {
					$current_os = 'sunos';
				} elseif ( strpos( $user_agent, 'openbsd' ) !== false ) {
					$current_os = 'openbsd';
				}

				if ( $current_os === $hide_on_os ) {
					return;
				}
			}
		}
		// ---- End Display Conditions ------------------------------------

		$animation       = ! empty( $settings['entrance_animation'] ) ? $settings['entrance_animation'] : '';
		$animation_class = $animation ? ' wpfnl-animation ' . esc_attr( $animation ) : '';

		$is_bricks_editor = isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'];
		$active_editor_class = '';
		if ( $is_bricks_editor ) {
			$bricks_editor_class = 'bricks-editor-active';
		}else {
			$bricks_editor_class = '';
		}

		$this->set_attribute( '_root', 'class', 'bricks-button '.$animation_class.'' );

		if ( ! empty( $settings['size'] ) ) {
			$this->set_attribute( '_root', 'class', $settings['size'] );
		}

		if ( ! empty( $settings['style'] ) ) {
			// Outline
			if ( isset( $settings['outline'] ) ) {
				$this->set_attribute( '_root', 'class', 'outline' );
				$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
			}

			// Fill (= default)
			else {
				$this->set_attribute( '_root', 'class', "bricks-background-{$settings['style']}" );
			}
		}

		// Button circle
		if ( isset( $settings['circle'] ) ) {
			$this->set_attribute( '_root', 'class', 'circle' );
		}

		if ( isset( $settings['block'] ) ) {
			$this->set_attribute( '_root', 'class', 'block' );
		}
		

		$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
		$icon_position = ! empty( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'right';

		$enable_subtitle = ! empty( $settings['enable_subtitle'] );
		$subtitle_text   = ! empty( $settings['subtitle_text'] ) ? $settings['subtitle_text'] : '';

		// Responsive display classes
		$responsive_classes = '';
		if ( ! empty( $settings['hide_on_desktop'] ) ) {
			$responsive_classes .= ' wpfnl-hide-desktop';
		}
		if ( ! empty( $settings['hide_on_tablet'] ) ) {
			$responsive_classes .= ' wpfnl-hide-tablet';
		}
		if ( ! empty( $settings['hide_on_mobile'] ) ) {
			$responsive_classes .= ' wpfnl-hide-mobile';
		}

		$output .= "<a href='#' class='wpfnl-next-step-button".' '.$responsive_classes.' '.$bricks_editor_class. ( $enable_subtitle && $subtitle_text ? ' wpfnl-has-subtitle' : '' ) . "' id='wpfunnels_next_step_controller' role='button'><span {$this->render_attributes( '_root' )} >";

				if ( $enable_subtitle && $subtitle_text ) {
					$output .= "<span class='icon-wrapper'>";
						if ( $icon && $icon_position === 'left' ) {
							$output .= $icon;
						}

						if ( ! empty( $settings['text'] ) ) {
							$output .= trim( $settings['text'] );
						}

						if ( $icon && $icon_position === 'right' ) {
							$output .= $icon;
						}
					$output .= "</span>";
					$output .= '<small class="wpfnl-button-subtitle">' . esc_html( $subtitle_text ) . '</small>';

				}else {
					if ( $icon && $icon_position === 'left' ) {
						$output .= $icon;
					}

					if ( ! empty( $settings['text'] ) ) {
						$output .= trim( $settings['text'] );
					}

					if ( $icon && $icon_position === 'right' ) {
						$output .= $icon;
					}
				}

			$output .= "</span>";

		$output .= "</a>";

		echo $output;
	}

    

}
