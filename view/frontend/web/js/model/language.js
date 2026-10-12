/**
 * The language of the drop-downs: Thai names in a Thai store view, English names otherwise.
 */
define([], function () {
    'use strict';

    var config = window.checkoutConfig && window.checkoutConfig.thaiAddress,
        isThai = !!config && config.language === 'th';

    return {
        isThai: isThai,

        /**
         * @param {String} th
         * @param {String} en
         * @return {String} The name in the language of the store view
         */
        pick: function (th, en) {
            return isThai ? th : en;
        }
    };
});
