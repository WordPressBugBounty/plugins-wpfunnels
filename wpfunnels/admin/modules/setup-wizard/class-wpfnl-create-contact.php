<?php
namespace WPFunnels\Admin\SetupWizard;

/**
 * Create Contact to MailMint and Appsero
 * 
 * @since 3.3.1
 */
class CreateContact {
    
    protected $webHookUrl = [
        'https://useraccount.getwpfunnels.com/?mailmint=1&route=webhook&topic=contact&hash=20319037-b98b-4566-a327-921e0878eb2a'
    ];

    /**
     * Email
     * 
     * @var string
     * @since 3.3.1
     */
    protected $email = '';

    /**
     * Name
     * 
     * @var string
     * @since 3.3.1
     */
    protected $name = '';

    /**
     * Appsero URL
     * 
     * @var string
     * @since 3.3.1
     */
    protected $appsero_url = 'https://api.appsero.com/';

    /**
     * API Key
     * 
     * @var string
     * @since 3.3.1
     */
    protected $appsero_api_key = '6fb1e340-8276-4337-bca6-28a7cd186f06';


    /**
     * Plugin Name
     * 
     * @var string
     * @since 3.3.1
     */
    protected $plugin_name = WPFNL_NAME;


    /**
     * Plugin Slug
     * 
     * @var string
     * @since 3.3.1
     */
    protected $plugin_slug = WPFNL_SLUG;


    /**
     * Plugin File
     * 
     * @var string
     * @since 3.3.1
     */
    protected $plugin_file = WPFNL_FILE;


    /**
     * Source
     * 
     * @var string
     * @since 3.3.1
     */
    protected $source = 'setup-wizard';

    /**
     * Constructor
     * 
     * @param string $email
     * @param string $name
     * @since 3.3.1
     */
    public function __construct( $email, $name ){
        $this->email = $email;
        $this->name = $name;
        if( 'setup-wizard' == $this->source ){
            add_filter($this->plugin_slug.'_tracker_data',[$this,'modify_contact_data'], 10);
        }
    }


    /**
     * Create contact to MailMint via webhook
     * 
     * @return array
     * @since 3.3.1
     */
    public function create_contact_via_webhook(){
        
        if( !$this->email ){
            return [
                'suceess' => false,
            ];
        }

        $response = [
            'suceess' => true,
        ];

        $json_body_data = json_encode([
            'email'         => $this->email,
            'first_name'    => $this->name
        ]);

        try{
            if( !empty($this->webHookUrl ) ){
                foreach( $this->webHookUrl as $url ){
                    $response = wp_remote_request($url, [
                        'method'    => 'POST',
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => $json_body_data
                    ]);
                }
            }
        }catch(\Exception $e){
            $response = [
                'suceess' => false,
            ];
        }
        
        return $response;
    }


    /**
     * Send contact to Appsero
     * 
     * @return void
     * @since 3.3.1
     */
    public function send_contact_to_appsero(){
        $client = new \Appsero\Client( $this->appsero_api_key, $this->plugin_name, $this->plugin_file );
        $client->insights()->send_tracking_data(true);
        update_option( $this->plugin_slug.'_allow_tracking', 'yes');
        update_option( $this->plugin_slug.'_tracking_notice', '	hide');
    }


    /**
     * Modify contact data before sending to appsero
     * 
     * @param array $data
     * @return array
     * @since 3.3.1
     */
    public function modify_contact_data($data){
        $data['admin_email'] = $this->email;
        $data['first_name'] = $this->name;
        return $data;
    }
}
?>