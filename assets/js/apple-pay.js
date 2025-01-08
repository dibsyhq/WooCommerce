const DibsApplePay = {

    __construct: () => setTimeout(() => DibsApplePay.bootstrap(), 750 ),

    bootstrap: e => {
        instance            = DibsApplePay,
        session             = null,
        applePayBtn         = document.querySelector('apple-pay-button'),
        applePayRequest     = {},
        instance.init();
    },

    init: () => {
        let element;
        if ( typeof wc != "undefined" ) {
            element = jQuery('.wc-block-components-express-payment.wc-block-components-express-payment--checkout');
        } else {
            element = jQuery('.apple-pay-container');
        }

        element.block({
            message: null,
            overlayCSS: {
            background: '#fff',
                opacity: 0.6
            }
        });

        if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
            jQuery('#wc-disby-express-checkout-button-separator').show();
        }
        
        jQuery.post( wc_add_to_cart_params.ajax_url, {
        'action': 'initialize_apple_pay',
        'order_id': DAP.order_pay_id
        }, response => {
            applePayRequest = response;
            let element;
            if ( typeof wc != "undefined" ) {
                element = jQuery('.wc-block-components-express-payment.wc-block-components-express-payment--checkout');
            } else {
                element = jQuery('.apple-pay-container');
            }

            element.unblock( { fadeOut: 0 } );
        });
    
        applePayBtn && applePayBtn.addEventListener('click', async (e) => {
            e.preventDefault()
            instance.onApplePayButtonClicked()
        });
    },

    onApplePayButtonClicked: async () => {
        if ( ! window.ApplePaySession || ! ApplePaySession.canMakePayments() ) {
            return;
        }
        
        session = new ApplePaySession(3, applePayRequest);

        session.onvalidatemerchant = instance.onvalidatemerchant();

        session.onpaymentauthorized = instance.onpaymentauthorized.bind(instance);

        session.oncancel = (event) => {
            console.log('Payment has been cancelled');
        }

        session.begin();
    },

    onvalidatemerchant: event => {
        jQuery.post( wc_add_to_cart_params.ajax_url, {
            'action': 'merchant_session_apple_pay',
            'security': DAP.nonce,
        }, response => {
            if ( typeof response != "undefined" && response != '' ) {
                if ( response.success != 'false' ) {
                    session.completeMerchantValidation( response.data )
                } else {
                    console.log(response.data.message);
                }
            }
        } );
    },

    onpaymentauthorized: async event =>  { 
        if ( typeof event == "undefined" || event == '' ) {
            return;
        }

        try {
            var token = JSON.stringify(event?.payment?.token);
            // var event = event.payment.shippingContact;

            console.log(token);

            console.log(event.payment.shippingContact); 

            const shippingDetails   = event.payment.shippingContact;

            const shippingName      = `${shippingDetails.givenName} ${shippingDetails.familyName}`;
            const shippingEmail     = shippingDetails.emailAddress;
            const shippingPhone     = shippingDetails.phoneNumber;
            const shippingAddress   = shippingDetails.addressLines.join(', ');
            const shippingPostalCode = shippingDetails.postalCode;
            const shippingCity      = shippingDetails.locality;
            const shippingState     = shippingDetails.administrativeArea;
            const shippingCountry   = shippingDetails.country;

            const payerName         = event.payment.payerName ? event.payment.payerName : shippingName;
            const payerEmail        = event.payment.payerEmail ? event.payment.payerEmail : shippingEmail;
            const payerPhone         = event.payment.payerPhone ? event.payment.payerPhone : shippingPhone;

            const paymentData = {
                payerName,
                payerEmail,
                payerPhone,
                shippingName,
                shippingEmail,
                shippingPhone,
                shippingAddress,
                shippingPostalCode,
                shippingCity,
                shippingState,
                shippingCountry,
                token,
            };

            console.log(paymentData);

            instance.block_checkout();
            
            console.log('trying to trigger ajax request');
            
            try {
                console.log('Ajax trigerred');

                jQuery.post(wc_add_to_cart_params.ajax_url, {
                    'action': 'process_payment',
                    'security': DAP.nonce,
                    'post_data': JSON.stringify(paymentData),
                }, response => {
                    instance.unblock_checkout();
                    
                    if ( typeof response.data.return_url != "undefined" ) {
                        window.location.href = response.data.return_url;
                    } else {
                        alert(response.data.error);
                    }
                });
            } catch(error) {
                console.log(error);
            }

            session.completePayment({ status: ApplePaySession.STATUS_SUCCESS });

            // if ( typeof wc != "undefined" ) {
            //     jQuery('label[for="radio-control-wc-payment-method-options-dibsy-v2"]').trigger('click')
            // } else {
            //     jQuery('#payment_method_dibsy-v2').trigger('click')
            // }

            // var $form = jQuery( 'form.woocommerce-checkout, form.wc-block-checkout__form, form#order_review' );
            // $form.append( '<input type="hidden" class="dsb-idempotent" name="dsb_applepay_token" value="' + token + '" />' );

            // session.completePayment( isSuccess )

            // if( jQuery('form.wc-block-checkout__form').length > 0 ) {
            //     jQuery(".wc-block-components-checkout-place-order-button").trigger("click");
            // } else {
            //     $form.submit();
            // }
        } catch(error) {
            console.log(error);
            session.completePayment({ status: ApplePaySession.STATUS_FAILURE });
        }
    },

    block_checkout: () => {
        let element;
        if ( typeof wc != "undefined" ) {
            element = jQuery('form.wc-block-checkout__form');
        } else {
            element = jQuery('form.checkout');
        }

        element.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    },

    unblock_checkout: () => {
        setTimeout(function () {  
            let element;
            if ( typeof wc != "undefined" ) {
                element = jQuery('form.wc-block-checkout__form');
            } else {
                element = jQuery('form.checkout');
            }

            element.unblock( { fadeOut: 0 } );
        }, 1500);
    }

}

document.addEventListener("DOMContentLoaded", DibsApplePay.__construct.bind(DibsApplePay));