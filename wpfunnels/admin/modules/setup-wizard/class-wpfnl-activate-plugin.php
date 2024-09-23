<?php
namespace WPFunnels\Admin\SetupWizard;

/**
 * Activate Plugins
 * 
 * @since 3.3.1
 */
class ActivatePlugin {
    
    protected $plugins = [];


    /**
     * Constructor
     * 
     * @param array $plugins
     * @since 3.3.1
     */
    public function __construct( $plugins = [] ){
        $this->plugins = $plugins;
    }

    /**
     * Activate plugins
     * 
     * @return array
     * @since 3.3.1
     */
    public function activate_plugins(){

        if( empty($this->plugins) && !is_array($this->plugins) ){
            return;
        }

        $response = [
            'suceess' => true,
        ];
        foreach( $this->plugins as $key=>$plugin ){
           
            if( !isset($plugin['name'],$plugin['status'],$plugin['path'],$plugin['type']) && ($plugin['status'] == 'activated' || $plugin['status'] == 'uninstalled') ){
                continue;
            }

            if( 'bricks' === $plugin['name'] ){
                if (wp_get_theme($plugin['path'])->exists()) {
                    switch_theme($plugin['path']);
                }
            }else{
                \activate_plugin( $plugin['path'] );

                if( isset($plugin['slug']) ){
                    if(  'woocommerce' === $plugin['slug'] ){
                        delete_transient( '_wc_activation_redirect' );
                    }
    
                    if(  'elementor' === $plugin['slug'] ){
                        update_option('elementor_onboarded',true);
                    }
                }
            }
        }
        return $response;
    }
}

?>