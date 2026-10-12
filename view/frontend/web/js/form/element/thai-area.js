/**
 * A checkout field that is a drop-down for Thailand and a plain text input for every other country.
 * Base of the district (City) and sub-district fields. The value is the Thai name, so nothing changes in
 * how the address is stored or validated.
 */
define([
    'ko',
    'underscore',
    'Magento_Ui/js/form/element/abstract',
    'Peerapatfc_ThaiAddress/js/model/language'
], function (ko, _, Abstract, language) {
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
         * Turns API rows into drop-down options, named in the language of the store view and sorted in it
         * (the API sorts in Thai).
         *
         * @param {Array} rows
         * @return {Array}
         */
        toOptions: function (rows) {
            var options = _.map(rows, function (row) {
                var name = language.pick(row['name_th'], row['name_en']);

                return {value: name, label: name, code: row.code, postcode: row.postcode};
            });

            return language.isThai ? options : options.sort(function (a, b) {
                return a.label.localeCompare(b.label, 'en');
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
