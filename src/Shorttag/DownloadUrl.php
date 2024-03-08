<?php

use GFExcel\Shortcode\DownloadUrl;
use GFExcel\Shorttag\DownloadUrl as DeprecatedDownloadUrl;

/**
 * @deprecated use {@see DownloadUrl} instead.
 * @since $ver$
 */
class_alias( DownloadUrl::class, DeprecatedDownloadUrl::class );
