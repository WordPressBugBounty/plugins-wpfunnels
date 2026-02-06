<?php
namespace WPFunnels\Admin\SetupWizard;

/**
 * Create Contact to MailMint and Appsero
 * 
 * @since 3.3.1
 */
class CreateContact {
    
    protected $webHookUrl = [
        'https://useraccount.getwpfunnels.com/?mailmint=1&route=webhook&topic=contact&hash=4ac0c970-adcb-42da-a64d-17620b35baa7',
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
            'first_name'    => $this->name,
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
        do_action('wpfunnels_after_accept_consent');
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

    public function update_contact_via_webhook($funnel_data) {
        if( !$this->email ){
            return false;
        }

        $json_body_data = json_encode([
            'email'               => $this->email,
            'wpfunnel_id'         => $funnel_data['funnel_id'],
            'wpfunnel_title'      => $funnel_data['funnel_title'],
            'wpfunnel_status'     => $funnel_data['funnel_status'],
            'wpfunnel_created_at' => $funnel_data['created_date'],
        ]);

        try{
            $response = wp_remote_request('https://useraccount.getwpfunnels.com/?mailmint=1&route=webhook&topic=contact&hash=227a9d2a-10e0-47ba-a729-ddbb0c9b87c3', [
                'method'  => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $json_body_data
            ]);
        }catch(\Exception $e){
            $response = [
                'suceess' => false,
            ];
        }
        
        return $response;
    }

    public function update_contact_order_via_webhook($order_data) {
        if( !$this->email ){
            return false;
        }

        $json_body_data = json_encode([
            'email'                    => $this->email,
            'wpfunnel_total_orders'    => $order_data['total_orders'],
            'wpfunnel_total_revenue'   => $order_data['total_revenue'],
            'wpfunnel_last_order_date' => $order_data['last_order_date'],
        ]);

        try{
            $response = wp_remote_request('https://useraccount.getwpfunnels.com/?mailmint=1&route=webhook&topic=contact&hash=3bdd62ee-05a2-4ab3-9d24-48b6e74b2c06', [
                'method'  => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $json_body_data
            ]);
        }catch(\Exception $e){
            $response = [
                'suceess' => false,
            ];
        }
        
        return $response;
    }
}
?>