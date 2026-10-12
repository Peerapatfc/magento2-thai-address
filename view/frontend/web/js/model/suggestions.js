/**
 * Suggestion lists for the checkout inputs, built on the browser's own <datalist>, so it works with any
 * theme that renders plain inputs (Luma) and needs no extra widget.
 */
define(['jquery'], function ($) {
    'use strict';

    var listIdByInput = {};

    // The input may not be in the DOM yet (or is rendered again when the country changes),
    // so the list is also attached when the input gets focus.
    $(document).on('focusin.thaiAddress', 'input[id]', function () {
        var listId = listIdByInput[this.id];

        if (listId && this.getAttribute('list') !== listId) {
            this.setAttribute('list', listId);
        }
    });

    return {
        /**
         * @param {String} inputId Id of the <input>
         * @param {Array<String>} values
         */
        set: function (inputId, values) {
            var listId = 'thai-address-list-' + inputId,
                $list = $('#' + listId),
                input;

            if (!values.length) {
                this.clear(inputId);

                return;
            }

            if (!$list.length) {
                $list = $('<datalist>').attr('id', listId).appendTo(document.body);
            }

            $list.empty();
            values.forEach(function (value) {
                $('<option>').attr('value', value).appendTo($list);
            });

            listIdByInput[inputId] = listId;
            input = document.getElementById(inputId);

            if (input) {
                input.setAttribute('list', listId);
            }
        },

        /**
         * @param {String} inputId
         */
        clear: function (inputId) {
            var input = document.getElementById(inputId);

            delete listIdByInput[inputId];
            $('#thai-address-list-' + inputId).remove();

            if (input) {
                input.removeAttribute('list');
            }
        }
    };
});
