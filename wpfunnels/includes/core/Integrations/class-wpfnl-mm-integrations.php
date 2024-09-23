<?php
namespace WPFunnels\Integrations;

use Mint\MRM\DataStores\ContactData;

use Mint\MRM\DataBase\Models\ContactModel;
use Mint\MRM\Admin\API\Controllers\MessageController;
use Mint\MRM\DataBase\Models\ContactGroupModel;

/**
 * MailMint class for managing contact creation, updates, and associations with lists and tags.
 *
 * @since 3.2.0
 */
class MailMint {

	/**
     * Contact data array.
     *
     * @var array
     * @since 3.2.0
     */
	protected $contact_data = [];

	/**
     * Lists array.
     *
     * @var array
     * @since 3.2.0
     */
	protected $lists = [];

	/**
     * Tags array.
     *
     * @var array
     * @since 3.2.0
     */
	protected $tags = [];

	/**
     * Contact ID.
     *
     * @var int
     * @since 3.2.0
     */
    protected $contact_id;

	/**
     * Constructor to initialize contact data, lists, and tags.
     *
     * @param array $contact_data Contact data.
     * @param array $lists Lists to associate with the contact.
     * @param array $tags Tags to associate with the contact.
     *
     * @since 3.2.0
     */
	public function __construct( $contact_data = [], $lists = [], $tags = [] ) {
		$this->contact_data = $contact_data;
		$this->lists = $lists;
		$this->tags = $tags;

	}

	/**
     * Create or update a contact based on provided contact data.
     *
     * This method checks if the contact exists and updates it or creates a new contact.
     * It also handles double opt-in if configured.
     *
     * @since 3.2.0
     */
	public function create_or_update_contact(){
		if( Helper::maybe_enabled() && !empty( $this->contact_data['email'] )  ){

			$settings = get_option( '_mint_integration_settings', array(
                'zero_bounce' => array(
                    'api_key' => '',
                    'email_address' => '',
                    'is_integrated' => false,
                ),
            ) );

            $zero_bounce   = isset( $settings['zero_bounce'] ) ? $settings['zero_bounce'] : array();
            $api_key       = isset( $zero_bounce['api_key'] ) ? $zero_bounce['api_key'] : '';
            $is_integrated = isset( $zero_bounce['is_integrated'] ) ? $zero_bounce['is_integrated'] : false;

            if( $is_integrated ) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.zerobounce.net/v2/validate?api_key='.$api_key.'&email='.$this->contact_data['email'].'&ip_address=null');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                $output = curl_exec($ch);

                curl_close($ch);

                $response = json_decode($output, true);

                if( isset( $response['status'] ) && 'valid' !== $response['status'] ){
                    return;
                }
            }

			$exist = ContactModel::is_contact_exist( $this->contact_data['email'] );
			if( $exist ){
				$this->contact_id = ContactModel::get_id_by_email( $this->contact_data['email'] );
				$this->contact_id = ContactModel::update( $this->contact_data, $this->contact_id );
			} else {
                    
				$contact    = new ContactData( $this->contact_data['email'], $this->contact_data );
				$this->contact_id = ContactModel::insert( $contact );
			}

			if ( isset( $this->contact_data['status'] ) && 'pending' === $this->contact_data['status'] ) {
				MessageController::get_instance()->send_double_opt_in( $this->contact_id );
			}
		}
	}

	/**
     * Add contact to specified lists.
     *
     * This method associates the contact with the provided lists.
     *
     * @since 3.2.0
     */
	public function add_contact_to_lists(){
		if( !empty( $this->lists ) && $this->contact_id ){
			ContactGroupModel::set_lists_to_contact( $this->lists, $this->contact_id );
		}
	}

	/**
     * Add contact to specified tags.
     *
     * This method associates the contact with the provided tags.
     *
     * @since 3.2.0
     */
	public function add_contact_to_tags(){
		if( !empty( $this->tags ) && $this->contact_id ){
			ContactGroupModel::set_tags_to_contact( $this->tags, $this->contact_id );
		}
	}
}

