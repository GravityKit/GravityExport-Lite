<?php
/**
 * Plugin Name:     Gravity Forms Entries in Excel
 * Plugin URI:      https://gfexcel.com
 * Description:     Export all Gravity Forms entries to Excel (.xlsx) via a secret (shareable) url.
 * Author:          Doeke Norg
 * Author URI:      https://paypal.me/doekenorg
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     gf-entries-in-excel
 * Domain Path:     /languages
 * Version:         1.8.0
 *
 * @package         GFExcel
 */


defined('ABSPATH') or die('No direct access!');

use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;

if (!defined('GFEXCEL_PLUGIN_FILE')) {
    define('GFEXCEL_PLUGIN_FILE', __FILE__);
}

add_action('gform_loaded', static function () {
    if (!class_exists('GFForms')) {
        return '';
    }
    if (!class_exists('GFExport')) {
        require_once(GFCommon::get_base_path() . '/export.php');
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once($autoload);
    }

    load_plugin_textdomain('gf-entries-in-excel', false, basename(__DIR__) . '/languages');

    if (!method_exists('GFForms', 'include_addon_framework')) {
        return false;
    }

    GFAddOn::register(GFExcelAdmin::class);

    do_action('gfexcel_loaded');

    if (!is_admin()) {
        new GFExcel();
    }
});
