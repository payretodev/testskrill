/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
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
                type: 'skrill_flexible',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_wlt',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_psc',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_pch',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_acc',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_vsa',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_msc',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_mae',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_amx',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_gcb',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_dnk',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_psp',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_csi',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_obt',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_ntl',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_gir',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_did',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_sft',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_ebt',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_idl',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_npy',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_pli',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_pwy',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_epy',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_glu',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_ali',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_adb',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_aob',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_aci',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_aup',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            },
            {
                type: 'skrill_btc',
                component: 'Skrill_Skrill/js/view/payment/method-renderer/skrill-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);



