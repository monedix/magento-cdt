define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'creditea_magento2',
                component: 'Creditea_Magento2/js/view/payment/method-renderer/creditea'
            }
        );
        return Component.extend({});
    }
);
