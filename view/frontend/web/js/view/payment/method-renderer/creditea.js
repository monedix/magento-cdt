define(
    [
        'mage/url',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (url, Component, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Creditea_Magento2/payment/creditea'
            },
            afterPlaceOrder: function () {
                redirectOnSuccessAction.redirectUrl = url.build('creditea/webhook/redirect');
                this.redirectAfterPlaceOrder = true;
            }
        });
    }
);