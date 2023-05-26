/**
 * Javascript that handles the selecting of all meta fields.
 * @since 2.0.0
 */
(function ($) {
    $(function () {
        var $container = $('#gform_setting_enabled_metafields .gform-settings-input__container');
        var $checkboxes = $container.find('input[type=checkbox]');
        var $metaAll = $('#gk-gravityexport-meta-all');

        var updateMetaAll = function () {
            var checked = $checkboxes.filter(function () {
                return this.checked;
            });

            $metaAll.prop('checked', checked.length === $checkboxes.length);
            setTimeout(function() {
                $metaAll.trigger('change', {'delegated': true});
            }, 50); // Give the DOM some time to respond.
        };

        // Set initial state.
        updateMetaAll();

        $checkboxes.on('change', function (event) {
            event.target.previousSibling.value = this.checked ? '1' : '0';
            event.target.previousSibling.dispatchEvent(new Event('change'));

            updateMetaAll();
        });

        $metaAll.on('change', function (e, data) {
            // Only trigger checkboxes on actual click.
            if (typeof data === 'undefined' || !data.delegated) {
                $checkboxes.prop('checked', this.checked).trigger('change');
            }

            var label = $(this).closest('div').find('label');
            label.text(this.checked ? label.data('deselect') : label.data('select'));
        });
    })
})(jQuery);
