<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

/**
 * Field transformer for Gravity Flow fields.
 *
 * Handles `workflow_user`, `workflow_multi_user`, `workflow_assignee_select`,
 * and `workflow_role` fields by resolving user IDs and assignee references
 * to human-readable values for export.
 *
 * @since $ver$
 */
class FlowField extends BaseField implements RowsInterface {
	/**
	 * User property options.
	 *
	 * @since $ver$
	 */
	public const PROPERTY_USER_ID = 'user_id';
	public const PROPERTY_NICKNAME = 'nickname';
	public const PROPERTY_DISPLAY_NAME = 'display_name';

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
				return $value;
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
		if ( $this->should_translate_role() ) {
			return translate_user_role( (string) $value );
		}

		return (string) $value;
	}

	/**
	 * Returns whether the role value should be translated.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether to translate the role.
	 */
	private function should_translate_role(): bool {
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
		return (bool) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/translate-role',
				$this->field->formId,
				$this->field->id,
			],
			true,
			$this->field
		);
	}

	/**
	 * Returns the user property name to use for export.
	 *
	 * @since $ver$
	 *
	 * @return string The property name.
	 */
	private function get_user_property_name(): string {
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
		return (string) gf_apply_filters(
			[
				'gk/gravityexport/field/flow/user-property',
				$this->field->formId,
				$this->field->id,
			],
			self::PROPERTY_USER_ID,
			$this->field
		);
	}
}
