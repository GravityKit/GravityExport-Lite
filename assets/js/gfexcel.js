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
            element.appendTo($('#' + element.closest('ul').data('send-to')));
            $elements.sortable('refresh');
            updateLists($elements);
        });
    }
})(jQuery);