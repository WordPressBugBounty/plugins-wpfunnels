<?php

namespace WPFunnels\Gateway\Exception;

class Wpfnl_Payment_Gateway_Exception extends \Exception {


    /**
     * Wpfnl_Payment_Gateway_Exception constructor.
     * @param $error_message
     * @param $error_code
     * @param string $gateway
     */
    public function __construct( $error_message, $error_code, $gateway = '' ) {
        parent::__construct( $error_message, $error_code );
    }


}
