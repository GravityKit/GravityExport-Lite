<?php

namespace GFExcel;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Renderer\RendererInterface;
use GFExcel\Routing\Request;
use GFExcel\Routing\Router;
use GFExcel\Routing\WordPressRouter;
use GFExcel\Transformer\Combiner;
use GFExcel\Transformer\CombinerInterface;

/**
 * The core of the plugin.
 * @since 1.0.0
 */
class GFExcel {
	/**
	 * Full name of the plugin
	 * @since 1.0.0
	 * @var string
	 */
	public static $name = 'GravityExport Lite';

	/**
	 * Short name of the plugin
	 * @since 1.0.0
	 * @var string
	 */
	public static $shortname = 'GravityExport Lite';

	/**
	 * Current version of the plugin
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = GFEXCEL_PLUGIN_VERSION;

	/**
	 * The endpoint slug of the plugin.
	 * @since 1.0.0
	 * @depreacted $ver$ Use Router instead.
	 * @var string
	 */
	public static $slug = 'gravityexport-lite';

	/**
	 * The endpoint slug of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @deprecated $ver$ Use Router instead.
	 *
	 * @var string[]
	 */
	public static $endpoints = [
		'gf-entries-in-excel',
		'gravityexport-lite',
		'gravityexport',
	];

	/**
	 * @depreacted $ver$ Use Router instead.
	 */
	public const KEY_HASH = Router::KEY_HASH;

	/**
	 * @depreacted $ver$ Use Router instead.
	 */
	public const KEY_ACTION = Router::KEY_ACTION;

	public const KEY_ENABLED_NOTES = 'gfexcel_enabled_notes';

	public const KEY_CUSTOM_FILENAME = 'gfexcel_custom_filename';

	public const KEY_FILE_EXTENSION = 'gfexcel_file_extension';

	private static $file_extension;

	/**
	 * The router instance.
	 * @since 2.4.0
	 *
	 * @var Router
	 */
	private $router;

	/**
	 * Holds the singleton.
	 *
	 * @since 2.4.0
	 *
	 * @var self
	 */
	private static $_instance = null;

	/**
	 * Instantiates the plugin.
	 * @since 1.0.0
	 */
	public function __construct( Router $router ) {
		if ( self::$_instance !== null ) {
			return;
		}

		$this->router = $router;

		add_action( 'init', [ $this->router, 'init' ] );
		add_filter( 'request', [ $this, 'request' ] );
		add_filter( 'parse_request', [ $this, 'downloadFile' ] );
		add_filter( 'robots_txt', [ $this, 'robotsTxt' ] );

		self::$_instance = $this;
	}

	/** Return the url for the form
	 * @since 1.0.0
	 *
	 * @param int|string $form_id The id of the form.
	 *
	 * @depreacted $ver$ Use Router instead.
	 *
	 * @return string|null
	 */
	public static function url( $form_id ) {
		$router = self::$_instance !== null ? self::$_instance->router : new WordPressRouter();

		return $router->get_url_for_form( $form_id );
	}

	/**
	 * Save new hash to the form
	 *
	 * @param int $form_id The form id.
	 * @param null|string $hash predefined hash {@since 1.7.0}
	 *
	 * @return mixed[] metadata form.
	 */
	public static function setHash( $form_id, $hash = null ) {
		if ( $hash === null ) {
			$hash = self::generateHash();
		}

		$meta                   = \GFFormsModel::get_form_meta( $form_id );
		$meta[ Router::KEY_HASH ] = (string) $hash;
		\GFFormsModel::update_form_meta( $form_id, $meta );

		return $meta;
	}

	/**
	 * Generates a secure random string.
	 * @since 1.0.0
	 * @return string
	 */
	private static function generateHash() {
		return bin2hex( openssl_random_pseudo_bytes( 32 ) );
	}

	/**
	 * Return the custom filename if it has one.
	 *
	 * @param array $form The form object.
	 *
	 * @return string The filename.
	 */
	public static function getFilename( $form ) {
		$form_id  = rgar( $form, 'id', 0 );
		$filename = GravityExportAddon::get_instance()->get_feed_meta_field( 'custom_filename', $form_id );

		return $filename ?: sprintf(
			'gfexcel-%d-%s-%s',
			$form_id,
			sanitize_title( $form['title'] ),
			date( 'Ymd' )
		);
	}

	/**
	 * Return the file extension to use for renderer and output
	 *
	 * @param array $form The form object.
	 *
	 * @return string The file extension.
	 */
	public static function getFileExtension( $form ) {
		if ( ! self::$file_extension ) {
			$form_id   = rgar( $form, 'id', 0 );
			$extension = gf_apply_filters(
				[
					self::KEY_FILE_EXTENSION,
					$form_id,
				],
				GravityExportAddon::get_instance()->get_feed_meta_field( 'file_extension', $form_id, 'xlsx' ),
				$form
			);

			if ( ! in_array( $extension, static::getPluginFileExtensions(), true ) ) {
				$extension = 'xlsx';
			}

			return $extension;
		}

		return self::$file_extension;
	}

	/**
	 * Helper method to retrieve the available file extensions for the plugin.
	 * @since 1.8.0
	 *
	 * @param bool $imploded Whether to return an imploded array for a regex pattern instead of an array.
	 *
	 * @return string[]|string The extensions.
	 */
	public static function getPluginFileExtensions( bool $imploded = false ) {
		$extensions = (array) apply_filters( 'gfexcel_file_extensions', [ 'xlsx', 'csv' ] );
		if ( $imploded ) {
			$extensions = implode( '|', array_map( static function ( string $extension ) {
				return preg_quote( $extension, '/' );
			}, $extensions ) );
		}

		return $extensions;
	}

	/**
	 * Whether the current user can download the form.
	 * @since 1.7.0
	 *
	 * @param int $form_id The form id of the form to download.
	 *
	 * @return bool Whether the current user can download the file.
	 */
	public static function canDownloadForm( int $form_id ): bool {
		// public urls can always be downloaded.
		if ( ! self::isFormSecured( $form_id ) ) {
			return true;
		}

		// does the user have rights?
		return \GFCommon::current_user_can_any( 'gravityforms_export_entries' );
	}


	/**
	 * Hooks into the request and outputs the file as the HTTP response.
	 * @since 1.0.0
	 *
	 * @param string[] $query_vars The original query vars.
	 *
	 * @return array The new query vars.
	 */
	public function request( $query_vars ) {
		$request = Request::from_query_vars( $query_vars );

		if ( ! $this->router->matches( $request ) ) {
			return $query_vars;
		}

		$feed = $this->router->get_feed_by_request( $request );
		if ( ! $feed ) {
			// Not found
			$query_vars['error'] = \WP_Http::NOT_FOUND;

			return $query_vars;
		}

		$form_id = rgar( $feed, 'form_id' );
		$feed_id = rgar( $feed, 'id' );

		if ( $form_id ) {
			if ( self::canDownloadForm( $form_id ) ) {
				$query_vars['gfexcel_download_form'] = $form_id;
				$query_vars['gfexcel_download_feed'] = $feed_id;

				self::$file_extension = $request->extension();
			} else {
				$query_vars['error'] = \WP_Http::FORBIDDEN;
			}
		} else {
			// Not found
			$query_vars['error'] = \WP_Http::NOT_FOUND;
		}

		return $query_vars;
	}

	/**
	 * Actually triggers the download response.
	 * @since 1.7.0
	 *
	 * @param \WP $wp WordPress request instance.
	 *
	 * @return mixed|void The output will be the file.
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	public function downloadFile( \WP $wp ) {
		if ( ! array_key_exists( 'gfexcel_download_form', $wp->query_vars ) ) {
			return;
		}

		$form_id = $wp->query_vars['gfexcel_download_form'] ?? null;
		$feed_id = $wp->query_vars['gfexcel_download_feed'] ?? null;

		if ( ! $form_id ) {
			return;
		}

		$renderer = GFExcel::getRenderer( $form_id );
		$output   = new GFExcelOutput( $form_id, $renderer, null, $feed_id );

		// trigger download event.
		/**
		 * Runs before download has been rendered but after it has been processed.
		 *
		 * @used-by \GFExcel\Action\CountDownloads::incrementCounter
		 *
		 * @param int $form_id ID of the form being downloaded
		 * @param GFExcelOutput $output Output of the file
		 */
		do_action( GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, $form_id, $output );

		return $output->render();
	}

	/**
	 * Adds a Disallow for the download URLs.
	 * @since 1.7.0
	 *
	 * @param string $output The robots.txt output
	 *
	 * @return string the new output.
	 */
	public function robotsTxt( $output ) {
		$site_url = parse_url( site_url() );
		$path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';

		$lines = '';
		foreach ( $this->router->endpoints() as $endpoint ) {
			$lines .= sprintf( 'Disallow: %s/%s/', esc_attr( $path ), $endpoint ) . "\n";
		}

		// there can be only one `user-agent: *` line, so we make sure it's just below.
		if ( preg_match( '/user-agent:\s*\*/is', $output, $matches ) ) {
			return str_replace( $matches[0], $matches[0] . "\n" . $lines, $output );
		}

		return trim( sprintf( "%s\n%s\n%s", $output, 'User-agent: *', $lines ) );
	}

	/**
	 * Whether all forms should be secured.
	 * @since 1.7.0
	 * @return bool Whether the constant is set.
	 */
	public static function isAllSecured() {
		return defined( 'GFEXCEL_SECURED_DOWNLOADS' ) && GFEXCEL_SECURED_DOWNLOADS;
	}

	/**
	 * Whether the form is secured.
	 * @since 1.7.0
	 *
	 * @param int $form_id
	 *
	 * @return bool Whether this form is secured.
	 */
	public static function isFormSecured( int $form_id ) {
		if ( self::isAllSecured() ) {
			return true;
		}

		$feed = GravityExportAddon::get_instance()->get_feed_by_form_id( $form_id );

		return (bool) rgars( $feed, 'meta/is_secured' );
	}

	/**
	 * Returns the combiner instance.
	 * @since 1.8.0
	 *
	 * @param int|null $form_id The form id.
	 *
	 * @return CombinerInterface The combiner.
	 */
	public static function getCombiner( $form_id = null ): CombinerInterface {
		return gf_apply_filters( array_filter( [
			GFExcelConfigConstants::GFEXCEL_DOWNLOAD_COMBINER,
			$form_id
		] ), new Combiner(), $form_id );
	}

	/**
	 * Returns the renderer instance.
	 * @since 1.9.0
	 *
	 * @param int|null $form_id The form id.
	 *
	 * @return RendererInterface The renderer.
	 */
	public static function getRenderer( $form_id = null ): RendererInterface {
		return gf_apply_filters( array_filter( [
			GFExcelConfigConstants::GFEXCEL_DOWNLOAD_RENDERER,
			$form_id
		] ), new PHPExcelRenderer(), $form_id );
	}
}
