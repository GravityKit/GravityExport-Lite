/**
 * Javascript that handles the selecting of all meta fields.
 * @since $ver$
 */
(function ($) {
    $(function () {
        var $container = $('#gform_setting_enabled_metafields .gform-settings-input__container');
        var $checkboxes = $container.find('input[type=checkbox]');

        $checkboxes.on('change', function (event) {
            event.target.previousSibling.value = this.checked ? '1' : '0';
            event.target.previousSibling.dispatchEvent(new Event('change'));
        });

        $('#gk-gravityexport-meta-all').on('change', function () {
            $checkboxes.prop('checked', this.checked).trigger('change');

            var label = $(this).closest('div').find('label');
            label.text(this.checked ? label.data('deselect') : label.data('select'));
        });
    })
})(jQuery);
