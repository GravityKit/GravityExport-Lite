<?php

require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('GFEXCEL_PLUGIN_FILE')) {
    define('GFEXCEL_PLUGIN_FILE', dirname(__FILE__, 2) . '/gfexcel.php');
}
WP_Mock::bootstrap();
