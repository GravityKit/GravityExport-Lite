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
 * Version:         1.8.6
 *
 * @package         GFExcel
 */

defined('ABSPATH') or die('No direct access!');

use GFExcel\GFExcel;
use GFExcel\GFExcelAdmin;
use GFExcel\GFExcelConfigConstants;
use GFExcel\ServiceProvider\AddOnProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;

if (!defined('GFEXCEL_PLUGIN_FILE')) {
    define('GFEXCEL_PLUGIN_FILE', __FILE__);
}

add_action('gform_loaded', static function (): void {
    if (!class_exists('GFForms') || !method_exists('GFForms', 'include_addon_framework')) {
        return;
    }

    load_plugin_textdomain('gf-entries-in-excel', false, basename(__DIR__) . '/languages');
    GFForms::include_addon_framework();

    if (!class_exists('GFExport')) {
        require_once(GFCommon::get_base_path() . '/export.php');
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once($autoload);
    }

    // Start DI container.
    $container = (new Container())
        ->defaultToShared()
        // add internal service provider
        ->addServiceProvider(new AddOnProvider())
        // auto wire it up
        ->delegate(new ReflectionContainer());

    // Instantiate add on from container.
    $addon = $container->get(GFExcelAdmin::class);

    // Dispatch event including the container.
    do_action('gfexcel_loaded', $container);

    // Set instance for Gravity Forms and register the add-on.
    GFExcelAdmin::set_instance($addon);
    GFAddOn::register(GFExcelAdmin::class);

    // Start actions
    $container->get(GFExcelConfigConstants::GFEXCEL_ACTION_TAG);

    if (!is_admin()) {
        $container->get(GFExcel::class);
    }
});
