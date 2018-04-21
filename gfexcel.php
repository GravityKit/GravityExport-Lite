<?php
/**
 * Plugin Name:     Gravity Forms Entries in Excel
 * Description:     Export all Gravity Forms entries to Excel (.xls) via a download button OR via a secret (shareable) url.
 * Author:          Doeke Norg
 * Author URI:      https://paypal.me/doekenorg
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     gf-entries-in-excel
 * Version:         1.2.4
 *
 * @package         GFExcel
 */


defined('ABSPATH') or die('No direct access!');

use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;

add_action("plugins_loaded", function () {
    if (!class_exists("GFForms")) {
        return '';
    }
    if (!class_exists("GFExport")) {
        require_once(GFCommon::get_base_path() . '/export.php');
    }
    require "vendor/autoload.php";

    load_plugin_textdomain('gf-entries-in-excel', false, basename(dirname(__FILE__)) . '/languages');

    if (is_admin()) {
        return new GFExcelAdmin();
    }

    return new GFExcel();
});
