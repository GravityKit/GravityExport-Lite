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

namespace GFExcel\\Analytics;

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
		'days_since_install' => [
			'type' => 'int',
		],
		'install_channel' => [
			'type' => 'enum',
			'enum' => 'install_channel',
		],
	];

	public const PROPS = [
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
			'upgrade_notice',
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
	];

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
