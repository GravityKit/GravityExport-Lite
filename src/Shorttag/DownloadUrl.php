<?php

use GFExcel\Shortcode\DownloadUrl;
use GFExcel\Shorttag\DownloadUrl as DeprecatedDownloadUrl;

/**
 * @deprecated use {@see DownloadUrl} instead.
 * @since 2.2.0
 */
class_alias( DownloadUrl::class, DeprecatedDownloadUrl::class );
