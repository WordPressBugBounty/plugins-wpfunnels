(function($) {
    'use strict';
    function initAutoCompleteBillingAddress( countryCode ) {
        var billingAddressAutocomplete = new google.maps.places.Autocomplete(
            document.getElementById( 'billing_address_1' ),
            {
                types: ['geocode'],
                componentRestrictions: { country: countryCode }
            }
        );

        // Listen for when the user selects an address
        google.maps.event.addListener( billingAddressAutocomplete, 'place_changed', function() {
            // after the initialization empty all address related fields
            $( '#billing_address_1' ).val( '' );
            $( '#billing_country' ).val( '' );
            $( '#billing_address_2' ).val( '' );
            $( '#billing_city' ).val( '' );
            $( '#billing_state' ).val( '' );
            $( '#billing_postcode' ).val( '' );

            var place = billingAddressAutocomplete.getPlace();
            $('#billing_address_1').val(place.name);


            var country = '',
                city = '',
                postal_code = '';


            // Fill other address fields
            for (var i = 0; i < place.address_components.length; i++) {
                var component = place.address_components[i];

                if (component.types.includes('locality')) {

                    city = component.long_name;

                } else if (component.types.includes('administrative_area_level_1')) {

                    var stateCode = component.short_name;
                    $('#billing_state option').filter(function() {
                        return $(this).val() == stateCode;
                    }).prop('selected', true);

                } else if (component.types.includes('postal_code')) {

                    postal_code = component.long_name;

                } else if (component.types.includes('country')) {

                    country = component.long_name;

                }
            }

            if ( $( '#billing_postcode' ).length > 0 ) {
                $( '#billing_postcode' ).val( postal_code );
            }

            if ( $( '#billing_city' ).length > 0 ) {
                $( '#billing_city' ).val( city );
            }

            if ( $( '#billing_country' ).length > 0 ) {
                $( '#billing_country' ).val( country );
            }

        });
    }

    function initAutoCompleteShippingAddress( countryCode ) {
        var billingAddressAutocomplete = new google.maps.places.Autocomplete(
            document.getElementById( 'shipping_address_1' ),
            {
                types: ['geocode'],
                componentRestrictions: { country: countryCode }
            }
        );

        // Listen for when the user selects an address
        google.maps.event.addListener( billingAddressAutocomplete, 'place_changed', function() {
            // after the initialization empty all address related fields
            $( '#shipping_address_1' ).val( '' );
            $( '#shipping_country' ).val( '' );
            $( '#shipping_address_2' ).val( '' );
            $( '#shipping_city' ).val( '' );
            $( '#shipping_state' ).val( '' );
            $( '#shipping_postcode' ).val( '' );

            var place = billingAddressAutocomplete.getPlace();
            $('#shipping_address_1').val(place.name);


            var country = '',
                city = '',
                postal_code = '';


            // Fill other address fields
            for (var i = 0; i < place.address_components.length; i++) {
                var component = place.address_components[i];

                if (component.types.includes('locality')) {

                    city = component.long_name;

                } else if (component.types.includes('administrative_area_level_1')) {

                    var stateCode = component.short_name;
                    $('#shipping_state option').filter(function() {
                        return $(this).val() == stateCode;
                    }).prop('selected', true);

                } else if (component.types.includes('postal_code')) {

                    postal_code = component.long_name;

                } else if (component.types.includes('country')) {

                    country = component.long_name;

                }
            }

            if ( $( '#shipping_postcode' ).length > 0 ) {
                $( '#shipping_postcode' ).val( postal_code );
            }

            if ( $( '#shipping_city' ).length > 0 ) {
                $( '#shipping_city' ).val( city );
            }

            if ( $( '#shipping_country' ).length > 0 ) {
                $( '#shipping_country' ).val( country );
            }

        });
    }


    $( document ).on( 'ready', function () {
        initAutoCompleteBillingAddress( $( '#billing_country :selected' ).val() );
        $( document ).on( 'change', '#billing_country', function () {
            const country = $( this ).val();
            initAutoCompleteBillingAddress( country );
        } );

        $( document ).on( 'change', '#shipping_country', function () {
            const country = $( this ).val();
            initAutoCompleteShippingAddress( country );
        } );
    } );

})(jQuery);
