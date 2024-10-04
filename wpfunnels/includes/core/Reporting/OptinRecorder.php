<?php

namespace WPFunnels\Report;


use WPFunnels\Optin\Optin_Record;
use WPFunnels\Wpfnl_functions;

/**
 * Class OptinRecorder
 *
 * Handles recording opt-in form submissions.
 */
class OptinRecorder {

    /**
     * OptinRecorder constructor.
     *
     * Registers hooks.
     */
    public function __construct() {
        add_action( 'wpfunnels/after_optin_submit', array($this, 'record_optin_submission'), 10, 5 );
    }


    /**
     * Record opt-in form submission.
     *
     * @param $step_id
     * @param $post_action
     * @param $action_type
     * @param $record Optin_Record
     * @param $post_data
     * @return void
     *
     * @since 3.5.0
     */
    public function record_optin_submission( $step_id, $post_action, $action_type, $record, $post_data ) {

        $funnel_id  = Wpfnl_functions::get_funnel_id_from_step( $step_id );
        $fields     = method_exists( $record, 'get_fields' ) ? $record->get_fields() : ( isset( $record->form_data ) ? $record->form_data : array() );
        $email      = '';
        $user_id    = 0;
        $hash       = '';
        if ( $fields && is_array( $fields ) ) {
            foreach( $fields as $key => $value ) {
                if ( 'email' === $key ) {
                    $email = $value;
                }
            }
        }

        if ( $email ) {
            $hash       = $this->get_rand_hash($email);
            $user       = get_user_by('email', $email );
            if ( $user ) {
                $user_id = $user->ID;
            }

            // Insert the data into the database table
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpfnl_optin_entries';
            $wpdb->insert($table_name, array(
                'funnel_id'     => $funnel_id,
                'step_id'       => $step_id,
                'user_id'       => $user_id,
                'email'         => $email,
                'hash'          => $hash,
                'data'          => serialize($post_data),
                'date_created'  => current_time('mysql'),
            ));
        }

    }


    /**
     * Returns alphanumeric hash
     *
     * @param $email
     * @param $len
     * @return string
     *
     * @since 3.5.0
     */
    public function get_rand_hash( $email, $len = 32 ) {
        return substr( md5( $email ), -$len );
    }
}
