<?php

namespace GFExcel;

class GFExcelConfigConstants
{
    const GFEXCEL_EVENT_DOWNLOAD = 'gfexcel_event_download';

    const GFEXCEL_DOWNLOAD_SECURED = 'gfexcel_download_secured';

    const GFEXCEL_DOWNLOAD_RENDERER = 'gfexcel_download_renderer';

    /**
     * The hook that rewrites the combiner.
     * @since 1.8.0
     */
    public const GFEXCEL_DOWNLOAD_COMBINER = 'gfexcel_download_combiner';

    /**
     * The hook that rewrites the notification manager.
     * @since 1.8.0
     */
    public const GFEXCEL_NOTIFICATION_MANAGER = 'gfexcel_notification_manager';

    /**
     * The hook that rewrites the notification repository.
     * @since 1.8.0
     */
    public const GFEXCEL_NOTIFICATION_REPOSITORY = 'gfexcel_notification_repository';

    /**
     * The key that is applied on the tagged actions.
     * @since $ver$
     */
    public const GFEXCEL_ACTION_TAG = 'gfexcel_action';
}
