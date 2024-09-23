<?php

namespace WPFunnels\Integrations;

use MRM\Common\MrmCommon;
use Mint\MRM\DataBase\Models\ContactGroupModel;

/**
 * Helper class for WP Funnels integrations.
 *
 * @since 3.2.0
 */

class Helper {


	/**
     * Get contact status based on the parameters provided.
     *
     * This method checks if double opt-in is enabled and sets the status accordingly.
     *
     * @param array $params Parameters containing contact status.
     * @return array|false Updated parameters with contact status or false if not enabled.
     *
     * @since 3.2.0
     */
	public static function get_contact_status( $params ) {
		if( !self::maybe_enabled() ){
			return false;
		}

        $is_enable = MrmCommon::is_double_optin_enable();
        if( ! $is_enable &&  empty( $params[ 'status' ][ 0 ] ) ) {
            $params['status'] = 'subscribed';
        } elseif( !is_array( $params['status'] ) ) {
            $params['status'] = isset( $params[ 'status' ] ) && in_array( $params[ 'status' ], array( 'subscribed', 'unsubscribed', 'pending' ), true ) ? $params[ 'status' ] : 'pending';
        } else {
            $params['status'] = isset( $params[ 'status' ][ 0 ] ) && ! empty( $params[ 'status' ][ 0 ] ) ? $params[ 'status' ][ 0 ] : 'pending';
        }
        return $params;
    }



	/**
     * Retrieve all lists.
     *
     * This method returns an array of lists. If the feature is not enabled, it returns a default list with a "Select list" option.
     *
     * @return array Array of lists.
     *
     * @since 3.2.0
     */
	public static function get_lists() {
		$lists = [
			'' => __( 'Select list', 'wpfnl'),
		];
		if( !self::maybe_enabled() ){
			return $lists;
		}
		$data = ContactGroupModel::get_all( 'lists', 0, 0, '','title','ASC' );

		if( !empty($data['data']) ){
			foreach ($data['data'] as $key => $value) {
				$lists[$value['id']] = $value['title'];
			}
		}
		return $lists;
	}


     /**
     * Retrieve all tags.
     *
     * This method returns an array of tags. If the feature is not enabled, it returns a default list with a "Select list" option.
     *
     * @return array Array of lists.
     *
     * @since 3.2.0
     */
	public static function get_tags() {
		$lists = [
			'' => __( 'Select tag', 'wpfnl'),
		];
		if( !self::maybe_enabled() ){
			return $lists;
		}
		$data = ContactGroupModel::get_all( 'tags', 0, 0, '','title','ASC' );

		if( !empty($data['data']) ){
			foreach ($data['data'] as $key => $value) {
				$lists[$value['id']] = $value['title'];
			}
		}
		return $lists;
	}



	/**
     * Retrieve all lists for gutenberg.
     *
     * This method returns an array of lists. If the feature is not enabled, it returns a default list with a "Select list" option.
     *
     * @return array Array of lists.
     *
     * @since 3.2.0
     */
	public static function get_lists_for_gutenberg() {
		$lists = [
			[
                    'label' => __( 'Select list', 'wpfnl'),
                    'value' => ''
               ]
		];
		if( !self::maybe_enabled() ){
			return $lists;
		}
		$data = ContactGroupModel::get_all( 'lists', 0, 0, '','title','ASC' );

		if( !empty($data['data']) ){
			foreach ($data['data'] as $key => $value) {
				$lists[] = [
                         'label' => $value['title'],
                         'value' => $value['id']
                    ];
			}
		}
		return $lists;
	}


	/**
     * Retrieve all tags for gutenberg.
     *
     * This method returns an array of contact tags. If the feature is not enabled, it returns a default tag with a "Select tag" option.
     *
     * @return array Array of tags.
     *
     * @since 3.2.0
     */
	public static function get_tags_for_gutenberg() {
		$tags = [
			[
                    'label' => __( 'Select tag', 'wpfnl'),
                    'value' => ''
               ]
		];
		if( !self::maybe_enabled() ){
			return $tags;
		}
		$data = ContactGroupModel::get_all( 'tags', 0, 0,'','title','ASC');

		if( !empty($data['data']) ){
			foreach ($data['data'] as $key => $value) {
				$tags[] = [
                         'label' => $value['title'],
                         'value' => $value['id']
                    ];
			}
		}
		return $tags;
	}


	/**
     * Retrieve a list by its ID.
     *
     * This method returns the details of a specific list based on its ID.
     *
     * @param int $id The ID of the list.
     * @return array|false The list details or false if not enabled.
     *
     * @since 3.2.0
     */
	public static function get_list_by_id( $id ) {
		if( !self::maybe_enabled() ){
			return false;
		}
		$data = ContactGroupModel::get( $id );
		return $data;
	}


	/**
     * Retrieve a contact tag by its ID.
     *
     * This method returns the details of a specific contact tag based on its ID.
     *
     * @param int $id The ID of the contact tag.
     * @return array|false The contact tag details or false if not enabled.
     *
     * @since 3.2.0
     */
	public static function get_tag_by_id( $id ) {
		if( !self::maybe_enabled() ){
			return false;
		}
		$data = ContactGroupModel::get( $id );
		return $data;
	}


	/**
     * Check if the MailMint feature is enabled.
     *
     * This method checks if the MAILMINT constant is defined to determine if the feature is enabled.
     *
     * @return bool True if enabled, false otherwise.
     *
     * @since 3.2.0
     */
	public static function maybe_enabled(){
		return defined('MAILMINT');
	}
}
