<?php
/**
 * Plugin Name:     GravityExport Lite
 * Version:         2.3.9
 * Plugin URI:      https://gfexcel.com
 * Description:     Export all Gravity Forms entries to Excel (.xlsx) or CSV via a secret shareable URL.
 * Author:          GravityKit
 * Author URI:      https://www.gravitykit.com/extensions/gravityexport/
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     gk-gravityexport-lite
 * Domain Path:     /languages
 *
 * @package         GFExcel
 */

defined( 'ABSPATH' ) or die( 'No direct access!' );

use GFExcel\Action\ActionAwareInterface;
use GFExcel\Addon\GravityExportAddon;
use GFExcel\Container\Container;
use GFExcel\GFExcel;
use GFExcel\ServiceProvider\AddOnProvider;
use GFExcel\ServiceProvider\BaseServiceProvider;

if ( ! defined( 'GFEXCEL_PLUGIN_FILE' ) ) {
	define( 'GFEXCEL_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'GFEXCEL_PLUGIN_VERSION' ) ) {
	define( 'GFEXCEL_PLUGIN_VERSION', '2.3.9' );
}

if ( ! defined( 'GFEXCEL_MIN_PHP_VERSION' ) ) {
	define( 'GFEXCEL_MIN_PHP_VERSION', '7.2' );
}

$src_folder    = __DIR__ . '/build/vendor_prefixed/gravitykit/gravityexport-lite-src';
if ( ! is_readable( $src_folder ) ) {
	$src_folder = __DIR__ . '/src';
}
define( 'GFEXCEL_SRC_FOLDER', $src_folder );

if ( version_compare( phpversion(), GFEXCEL_MIN_PHP_VERSION, '<' ) ) {
	$show_minimum_php_version_message = function () {
		$message = wpautop( sprintf( esc_html__( 'GravityExport Lite requires PHP %s or newer.', 'gk-gravityexport-lite' ), GFEXCEL_MIN_PHP_VERSION ) );
		echo "<div class='error'>$message</div>";
	};

	add_action( 'admin_notices', $show_minimum_php_version_message );

	return;
}

add_action( 'init', static function (): void {
	load_plugin_textdomain( 'gk-gravityexport-lite', false, basename( __DIR__ ) . '/languages' );
} );

add_action( 'gform_loaded', static function (): void {
	if ( ! class_exists( 'GFForms' ) || ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	GFForms::include_addon_framework();
	GFForms::include_feed_addon_framework();

	if ( ! class_exists( 'GFExport' ) ) {
		require_once( GFCommon::get_base_path() . '/export.php' );
	}

	$autoload_file = __DIR__ . '/build/vendor/autoload.php';

	$is_build = true;
	if ( ! file_exists( $autoload_file ) ) {
		$autoload_file = __DIR__ . '/vendor/autoload.php';
		$is_build      = false;
	}

	require_once $autoload_file;

	if ( $is_build ) {
		// Make old class names available as aliases if possible.
		$class_aliases = [
			'PhpOffice\PhpSpreadsheet\Document\Properties',
			'PhpOffice\PhpSpreadsheet\IOFactory',
			'PhpOffice\PhpSpreadsheet\Worksheet\PageSetup',
			'PhpOffice\PhpSpreadsheet\Writer\Exception',
			'PhpOffice\PhpSpreadsheet\Spreadsheet',
			'PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf',
			'PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf',
		];

		foreach ( $class_aliases as $alias ) {
			if (
				class_exists( $alias )
				|| interface_exists( $alias )
			) {
				continue;
			}

			class_alias( 'GFExcel\Vendor\\' . $alias, $alias );
		}

		// Also autoload any old class as possible class aliases.
		spl_autoload_register( function ( string $class ) {
			if ( strpos( $class, 'PhpOffice\\PhpSpreadsheet\\' ) === 0 ) {
				$target = 'GFExcel\\Vendor\\' . $class;
				if ( class_exists( $target ) || interface_exists( $target ) ) {
					class_alias( $target, $class );
				}
			}
		} );
	}

	// Start DI container.
	$container = ( new Container() )
		// add internal service provider
		->addServiceProvider( new BaseServiceProvider() )
		->addServiceProvider( new AddOnProvider() );

	// Instantiate add on from container.
	$addon = $container->get( GravityExportAddon::class );

	// Set instance for Gravity Forms and register the add-on.
	GravityExportAddon::set_instance( $addon );
	GFAddOn::register( GravityExportAddon::class );

	$addon->setAssetsDir( plugin_dir_url( GFEXCEL_PLUGIN_FILE ) . 'public/' );

	// Dispatch event including the container.
	do_action( 'gfexcel_loaded', $container );

	// Start actions
	if ( $container->has( ActionAwareInterface::ACTION_TAG ) ) {
		$container->get( ActionAwareInterface::ACTION_TAG );
	}

	if ( $container->has( AddOnProvider::AUTOSTART_TAG ) ) {
		$container->get( AddOnProvider::AUTOSTART_TAG );
	}

	if ( ! is_admin() ) {
		$container->get( GFExcel::class );
	}
} );
