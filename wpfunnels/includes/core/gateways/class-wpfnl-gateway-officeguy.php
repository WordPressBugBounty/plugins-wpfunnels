<?php

namespace WPFunnels\Gateway;

use OfficeGuyPayment;
use OfficeGuyAPI;

class Wpfnl_Gateway_OfficeGuy {


    function __construct()
    {
        add_action('og_payment_request_handle', array($this, 'store_sigle_use_token'), 10 ,2);
    }


    /**
     * Store token into meta post
     * 
     * @param mixed $Order
     * @param mixed $Request
     * 
     * @return void
     * @since 1.0.0
     */
    public function store_sigle_use_token($order, $Request)
    {
        $token = sanitize_text_field( $Request['SingleUseToken'] );
        $order->update_meta_data('SingleUseToken', $token );
    }



    /**
     * Process the offer payment
     * 
     * @param mixed $order
     * @param mixed $offer_product
     * 
     * @return bool
     * @since 1.0.0
     */
    public function process_payment($order, $offer_product)
    {
        $result = array(
            'is_success' => false,
            'message' => ''
        );
        $Request = $this->PreapreOfferProductPaymentRequest($order, $offer_product);
        $Gateway = GetOfficeGuyGateway();
        $IsOfficeGuySubscription = get_post_meta($offer_product['id'], 'OfficeGuySubscription', true) === 'yes';
        if ($IsOfficeGuySubscription)
            $Response = OfficeGuyAPI::Post($Request, '/billing/recurring/charge/', $Gateway->settings['environment'], true);
        else
            $Response = OfficeGuyAPI::Post($Request, '/billing/payments/charge/', $Gateway->settings['environment'], true);

        if ( ! is_wp_error( $Response ) ) {
            if (  $Response['Status'] == 0  ) {
                $result['is_success'] = true;
                $result['message'] = "Payment Successful";
            } else {
                $result['is_success'] = false;
                $result['message'] = $Response['UserErrorMessage'];
            }
        }
        return $result;
    }


    /**
     * Prepare an array for SUMIT payment charge API request
     * 
     * @param mixed $order
     * @param mixed $product
     * 
     * @return array
     * @since 1.0.0
     */
    private function PreapreOfferProductPaymentRequest($order, $product)
    {
        $Gateway = GetOfficeGuyGateway();
        $Request = array();
        $Request['Credentials'] = OfficeGuyPayment::GetCredentials($Gateway);
        $Request['SingleUseToken'] = $order->get_meta('SingleUseToken');

        $Request['Items'] = array();
        $Item = OfficeGuyPayment::GetPaymentOrderItem(null, $product['id'], round($product['price'], 2), 1, $order->get_currency(), null, null, $order);
        array_push($Request['Items'], $Item);
        
        $Request['VATIncluded'] = 'true';
        $Request['VATRate'] = OfficeGuyPayment::GetOrderVatRate($order);
        $Request['Customer'] = array();
        $Request['Customer']['ID'] = $order->get_meta("OfficeGuyCustomerID");
        $Request['Customer']['Name'] = $this->getCustomerFullName($order);
        $Request['Customer']['EmailAddress'] = $order->data['billing']['email'];

        $Request['AuthoriseOnly'] = $Gateway->settings['testing'] != 'no' ? 'true' : 'false';
        $Request['DraftDocument'] = $Gateway->settings['draftdocument'] != 'no' ? 'true' : 'false';
        $Request['SendDocumentByEmail'] = $Gateway->settings['emaildocument'] == 'yes' ? 'true' : 'false';
        $Request['UpdateCustomerByEmail'] = 'false';
        $Request['DocumentDescription'] = __('Order number', 'officeguy') . ': ' . $order->get_id();
        $Request['DocumentLanguage'] = OfficeGuyPayment::GetOrderLanguage($Gateway);
        $Request['MerchantNumber'] = $Gateway->settings['merchantnumber'];
        
        return $Request;
    }


    /**
     * Return customer full name
     * 
     * @param mixed $order
     * 
     * @return string
     * @since 1.0.0
     */
    private function getCustomerFullName($order)
    {
        return $order->data['billing']['first_name'] . ' ' . $order->data['billing']['last_name'];
    }
}