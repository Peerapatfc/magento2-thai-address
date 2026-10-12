/**
 * Fills province, district and sub-district from the Thai postal code, and suggests the district and
 * sub-district names that use it. Only acts when the country is Thailand and the code has 5 digits.
 */
define([
    'underscore',
    'uiRegistry',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'Peerapatfc_ThaiAddress/js/model/suggestions'
], function (_, registry, storage, urlBuilder, suggestions) {
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
                thaiCountry: null,
                autoFilled: {}
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

                if (!this.isThaiAutocompleteOn()) {
                    return;
                }

                if (this.thaiCountry !== COUNTRY || !/^\d{5}$/.test(postcode || '')) {
                    this.withSiblings(function (city, street) {
                        suggestions.clear(city.uid);
                        suggestions.clear(street.uid);
                    });

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
             * @param {Function} callback Gets the city and the second street line elements
             */
            withSiblings: function (callback) {
                var parent = this.parentName;

                registry.get([parent + '.city', parent + '.street.1'], callback);
            },

            /**
             * Writes a value the customer did not type, unless the customer has since put their own there.
             *
             * @param {Object} element
             * @param {String} value
             */
            fill: function (element, value) {
                var current = element.value(),
                    last = this.autoFilled[element.name];

                if (!current || current === last) {
                    element.value(value);
                    this.autoFilled[element.name] = value;
                }
            },

            /**
             * @param {Array<Object>} matches Rows from GET /V1/thai-address/postcode/:postcode
             */
            apply: function (matches) {
                var self = this,
                    subdistrict = registry.get(this.parentName + '.thai_subdistrict'),
                    regionIds = _.uniq(_.compact(_.pluck(matches, 'region_id'))),
                    districts = _.uniq(_.pluck(matches, 'district'));

                if (!matches.length) {
                    return;
                }

                if (regionIds.length === 1) {
                    registry.get(this.parentName + '.region_id', function (region) {
                        region.value(String(regionIds[0]));
                    });
                }

                if (subdistrict && subdistrict.isThaiCountry()) {
                    this.applyToDropdowns(matches, subdistrict);

                    return;
                }

                this.withSiblings(function (city, street) {
                    var chosen = _.find(districts, function (district) {
                            return normalize(district) === normalize(city.value());
                        }) || (districts.length === 1 ? districts[0] : null),
                        rows = chosen ?
                            _.filter(matches, function (row) {
                                return row.district === chosen;
                            }) :
                            matches,
                        subdistricts = _.uniq(_.pluck(rows, 'subdistrict'));

                    if (districts.length === 1) {
                        self.fill(city, districts[0]);
                    }

                    if (chosen && subdistricts.length === 1) {
                        self.fill(street, subdistricts[0]);
                    }

                    suggestions.set(city.uid, districts);
                    suggestions.set(street.uid, subdistricts);
                });
            },

            /**
             * With the drop-downs on, only choose what the postal code settles; the options come from the
             * drop-downs themselves.
             *
             * @param {Array<Object>} matches
             * @param {Object} subdistrict The sub-district drop-down
             */
            applyToDropdowns: function (matches, subdistrict) {
                var districts = _.uniq(_.pluck(matches, 'district'));

                registry.get(this.parentName + '.city', function (city) {
                    var chosen = _.find(districts, function (district) {
                            return normalize(district) === normalize(city.value());
                        }) || (districts.length === 1 ? districts[0] : null),
                        names;

                    if (!chosen) {
                        return;
                    }

                    city.value(chosen);
                    names = _.uniq(_.pluck(_.where(matches, {district: chosen}), 'subdistrict'));

                    if (names.length === 1) {
                        subdistrict.value(names[0]);
                    }
                });
            }
        });
    };
});
