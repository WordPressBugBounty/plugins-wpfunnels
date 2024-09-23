<?php
/**
 * Optin field registration
 * 
 * @package
 */
namespace WPFunnels\Admin\OptinField;

class OptinField{

	public function __construct() {
		add_action( 'user_contactmethods', [$this, 'wpfnl_extra_user_profile_fields'], 10, 2 );
	}

	/**
	 * Display Optin Registration field Phone
  	 *
	 * @param $user
	 */
	public function wpfnl_extra_user_profile_fields( $methods, $user ) {
		if( isset($user->ID) ){
			$phone = get_user_meta( $user->ID, 'phone', true );
			if( $phone ){
				$methods['phone'] = 'Phone';
			}
		
		}
		return $methods;
	}

	
}
