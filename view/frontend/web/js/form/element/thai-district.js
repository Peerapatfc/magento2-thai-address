/**
 * The City field for Thailand: the districts (อำเภอ / เขต) of the chosen province.
 */
define([
    'mage/translate',
    './thai-area',
    'Peerapatfc_ThaiAddress/js/model/areas'
], function ($t, Area, areas) {
    'use strict';

    return Area.extend({
        defaults: {
            caption: $t('Select district'),
            thaiLabel: $t('District'),
            defaultLabel: null,
            regionId: null,
            requestId: 0,
            imports: {
                regionId: '${ $.parentName }.region_id:value'
            },
            listens: {
                regionId: 'loadOptions',
                countryId: 'loadOptions'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.defaultLabel = this.label;
            this.observe('label');

            return this;
        },

        /**
         * Loads the districts of the chosen province.
         */
        loadOptions: function () {
            var self = this,
                request = ++this.requestId;

            // City is called District for Thailand.
            this.label(this.isThaiCountry() ? this.thaiLabel : this.defaultLabel);

            if (!this.isThaiCountry() || !this.regionId) {
                this.options([]);

                return;
            }

            areas.provinceCode(this.regionId).then(function (code) {
                return code ? areas.districts(code) : [];
            }).then(function (rows) {
                var options;

                if (request !== self.requestId) {
                    return; // The province changed again while this was loading.
                }

                options = self.toOptions(rows);
                self.options(options);
                self.dropUnknownValue(options);
            });
        }
    });
});
