var gfexcel_sortable;

(function ($) {
    var updateLists = function ($elements) {
        $elements.each(function (i, el) {
            var $input = $(el).prevAll('input[type=hidden]');
            $input.val($(el).sortable('toArray', {attribute: 'data-value'}).join(','));
        })
    };

    gfexcel_sortable = function (elements, connector_class) {
        var $elements = $(elements);
        var labels_i10n = typeof gravityexport_lite_strings !== 'undefined'
            ? gravityexport_lite_strings
            : {'enable': 'Enable all', 'disable': 'Disable all'}; // Fallback to English

        $elements.each(function () {
            var $list = $(this);
            var send_to = '#' + $list.data('send-to');
            var label = send_to.indexOf('enabled') > 0 ? labels_i10n.enable : labels_i10n.disable;
            var $move_all_button = $('<button type="button">' + label + '</button>');

            $move_all_button
                // Add css via JS to hit add-ons.
                .css({
                    background: 'none',
                    border: 0,
                    float: 'right',
                    marginTop: '-30px',
                    color: '#3e7da6',
                    cursor: 'pointer'
                })
                // Move all items to the `send-to` list when clicked.
                .on('click', function () {
                    $list.find('li').appendTo($(send_to));
                    $elements.sortable('refresh');
                    updateLists($elements);
                });

            // Add the button before the list.
            $(this).before($move_all_button);
        });

        $elements.sortable({
            connectWith: '.' + connector_class,
            update: function () {
                updateLists($elements);
            }
        }).disableSelection();

        $elements
            .on('click', '.move', function () {
                var element = $(this).closest('li');
                var send_to = '#' + element.closest('ul').data('send-to');
                element.appendTo($(send_to));
                setTimeout(function () {
                    element.addClass('light-up');
                    setTimeout(function () {
                        element.removeClass('light-up');
                    }, 200);
                }, 10);
                $elements.sortable('refresh');
                updateLists($elements);
            });
    };

    $(document).ready(function () {
        $("#start_date, #end_date").datepicker({dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true});
    });
})(jQuery);
