<?php

namespace GFExcel\Addon;

use GFExcel\Action\ActionAware;
use GFExcel\Action\ActionAwareInterface;
use GFExcel\GFExcel;
use GFExcel\GravityForms\Field\DownloadUrl;
use GFExcel\GravityForms\Field\Sortable;
use GFExcel\Repository\FieldsRepository;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

/**
 * GravityExport Lite add-on.
 * @since $ver$
 */
class GFExcelAddon extends \GFFeedAddon implements AddonInterface, ActionAwareInterface {
	use ActionAware;
	use AddonTrait;
	use AddonHelperTrait;

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
	protected $_slug = 'gravityexport-lite';
	/**
	 * @since $ver$
	 * @var string Feed settings permissions.
	 */
	protected $_capabilities_form_settings = 'gravityforms_export_entries';
	/**
	 * @since $ver$
	 * @var array|null GF feed object.
	 */
	private $feed = [];

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function init_admin(): void {
		parent::init_admin();

		add_action( 'admin_enqueue_scripts', \Closure::fromCallable( [ $this, 'register_sortable_js' ] ) );
	}

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function feed_settings_fields(): array {
		// Register custom fields first.
		Fields::register( 'download_url', DownloadUrl::class );
		Fields::register( 'sortable', Sortable::class );

		$form = $this->get_current_form();

		$settings_sections[] = [
			'title'  => __( 'Download settings', GFExcel::$slug ),
			'fields' => [
				[
					'label' => esc_html__( 'Download URL', GFExcel::$slug ),
					'name'  => 'hash',
					'type'  => 'download_url',
				],
				[
					'label'         => esc_html__( 'Custom Filename', GFExcel::$slug ),
					'type'          => 'text',
					'name'          => 'custom_filename',
					'placeholder'   => sprintf( esc_html__( 'Default: %s', GFExcel::$slug ),
						GFExcel::getFilename( $form ) ),
					'class'         => 'medium code',
					'description'   => esc_html__(
						'Most non-alphanumeric characters will be replaced with hyphens. Leave empty for default.',
						'gk-gravityexport'
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
			'title'  => __( 'Security Settings', GFExcel::$slug ),
			'fields' => [
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

		$settings_sections[] = apply_filters(
			'gfexcel_general_settings',
			[
				'title'  => __( 'General Settings', GFExcel::$slug ),
				'fields' => [
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
				],
			]
		);

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

			'title'  => esc_html__( 'Field settings', GFExcel::$slug ),
			'fields' => [
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
									'label' => esc_html__( 'Ascending', 'gk-gravityexport' ),
								],
								[
									'value' => 'DESC',
									'label' => esc_html__( 'Descending', 'gk-gravityexport' ),
								],
							],
						];

						$this->settings_select( $sort_field );
						$this->settings_select( $sort_order );
					},
				],
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
	 * Returns the notification options list.
	 * @since $ver$
	 * @return mixed[] The notification options.
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
	 * @inheritdoc
	 * @since $ver$
	 */
	public function styles(): array {
		return array_merge( parent::styles(), [
			[
				'handle'  => 'gravityexport-lite',
				'src'     => $this->assets_dir . 'public/css/gravityexport-lite.css',
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
				'src'     => $this->assets_dir . 'public/js/gfexcel.js',
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
	 * Helper method to get the only available feed for this add-on.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array|null The feed.
	 */
	final public function get_feed_by_form_id( int $form_id = 0 ) {
		if ( ! $form_id ) {
			$form    = $this->get_current_form();
			$form_id = rgar( $form, 'id', 0 );
		}

		if ( ! isset( $this->feed[ $form_id ] ) ) {
			$feed_id                = $this->get_default_feed_id( $form_id );
			$this->feed[ $form_id ] = $this->get_feed( $feed_id );
		}

		return $this->feed[ $form_id ];
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
	 * @return array|null The field value.
	 */
	final public function get_feed_meta_field( string $field, int $form_id = 0, $default = null ) {
		$feed = $this->get_feed_by_form_id( $form_id );

		return rgars( $feed, sprintf( 'meta/%s', $field ), $default );
	}
}
