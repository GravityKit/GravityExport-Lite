<?php

require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('GFEXCEL_PLUGIN_FILE')) {
    define('GFEXCEL_PLUGIN_FILE', dirname(__FILE__, 2) . '/gfexcel.php');
}
if (!defined('GFEXCEL_PLUGIN_VERSION')) {
	define('GFEXCEL_PLUGIN_VERSION', '1.10.1');
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
WP_Mock::bootstrap();
