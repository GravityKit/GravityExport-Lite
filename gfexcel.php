<?php
/**
 * Plugin Name:     Gravity Forms Entries in Excel
 * Description:     Export all Gravity Forms entries to Excel (.xls) via a download button OR via a secret (shareable) url.
 * Author:          Doeke Norg
 * Author URI:      https://paypal.me/doekenorg
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     gf-entries-in-excel
 * Domain Path:     /languages
 * Version:         1.5.1
 *
 * @package         GFExcel
 */


defined('ABSPATH') or die('No direct access!');

use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;

add_action("gform_loaded", function () {
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

    load_plugin_textdomain('gf-entries-in-excel', false, basename(dirname(__FILE__)) . '/languages');

    if (!method_exists('GFForms', 'include_addon_framework')) {
        return false;
    }

    GFAddOn::register(GFExcelAdmin::class);

    if (!is_admin()) {
        new GFExcel();
    }
});