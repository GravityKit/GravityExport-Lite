<?php
/**
 * Plugin Name:     Gravity Forms Entries in Excel
 * Description:     Download all entries for a form using a unique and hashed URL
 * Author:          SQUID Media
 * Author URI:      https://www.squidmedia.nl
 * Text Domain:     gfexcel
 * Version:         1.0.0
 *
 * @package         GFExcel
 */


defined('ABSPATH') or die('No direct access!');

use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;

if (!class_exists("GFForms")) {
    return;
}

require "vendor/autoload.php";

if (is_admin()) {
    return new GFExcelAdmin();
}


return new GFExcel();
