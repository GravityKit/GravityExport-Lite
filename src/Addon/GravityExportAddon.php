<?php

namespace GFExcel\Addon;

use GFCommon;
use GFExcel\Action\ActionAware;
use GFExcel\Action\ActionAwareInterface;
use GFExcel\Action\ActionNotice;
use GFExcel\Action\DownloadUrlDisableAction;
use GFExcel\Action\DownloadUrlEnableAction;
use GFExcel\Action\DownloadUrlResetAction;
use GFExcel\Action\NotifyingActionInterface;
use GFExcel\Component\Usage;
use GFExcel\Field\ProductField;
use GFExcel\Field\SeparableField;
use GFExcel\GFExcel;
use GFExcel\GFExcelOutput;
use GFExcel\GravityForms\Field\CopyShortcode;
use GFExcel\GravityForms\Field\DownloadFile;
use GFExcel\GravityForms\Field\DownloadUrl;
use GFExcel\GravityForms\Field\SortFields;
use GFExcel\Renderer\AbstractPHPExcelRenderer;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Routing\Router;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

/**
 * GravityExport Lite add-on.
 * @since 2.0.0
 */
final class GravityExportAddon extends \GFFeedAddOn implements AddonInterface, ActionAwareInterface {
	use ActionAware;
	use AddonTrait;
	use AddonHelperTrait;

	/**
	 * Slug for bulk action download.
	 * @since 2.0.0
	 */
	private const BULK_DOWNLOAD = 'gk-download';

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_min_gravityforms_version = '2.5';

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_multiple_feeds = false;

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_title = 'GravityExport Lite';

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_short_title = 'GravityExport Lite';

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_slug = 'gravityexport-lite';

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	protected $_version = GFEXCEL_PLUGIN_VERSION;

	/**
	 * @since 2.0.0
	 * @var string Feed settings permissions.
	 */
	protected $_capabilities_form_settings = 'gravityforms_export_entries';

	/**
	 * @since 2.0.0
	 * @var string Relative path to file from plugins directory.
	 */
	protected $_path = 'gravityexport-lite/gfexcel.php';

	/**
	 * @since 2.0.0
	 * @var string Full path to this file.
	 */
	protected $_full_path = GFEXCEL_PLUGIN_FILE;

	/**
	 * A micro cache for the feed object.
	 * @since 2.0.0
	 * @var array|null GF feed object.
	 */
	private $feed = [];

	/**
	 * The router.
	 *
	 * @since 2.4.0
	 *
	 * @var Router
	 */
	private $router;

	/**
	 * The usage component.
	 * @since 2.0.0
	 * @var Usage
	 */
	private $component_usage;

	/**
	 * Set minimum requirements to prevent bugs when using older versions, or missing dependencies
	 * @since 1.0.0
	 * @return array
	 */
	public function minimum_requirements(): array {
		return [
			'php' => [
				'version'    => '7.2',
				'extensions' => [
					'zip',
					'ctype',
					'dom',
					'zlib',
					'xml',
					'iconv',
					'mbstring',
				],
			]
		];
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function __construct( Router $router ) {
		parent::__construct();

		$title = defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ? 'GravityExport' : 'GravityExport Lite';

		$this->_title       = $title;
		$this->_short_title = $title;

		$this->router          = $router;
		$this->component_usage = new Usage();
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function init_admin(): void {
		parent::init_admin();

		add_filter( 'bulk_actions-toplevel_page_gf_edit_forms', \Closure::fromCallable( [ $this, 'bulk_actions' ] ) );
		add_action( 'wp_loaded', \Closure::fromCallable( [ $this, 'handle_bulk_actions' ] ) );
		add_filter( 'gform_form_actions', \Closure::fromCallable( [ $this, 'gform_form_actions' ] ), 10, 2 );
		add_action( 'wp_before_admin_bar_render', \Closure::fromCallable( [ $this, 'admin_bar' ] ), 20 );
		add_filter( 'gform_export_fields', \Closure::fromCallable( [ $this, 'gform_export_fields' ] ) );
		add_action( 'admin_notices', \Closure::fromCallable( [ $this, 'queue_download_url_error_notice' ] ) );

		// Strip a one-time success/error token from the address bar after first paint so a browser
		// refresh (a GET of the same URL) cannot re-surface the message. Keeps the flow storage-free;
		// if JS is off it degrades to re-showing the message on refresh, which never re-runs the action.
		if ( rgget( 'gexcel_notice' ) || rgget( 'gexcel_error' ) ) {
			add_action( 'admin_print_footer_scripts', [ self::class, 'clean_notice_query_arg' ] );
		}
	}

	/**
	 * Queues a download-URL action's failure, carried across its redirect by an allowlisted
	 * `gexcel_error` token (see self::save_feed_settings()).
	 *
	 * Uses Gravity Forms' own error queue rather than the Settings postback box (which can only paint
	 * success styling on a GET) or a raw admin notice (GF renders its pages in a custom layout where
	 * admin_notices output lands in the document head). The specific cause is logged, not shown, so
	 * internals do not leak to the browser.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function queue_download_url_error_notice(): void {
		if ( ! $this->is_download_url_notice( sanitize_key( (string) rgget( 'gexcel_error' ) ) ) ) {
			return;
		}

		GFCommon::add_error_message( esc_html__( 'There was an error generating the download URL. Please try again.', 'gk-gravityexport-lite' ) );
	}

	/**
	 * @inheritDoc
	 *
	 * Re-surfaces a successful download-URL action's confirmation after its PRG redirect via the
	 * Settings framework's postback message. The action name rides an allowlisted `gexcel_notice`
	 * token on the redirect (see self::save_feed_settings()), so nothing is stored to survive it.
	 *
	 * @since TBD
	 */
	public function feed_settings_init(): void {
		parent::feed_settings_init();

		$message = $this->get_download_url_notice_message( sanitize_key( (string) rgget( 'gexcel_notice' ) ) );
		if ( '' === $message ) {
			return;
		}

		$renderer = $this->get_settings_renderer();
		if ( ! $renderer ) {
			return;
		}

		// Only reached with a valid token, so replacing GF's own postback callback (which handles the
		// new-feed fid=0 redirect) is safe: that flow never carries a gexcel_notice token.
		$renderer->set_postback_message_callback( static function ( $default ) use ( $message ) {
			// Only surface our notice on the post-redirect GET. On a save (POST) pass GF's own
			// success/validation message through untouched, as GFFeedAddOn's own callback does.
			if ( ! empty( $_POST ) ) {
				return $default;
			}

			return $message ?: $default;
		} );
	}

	/**
	 * Prints a one-liner that removes the one-time `gexcel_notice`/`gexcel_error` tokens from the
	 * current URL, so a browser refresh does not re-surface the message.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function clean_notice_query_arg(): void {
		?>
		<script>
			( function () {
				const url = new URL( window.location.href );

				if ( ! url.searchParams.has( 'gexcel_notice' ) && ! url.searchParams.has( 'gexcel_error' ) ) {
					return;
				}

				url.searchParams.delete( 'gexcel_notice' );
				url.searchParams.delete( 'gexcel_error' );
				window.history.replaceState( {}, '', url.toString() );
			} )();
		</script>
		<?php
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function feed_settings_fields(): array {
		require_once GFCommon::get_base_path() . '/includes/settings/class-fields.php';

		// Register custom fields first.
		Fields::register( 'download_file', DownloadFile::class );
		Fields::register( 'download_url', DownloadUrl::class );
		Fields::register( 'sort_fields', SortFields::class );
		Fields::register( 'copy_shortcode', CopyShortcode::class );

		$form = $this->get_current_form();

		// Only show
		if ( ! $this->get_setting( 'hash' ) ) {
			$settings_sections[] = [
				'title'  => __( 'Activate GravityExport', 'gk-gravityexport-lite' ),
				'fields' => [
					[
						'name' => 'hash',
						'type' => 'download_url',
					],
				],
			];

			add_filter( 'gform_settings_save_button', '__return_null' );

			return $settings_sections;
		}

		if ( ! defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ) {
			$settings_sections[] = [
				'title'       => '',
				'description' => $this->get_gravityexport_message(),
				'fields'      => [
					[
						'name'  => 'gravityexport-rocks',
						'type'  => 'hidden',
						'value' => 'You should try it!',
					],
				],
			];
		}

		$hash = $this->get_setting( 'hash' );
		$settings_sections[] = [
			'id'          => 'gk-gravityexport-download',
			'title'       => __( 'Download settings', 'gk-gravityexport-lite' ),
			'collapsible' => true,
			'fields'      => [
				[
					'label'         => esc_html__( 'Download URL', 'gk-gravityexport-lite' ),
					'name'          => 'hash',
					'type'          => 'download_url',
					'assets_dir'    => $this->assets_dir,
					'default_value' => $hash,
					'url'           => $this->router->get_url_for_hash( $hash ),
				],
				[
					'label'      => esc_html__( 'Embed shortcode', 'gk-gravityexport-lite' ),
					'name'       => 'copy_shortcode',
					'type'       => 'copy_shortcode',
					'embed_type' => $this->get_setting( 'file_extension' ),
				],
				[
					'label'         => esc_html__( 'Custom Filename', 'gk-gravityexport-lite' ),
					'type'          => 'text',
					'name'          => 'custom_filename',
					'placeholder'   => sprintf(
						esc_html__( 'Default: %s', 'gk-gravityexport-lite' ),
						GFExcel::getFilename( $form )
					),
					'class'         => 'medium code',
					'description'   => esc_html__(
						'Most non-alphanumeric characters will be replaced with hyphens. Leave empty for default.',
						'gk-gravityexport-lite'
					),
					'save_callback' => function ( $field, $value ) {
						return sanitize_file_name( $value );
					},
				],
				[
					'label'       => esc_html__( 'File Extension', 'gk-gravityexport-lite' ),
					'type'        => 'select',
					'name'        => 'file_extension',
					'class'       => 'small-text',
					'description' => sprintf(
						esc_html__(
							'Note: You may override the file type by adding the desired extension (%s) to the end of the Download URL.',
							'gk-gravityexport-lite'
						),
						'<code>.' . implode( '</code>, <code>.', GFExcel::getPluginFileExtensions() ) . '</code>'
					),
					'choices'     => array_map( static function ( $extension ) {
						return
							[
								'name'  => 'file_extension',
								'label' => '.' . $extension,
								'value' => $extension,
							];
					}, GFExcel::getPluginFileExtensions() ),
				],
			],
		];

		$settings_sections[] = [
			'id'     => 'gk-gravityexport-download-file',
			'class'  => 'gk-gravityexport-download-file',
			'title'  => __( 'Instant Download ⚡️', 'gk-gravityexport-lite' ),
            'fields' => [
                [
                    'name'          => 'download_file',
                    'label'         => esc_html__( 'Select Date Range (optional)', 'gk-gravityexport-lite' ),
                    'tooltip'       => 'export_date_range',
                    'type'          => 'download_file',
                    'default_value' => $hash,
                    'url'           => $this->router->get_url_for_hash( $hash ),
                ],
            ],
		];

		$settings_sections[] = [
			'id'          => 'gk-section-security',
			'collapsible' => true,
			'title'       => __( 'Security Settings', 'gk-gravityexport-lite' ),
			'fields'      => [
				[
					'name'        => 'has_embed_secret',
					'label'       => esc_html__( 'Secure Shortcodes', 'gk-gravityexport-lite' ),
					'type'        => 'checkbox',
					'description' => __( 'A secure shortcode contains a unique <code>secret</code>-attribute which prevents generating the URL for a form without permission.', 'gk-gravityexport-lite' ),
					'choices'     => [
						[
							'name'  => 'has_embed_secret',
							'label' => esc_html__( 'Enable secure embed shortcode', 'gk-gravityexport-lite' ),
							'value' => '1',
						],
					],
				],
				[
					'name'          => 'is_secured',
					'label'         => esc_html__( 'Download Permissions', 'gk-gravityexport-lite' ),
					'type'          => 'select',
					'description'   => sprintf(
						esc_html__(
							'If set to "Everyone can download", anyone with the link can download. If "Logged-in users who have \'Export Entries\' access" is selected, users must be logged-in and have the %s capability.',
							'gk-gravityexport-lite'
						),
						'<code>gravityforms_export_entries</code>'
					),
					'default_value' => 0,
					'choices'       => ( static function (): array {
						$options = [];
						if ( ! GFExcel::isAllSecured() ) {
							$options[] = [
								'name'  => 'is_secured',
								'label' => __( 'Everyone can download', 'gk-gravityexport-lite' ),
								'value' => 0,
							];
						}
						$options[] = [
							'name'  => 'is_secured',
							'label' => __( 'Logged-in users who have "Export Entries" access', 'gk-gravityexport-lite' ),
							'value' => 1,
						];

						return $options;
					} )(),
				],
			],
		];

		$general_settings = apply_filters(
			'gfexcel_general_settings',
			[
				[
					'id'          => 'gk-general-security',
					'collapsible' => true,
					'title'       => __( 'General Settings', 'gk-gravityexport-lite' ),
					'fields'      => [
						[
							'name'    => 'enable_notes',
							'label'   => esc_html__( 'Include Entry Notes', 'gk-gravityexport-lite' ),
							'type'    => 'checkbox',
							'choices' => [
								[
									'name'  => 'enable_notes',
									'label' => esc_html__( 'Yes, enable the notes for every entry', 'gk-gravityexport-lite' ),
									'value' => '1',
								],
							],
						],
						[
							'label'   => esc_html__( 'Attach Single Entry to Notification', 'gk-gravityexport-lite' ),
							'type'    => 'select',
							'name'    => 'attachment_notification',
							'choices' => $this->getNotifications(),
						],
						[
							'name'          => 'is_transposed',
							'type'          => 'radio',
							'label'         => esc_html__( 'Column Position', 'gk-gravityexport-lite' ),
							'default_value' => 0,
							'choices'       => [
								[
									'name'  => 'is_transposed',
									'label' => esc_html__( 'At the top (normal)', 'gk-gravityexport-lite' ),
									'value' => 0,
								],
								[
									'name'  => 'is_transposed',
									'label' => esc_html__( 'At the left (transposed)', 'gk-gravityexport-lite' ),
									'value' => 1,
								],
							],
						],
						[
							'name'     => 'order_by',
							'label'    => esc_html__( 'Order By', 'gk-gravityexport-lite' ),
							'type'     => 'callback',
							'class'    => 'gform-settings-field--multiple-inputs',
							'callback' => function () {
								$sort_field = [
									'name'    => 'sort_field',
									'choices' => ( new FieldsRepository( $this->get_current_form() ) )->getSortFieldOptions(),
								];

								$sort_order = [
									'name'    => 'sort_order',
									'type'    => 'select',
									'choices' => [
										[
											'value' => 'ASC',
											'label' => esc_html__( 'Ascending', 'gk-gravityexport-lite' ),
										],
										[
											'value' => 'DESC',
											'label' => esc_html__( 'Descending', 'gk-gravityexport-lite' ),
										],
									],
								];

								$this->settings_select( $sort_field );
								$this->settings_select( $sort_order );
							},
						],
					],
				],
			]
		);

		// A filter callback can return anything; only merge usable sections.
		if ( is_array( $general_settings ) ) {
			$settings_sections = array_merge( $settings_sections, $general_settings );
		}

		$settings_sections[] = [
			'id'          => 'gk-section-fields',
			'collapsible' => true,
			'title'       => esc_html__( 'Field settings', 'gk-gravityexport-lite' ),
			'fields'      => [
				[
					'name'             => 'export-fields',
					'type'             => 'sort_fields',
					'choices'          => $this->getFields(),
					'use_admin_labels' => $this->useAdminLabels(),
					'sections'         => [
						'disabled' => [ esc_html__( 'Disabled Fields', 'gk-gravityexport-lite' ), 'enabled' ],
						'enabled'  => [ esc_html__( 'Enabled Fields', 'gk-gravityexport-lite' ), 'disabled' ],
					],
				],
			],
		];

		return $settings_sections;
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function get_menu_icon(): string {
		return '<svg style="height: 24px; width: 37px;" fill="currentColor" enable-background="new 0 0 226 148" height="148" viewBox="0 0 226 148" width="226" xmlns="http://www.w3.org/2000/svg"><path d="m176.8 118.8c-1.6 1.6-4.1 1.6-5.7 0l-5.7-5.7c-1.6-1.6-1.6-4.1 0-5.7l27.6-27.4h-49.2c-4.3 39.6-40 68.2-79.6 63.9s-68.2-40-63.9-79.6 40.1-68.2 79.7-63.9c25.9 2.8 48.3 19.5 58.5 43.5.6 1.5-.1 3.3-1.7 3.9-.4.1-.7.2-1.1.2h-9.9c-1.9 0-3.6-1.1-4.4-2.7-14.7-27.1-48.7-37.1-75.8-22.4s-37.2 48.8-22.4 75.9 48.8 37.2 75.9 22.4c15.5-8.4 26.1-23.7 28.6-41.2h-59.4c-2.2 0-4-1.8-4-4v-8c0-2.2 1.8-4 4-4h124.7l-27.5-27.5c-1.6-1.6-1.6-4.1 0-5.7l5.7-5.7c1.6-1.6 4.1-1.6 5.7 0l41.1 41.2c3.1 3.1 3.1 8.2 0 11.3z"/></svg>';
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function plugin_settings_icon(): string {
		return $this->get_menu_icon();
	}

	/**
	 * Returns the feed settings for this form for backwards compatibility.
	 * @since 2.0.0
	 *
	 * @param array $form The form object.
	 *
	 * @return array The feed settings.
	 */
	public function get_form_settings( $form ): array {
		$feed = $this->get_feed_by_form_id( $form['id'] );

		return \rgar( $feed, 'meta', [] );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function plugin_settings_fields(): array {
		$settings_sections = [];

		if ( ! defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ) {
			$settings_sections[] = [
				'id'          => 'gravityexport-lite-rating-fieldset',
				'description' => $this->get_rating_message(),
				'fields'      => [
					[
						'name'  => 'gravityexport-rocks',
						'type'  => 'hidden',
						'value' => 'You should try it!',
					],
				],
			];
		}

		$settings_sections[] = [
			'title'       => esc_html__( 'Default Settings', 'gk-gravityexport-lite' ),
			'description' => $this->plugin_settings_description(),
			'fields'      => [
				[
					'name'    => 'labels',
					'label'   => esc_html__( 'Labels', 'gk-gravityexport-lite' ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label' => esc_html__(
								'Use admin labels',
								'gk-gravityexport-lite'
							),
							'name'  => 'use_admin_label',
						],
					],
				],
				[
					'name'    => 'field_separate',
					'label'   => esc_html__( 'Multiple Columns', 'gk-gravityexport-lite' ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label' => esc_html__(
								'Split multi-fields (name, address) into multiple columns',
								'gk-gravityexport-lite'
							),
							'name'  => SeparableField::SETTING_KEY,
						],
					],
				],
				[
					'name'    => 'notes',
					'label'   => esc_html__( 'Notes', 'gravityforms' ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label'         => esc_html__( 'Enable notes by default', 'gk-gravityexport-lite' ),
							'name'          => 'notes_enabled',
							'default_value' => false,
						],
					],
				],
				[
					'name'    => 'sections',
					'label'   => esc_html__( 'Sections', 'gk-gravityexport-lite' ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label'         => esc_html__( 'Enable (empty) section column', 'gk-gravityexport-lite' ),
							'name'          => 'sections_enabled',
							'default_value' => false,
						],
					],
				],
				[
					'name'  => 'fileuploads',
					'label' => esc_html__( 'File Uploads', 'gk-gravityexport-lite' ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__( 'Enable file upload columns', 'gk-gravityexport-lite' ),
							'name'          => 'fileuploads_enabled',
							'default_value' => true,
						],
					],
				],
				[
					'name'  => 'hyperlinks',
					'label' => esc_html__( 'Hyperlinks', 'gk-gravityexport-lite' ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__( 'Enable hyperlinks on URL-only columns', 'gk-gravityexport-lite' ),
							'name'          => 'hyperlinks_enabled',
							'default_value' => true,
						],
					],
				],
				[
					'name'  => 'products_price',
					'label' => esc_html__( 'Product Fields', 'gk-gravityexport-lite' ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__(
								'Export prices as numeric fields, without currency symbol ($)',
								'gk-gravityexport-lite'
							),
							'name'          => ProductField::SETTING_KEY,
							'default_value' => false,
						],
					],
				],
			],
		];

		$settings_sections[] = [
			'title'  => esc_html__( 'Default Enabled Meta Fields', 'gk-gravityexport-lite' ),
			'fields' => [
				[
					'name'        => 'enabled_metafields',
					'description' => wpautop( esc_html__(
							'Select all meta fields that are enabled by default. Once you\'ve saved your form, these settings will not do anything any more.',
							'gk-gravityexport-lite'
						) ) . $this->getSelectAllHtml(),
					'type'        => 'checkbox',
					'choices'     => $this->meta_fields(),
				],
			],
		];

		if ( ! defined( 'GK_GRAVITYEXPORT_PLUGIN_VERSION' ) ) {
			$settings_sections[] = [
				'title'       => '',
				'description' => $this->get_gravityexport_message(),
				'fields'      => [
					[
						'name'  => 'gravityexport-rocks',
						'type'  => 'hidden',
						'value' => 'You should try it!',
					],
				],
			];
		}

		return $settings_sections;
	}

	/**
	 * Returns the available metadata fields.
	 * @since 2.0.0
	 * @return array[] The metadata fields.
	 */
	private function meta_fields(): array {
		$repository = new FieldsRepository( [ 'fields' => [] ] );

		// Suppress notices of missing form. There is no form, we just need the metadata.
		$fields = @$repository->getFields( true );

		return array_map( static function ( $field ): array {
			return [
				'label'         => $field->label,
				'name'          => 'enabled_metafield_' . $field->id,
				'default_value' => true,
			];
		}, $fields );
	}

	/**
	 * Returns the notification options list.
	 * @since 2.0.0
	 * @return array The notification options.
	 */
	private function getNotifications(): array {
		$options       = [ [ 'label' => __( 'Select a Notification', 'gk-gravityexport-lite' ), 'value' => '' ] ];
		$notifications = $this->get_current_form()['notifications'] ?? [];
		foreach ( $notifications as $key => $notification ) {
			$options[] = [ 'label' => \rgar( $notification, 'name', __( 'Unknown' ) ), 'value' => $key ];
		}

		return $options;
	}

	/**
	 * Returns the rating message.
	 * @since 2.0.0
	 * @return string The message.
	 */
	public function get_rating_message(): string {
		ob_start();
		?>
		<div id="gravityexport-lite-rating" class="wrap gravityexport-lite-callout">
			<p style="font-size: 1.2rem; margin-bottom: 0;"><?php
				printf( ' ' . esc_html__(
						'If you like the plugin, 📣 %slet others know%s! We already have %s active users. Let\'s get to %s by spreading the news!',
						'gk-gravityexport-lite'
					),
					'<strong><a href="https://wordpress.org/support/plugin/gf-entries-in-excel/reviews/?filter=5#new-post" target="_blank" title="' . esc_attr__( 'This link opens in a new window',
						'gk-gravityexport-lite' ) . '">',
					'</a></strong>', esc_html( $this->component_usage->getCount() ),
					esc_html( $this->component_usage->getTarget() )
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the description for the plugin settings.
	 * @since 2.0.0
	 * @return string The settings description.
	 */
	private function plugin_settings_description(): string {
		return sprintf(
			'<p>%s</p>',
			esc_html__(
				'These are global settings for new forms. You can overwrite them per form using the available hooks.',
				'gk-gravityexport-lite'
			) );
	}

	/**
	 * Returns a nice upgrade message to the Pro version.
	 * @since 2.0.0
	 * @return string The upgrade message.
	 */
	private function get_gravityexport_message(): string {
		ob_start();
		?>
		<div id="gravityexport-additional-features" class="wrap gravityexport-lite-callout">
			<h2><?php
				esc_html_e( 'Upgrade to GravityExport for these useful features:', 'gk-gravityexport-lite' ); ?></h2>

			<div>
				<h3><?php
					esc_html_e( 'Save exports to Dropbox, FTP, &amp; local storage', 'gk-gravityexport-lite' ); ?>
					💾</h3>
				<p><?php
					esc_html_e( 'Automatically upload exports to Dropbox, a remote server using SFTP and FTP, or store locally.',
						'gk-gravityexport-lite' ); ?></p>
			</div>

			<div>
				<h3><?php
					esc_html_e( 'Filter exports with Conditional Logic', 'gk-gravityexport-lite' ); ?> 😎</h3>
				<p><?php
					esc_html_e( 'Create advanced filters, including exporting entries created by only the currently logged-in user.',
						'gk-gravityexport-lite' ); ?></p>
			</div>

			<div>
				<h3><?php
					esc_html_e( 'Exports are ready for data analysis', 'gk-gravityexport-lite' ); ?> 📊</h3>
				<p><?php
					esc_html_e( 'When analyzing data, you want fields with multiple values broken into multiple rows each with one value. If you work with data, you&rsquo;ll love this feature!',
						'gk-gravityexport-lite' ); ?></p>
			</div>

			<p>
				<a class="button button-primary primary large button-hero button-cta"
				   href="https://www.gravitykit.com/extensions/gravityexport/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=upgrade-message"
				   target="_blank" rel="noopener noreferrer"
				   title="<?php esc_attr_e( 'This link opens in a new window', 'gk-gravityexport-lite' ); ?>">⚡️&nbsp;<?php
					esc_html_e( 'Gain Powerful Features with GravityExport', 'gk-gravityexport-lite' ); ?>️</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function styles(): array {
		$css_file    = plugin_dir_path( GFEXCEL_PLUGIN_FILE ) . 'public/css/gravityexport-lite.css';
		$css_version = file_exists( $css_file ) ? (string) filemtime( $css_file ) : $this->get_version();

		return array_merge( parent::styles(), [
			[
				'handle'  => 'gravityexport_lite',
				'src'     => $this->assets_dir . 'css/gravityexport-lite.css',
				'version' => $css_version,
				'enqueue' => [
					[ 'admin_page' => 'form_settings', 'tab' => $this->get_slug() ],
					[ 'admin_page' => 'plugin_settings', 'tab' => $this->get_slug() ],
				],
			],
		] );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function scripts(): array {
		$js_file    = plugin_dir_path( GFEXCEL_PLUGIN_FILE ) . 'public/js/gravityexport-lite.js';
		$js_version = file_exists( $js_file ) ? (string) filemtime( $js_file ) : $this->get_version();

		return array_merge( parent::scripts(), [
			[
				'handle'  => 'jquery-ui-sortable',
				'enqueue' => [
					[
						'admin_page' => 'form_settings',
						'tab'        => $this->get_slug(),
					],
				],
			],
			[
				'handle'   => 'gravityexport_lite',
				'src'      => $this->assets_dir . 'js/gravityexport-lite.js',
				'version'  => $js_version,
				'enqueue'  => [
					[
						'admin_page' => 'form_settings',
						'tab'        => $this->get_slug(),
					],
				],
				'deps'     => [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'underscore', 'wp-i18n', 'wp-a11y' ],
				'callback' => [ $this, 'localize_sortable_script' ],
			],
			[
				'handle'  => 'gravityexport_lite_settings',
				'src'     => $this->assets_dir . 'js/gravityexport-lite-settings.js',
				'enqueue' => [
					[
						'admin_page' => 'plugin_settings',
						'tab'        => $this->get_slug(),
					],
				],
				'deps'    => [ 'jquery' ],
			],
		] );
	}

	/**
	 * Localize the sortable script with i18n strings.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function localize_sortable_script(): void {
		wp_localize_script( 'gravityexport_lite', 'gravityexport_lite_strings', [
			'enable'              => esc_html__( 'Enable all', 'gk-gravityexport-lite' ),
			'disable'             => esc_html__( 'Disable all', 'gk-gravityexport-lite' ),
			'enable_visible'      => esc_html__( 'Enable visible', 'gk-gravityexport-lite' ),
			'disable_visible'     => esc_html__( 'Disable visible', 'gk-gravityexport-lite' ),
			'no_fields_match'     => esc_html__( 'No fields match your search.', 'gk-gravityexport-lite' ),
			'one_field_matches'   => esc_html__( '1 field matches your search.', 'gk-gravityexport-lite' ),
			/* translators: [count] is replaced with the number of matching fields. Do not translate text inside square brackets. */
			'n_fields_match'      => esc_html__( '[count] fields match your search.', 'gk-gravityexport-lite' ),
			/* translators: [field] is the field name, [destination] is the list name (e.g., "Enabled Fields"). Do not translate text inside square brackets. */
			'field_moved'         => esc_html__( '[field] moved to [destination].', 'gk-gravityexport-lite' ),
			/* translators: [count] is the number of fields, [destination] is the list name (e.g., "Enabled Fields"). Do not translate text inside square brackets. */
			'fields_moved'        => esc_html__( '[count] fields moved to [destination].', 'gk-gravityexport-lite' ),
			/* translators: [destination] is the list name (e.g., "Enabled Fields"). Do not translate text inside square brackets. */
			'one_field_moved'     => esc_html__( '1 field moved to [destination].', 'gk-gravityexport-lite' ),
		] );
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function save_feed_settings( $feed_id, $form_id, $settings ) {
		// In GF 2.5., $_POST must contain 'gform-settings-save' variable no matter what its value is.
		$action = rgpost( 'gform-settings-save' );
		// Keep old settings that were not provided (used for download_count).
		$settings = array_merge( $this->get_previous_settings(), $settings );

		/**
		 * Modifies the feed settings before they are stored.
		 *
		 * Runs inside the save, so related settings are written in a single update.
		 *
		 * @since TBD
		 *
		 * @param array      $settings The settings about to be stored.
		 * @param int|string $feed_id  The feed ID.
		 * @param int|string $form_id  The form ID.
		 */
		$filtered = apply_filters( 'gk/gravityexport/feed/pre-save-settings', $settings, $feed_id, $form_id );

		if ( is_array( $filtered ) ) {
			$settings = $filtered;
		}

		if ( $this->hasAction( $action ) ) {
			// Prevent indefinite loop in case action's fire() method calls save_feed_settings().
			unset( $_POST['gform-settings-save'] );

			$action_object = $this->getAction( $action );

			if ( $action_object instanceof NotifyingActionInterface ) {
				$notice = $action_object->fire_with_notice( $this, [ $feed_id, $form_id, $settings ] );

				if ( $notice && ActionNotice::ERROR === $notice->type() ) {
					// The action failed and changed nothing. Log the specific cause and carry an
					// error token so the redirect target paints a red notice; GF's postback box can
					// only render success styling on a GET, so the error cannot ride that channel.
					$this->log_error( __METHOD__ . '(): ' . $notice->message() );

					self::refresh( self::feed_settings_url( null, $action ) );

					return $feed_id;
				}

				// PRG so a browser refresh cannot re-run a successful destructive action. Carry the
				// allowlisted action token only on success, so feed_settings_init() never confirms
				// something that did not happen. Nothing is stored to survive the redirect.
				$token = $notice && ActionNotice::SUCCESS === $notice->type() ? $action : null;

				self::refresh( self::feed_settings_url( $token ) );

				return $feed_id;
			}

			$action_object->fire( $this, [ $feed_id, $form_id, $settings ] );

			self::refresh( self::feed_settings_url() );

			return $feed_id;
		}

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}

	/**
	 * Redirects (PRG) to the current feed settings page, or to an explicit URL.
	 *
	 * @since 2.0.0
	 */
	protected static function refresh( ?string $url = null ): void {
		wp_safe_redirect( $url ?? self::feed_settings_url() );
		exit;
	}

	/**
	 * Builds the current feed-settings URL, optionally carrying a one-time success or error token.
	 *
	 * @since TBD
	 *
	 * @param string|null $notice Allowlisted success token to confirm after the redirect.
	 * @param string|null $error  Allowlisted error token to surface a failure after the redirect.
	 *
	 * @return string
	 */
	protected static function feed_settings_url( ?string $notice = null, ?string $error = null ): string {
		$args = [
			'page'    => rgget( 'page' ),
			'view'    => rgget( 'view' ),
			'subview' => rgget( 'subview' ),
			'id'      => rgget( 'id' ),
		];

		if ( $fid = rgget( 'fid' ) ) {
			$args['fid'] = $fid;
		}

		if ( $notice ) {
			$args['gexcel_notice'] = $notice;
		}

		if ( $error ) {
			$args['gexcel_error'] = $error;
		}

		return add_query_arg( $args, get_admin_url() );
	}

	/**
	 * Whether the token identifies a download-URL action whose success message may be shown after a redirect.
	 *
	 * @since TBD
	 *
	 * @param string $notice The notice token.
	 *
	 * @return bool
	 */
	private function is_download_url_notice( string $notice ): bool {
		return in_array( $notice, [
			DownloadUrlResetAction::$name,
			DownloadUrlEnableAction::$name,
			DownloadUrlDisableAction::$name,
		], true );
	}

	/**
	 * Returns the translated success message for an allowlisted download-URL notice token, sourced
	 * from the action itself so the string has a single source of truth.
	 *
	 * @since TBD
	 *
	 * @param string $notice The notice token.
	 *
	 * @return string
	 */
	private function get_download_url_notice_message( string $notice ): string {
		if ( ! $this->is_download_url_notice( $notice ) || ! $this->hasAction( $notice ) ) {
			return '';
		}

		$action = $this->getAction( $notice );

		return $action instanceof NotifyingActionInterface ? $action->get_success_notice()->message() : '';
	}

	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public function settings_select( $field, $echo = true ): string {
		/**
		 * This overwrites {@see AddonHelperTrait::settings_select()} method to the original.
		 */
		return parent::settings_select( $field, $echo );
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten to add custom after-render hook.
	 *
	 * @since 2.0.0
	 */
	public function feed_edit_page( $form, $feed_id ) {
		parent::feed_edit_page( $form, $feed_id );

		do_action( 'gk-gravityexport-after_feed_edit_page', $form, $feed_id );
	}

	/**
	 * Helper method to get the only available feed for this add-on.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array|null The feed.
	 */
	public function get_feed_by_form_id( int $form_id = 0 ): ?array {
		if ( ! $form_id ) {
			$form    = $this->get_current_form();
			$form_id = rgar( $form, 'id', 0 );
		}

		if ( ! isset( $this->feed[ $form_id ] ) ) {
			$feed_id = $this->get_default_feed_id( $form_id );
			// update meta settings to include any posted values.
			if ( $feed = $this->get_feed( $feed_id ) ) {
				$feed['meta'] = array_merge( $feed['meta'] ?? [], $this->get_current_settings() );

				$this->feed[ $form_id ] = $feed;
			}
		}

		return $this->feed[ $form_id ] ?? null;
	}

	/**
	 * Helper method to return the value of a feed meta field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field The name of the meta field.
	 * @param int $form_id The form id.
	 * @param mixed|null $default The default value.
	 *
	 * @return mixed The field value.
	 */
	public function get_feed_meta_field( string $field, int $form_id = 0, $default = null ) {
		$feed = $this->get_feed_by_form_id( $form_id );

		return rgars( $feed, sprintf( 'meta/%s', $field ), $default );
	}

	/**
	 * Add GravityExport download option to bulk action dropdown.
	 *
	 * @param array $actions The current actions.
	 *
	 * @return array The new actions.
	 */
	private function bulk_actions( array $actions ): array {
		if ( 'form_list' === \GFForms::get_page() ) {
			$actions[ self::BULK_DOWNLOAD ] = esc_html__( 'Download as one file', 'gk-gravityexport-lite' );
		}

		return $actions;
	}

	/**
	 * Handles the download of multiple forms as a bulk action.
	 * @since 1.2.0
	 * @throws \PhpOffice\PhpSpreadsheet\Exception When the file could not be rendered.
	 */
	private function handle_bulk_actions(): void {
		if (
			! current_user_can( 'editor' )
			&& ! current_user_can( 'administrator' )
			&& ! \GFCommon::current_user_can_any( 'gravityforms_export_entries' )
		) {
			return;
		}

		if ( $this->get_bulk_action() !== self::BULK_DOWNLOAD || ! array_key_exists( 'form', $_REQUEST ) ) {
			return;
		}

		$form_ids = (array) $_REQUEST['form'];
		if ( count( $form_ids ) < 1 ) {
			return;
		}

		$renderer = count( $form_ids ) > 1
			? new PHPExcelMultisheetRenderer()
			: GFExcel::getRenderer( current( $form_ids ) );

		if ( ! $renderer instanceof AbstractPHPExcelRenderer ) {
			return;
		}

		foreach ( $form_ids as $form_id ) {
			$feed   = $this->get_feed_by_form_id( $form_id );
			$output = new GFExcelOutput( (int) $form_id, $renderer, null, $feed['id'] ?? null );
			$output->render();
		}

		$renderer->renderOutput();
	}

	/**
	 * {@see \GFFeedAddOn::get_bulk_action()}.
	 * @since 2.0.0
	 * @return null|string The current action.
	 */
	private function get_bulk_action(): ?string {
		$action = rgpost( 'action' );
		if ( empty( $action ) || $action === '-1' ) {
			$action = rgpost( 'action2' );
		}

		return empty( $action ) || $action === '-1' ? null : $action;
	}

	/**
	 * Adds a quick download link if the form download is enabled.
	 *
	 * @param array $form_actions The form action.
	 * @param string $form_id the form id.
	 *
	 * @return array The new form actions.
	 */
	private function gform_form_actions( array $form_actions, string $form_id ): array {
		$url = $this->router->get_url_for_form( (int) $form_id );

		if ( $url ) {

			$form_actions['download'] = [
				'label'      => __( 'Download', 'gk-gravityexport-lite' ),
				'title'      => __( 'Download an Export', 'gk-gravityexport-lite' ),
				'url'        => $url,
				'menu_class' => 'download',
			];
		}

		return $form_actions;
	}

	/**
	 * Adds the export links to the admin bar.
	 * @since 1.7.0
	 */
	private function admin_bar(): void {
		// Only show links if the user has the rights for exporting.
		if ( ! \GFCommon::current_user_can_any( 'gravityforms_export_entries' ) ) {
			return;
		}

		/**
		 * @var  \WP_Admin_Bar $wp_admin_bar
		 */
		global $wp_admin_bar;

		// Get all recent form id's.
		$form_ids = array_reduce( array_keys( $wp_admin_bar->get_nodes() ), static function ( array $output, $key ) {
			if ( preg_match( '/gform-form-(\d)$/i', $key, $matches ) ) {
				$output[] = (int) $matches[1];
			}

			return $output;
		}, [] );

		// add download URL to every form that has a hash.
		foreach ( $form_ids as $id ) {
			$url = $this->router->get_url_for_form( $id );
			if ( $url ) {
				$wp_admin_bar->add_node( [
					'id'     => 'gfexcel-form-' . $id . '-download',
					'parent' => 'gform-form-' . $id,
					'title'  => esc_html__( 'Download', 'gk-gravityexport-lite' ),
					'href'   => trailingslashit( esc_url( $url ) ),
				] );
			}
		}
	}

	/**
	 * Adds any extra fields to the export.
	 *
	 * @since 1.11.2
	 *
	 * @param array $form The form object.
	 *
	 * @return array The updated form object.
	 */
	private function gform_export_fields( array $form ): array {
		$form['fields'][] = [ 'id' => 'date_updated', 'label' => __( 'Date Updated', 'gravityforms' ) ];

		return $form;
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function can_duplicate_feed( $id ): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function duplicate_feed( $id, $new_form_id = false ): ?int {
		$new_feed_id = parent::duplicate_feed( $id, $new_form_id );
		if ( ! $new_feed_id ) {
			return null;
		}

		$new_feed = \GFAPI::get_feed( $new_feed_id );

		if ( is_array( $new_feed ) && $this->hasAction( DownloadUrlResetAction::$name ) ) {
			$form_id  = $new_feed['form_id'] ?? 0;
			$settings = $new_feed['meta'] ?? [];

			$this->getAction( DownloadUrlResetAction::$name )->fire( $this, [ $new_feed_id, $form_id, $settings ] );
		}

		return $new_feed_id;
	}

	/**
	 * Returns disabled/enabled form fields as configured by the feed.
	 * @since 2.0.0
	 * @return array The fields.
	 */
	private function getFields(): array {
		$form            = $this->get_current_form();
		$feed            = $this->get_feed_by_form_id();
		$repository      = new FieldsRepository( $form, $feed ?: [] );
		$disabled_fields = $repository->getDisabledFields();
		$all_fields      = $repository->getFields( true );

		$active_fields = $inactive_fields = [];
		foreach ( $all_fields as $field ) {
			$array_name      = in_array( $field->id, $disabled_fields, false ) ? 'inactive_fields' : 'active_fields';
			${$array_name}[] = $field;
		}

		return [
			'disabled' => $inactive_fields,
			'enabled'  => $repository->sortFields( $active_fields ),
		];
	}

	/**
	 * @since 2.0.0
	 * @return string The HTML for select only.
	 */
	private function getSelectAllHtml(): string {
		return sprintf(
			'<div class="%s %2$s">
				<input type="checkbox" id="%2$s" value="" />
				<label for="%2$s" data-deselect="%3$s" data-select="%4$s">%4$s</label>
			</div>',
			esc_attr( 'gform-settings-choice' ),
			esc_attr( 'gk-gravityexport-meta-all' ),
			esc_attr__( 'Deselect All', 'gk-gravityexport-lite' ),
			esc_attr__( 'Select All', 'gk-gravityexport-lite' )
		);
	}

	/**
	 * Whether to use the admin labels as labels for the export.
	 * @since 2.1.0
	 * @return bool
	 */
	public function useAdminLabels(): bool {
		return apply_filters(
			'gk/gravityexport/settings/use-admin-labels',
			(bool) $this->get_plugin_setting( 'use_admin_label' )
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.2.0
	 */
	public function get_current_settings(): array {
		$settings = parent::get_current_settings();

		$feed = $this->get_feed( $this->get_default_feed_id( rgget( 'id' ) ) );
		if ( ! $feed ) {
			return $settings;
		}

		// Prevent hash from being overwritten with an input value.
		$settings['hash'] = rgars( $feed, 'meta/hash', $settings['hash'] ?? '' );

		return $settings;
	}
}
