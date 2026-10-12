/**
 * Fills province, district and sub-district from the Thai postal code. Only acts when the country is
 * Thailand and the code has 5 digits. The district and sub-district fields are the drop-downs of this module.
 */
define([
    'underscore',
    'uiRegistry',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'Peerapatfc_ThaiAddress/js/model/language'
], function (_, registry, storage, urlBuilder, language) {
    'use strict';

    var COUNTRY = 'TH',
        cache = {};

    /**
     * Tone marks, spaces and the เขต/อำเภอ/แขวง/ตำบล prefixes do not change which area is meant.
     *
     * @param {String} name
     * @return {String}
     */
    function normalize(name) {
        return String(name || '')
            .toLowerCase()
            .replace(/^\s*(กิ่งอำเภอ|อำเภอ|เขต|แขวง|ตำบล|อ\.|ต\.)\s*/, '')
            .replace(/[่-๋\s.\-ฯ]/g, '');
    }

    /**
     * @param {String} postcode
     * @return {Deferred}
     */
    function fetchMatches(postcode) {
        if (cache[postcode]) {
            return cache[postcode];
        }

        cache[postcode] = storage.get(
            // The parameter must not be called "postcode": the builder would also replace that path segment.
            urlBuilder.createUrl('/thai-address/postcode/:code', {code: postcode}),
            false
        ).fail(function () {
            delete cache[postcode];
        });

        return cache[postcode];
    }

    return function (PostCode) {
        return PostCode.extend({
            defaults: {
                thaiCountry: null
            },

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super();
                this.value.subscribe(this.suggestThaiAddress.bind(this));

                return this;
            },

            /**
             * @inheritdoc
             */
            update: function (country) {
                this._super(country);
                this.thaiCountry = country;
                this.suggestThaiAddress(this.value());
            },

            /**
             * @return {Boolean}
             */
            isThaiAutocompleteOn: function () {
                var config = window.checkoutConfig && window.checkoutConfig.thaiAddress;

                return !config || config.autocomplete !== false;
            },

            /**
             * @param {String} postcode
             */
            suggestThaiAddress: function (postcode) {
                var self = this;

                if (!this.isThaiAutocompleteOn() || this.thaiCountry !== COUNTRY || !/^\d{5}$/.test(postcode || '')) {
                    return;
                }

                fetchMatches(postcode).done(function (matches) {
                    // The customer may have changed the code while the request was running.
                    if (self.value() === postcode && self.thaiCountry === COUNTRY) {
                        self.apply(matches);
                    }
                });
            },

            /**
             * Chooses the province, and the district and sub-district when the postal code settles them.
             *
             * @param {Array<Object>} matches Rows from GET /V1/thai-address/postcode/:postcode
             */
            apply: function (matches) {
                var parent = this.parentName,
                    regionIds = _.uniq(_.compact(_.pluck(matches, 'region_id'))),
                    district = function (row) {
                        return language.pick(row.district, row['district_en']);
                    },
                    districts = _.uniq(_.map(matches, district));

                if (!matches.length) {
                    return;
                }

                if (regionIds.length === 1) {
                    registry.get(parent + '.region_id', function (region) {
                        region.value(String(regionIds[0]));
                    });
                }

                registry.get(parent + '.city', function (city) {
                    var chosen = _.find(districts, function (name) {
                            return normalize(name) === normalize(city.value());
                        }) || (districts.length === 1 ? districts[0] : null),
                        subdistrict = registry.get(parent + '.thai_subdistrict'),
                        names;

                    if (!chosen) {
                        return;
                    }

                    city.value(chosen);

                    // Without a second street line there is no sub-district field.
                    if (!subdistrict) {
                        return;
                    }

                    names = _.uniq(_.map(_.filter(matches, function (row) {
                        return district(row) === chosen;
                    }), function (row) {
                        return language.pick(row.subdistrict, row['subdistrict_en']);
                    }));

                    if (names.length === 1) {
                        subdistrict.value(names[0]);
                    }
                });
            }
        });
    };
});
