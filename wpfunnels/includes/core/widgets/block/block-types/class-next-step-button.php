<?php
/**
 * Next step button 
 * 
 * @package
 */
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;


/**
 * NextStep button class.
 */
class NextStepButton extends AbstractBlock {

    protected $defaults = array(
        'outline'   => 'fill',
        'buttonColor'   => 'red',
        'buttonRadius'   => 5,
        'paddingTopBottom'   => 14,
        'paddingLeftRight'   => 25,
        'buttonFontSize'   => '18',
        'borderStyle'   => 'solid',
        'borderWidth'   => 1,
        'borderColor'   => '#39414d',
    );

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'next-step-button';


    /**
     * Render the Featured Product block.
     *
     * @param string $content    Block content.
     * @param array  $attributes Block attributes.
     * 
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
        $attributes = wp_parse_args( $attributes, $this->defaults );
        
        // Check display conditions
        $display_condition = isset($attributes['displayConditionType']) ? $attributes['displayConditionType'] : 'none';
        
        if ($display_condition !== 'none') {
            // User State condition
            if ($display_condition === 'user_state') {
                $hide_logged_in = isset($attributes['hideFromLoggedIn']) ? $attributes['hideFromLoggedIn'] : false;
                $hide_logged_out = isset($attributes['hideFromLoggedOut']) ? $attributes['hideFromLoggedOut'] : false;
                
                if ($hide_logged_in && is_user_logged_in()) {
                    return ''; // Don't render button
                }
                if ($hide_logged_out && !is_user_logged_in()) {
                    return ''; // Don't render button
                }
            }
            
            // User Role condition
            elseif ($display_condition === 'user_role') {
                $hide_for_user_role = isset($attributes['hideForUserRole']) ? $attributes['hideForUserRole'] : 'none';
                
                if ($hide_for_user_role !== 'none' && is_user_logged_in()) {
                    $user = wp_get_current_user();
                    if (in_array($hide_for_user_role, $user->roles)) {
                        return ''; // Don't render button
                    }
                }
            }
            
            // Day condition
            elseif ($display_condition === 'day') {
                $disable_on_days = isset($attributes['disableOnDays']) ? $attributes['disableOnDays'] : array();
                
                if (!empty($disable_on_days) && is_array($disable_on_days)) {
                    $current_day = strtolower(date('l')); // e.g., 'monday'
                    if (in_array($current_day, $disable_on_days)) {
                        return ''; // Don't render button
                    }
                }
            }
            
            // Browser condition
            elseif ($display_condition === 'browser') {
                $hide_on_browser = isset($attributes['hideOnBrowser']) ? $attributes['hideOnBrowser'] : 'none';
                
                if ($hide_on_browser !== 'none') {
                    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
                    $current_browser = '';
                    
                    // Detect browser from user agent
                    if (strpos($user_agent, 'edg') !== false) {
                        $current_browser = 'edge';
                    } elseif (strpos($user_agent, 'opr') !== false || strpos($user_agent, 'opera') !== false) {
                        $current_browser = 'opera_mini';
                    } elseif (strpos($user_agent, 'chrome') !== false) {
                        $current_browser = 'chrome';
                    } elseif (strpos($user_agent, 'safari') !== false) {
                        $current_browser = 'safari';
                    } elseif (strpos($user_agent, 'firefox') !== false) {
                        $current_browser = 'mozilla';
                    }
                    
                    // Hide button if current browser matches
                    if ($current_browser === $hide_on_browser) {
                        return ''; // Don't render button
                    }
                }
            }
            
            // Operating System condition
            elseif ($display_condition === 'operating_system') {
                $hide_on_os = isset($attributes['hideOnOS']) ? $attributes['hideOnOS'] : 'none';
                
                if ($hide_on_os !== 'none') {
                    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
                    $current_os = '';
                    
                    // Detect operating system from user agent
                    if (strpos($user_agent, 'windows') !== false || strpos($user_agent, 'win32') !== false || strpos($user_agent, 'win64') !== false) {
                        $current_os = 'windows';
                    } elseif (strpos($user_agent, 'macintosh') !== false || strpos($user_agent, 'mac os x') !== false) {
                        $current_os = 'macos';
                    } elseif (strpos($user_agent, 'linux') !== false && strpos($user_agent, 'android') === false) {
                        $current_os = 'linux';
                    } elseif (strpos($user_agent, 'android') !== false) {
                        $current_os = 'android';
                    } elseif (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false || strpos($user_agent, 'ipod') !== false) {
                        $current_os = 'ios';
                    } elseif (strpos($user_agent, 'sunos') !== false) {
                        $current_os = 'sunos';
                    } elseif (strpos($user_agent, 'openbsd') !== false) {
                        $current_os = 'openbsd';
                    }
                    
                    // Hide button if current OS matches
                    if ($current_os === $hide_on_os) {
                        return ''; // Don't render button
                    }
                }
            }
        }
        
        $dynamic_css = $this->generate_assets($attributes);
        $new_content = "<style>$dynamic_css</style>".$content;
        return $this->inject_html_data_attributes( $new_content, $attributes );
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
            '.wpfunnels-landing-block' => array(
                'background-color' => $attributes['buttonColor'],
                'border-radius' => $attributes['buttonRadius'],
                'padding-top' => $attributes['paddingTopBottom'],
                'padding-bottom' => $attributes['paddingTopBottom'],
                'padding-left' => $attributes['paddingLeftRight'],
                'padding-right' => $attributes['paddingLeftRight'],
                'font-size' => $attributes['buttonFontSize'],
                'border-style' => $attributes['borderStyle'],
                'border-width' => $attributes['borderWidth'],
                'border-color' => $attributes['borderColor'],
            ),
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
     * Get the frontend script handle for this block type.
     *
     * @param string $key Data to get, or default to everything.
     * 
     * @return array|string
     */
    protected function get_block_type_script( $key = null ) {
        $script = [
            'handle'       => 'wpfnl-next-step-button-frontend',
            'path'         => $this->get_block_asset_build_path( 'next-step-button-frontend' ),
            'dependencies' => [],
        ];
        return $key ? $script[ $key ] : $script;
    }
}
