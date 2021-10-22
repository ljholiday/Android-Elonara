'use strict';
jQuery(document).ready(function ($) {
    $('select.vi-ui.dropdown').dropdown();
    /*Search categories*/
    $(".search-category").select2({
        closeOnSelect: false,
        placeholder: "Please fill in your category title",
        ajax: {
            url: "admin-ajax.php?action=wad_search_cate",
            dataType: 'json',
            type: "GET",
            quietMillis: 50,
            delay: 250,
            data: function (params) {
                return {
                    keyword: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup;
        }, // let our custom formatter work
        minimumInputLength: 1
    });
    /*Add row*/
    $('.vi-wad-price-rule-add').on('click', function () {
        let $rows = $('.vi-wad-price-rule-row'),
            $lastRow = $rows.last(),
            $newRow = $lastRow.clone();
        $newRow.find('.vi-wad-price-from').val('');
        $newRow.find('.vi-wad-price-to').val('');
        $newRow.find('.vi-wad-plus-value-type').dropdown();
        $('.vi-wad-price-rule-container').append($newRow);
    });

    /*remove last row*/
    $(document).on('click','.vi-wad-price-rule-remove', function () {
        let $button = $(this), $rows = $('.vi-wad-price-rule-row'),
            $row = $button.closest('.vi-wad-price-rule-row');
        if ($rows.length > 1) {
            if (confirm('Do you want to remove this row?')) {
                $row.fadeOut(300);
                setTimeout(function () {
                    $row.remove();
                }, 300)
            }
        }
    });

    $('.vi-ui.button.primary').on('click', function () {
        if ($('.vi-wad-secret-key').val() == '') {
            alert('Secret key cannot be empty.');
            return false;
        } else if (!$('#vi-wad-import-currency-rate').val()) {
            alert('Please enter Import products currency exchange rate');
            return false;
        }
    });
    $(document).on('change', 'select[name="wad_plus_value_type[]"]', function () {
        change_price_label($(this));
    });
    $(document).on('change', 'select[name="wad_price_default[plus_value_type]"]', function () {
        change_price_label($(this));
    });

    function change_price_label($select) {
        let $current = $select.closest('tr');
        switch ($select.val()) {
            case 'fixed':
                $current.find('.vi-wad-value-label-left').html('+');
                $current.find('.vi-wad-value-label-right').html('$');
                break;
            case 'percent':
                $current.find('.vi-wad-value-label-left').html('+');
                $current.find('.vi-wad-value-label-right').html('%');
                break;
            case 'multiply':
                $current.find('.vi-wad-value-label-left').html('x');
                $current.find('.vi-wad-value-label-right').html('');
                break;
            default:
                $current.find('.vi-wad-value-label-left').html('=');
                $current.find('.vi-wad-value-label-right').html('$');
        }
    }

});