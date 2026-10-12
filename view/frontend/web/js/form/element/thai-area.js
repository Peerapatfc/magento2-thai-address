/**
 * A checkout field that is a drop-down for Thailand and a plain text input for every other country.
 * Base of the district (City) and sub-district fields. The value is the Thai name, so nothing changes in
 * how the address is stored or validated.
 */
define([
    'ko',
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function (ko, _, Abstract) {
    'use strict';

    var COUNTRY = 'TH';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Peerapatfc_ThaiAddress/form/element/area',
            caption: '',
            countryId: null,
            options: [],
            imports: {
                countryId: '${ $.parentName }.country_id:value'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.observe('options countryId');

            this.isThai = ko.pureComputed(this.isThaiCountry, this);

            return this;
        },

        /**
         * Read the country directly in change handlers: a computed may not have caught up yet there.
         *
         * @return {Boolean}
         */
        isThaiCountry: function () {
            return this.countryId() === COUNTRY;
        },

        /**
         * Turns API rows into drop-down options. The label shows both names.
         *
         * @param {Array} rows
         * @return {Array}
         */
        toOptions: function (rows) {
            return _.map(rows, function (row) {
                return {
                    value: row['name_th'],
                    label: row['name_th'] + ' (' + row['name_en'] + ')',
                    code: row.code,
                    postcode: row.postcode
                };
            });
        },

        /**
         * Empties the field when its value is not one of the options. Does nothing while the options are
         * still loading, so a value filled from the postal code survives.
         *
         * @param {Array} options
         */
        dropUnknownValue: function (options) {
            var value = this.value();

            if (value && options.length && !_.findWhere(options, {value: value})) {
                this.value('');
            }
        }
    });
});
