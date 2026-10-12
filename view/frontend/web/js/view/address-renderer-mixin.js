/**
 * Marks a saved address the checkout would refuse: a warning with a link to edit it in My Account, and no
 * "Ship Here" button. The server decides which addresses (checkoutConfig.thaiAddress.invalidAddresses).
 */
define([
    'mage/url'
], function (url) {
    'use strict';

    return function (Renderer) {
        return Renderer.extend({
            defaults: {
                template: 'Peerapatfc_ThaiAddress/shipping-address/address-renderer/default'
            },

            /**
             * @return {String} Why the address is refused, or an empty string
             */
            getInvalidMessage: function () {
                var config = window.checkoutConfig && window.checkoutConfig.thaiAddress,
                    invalid = config && config.invalidAddresses,
                    id = this.address().customerAddressId;

                return invalid && id && invalid[id] ? invalid[id] : '';
            },

            /**
             * @return {String}
             */
            getEditUrl: function () {
                return url.build('customer/address/edit/id/' + this.address().customerAddressId);
            }
        });
    };
});
