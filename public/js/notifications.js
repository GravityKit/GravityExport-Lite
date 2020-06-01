/**
 * Javascript that handles the dismissal of notifications.
 * @since $ver$
 */
;jQuery(document).ready(function ($) {
    $(document).on('click', '.notice-dismiss', function () {
        var $notice = $(this).closest('div.notice');

        if (!$notice.data('gfexcel-notification') || !$notice.data('gfexcel-nonce')) {
            return;
        }

        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gfexcel_dismiss_notification',
                notification_key: $notice.data('gfexcel-notification'),
                nonce: $notice.data('gfexcel-nonce'),
            }
        });
    });
});
