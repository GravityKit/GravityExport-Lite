<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;
use Gravity_Flow;

/**
 * Field transformer for Gravity Flow fields.
 *
 * @since $ver$
 */
final class FlowField extends BaseField implements RowsInterface {
	/**
	 * User property options.
	 *
	 * @since $ver$
	 */
	public const PROPERTY_USER_ID = 'user_id';
	public const PROPERTY_NICKNAME = 'nickname';
	public const PROPERTY_DISPLAY_NAME = 'display_name';

	/**
	 * Timestamp display type options.
	 *
	 * @since $ver$
	 */
	public const TIMESTAMP_GMT = 'gmt';
	public const TIMESTAMP_LOCAL = 'local';
	public const TIMESTAMP_RAW = 'timestamp';

	/**
	 * Cached user property name.
	 *
	 * @since $ver$
	 *
	 * @var string|null
	 */
	private $user_property_name;

	/**
	 * Cached translate role flag.
	 *
	 * @since $ver$
	 *
	 * @var bool|null
	 */
	private $translate_role;

	/**
	 * Cached timestamp type.
	 *
	 * @since $ver$
	 *
	 * @var string|null
	 */
	private $timestamp_type;

	/**
	 * Cached timestamp format.
	 *
	 * @since $ver$
	 *
	 * @var string|null
	 */
	private $timestamp_format;

	/**
	 * {@inheritdoc}
	 *
	 * Resolves the field value to a human-readable representation.
	 */
	public function getCells( $entry ) {
		$value = $this->get_raw_value( $entry );
		$value = $this->resolve_field_value( $value );
		$value = $this->filter_value( $value, $entry );

		return $this->wrap( [ $value ] );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function getRows( ?array $entry = null ): iterable {
		$entry = $entry ?? [];

		if ( $this->field->get_input_type() !== 'workflow_multi_user' ) {
			yield $this->getCells( $entry );

			return;
		}

		$value = $this->get_raw_value( $entry );

		foreach ( $this->resolve_multi_user( $value, true ) as $user_value ) {
			$user_value = $this->filter_value( $user_value, $entry );
			yield $this->wrap( [ $user_value ] );
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * Returns numeric type only for single user fields with the user_id property.
	 */
	public function getValueType() {
		if ( $this->is_timestamp_field() && $this->get_timestamp_type() === self::TIMESTAMP_RAW ) {
			return BaseValue::TYPE_NUMERIC;
		}

		if (
			$this->field->get_input_type() === 'workflow_user'
			&& $this->get_user_property_name() === self::PROPERTY_USER_ID
		) {
			return BaseValue::TYPE_NUMERIC;
		}

		return BaseValue::TYPE_STRING;
	}

	/**
	 * Returns the raw entry value, bypassing Gravity Flow's `get_value_export()`.
	 *
	 * Gravity Flow's export methods pre-process values (e.g., stripping the
	 * `type|id` format from assignee fields), which prevents our own resolution.
	 *
	 * @since $ver$
	 *
	 * @param array|null $entry The entry object.
	 *
	 * @return mixed The raw field value.
	 */
	private function get_raw_value( ?array $entry ) {
		return rgar( $entry ?? [], (string) $this->field->id );
	}

	/**
	 * Resolves the field value based on the Gravity Flow field type.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The raw field value.
	 *
	 * @return mixed The resolved value.
	 */
	private function resolve_field_value( $value ) {
		if ( $this->is_timestamp_field() ) {
			return $this->resolve_timestamp( $value );
		}

		if ( $this->is_status_field() ) {
			return $this->resolve_status( $value );
		}

		switch ( $this->field->get_input_type() ) {
			case 'workflow_user':
				return $this->resolve_user( $value );

			case 'workflow_multi_user':
				return $this->resolve_multi_user( $value, false );

			case 'workflow_assignee_select':
				return $this->resolve_assignee( $value );

			case 'workflow_role':
				return $this->resolve_role( $value );

			default:
				return $value;
		}
	}

	/**
	 * Resolves a single user ID to the configured user property.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The user ID.
	 *
	 * @return mixed The resolved user property value, or the original value if unresolvable.
	 */
	private function resolve_user( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$user_id  = (int) $value;
		$property = $this->get_user_property_name();

		if ( $property === self::PROPERTY_USER_ID ) {
			return $user_id;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return $value;
		}

		return $user->$property ?? $value;
	}

	/**
	 * Resolves a JSON-encoded array of user IDs to a comma-separated string.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The JSON-encoded array of user IDs.
	 * @param bool $as_array Whether the results should be returns as an array.
	 *
	 * @return string|array<int|string> The resolved comma-separated string, or the original value.
	 */
	private function resolve_multi_user( $value, bool $as_array ) {
		$user_ids = json_decode( $value, true );
		if ( ! is_array( $user_ids ) ) {
			return $as_array ? [ $value ] : (string) $value;
		}

		$resolved = array_map( [ $this, 'resolve_user' ], $user_ids );

		if ( $as_array ) {
			return $resolved;
		}

		return implode( ', ', $resolved );
	}

	/**
	 * Resolves an assignee select value in `type|id` format.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The assignee value (e.g., `user_id|123` or `role|administrator`).
	 *
	 * @return mixed The resolved value.
	 */
	private function resolve_assignee( $value ) {
		if ( empty( $value ) || ! is_string( $value ) || strpos( $value, '|' ) === false ) {
			return $value;
		}

		[ $type, $id ] = explode( '|', $value, 2 );

		switch ( $type ) {
			case 'user_id':
				return $this->resolve_user( $id );

			case 'role':
				return $this->resolve_role( $id );

			default:
				/**
				 * Resolves the value for an unknown assignee type.
				 *
				 * @since $ver$
				 *
				 * @param mixed     $value The raw assignee value.
				 * @param string    $type  The assignee type prefix.
				 * @param string    $id    The assignee identifier.
				 * @param \GF_Field $field The current field object.
				 */
				return gf_apply_filters(
					[
						'gk/gravityexport/field/flow/assignee-value',
						$this->field->formId,
						$this->field->id,
					],
					$value,
					$type,
					$id,
					$this->field
				);
		}
	}

	/**
	 * Resolves a role slug to a translated role name, or returns the slug as-is.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The role slug.
	 *
	 * @return string The resolved role value.
	 */
	private function resolve_role( $value ): string {
		$role_key = (string) $value;

		if ( ! $this->should_translate_role() ) {
			return $role_key;
		}

		$roles     = wp_roles()->roles;
		$role_name = $roles[ $role_key ]['name'] ?? $role_key;

		return translate_user_role( $role_name );
	}

	/**
	 * Returns whether the role value should be translated.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether to translate the role.
	 */
	private function should_translate_role(): bool {
		if ( $this->translate_role !== null ) {
			return $this->translate_role;
		}

		/**
		 * Controls whether Gravity Flow role field values are translated.
		 *
		 * By default, role slugs (e.g., `administrator`) are translated to their
		 * display name (e.g., "Administrator"). Return `false` to export the raw
		 * role key instead.
		 *
		 * Applies to `workflow_role` and the role portion of
		 * `workflow_assignee_select` fields.
		 *
		 * @since $ver$
		 *
		 * @param bool $translate Whether to translate the role. Default `true`.
		 * @param \GF_Field $field The current field object.
		 */
		$this->translate_role = (bool) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/translate-role',
				$this->field->formId,
				$this->field->id,
			],
			true,
			$this->field
		);

		return $this->translate_role;
	}

	/**
	 * Returns the user property name to use for export.
	 *
	 * @since $ver$
	 *
	 * @return string The property name.
	 */
	private function get_user_property_name(): string {
		if ( $this->user_property_name !== null ) {
			return $this->user_property_name;
		}

		/**
		 * Modifies the user property used to resolve Gravity Flow user field values.
		 *
		 * By default, the field exports the user ID. Use this filter to export
		 * a different property, such as `display_name` or `nickname`.
		 *
		 * Applies to `workflow_user`, `workflow_multi_user`, and the user portion
		 * of `workflow_assignee_select` fields.
		 *
		 * @since $ver$
		 *
		 * @param string $property The user property name. Default `user_id`.
		 * @param \GF_Field $field The current field object.
		 */
		$this->user_property_name = (string) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/user-property',
				$this->field->formId,
				$this->field->id,
			],
			self::PROPERTY_USER_ID,
			$this->field
		);

		return $this->user_property_name;
	}

	/**
	 * Returns whether the current field is a timestamp field.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the field ID ends in `_timestamp`.
	 */
	private function is_timestamp_field(): bool {
		return str_ends_with( (string) $this->field->id, '_timestamp' );
	}

	/**
	 * Returns whether the current field is a status field.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the field ID contains `_status`.
	 */
	private function is_status_field(): bool {
		return strpos( (string) $this->field->id, '_status' ) !== false;
	}

	/**
	 * Resolves a status value to a human-readable label.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The raw status value (e.g., `complete`, `pending`).
	 *
	 * @return string The resolved status label.
	 */
	private function resolve_status( $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$status = (string) $value;

		if ( class_exists( 'Gravity_Flow' ) ) {
			$gravity_flow = Gravity_Flow::get_instance();
			$label        = $gravity_flow->translate_status_label( $status );
		} else {
			$label = ucfirst( $status );
		}

		/**
		 * Controls the label for a Gravity Flow status value.
		 *
		 * @since $ver$
		 *
		 * @param string $label The translated status label.
		 * @param string $status The raw status value.
		 * @param \GF_Field $field The current field object.
		 */
		return (string) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/status-label',
				$this->field->formId,
				$this->field->id,
			],
			$label,
			$status,
			$this->field
		);
	}

	/**
	 * Resolves a unix timestamp to a human-readable date string.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The raw timestamp value.
	 *
	 * @return mixed The formatted date string, or the original value.
	 */
	private function resolve_timestamp( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$timestamp = (int) $value;
		$type      = $this->get_timestamp_type();

		if ( $type === self::TIMESTAMP_RAW ) {
			return $timestamp;
		}

		$format = $this->get_timestamp_format();

		if ( $type === self::TIMESTAMP_GMT ) {
			return gmdate( $format, $timestamp );
		}

		// Local time.
		$local_time = \GFCommon::get_local_timestamp( $timestamp );

		return date_i18n( $format, $local_time, true );
	}

	/**
	 * Returns the timestamp display type.
	 *
	 * @since $ver$
	 *
	 * @return string One of the `TIMESTAMP_*` constants.
	 */
	private function get_timestamp_type(): string {
		if ( $this->timestamp_type !== null ) {
			return $this->timestamp_type;
		}

		/**
		 * Controls how Gravity Flow timestamp values are displayed.
		 *
		 * Accepts `local` (default), `gmt`, or `timestamp` (raw unix timestamp).
		 *
		 * @since $ver$
		 *
		 * @param string $type The display type. Default `local`.
		 * @param \GF_Field $field The current field object.
		 */
		$this->timestamp_type = (string) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/timestamp-type',
				$this->field->formId,
				$this->field->id,
			],
			self::TIMESTAMP_LOCAL,
			$this->field
		);

		return $this->timestamp_type;
	}

	/**
	 * Returns the date format for timestamp output.
	 *
	 * @since $ver$
	 *
	 * @return string The date format string.
	 */
	private function get_timestamp_format(): string {
		if ( $this->timestamp_format !== null ) {
			return $this->timestamp_format;
		}

		/**
		 * Controls the date format for Gravity Flow timestamp values.
		 *
		 * Only applies when the timestamp type is `gmt` or `local`.
		 *
		 * @since $ver$
		 *
		 * @param string $format The date format. Default `Y-m-d H:i:s`.
		 * @param \GF_Field $field The current field object.
		 */
		$this->timestamp_format = (string) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/timestamp-format',
				$this->field->formId,
				$this->field->id,
			],
			'Y-m-d H:i:s',
			$this->field
		);

		return $this->timestamp_format;
	}
}
