var addClipboard;
(function ($) {
    addClipboard = function (selector, feedback) {
        var successTimeout, clipboard = new ClipboardJS(selector);

        clipboard.on('success', function (e) {
            var $triggerElement = $(e.trigger),
                $successElement = $('.success', $triggerElement.closest('div'));

            // Clear the selection and move focus back to the trigger.
            e.clearSelection();

            // Handle ClipboardJS focus bug, see https://github.com/zenorocha/clipboard.js/issues/680
            $triggerElement.trigger('focus');

            // Show success visual feedback.
            clearTimeout(successTimeout);

            $successElement.removeClass('hidden');

            // Hide success visual feedback after 3 seconds since last success.
            successTimeout = setTimeout(function () {
                $successElement.addClass('hidden');
                // Remove the visually hidden textarea so that it isn't perceived by assistive technologies.
                if (clipboard.clipboardAction && clipboard.clipboardAction.fakeElem && clipboard.clipboardAction.removeFake) {
                    clipboard.clipboardAction.removeFake();
                }
            }, 3000);

            // Handle success audible feedback.
            wp.a11y.speak(feedback);
        });
    };
})(jQuery);
