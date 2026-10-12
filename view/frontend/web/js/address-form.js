/**
 * Drop-downs for the My Account address form. For Thailand, City becomes a district drop-down and a sub-district
 * drop-down replaces the second street line (it writes the name there), the postal code is filled from the
 * sub-district and the postal code fills the rest. Other countries keep the plain fields.
 */
define([
    'jquery',
    'underscore',
    'mage/translate'
], function ($, _, $t) {
    'use strict';

    var COUNTRY = 'TH';

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
            .replace(/[่-๋\s.\-ฯ'’(),]/g, '');
    }

    return function (config) {
        var isThaiStore = config.language === 'th',
            cache = {},
            $country = $('#country'),
            $region = $('#region_id'),
            $city = $('#city'),
            $zip = $('#zip'),
            $street2 = $('#street_2'),
            $cityField = $city.closest('.field'),
            $street2Field = $street2.closest('.field'),
            $citySelect = $('<select>').attr({id: 'city-th', 'class': 'select required-entry'}).hide(),
            $subSelect = $('<select>').attr({id: 'subdistrict-th', 'class': 'select required-entry'}),
            $subField = $('<div class="field subdistrict required"><label class="label" for="subdistrict-th">' +
                '<span></span></label><div class="control"></div></div>'),
            districts = [],
            request = 0;

        /**
         * @param {String} path
         * @return {Deferred}
         */
        function get(path) {
            if (!cache[path]) {
                cache[path] = $.getJSON(config.restUrl + path).fail(function () {
                    delete cache[path];
                });
            }

            return cache[path];
        }

        /**
         * @param {Object} row An item of a list: name_th and name_en
         * @return {String} The name in the language of the store view
         */
        function nameOf(row) {
            return isThaiStore ? row['name_th'] : row['name_en'];
        }

        /**
         * @param {Object} row A postal code match: district and district_en, subdistrict and subdistrict_en
         * @param {String} key 'district' or 'subdistrict'
         * @return {String} The name in the language of the store view
         */
        function matchName(row, key) {
            return isThaiStore ? row[key] : row[key + '_en'];
        }

        /**
         * Fills a drop-down with API rows and selects the one that matches a value, in any language.
         *
         * @param {jQuery} $select
         * @param {Array} rows Rows with name_th and name_en
         * @param {String} caption
         * @param {String} current
         * @return {Array} The options: {value, label, code, postcode}
         */
        function fill($select, rows, caption, current) {
            var options = _.map(rows, function (row) {
                    return {
                        value: nameOf(row),
                        label: nameOf(row),
                        code: row.code,
                        postcode: row.postcode,
                        names: [row['name_th'], row['name_en']]
                    };
                }),
                match;

            if (!isThaiStore) {
                options.sort(function (a, b) {
                    return a.label.localeCompare(b.label, 'en');
                });
            }

            $select.empty().append($('<option>').val('').text(caption));
            _.each(options, function (option) {
                $select.append($('<option>').val(option.value).text(option.label));
            });

            match = _.find(options, function (option) {
                return current && _.some(option.names, function (name) {
                    return normalize(name) === normalize(current);
                });
            });
            $select.val(match ? match.value : '');

            return options;
        }

        /**
         * @return {Boolean}
         */
        function isThai() {
            return $country.val() === COUNTRY;
        }

        /**
         * Loads the districts of the chosen province into the City drop-down.
         *
         * @param {String} current
         * @return {Promise}
         */
        function loadDistricts(current) {
            var id = ++request;

            return get('provinces').then(function (provinces) {
                var province = _.find(provinces, function (row) {
                    return String(row['region_id']) === String($region.val());
                });

                return province ? get('provinces/' + province.code + '/districts') : [];
            }).then(function (rows) {
                if (id === request) {
                    districts = fill($citySelect, rows, $t('Select district'), current);
                }
            });
        }

        /**
         * Loads the sub-districts of the chosen district.
         *
         * @param {String} current
         * @return {Promise}
         */
        function loadSubdistricts(current) {
            var district = _.findWhere(districts, {value: $citySelect.val()});

            if (!district) {
                fill($subSelect, [], $t('Select sub-district'), '');

                return $.Deferred().resolve().promise();
            }

            return get('districts/' + district.code + '/subdistricts').then(function (rows) {
                fill($subSelect, rows, $t('Select sub-district'), current);
                $subSelect.data('rows', rows);
            });
        }

        /**
         * Shows the drop-downs for Thailand and the plain fields for other countries.
         */
        function update() {
            var thai = isThai();

            $citySelect.toggle(thai);
            $city.toggle(!thai);
            $cityField.find('label span').first().text(thai ? $t('District') : $t('City'));
            $subField.toggle(thai);
            $street2Field.toggle(!thai);

            if (thai) {
                loadDistricts($city.val()).then(function () {
                    return loadSubdistricts($street2.val());
                });
            }
        }

        /**
         * Chooses the province, district and sub-district the postal code settles.
         */
        function fromPostcode() {
            var postcode = $zip.val();

            if (!isThai() || !/^\d{5}$/.test(postcode)) {
                return;
            }

            get('postcode/' + postcode).then(function (matches) {
                var regionIds = _.uniq(_.compact(_.pluck(matches, 'region_id'))),
                    names = _.uniq(_.map(matches, function (row) {
                        return matchName(row, 'district');
                    })),
                    subs;

                if (regionIds.length !== 1) {
                    return null;
                }

                $region.val(String(regionIds[0]));

                return loadDistricts(names.length === 1 ? names[0] : $city.val()).then(function () {
                    if (names.length === 1 && $citySelect.val()) {
                        $city.val($citySelect.val());
                    }

                    return loadSubdistricts('');
                }).then(function () {
                    subs = _.uniq(_.map(_.filter(matches, function (row) {
                        return matchName(row, 'district') === $citySelect.val();
                    }), function (row) {
                        return matchName(row, 'subdistrict');
                    }));

                    if (subs.length === 1) {
                        $subSelect.val(subs[0]).trigger('change');
                    }
                });
            });
        }

        $subField.find('span').text($t('Sub-district'));
        $subField.find('.control').append($subSelect);
        $cityField.find('.control').prepend($citySelect);
        $cityField.after($subField);

        $country.on('change', function () {
            // The region list is rebuilt by Magento's own script on this event.
            _.defer(update);
        });
        $region.on('change', function () {
            if (isThai()) {
                $city.val('');
                $street2.val('');
                loadDistricts('').then(function () {
                    return loadSubdistricts('');
                });
            }
        });
        $citySelect.on('change', function () {
            $city.val($citySelect.val());
            $street2.val('');
            loadSubdistricts('');
        });
        $subSelect.on('change', function () {
            var row = _.find($subSelect.data('rows') || [], function (item) {
                return nameOf(item) === $subSelect.val();
            });

            $street2.val($subSelect.val());

            if (row && row.postcode) {
                $zip.val(row.postcode);
            }
        });
        $zip.on('change', fromPostcode);

        _.defer(update);
    };
});
