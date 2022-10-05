var gfexcel_sortable;

(function ($) {
    var updateLists = function ($elements) {
        $elements.each(function (i, el) {
            var $input = $(el).prev();
            $input.val($(el).sortable('toArray', {attribute: 'data-value'}).join(','));
        })
    };

    gfexcel_sortable = function (elements, connector_class) {
        var $elements = $(elements);

        $elements.sortable({
            connectWith: '.' + connector_class,
            update: function () {
                updateLists($elements);
            }
        }).disableSelection();

        $elements.on('click', '.move', function () {
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