<?php
/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Source:    analytics-schema/schema/v1/analytics-schema.json
 * Generator: analytics-schema/build/generate-php.php
 *
 * Regenerate with `composer analytics:generate`. CI fails if this file differs
 * from a fresh generation, which is what stops Lite and Foundation drifting.
 */

namespace GFExcel\Analytics;

/**
 * The analytics wire contract, as constants.
 *
 * @since $ver$
 */
final class Schema {
	public const SCHEMA_VERSION = 1;

	public const EVENTS = [
		'product_activated',
		'product_deactivated',
		'gf_form_connected',
		'analytics_opt_in',
		'object_created',
		'object_configured',
		'object_published',
		'object_embedded',
		'object_rendered',
		'feature_enabled',
		'job_started',
		'onboarding_started',
		'onboarding_step_viewed',
		'onboarding_completed',
		'onboarding_dismissed',
		'onboarding_task_completed',
		'sample_created',
		'cta_clicked',
		'video_played',
		'inline_edit_saved',
		'card_moved',
		'card_voted',
		'export_completed',
		'import_completed',
		'bulk_action_completed',
		'revision_recorded',
		'revision_history_viewed',
		'revision_restored',
		'migration_export_completed',
		'migration_import_completed',
	];

	public const SUPER_PROPS = [
		'environment' => [
			'type' => 'enum',
			'enum' => 'environment',
		],
		'site_id' => [
			'type' => 'hash',
		],
		'gk_analytics_schema_version' => [
			'type' => 'int',
		],
		'wp_version' => [
			'type' => 'string',
		],
		'gf_version' => [
			'type' => 'string',
			'nullable' => true,
		],
		'php_version' => [
			'type' => 'string',
		],
		'locale' => [
			'type' => 'string',
		],
		'is_multisite' => [
			'type' => 'bool',
		],
		'license_tier' => [
			'type' => 'enum',
			'enum' => 'license_tier',
		],
		'user_role' => [
			'type' => 'string',
		],
		'gk_product' => [
			'type' => 'enum',
			'enum' => 'gk_product',
		],
		'gk_product_version' => [
			'type' => 'string',
		],
		'trigger_source' => [
			'type' => 'enum',
			'enum' => 'trigger_source',
		],
		'activated_product' => [
			'type' => 'enum',
			'enum' => 'gk_product',
			'nullable' => true,
		],
		'is_activation_event' => [
			'type' => 'bool',
		],
		'funnel_stage' => [
			'type' => 'enum',
			'enum' => 'funnel_stage',
		],
		'install_channel' => [
			'type' => 'enum',
			'enum' => 'install_channel',
		],
		'mysql_version' => [
			'type' => 'string',
		],
		'theme' => [
			'type' => 'enum',
			'enum' => 'theme',
		],
		'is_block_theme' => [
			'type' => 'bool',
		],
		'theme_is_child' => [
			'type' => 'bool',
		],
		'plugin_count_bucket' => [
			'type' => 'enum',
			'enum' => 'scale_bucket',
		],
	];

	public const PROPS = [
		'link_campaign' => [
			'type' => 'enum',
			'enum' => 'link_campaign',
		],
		'file_format' => [
			'type' => 'enum',
			'enum' => 'file_format',
		],
		'column_count' => [
			'type' => 'int',
		],
		'download_permission' => [
			'type' => 'enum',
			'enum' => 'download_permission',
		],
		'filter_set_count' => [
			'type' => 'int',
		],
		'save_destination' => [
			'type' => 'enum',
			'enum' => 'save_destination',
		],
		'schedule_enabled' => [
			'type' => 'bool',
		],
		'object_type' => [
			'type' => 'enum',
			'enum' => 'object_type',
		],
		'feature' => [
			'type' => 'enum',
			'enum' => 'feature',
		],
		'config_kind' => [
			'type' => 'enum',
			'enum' => 'config_kind',
		],
		'cta_id' => [
			'type' => 'enum',
			'enum' => 'cta_id',
		],
		'consent_source' => [
			'type' => 'enum',
			'enum' => 'consent_source',
		],
		'has_gravity_pdf' => [
			'type' => 'bool',
		],
		'has_zapier' => [
			'type' => 'bool',
		],
		'has_gravity_perks' => [
			'type' => 'bool',
		],
		'has_gravity_flow' => [
			'type' => 'bool',
		],
		'has_woocommerce' => [
			'type' => 'bool',
		],
		'has_other_form_plugin' => [
			'type' => 'bool',
		],
		'has_elementor' => [
			'type' => 'bool',
		],
		'has_wpbakery' => [
			'type' => 'bool',
		],
		'has_beaver_builder' => [
			'type' => 'bool',
		],
		'has_gravityview' => [
			'type' => 'bool',
		],
		'has_gravitycharts' => [
			'type' => 'bool',
		],
		'has_gravityimport' => [
			'type' => 'bool',
		],
	];

	public const ENUMS = [
		'environment' => [
			'demo',
			'production',
		],
		'license_tier' => [
			'none',
			'free',
			'core',
			'pro',
			'all_access',
			'agency',
		],
		'trigger_source' => [
			'real',
			'walkthrough',
			'sample',
		],
		'funnel_stage' => [
			'installed',
			'setup',
			'activated',
			'habit',
		],
		'install_channel' => [
			'wordpress_org',
			'gravitykit_com',
			'unknown',
		],
		'link_campaign' => [
			'upgrade',
			'docs',
			'support',
		],
		'link_medium' => [
			'plugin',
			'frontend',
		],
		'scale_bucket' => [
			'0',
			'1',
			'2-5',
			'6-10',
			'11-25',
			'26-100',
			'101-1000',
			'1001-10000',
			'10001-50000',
			'50001-250000',
			'250001-1000000',
			'1000000+',
		],
		'theme' => [
			'divi',
			'hello_elementor',
			'astra',
			'avada',
			'beaver_builder',
			'buddyboss',
			'generatepress',
			'betheme',
			'enfold',
			'oceanwp',
			'kadence',
			'blocksy',
			'genesis',
			'bricks',
			'flatsome',
			'twenty',
			'other',
		],
		'gk_product' => [
			'gravityexport',
			'gravityexport-lite',
		],
		'object_type' => [
			'export',
		],
		'file_format' => [
			'xlsx',
			'csv',
			'pdf',
		],
		'download_permission' => [
			'everyone',
			'logged_in',
			'capability',
		],
		'save_destination' => [
			'server',
			'dropbox',
			'ftp',
		],
		'feature' => [
			'filter_set',
			'scheduled_save',
		],
		'config_kind' => [
			'fields',
			'columns',
		],
		'cta_id' => [
			'plugin_meta_docs',
			'plugin_meta_upgrade',
			'feed_settings_upgrade',
			'feed_settings_notification_docs',
			'feed_settings_file_access_docs',
			'consent_card',
		],
		'consent_source' => [
			'lite_settings_card',
		],
	];

	public const ACTIVATION = [
		'gravityexport-lite' => [
			'export_completed',
			null,
			null,
			null,
		],
		'gravityexport' => [
			'export_completed',
			null,
			null,
			null,
		],
	];

	public const IDENTITY = [
		'salt_option' => 'gk_analytics_site_salt',
		'hmac_algo' => 'sha256',
		'site_id_input' => 'home_url',
		'url_normalization' => 'strip_trailing_slash_and_scheme_case',
		'multisite_scope' => 'per_site',
		'refuse_on_no_salt' => true,
	];

	public const CONSENT = [
		'option' => 'gk_analytics_consent',
		'record' => [
			'granted',
			'ts',
			'source',
			'schema_version',
			'disclosure_hash',
		],
	];

	public const ATTRIBUTION = [
		'salt_option' => 'gk_attribution_salt',
	];

	public const TRANSPORT = [
		'host_constant' => 'GK_ANALYTICS_HOST',
		'host_filter' => 'gk/analytics/host',
		'default_host' => 'understand.gravitykit.com',
		'path' => '/capture',
		'max_queue' => 50,
		'timeout' => 5,
	];

	public const SCRUB = [
		'free_text_cutoff' => 200,
		'drop_key_patterns' => [
			'^\\$',
		],
		'redact_value_patterns' => [
			'email' => '/[\\w.+-]+@[\\w-]+\\.[\\w.-]+/',
			'url' => '/https?:\\/\\/[^\\s]+/i',
		],
		'min_site_name_length' => 4,
	];

	public const PRIVACY = [
		'proxy_requirement' => 'understand.gravitykit.com MUST discard the connecting IP before forwarding to PostHog, MUST NOT set or forward X-Forwarded-For / X-Real-IP, and MUST NOT enable GeoIP enrichment. This is the ONLY control; there is no plugin-side half. Until it exists and is tested, the consent card\'s anonymity claim is unproven and should not be described as anything else.',
	];

	public const LINKS = [
		'host_allowlist' => [
			'https://www.gravitykit.com/',
		],
		'content_is_cta_id' => true,
		'utm_medium_default' => 'plugin',
		'utm_source' => 'gravityexport-lite',
		'destinations' => [
			'upgrade' => 'https://www.gravitykit.com/products/gravityexport/',
			'docs' => 'https://www.gravitykit.com/docs/gravityexport-lite/',
			'docs_notification_attachment' => 'https://www.gravitykit.com/docs/gravityexport/attaching-an-entry-export-to-a-notification-using-gravityexport-lite/',
			'docs_file_access' => 'https://www.gravitykit.com/docs/gravityexport/restricting-file-access-in-gravityexport-gravityexport-lite/',
		],
	];

	public const PLUGIN_PROPS = [
		'has_gravity_pdf' => [
			'group' => 'export_alternatives',
			'paths' => [
				'gravity-forms-pdf-extended/pdf.php',
				'gravity-pdf/pdf.php',
			],
		],
		'has_zapier' => [
			'group' => 'export_alternatives',
			'paths' => [
				'gravityformszapier/zapier.php',
			],
		],
		'has_gravity_perks' => [
			'group' => 'ecosystem',
			'paths' => [
				'gravityperks/gravityperks.php',
			],
		],
		'has_gravity_flow' => [
			'group' => 'ecosystem',
			'paths' => [
				'gravityflow/gravityflow.php',
			],
		],
		'has_woocommerce' => [
			'group' => 'ecosystem',
			'paths' => [
				'woocommerce/woocommerce.php',
			],
		],
		'has_other_form_plugin' => [
			'group' => 'form_platform',
			'paths' => [
				'wpforms/wpforms.php',
				'wpforms-lite/wpforms.php',
				'ninja-forms/ninja-forms.php',
				'formidable/formidable.php',
				'fluentform/fluentform.php',
				'forminator/forminator.php',
			],
		],
		'has_elementor' => [
			'group' => 'page_builders',
			'paths' => [
				'elementor/elementor.php',
			],
		],
		'has_wpbakery' => [
			'group' => 'page_builders',
			'paths' => [
				'js_composer/js_composer.php',
			],
		],
		'has_beaver_builder' => [
			'group' => 'page_builders',
			'paths' => [
				'beaver-builder-lite-version/fl-builder.php',
				'bb-plugin/fl-builder.php',
			],
		],
		'has_gravityview' => [
			'group' => 'family',
			'paths' => [
				'gravityview/gravityview.php',
				'GravityView/gravityview.php',
			],
		],
		'has_gravitycharts' => [
			'group' => 'family',
			'paths' => [
				'gravitycharts/gravitycharts.php',
			],
		],
		'has_gravityimport' => [
			'group' => 'family',
			'paths' => [
				'gravityimport/gravityimport.php',
			],
		],
	];

	public const THEMES = [
		'divi' => [
			'Divi',
		],
		'hello_elementor' => [
			'hello-elementor',
		],
		'astra' => [
			'astra',
		],
		'avada' => [
			'Avada',
		],
		'beaver_builder' => [
			'bb-theme',
			'beaver-builder-theme',
		],
		'buddyboss' => [
			'buddyboss-theme',
		],
		'generatepress' => [
			'generatepress',
		],
		'betheme' => [
			'betheme',
		],
		'enfold' => [
			'enfold',
		],
		'oceanwp' => [
			'oceanwp',
		],
		'kadence' => [
			'kadence',
		],
		'blocksy' => [
			'blocksy',
		],
		'genesis' => [
			'genesis',
		],
		'bricks' => [
			'bricks',
		],
		'flatsome' => [
			'flatsome',
		],
		'twenty' => [
			'twentytwenty',
			'twentytwentyone',
			'twentytwentytwo',
			'twentytwentythree',
			'twentytwentyfour',
			'twentytwentyfive',
			'twentyseventeen',
			'twentynineteen',
			'twentysixteen',
		],
	];

	public const PRODUCT = 'gravityexport-lite';

	/**
	 * Returns the legal values for a closed enum, or null when the name is not a closed enum.
	 *
	 * @since $ver$
	 *
	 * @param string $enum The enum name.
	 *
	 * @return string[]|null The legal values.
	 */
	public static function enum( string $enum ): ?array {
		return self::ENUMS[ $enum ] ?? null;
	}

	/**
	 * Returns the closed-enum name backing a property, or null when the property is not enum-typed.
	 *
	 * @since $ver$
	 *
	 * @param string $prop The property key.
	 *
	 * @return string|null The enum name.
	 */
	public static function enumForProp( string $prop ): ?string {
		$spec = self::PROPS[ $prop ] ?? self::SUPER_PROPS[ $prop ] ?? null;

		return $spec['enum'] ?? null;
	}
}
