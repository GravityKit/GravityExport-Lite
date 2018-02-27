<?php
/**
 * Plugin Name:     Gravity Forms Entries in Excel
 * Description:     Download all entries for a form using a unique and hashed URL
 * Author:          Doeke Norg
 * Author URI:      https://paypal.me/doekenorg
 * Text Domain:     gf-entries-in-excel
 * Version:         1.2.1
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

    require "vendor/autoload.php";

    load_plugin_textdomain('gf-entries-in-excel', false, basename(dirname(__FILE__)) . '/languages');

    if (is_admin()) {
        return new GFExcelAdmin();
    }

    return new GFExcel();
});
