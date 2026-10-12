/**
 * Provinces, districts and sub-districts from the module's REST API, fetched once each.
 */
define([
    'underscore',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (_, storage, urlBuilder) {
    'use strict';

    var cache = {};

    /**
     * @param {String} path
     * @param {Object} params
     * @return {Deferred}
     */
    function get(path, params) {
        var key = path + JSON.stringify(params);

        if (!cache[key]) {
            cache[key] = storage.get(urlBuilder.createUrl(path, params), false).fail(function () {
                delete cache[key];
            });
        }

        return cache[key];
    }

    return {
        /**
         * @return {Deferred} Resolves with [{code, name_th, name_en, region_id}]
         */
        provinces: function () {
            return get('/thai-address/provinces', {});
        },

        /**
         * @param {Number|String} regionId Magento region id
         * @return {Promise} Resolves with the province code, or null
         */
        provinceCode: function (regionId) {
            return this.provinces().then(function (provinces) {
                var province = _.find(provinces, function (row) {
                    return String(row.region_id) === String(regionId);
                });

                return province ? province.code : null;
            });
        },

        /**
         * @param {Number} provinceCode
         * @return {Deferred} Resolves with [{code, name_th, name_en}]
         */
        districts: function (provinceCode) {
            return get('/thai-address/provinces/:provinceCode/districts', {provinceCode: provinceCode});
        },

        /**
         * @param {Number} districtCode
         * @return {Deferred} Resolves with [{code, name_th, name_en, postcode}]
         */
        subdistricts: function (districtCode) {
            return get('/thai-address/districts/:districtCode/subdistricts', {districtCode: districtCode});
        }
    };
});
