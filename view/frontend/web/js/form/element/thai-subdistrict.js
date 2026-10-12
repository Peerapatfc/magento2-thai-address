/**
 * The sub-district (ตำบล / แขวง) drop-down for Thailand. It is shown instead of the second street line and
 * writes the chosen name into it, so the address is stored and validated as before. Choosing one also fills
 * the postal code.
 */
define([
    'underscore',
    'uiRegistry',
    'mage/translate',
    './thai-area',
    'Peerapatfc_ThaiAddress/js/model/areas'
], function (_, registry, $t, Area, areas) {
    'use strict';

    return Area.extend({
        defaults: {
            caption: $t('Select sub-district'),
            districtOptions: [],
            districtValue: null,
            requestId: 0,
            imports: {
                districtOptions: '${ $.parentName }.city:options',
                districtValue: '${ $.parentName }.city:value'
            },
            listens: {
                districtOptions: 'loadOptions',
                districtValue: 'loadOptions',
                countryId: 'onCountryChange',
                value: 'onValueChange'
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.onCountryChange();

            return this;
        },

        /**
         * Shown and required for Thailand only; the second street line is hidden then.
         */
        onCountryChange: function () {
            var thai = this.isThaiCountry();

            this.visible(thai);
            this.required(thai);
            this.validation['required-entry'] = thai;

            registry.get(this.parentName + '.street.1', function (street) {
                street.visible(!thai);
            });
        },

        /**
         * Loads the sub-districts of the chosen district.
         */
        loadOptions: function () {
            var self = this,
                request = ++this.requestId,
                district = _.findWhere(this.districtOptions, {value: this.districtValue});

            if (!this.isThaiCountry() || !district) {
                this.options([]);

                return;
            }

            areas.subdistricts(district.code).then(function (rows) {
                var options;

                if (request !== self.requestId) {
                    return;
                }

                options = self.toOptions(rows);
                self.options(options);
                self.dropUnknownValue(options);
            });
        },

        /**
         * Copies the choice into the second street line and fills the postal code.
         *
         * @param {String} value
         */
        onValueChange: function (value) {
            var option = _.findWhere(this.options(), {value: value}),
                parent = this.parentName;

            if (!this.isThaiCountry()) {
                return;
            }

            registry.get(parent + '.street.1', function (street) {
                street.value(value || '');
            });

            if (option && option.postcode) {
                registry.get(parent + '.postcode', function (postcode) {
                    postcode.value(option.postcode);
                });
            }
        }
    });
});
