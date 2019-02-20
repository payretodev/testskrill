/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Skrill_Skrill/js/action/set-payment-method',
    ],
    function ($, Component, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Skrill_Skrill/payment/skrill-method'
            },
            /** Redirect to Payment Form */
            placeOrderAction: function () {
                // var validate = true;
                
                // if (!validate) {
                //     return false;
                // }
                this.selectPaymentMethod(); // save selected payment method in Quote
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            getLogos: function () {
                return window.checkoutConfig.payment.skrill.logos[this.getCode()];
            }
        });
    }
);



