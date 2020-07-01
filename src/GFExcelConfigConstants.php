<?php

namespace GFExcel;

class GFExcelConfigConstants
{
    const GFEXCEL_RENDERER_TRANSPOSE = 'gfexcel_renderer_transpose';

    const GFEXCEL_EVENT_DOWNLOAD = 'gfexcel_event_download';

    const GFEXCEL_DOWNLOAD_SECURED = 'gfexcel_download_secured';

    const GFEXCEL_DOWNLOAD_RENDERER = 'gfexcel_download_renderer';

    /**
     * The hook that rewrites the combiner.
     * @since $ver$
     */
    public const GFEXCEL_DOWNLOAD_COMBINER = 'gfexcel_download_combiner';

    /**
     * The hook that rewrites the notification manager.
     * @since $ver$
     */
    public const GFEXCEL_NOTIFICATION_MANAGER = 'gfexcel_notification_manager';

    /**
     * The hook that rewrites the notification repository.
     * @since $ver$
     */
    public const GFEXCEL_NOTIFICATION_REPOSITORY = 'gfexcel_notification_repository';
}
