<?php

namespace GFExcel\Addon;

use GFExcel\Action\ActionAware;
use GFExcel\Action\ActionAwareInterface;
use GFExcel\Action\CountDownloads;
use GFExcel\Component\Usage;
use GFExcel\Field\ProductField;
use GFExcel\Field\SeparableField;
use GFExcel\GFExcel;
use GFExcel\GFExcelOutput;
use GFExcel\GravityForms\Field\DownloadFile;
use GFExcel\GravityForms\Field\DownloadUrl;
use GFExcel\GravityForms\Field\Sortable;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Repository\FormRepositoryInterface;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

/**
 * GravityExport Lite add-on.
 * @since $ver$
 */
final class GFExcelAddon extends \GFFeedAddon implements AddonInterface, ActionAwareInterface {
	use ActionAware;
	use AddonTrait;
	use AddonHelperTrait;

	/**
	 * Slug for bulk action download.
	 * @since $ver$
	 */
	private const BULK_DOWNLOAD = 'gk-download';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected $_multiple_feeds = false;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected $_title = 'GravityExport Lite (V2)';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected $_short_title = 'GravityExport Lite (V2)';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected $_slug = 'gf-entries-in-excel';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected $_version = GFEXCEL_PLUGIN_VERSION;

	/**
	 * @since $ver$
	 * @var string Feed settings permissions.
	 */
	protected $_capabilities_form_settings = 'gravityforms_export_entries';

	/**
	 * A micro cache for the feed object.
	 * @since $ver$
	 * @var array|null GF feed object.
	 */
	private $feed = [];

	/**
	 * The form repository.
	 * @since $ver$
	 * @var FormRepositoryInterface
	 */
	private $form_repository;

	/**
	 * The usage component.
	 * @since $ver$
	 * @var Usage
	 */
	private $component_usage;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function __construct( FormRepositoryInterface $form_repository ) {
		parent::__construct();

		$this->form_repository = $form_repository;
		$this->component_usage = new Usage();
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function init_admin(): void {
		parent::init_admin();

		add_action( 'admin_enqueue_scripts', \Closure::fromCallable( [ $this, 'register_sortable_js' ] ) );
		add_action( 'bulk_actions-toplevel_page_gf_edit_forms', \Closure::fromCallable( [ $this, 'bulk_actions' ] ) );
		add_action( 'wp_loaded', \Closure::fromCallable( [ $this, 'handle_bulk_actions' ] ) );
		add_filter( 'gform_form_actions', \Closure::fromCallable( [ $this, 'gform_form_actions' ] ), 10, 2 );
		add_filter( 'gform_post_form_duplicated', \Closure::fromCallable( [ $this, 'refresh_download_data' ] ), 10, 2 );
		add_filter( 'wp_before_admin_bar_render', \Closure::fromCallable( [ $this, 'admin_bar' ] ), 20 );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function feed_settings_fields(): array {
		// Register custom fields first.
		Fields::register( 'download_file', DownloadFile::class );
		Fields::register( 'download_url', DownloadUrl::class );
		Fields::register( 'sortable', Sortable::class );

		$form = $this->get_current_form();

		// Only show
		if ( ! $this->get_setting( 'hash' ) ) {
			$settings_sections[] = [
				'title'  => __( 'Activate GravityExport', GFExcel::$slug ),
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

		$settings_sections[] = [
			'id'          => 'gk-gravity-export-download',
			'title'       => __( 'Download settings', GFExcel::$slug ),
			'collapsible' => true,
			'fields'      => [
				[
					'label'      => esc_html__( 'Download URL', GFExcel::$slug ),
					'name'       => 'hash',
					'type'       => 'download_url',
					'assets_dir' => $this->assets_dir,
				],
				[
					'label'         => esc_html__( 'Custom Filename', GFExcel::$slug ),
					'type'          => 'text',
					'name'          => 'custom_filename',
					'placeholder'   => sprintf(
						esc_html__( 'Default: %s', GFExcel::$slug ),
						GFExcel::getFilename( $form )
					),
					'class'         => 'medium code',
					'description'   => esc_html__(
						'Most non-alphanumeric characters will be replaced with hyphens. Leave empty for default.',
						GFExcel::$slug
					),
					'save_callback' => function ( $field, $value ) {
						return sanitize_file_name( $value );
					},
				],
				[
					'label'       => esc_html__( 'File Extension', GFExcel::$slug ),
					'type'        => 'select',
					'name'        => 'file_extension',
					'class'       => 'small-text',
					'description' => sprintf(
						esc_html__(
							'Note: You may override the file type by adding the desired extension (%s) to the end of the Download URL.',
							GFExcel::$slug
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
			'id'     => 'gk-section-download-file',
			'title'  => __( 'Download File', GFExcel::$slug ),
			'fields' => [
				[
					'name'    => 'download_file',
					'label'   => esc_html__( 'Select Date Range (optional)', GFExcel::$slug ),
					'tooltip' => 'export_date_range',
					'type'    => 'download_file',
					'url'     => $this->form_repository->getDownloadUrl( $this->get_current_settings() ),
				],
				[
					'name'  => 'download_count',
					'label' => esc_html__( 'Download Count', GFExcel::$slug ),
					'type'  => 'download_count',
				],
			],
		];

		$settings_sections[] = [
			'id'          => 'gk-section-security',
			'collapsible' => true,
			'title'       => __( 'Security Settings', GFExcel::$slug ),
			'fields'      => [
				[
					'name'          => 'is_secured',
					'label'         => esc_html__( 'Download Permissions', GFExcel::$slug ),
					'type'          => 'select',
					'description'   => sprintf(
						esc_html__(
							'If set to "Everyone can download", anyone with the link can download. If "Logged-in users who have \'Export Entries\' access" is selected, users must be logged-in and have the %s capability.',
							GFExcel::$slug
						),
						'<code>gravityforms_export_entries</code>'
					),
					'default_value' => 0,
					'choices'       => ( static function (): array {
						$options = [];
						if ( ! GFExcel::isAllSecured() ) {
							$options[] = [
								'name'  => 'is_secured',
								'label' => __( 'Everyone can download', GFExcel::$slug ),
								'value' => 0,
							];
						}
						$options[] = [
							'name'  => 'is_secured',
							'label' => __( 'Logged-in users who have "Export Entries" access', GFExcel::$slug ),
							'value' => 1,
						];

						return $options;
					} )(),
				],
			],
		];

		$settings_sections = array_merge( $settings_sections, apply_filters(
			'gfexcel_general_settings',
			[
				[
					'id'          => 'gk-general-security',
					'collapsible' => true,
					'title'       => __( 'General Settings', GFExcel::$slug ),
					'fields'      => [
						[
							'name'    => 'enable_notes',
							'label'   => esc_html__( 'Include Entry Notes', GFExcel::$slug ),
							'type'    => 'checkbox',
							'choices' => [
								[
									'name'  => 'enable_notes',
									'label' => esc_html__( 'Yes, enable the notes for every entry', GFExcel::$slug ),
									'value' => '1',
								],
							],
						],
						[
							'label'   => esc_html__( 'Attach Single Entry to Notification', GFExcel::$slug ),
							'type'    => 'select',
							'name'    => 'attachment_notification',
							'choices' => $this->getNotifications(),
						],
						[
							'name'          => 'is_transposed',
							'type'          => 'radio',
							'label'         => esc_html__( 'Column Position', GFExcel::$slug ),
							'default_value' => 0,
							'choices'       => [
								[
									'name'  => 'is_transposed',
									'label' => esc_html__( 'At the top (normal)', GFExcel::$slug ),
									'value' => 0,
								],
								[
									'name'  => 'is_transposed',
									'label' => esc_html__( 'At the left (transposed)', GFExcel::$slug ),
									'value' => 1,
								],
							],
						],
						[
							'name'     => 'order_by',
							'label'    => esc_html__( 'Order By', GFExcel::$slug ),
							'type'     => 'callback',
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
											'label' => esc_html__( 'Ascending', GFExcel::$slug ),
										],
										[
											'value' => 'DESC',
											'label' => esc_html__( 'Descending', GFExcel::$slug ),
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
		) );

		$form            = $this->get_current_form();
		$feed_id         = $this->get_default_feed_id( rgar( $form, 'id', 0 ) );
		$repository      = new FieldsRepository( $form, $this->get_feed( $feed_id ) ?: [] );
		$disabled_fields = $repository->getDisabledFields();
		$all_fields      = $repository->getFields( true );

		$active_fields = $inactive_fields = [];
		foreach ( $all_fields as $field ) {
			$array_name      = in_array( $field->id, $disabled_fields, false ) ? 'inactive_fields' : 'active_fields';
			${$array_name}[] = $field;
		}

		$active_fields = $repository->sortFields( $active_fields );

		$settings_sections[] = [
			'id'          => 'gk-section-fields',
			'collapsible' => true,
			'title'       => esc_html__( 'Field settings', GFExcel::$slug ),
			'fields'      => [
				[
					'name'   => 'sortfields',
					'type'   => 'html',
					'html'   => sprintf(
						'<p>%s</p>',
						esc_html__( 'Drag & drop fields to re-order them in the exported file.', GFExcel::$slug )
					),
					'fields' => [
						[
							'label'   => esc_html__( 'Disabled fields', GFExcel::$slug ),
							'name'    => 'disabled_fields',
							'move_to' => 'enabled_fields',
							'type'    => 'sortable',
							'class'   => 'fields-select',
							'side'    => 'left',
							'choices' => array_map( function ( \GF_Field $field ) {
								return [
									'value' => $field->id,
									'label' => $this->get_field_label( $field ),
								];
							}, $inactive_fields ),
						],
						[
							'label'   => esc_html__( 'Enable & sort the fields', GFExcel::$slug ),
							'name'    => 'enabled_fields',
							'move_to' => 'disabled_fields',
							'type'    => 'sortable',
							'class'   => 'fields-select',
							'side'    => 'right',
							'choices' => array_map( function ( \GF_Field $field ) {
								return [
									'value' => $field->id,
									'label' => $this->get_field_label( $field ),
								];
							}, $active_fields ),
						],
					],
				],
			],
		];

		return $settings_sections;
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function get_menu_icon(): string {
		return '<svg style="height: 24px; width: 37px;" enable-background="new 0 0 226 148" height="148" viewBox="0 0 226 148" width="226" xmlns="http://www.w3.org/2000/svg"><path d="m176.8 118.8c-1.6 1.6-4.1 1.6-5.7 0l-5.7-5.7c-1.6-1.6-1.6-4.1 0-5.7l27.6-27.4h-49.2c-4.3 39.6-40 68.2-79.6 63.9s-68.2-40-63.9-79.6 40.1-68.2 79.7-63.9c25.9 2.8 48.3 19.5 58.5 43.5.6 1.5-.1 3.3-1.7 3.9-.4.1-.7.2-1.1.2h-9.9c-1.9 0-3.6-1.1-4.4-2.7-14.7-27.1-48.7-37.1-75.8-22.4s-37.2 48.8-22.4 75.9 48.8 37.2 75.9 22.4c15.5-8.4 26.1-23.7 28.6-41.2h-59.4c-2.2 0-4-1.8-4-4v-8c0-2.2 1.8-4 4-4h124.7l-27.5-27.5c-1.6-1.6-1.6-4.1 0-5.7l5.7-5.7c1.6-1.6 4.1-1.6 5.7 0l41.1 41.2c3.1 3.1 3.1 8.2 0 11.3z"/></svg>';
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function plugin_settings_icon(): string {
		return $this->get_menu_icon();
	}

	/**
	 * Returns the feed settings for this form for backwards compatibility.
	 * @since $ver$
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
	 * @since $ver$
	 */
	public function plugin_settings_fields(): array {
		$settings_sections = [];

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

		$settings_sections[] = [
			'title'       => esc_html__( 'Default Settings', GFExcel::$slug ),
			'description' => $this->plugin_settings_description(),
			'fields'      => [
				[
					'name'    => 'field_separate',
					'label'   => esc_html__( 'Multiple Columns', GFExcel::$slug ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label' => esc_html__(
								'Split multi-fields (name, address) into multiple columns',
								GFExcel::$slug
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
							'label'         => esc_html__( 'Enable notes by default', GFExcel::$slug ),
							'name'          => 'notes_enabled',
							'default_value' => false,
						],
					],
				],
				[
					'name'    => 'sections',
					'label'   => esc_html__( 'Sections', GFExcel::$slug ),
					'type'    => 'checkbox',
					'choices' => [
						[
							'label'         => esc_html__( 'Enable (empty) section column', GFExcel::$slug ),
							'name'          => 'sections_enabled',
							'default_value' => false,
						],
					],
				],
				[
					'name'  => 'fileuploads',
					'label' => esc_html__( 'File Uploads', GFExcel::$slug ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__( 'Enable file upload columns', GFExcel::$slug ),
							'name'          => 'fileuploads_enabled',
							'default_value' => true,
						],
					],
				],
				[
					'name'  => 'hyperlinks',
					'label' => esc_html__( 'Hyperlinks', GFExcel::$slug ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__( 'Enable hyperlinks on URL-only columns', GFExcel::$slug ),
							'name'          => 'hyperlinks_enabled',
							'default_value' => true,
						],
					],
				],
				[
					'name'  => 'products_price',
					'label' => esc_html__( 'Product Fields', GFExcel::$slug ),
					'type'  => 'checkbox',

					'choices' => [
						[
							'label'         => esc_html__(
								'Export prices as numeric fields, without currency symbol ($)',
								GFExcel::$slug
							),
							'name'          => ProductField::SETTING_KEY,
							'default_value' => false,
						],
					],
				],
			],
		];

		$settings_sections[] = [
			'title'  => esc_html__( 'Default Enabled Meta Fields', GFExcel::$slug ),
			'fields' => [
				[
					'name'        => 'enabled_metafields',
					'description' => esc_html__(
						'Select all meta fields that are enabled by default. Once you\'ve saved your form, these settings will not do anything any more.',
						GFExcel::$slug
					),
					'type'        => 'checkbox',
					'choices'     => $this->meta_fields(),
				],
			],
		];

		if ( ! class_exists( 'GravityKit\GravityExport\GravityExport' ) ) {
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
	 * @since $ver$
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
	 * @since $ver$
	 * @return array The notification options.
	 */
	private function getNotifications(): array {
		$options       = [ [ 'label' => __( 'Select a Notification', GFExcel::$slug ), 'value' => '' ] ];
		$notifications = $this->get_current_form()['notifications'] ?? [];
		foreach ( $notifications as $key => $notification ) {
			$options[] = [ 'label' => \rgar( $notification, 'name', __( 'Unknown' ) ), 'value' => $key ];
		}

		return $options;
	}

	/**
	 * Returns the rating message.
	 * @since $ver$
	 * @return string The message.
	 */
	public function get_rating_message(): string {
		ob_start();
		?>
        <div id="gravityexport-lite-rating" class="wrap gravityexport-lite-callout">
            <p><?php
				printf( ' ' . esc_html__(
						'If you like the plugin, 📣 %slet others know%s! We already have %s active users. Let\'s get to %s by spreading the news!',
						GFExcel::$slug
					),
					'<strong><a href="https://wordpress.org/support/plugin/gf-entries-in-excel/reviews/?filter=5#new-post" target="_blank" title="' . esc_attr__( 'This link opens in a new window',
						GFExcel::$slug ) . '">',
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
	 * @since $ver$
	 * @return string The settings description.
	 */
	private function plugin_settings_description(): string {
		return sprintf(
			'<p>%s</p>',
			esc_html__(
				'These are global settings for new forms. You can overwrite them per form using the available hooks.',
				GFExcel::$slug
			) );
	}

	/**
	 * Returns a nice upgrade message to the Pro version.
	 * @since $ver$
	 * @return string The upgrade message.
	 */
	private function get_gravityexport_message(): string {
		ob_start();
		?>
        <div id="gravityexport-additional-features" class="wrap gravityexport-lite-callout">
            <h2><?php
				esc_html_e( 'Upgrade to GravityExport for these useful features:', GFExcel::$slug ); ?></h2>

            <div>
                <h3><?php
					esc_html_e( 'Save exports to Dropbox, FTP, &amp; local storage', GFExcel::$slug ); ?> 💾</h3>
                <p><?php
					esc_html_e( 'Automatically upload exports to Dropbox, a remote server using SFTP and FTP, or store locally.',
						GFExcel::$slug ); ?></p>
            </div>

            <div>
                <h3><?php
					esc_html_e( 'Filter exports with Conditional Logic', GFExcel::$slug ); ?> 😎</h3>
                <p><?php
					esc_html_e( 'Create advanced filters, including exporting entries created by only the currently logged-in user.',
						GFExcel::$slug ); ?></p>
            </div>

            <div>
                <h3><?php
					esc_html_e( 'Exports are ready for data analysis', GFExcel::$slug ); ?> 📊</h3>
                <p><?php
					esc_html_e( 'When analyzing data, you want fields with multiple values broken into multiple rows each with one value. If you work with data, you&rsquo;ll love this feature!',
						GFExcel::$slug ); ?></p>
            </div>

            <p>
                <a class="button button-hero button-cta" href="https://gravityview.co/extensions/gravityexport/"
                   target="_blank" title="<?php
				esc_attr_e( 'This link opens in a new window', GFExcel::$slug ); ?>"><?php
					esc_html_e( 'Gain Powerful Features with GravityExport', GFExcel::$slug ); ?>️</a>
            </p>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function styles(): array {
		return array_merge( parent::styles(), [
			[
				'handle'  => 'gravityexport-lite',
				'src'     => $this->assets_dir . 'css/gravityexport-lite.css',
				'enqueue' => [
					[ 'admin_page' => 'form_settings', 'tab' => $this->get_slug() ],
					[ 'admin_page' => 'plugin_settings', 'tab' => $this->get_slug() ],
				],
			],
		] );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function scripts(): array {
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
				'handle'  => 'gfexcel-js',
				'src'     => $this->assets_dir . 'js/gfexcel.js',
				'enqueue' => [
					[
						'admin_page' => 'form_settings',
						'tab'        => $this->get_slug(),
					],
				],
				'deps'    => [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ],
			],
		] );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function save_feed_settings( $feed_id, $form_id, $settings ) {
		// In GF 2.5., $_POST must contain 'gform-settings-save' variable no matter what its value is.
		$action = rgpost( 'gform-settings-save' );

		if ( $this->hasAction( $action ) ) {
			// Prevent indefinite loop in case action's fire() method calls save_feed_settings().
			unset( $_POST['gform-settings-save'] );

			$this->getAction( $action )->fire( $this, [ $feed_id, $form_id, $settings ] );

			return $feed_id;
		}

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function settings_select( $field, $echo = true ): string {
		return parent::settings_select( $field, $echo );
	}

	/**
	 * Retrieves the formatted label for a field.
	 * @since $ver$
	 *
	 * @param \GF_Field $field The field.
	 *
	 * @return string The formatted label.
	 */
	private function get_field_label( \GF_Field $field ): string {
		return gf_apply_filters(
			[
				'gfexcel_field_label',
				$field->get_input_type(),
				$field->formId,
				$field->id,
			],
			$field->get_field_label( true, '' ),
			$field
		);
	}

	/**
	 * Add sortable javascript to the page.
	 * @since $ver$
	 */
	private function register_sortable_js(): void {
		if ( 'gf_edit_forms' !== rgget( 'page' ) || $this->get_slug() !== rgget( 'subview' ) ) {
			return;
		}

		wp_add_inline_script(
			'gfexcel-js',
			sprintf(
				'(function($) { $(document).ready(function() { gfexcel_sortable(\'%s\', \'%s\'); }); })(jQuery);',
				'#enabled_fields, #disabled_fields',
				'fields-select'
			)
		);
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten to add custom after-render hook.
	 *
	 * @since $ver$
	 */
	public function feed_edit_page( $form, $feed_id ) {
		parent::feed_edit_page( $form, $feed_id );

		do_action( 'gk-gravityexport-after_feed_edit_page', $form, $feed_id );
	}

	/**
	 * Helper method to get the only available feed for this add-on.
	 *
	 * @since $ver$
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
			$feed_id                = $this->get_default_feed_id( $form_id );
			$this->feed[ $form_id ] = $this->get_feed( $feed_id ) ?: null;
		}

		return $this->feed[ $form_id ] ?? null;
	}

	/**
	 * Helper method to return the value of a feed meta field.
	 *
	 * @since $ver$
	 *
	 * @param string $field The name of the meta field.
	 * @param int $form_id The form id.
	 * @param null $default The default value.
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
			$actions[ self::BULK_DOWNLOAD ] = esc_html__( 'Download as one file', GFExcel::$slug );
		}

		return $actions;
	}

	/**
	 * /**
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

		foreach ( $form_ids as $form_id ) {
			$feed   = $this->get_feed_by_form_id( $form_id );
			$output = new GFExcelOutput( (int) $form_id, $renderer, null, $feed['id'] ?? null );
			$output->render();
		}

		$renderer->renderOutput();
	}

	/**
	 * {@see \GFFeedAddOn::get_bulk_action()}.
	 * @since $ver$
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
		if ( $url = GFExcel::url( $form_id ) ) {

			$form_actions['download'] = [
				'label'      => __( 'Download', GFExcel::$slug ),
				'title'      => __( 'Download an Export', GFExcel::$slug ),
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
	private static function admin_bar(): void {
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
			if ( $url = GFExcel::url( $id ) ) {
				$wp_admin_bar->add_node( [
					'id'     => 'gfexcel-form-' . $id . '-download',
					'parent' => 'gform-form-' . $id,
					'title'  => esc_html__( 'Download', GFExcel::$slug ),
					'href'   => trailingslashit( esc_url( $url ) ),
				] );
			}
		}
	}

	/**
	 * Updates download data for a duplicated form.
	 * @since 1.7.0
	 *
	 * @param int $form_id the ID of the duplicated form
	 * @param int $new_id the ID of the new form.
	 */
	private function refresh_download_data( int $form_id, int $new_id ): void {
		// new hash to prevent doubles.
		$feed_old = $this->get_feed_by_form_id($form_id);

        // todo: duplicate feed for this add-on. ANy other add-on should not be dupicated.


		// reset the download counter
		//do_action( 'gfexcel_action_' . CountDownloads::ACTION_RESET, $new_id );
	}
}
