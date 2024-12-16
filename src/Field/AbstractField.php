<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;
use Throwable;

/**
 * @since 1.0.0
 */
abstract class AbstractField implements FieldInterface {
	/**
	 * Holds the current GF_Field instance.
	 * @since 1.0.0
	 * @var \GF_Field
	 */
	protected $field;

	/**
	 * AbstractField constructor.
	 * @since 1.0.0
	 *
	 * @param \GF_Field $field
	 */
	public function __construct( \GF_Field $field ) {
		$this->field = $field;

		// Make sure the field can export the admin label.
		if ( $this->useAdminLabels() ) {
			$field->set_context_property( 'use_admin_label', true );
		}
	}

	/**
	 * Array of needed column names for this field.
	 * @since 1.0.0
	 * @return BaseValue[]
	 */
	public function getColumns() {
		$label = gf_apply_filters( [
			'gfexcel_field_label',
			$this->field->get_input_type(),
			$this->field->formId,
			$this->field->id
		], $this->field->get_field_label( ! $this->useAdminLabels(), '' ), $this->field );

		return $this->wrap( [ $label ], true );
	}

	/**
	 * Array of needed cell values for this field (a row).
	 * @since 1.0.0
	 *
	 * @param array $entry The entry object.
	 *
	 * @return BaseValue[] A single row.
	 */
	abstract public function getCells( $entry );

	/**
	 * Get the type of this value object
	 * @since 1.3.0
	 * @return string The type of the value.
	 */
	public function getValueType() {
		return BaseValue::TYPE_STRING;
	}

	/**
	 * Wrap a value within a value Object to get more info when rendering it.
	 * @since 1.3.0
	 *
	 * @param array $values The values.
	 * @param bool $is_label Whether this is a label cell.
	 *
	 * @return BaseValue[] The value Object.
	 */
	protected function wrap( array $values, bool $is_label = false ): array {
		return array_map( function ( $value ) use ( $is_label ) {
			return BaseValue::getValueObject( $this, $value, $this->field, $is_label );
		}, $values );
	}

	/**
	 * Helper method to apply the regular value filters.
	 * @since 1.10
	 *
	 * @param mixed $value The value to filter.
	 * @param array $entry The entry object.
	 * @param array $context Optional additional context.
	 *
	 * @return mixed The filtered value.
	 */
	final protected function filter_value( $value, array $entry, array $context = [] ) {
		/** @see https://gfexcel.com/docs/changing-values/ for documentation */
		return gf_apply_filters( [
			'gfexcel_field_value',
			$this->field->get_input_type(),
			$this->field->formId,
			$this->field->id,
		], $value, $entry, $this->field, $context );
	}

	/**
	 * Internal function to get the Field Value for an entry, and maybe override it.
	 *
	 * @param array $entry The entry object.
	 * @param string $input_id The id of the input field.
	 *
	 * @return array|string|int The value of the field. May be `0` if not found.
	 */
	protected function getFieldValue( $entry, $input_id = '' ) {
		$input_id    = $input_id ?: (string) $this->field->id;
		$gform_value = $this->getGFieldValue( $entry, $input_id );

		// and our own filters!
		return gf_apply_filters( [
			'gfexcel_export_field_value',
			$this->field->get_input_type(),
			$input_id,
		], $gform_value, $this->field->formId, $input_id, $entry );
	}

	/**
	 * Get the original Gravity Field value.
	 *
	 * @param array $entry The entry object.
	 * @param string $input_id The id of the input field.
	 *
	 * @return mixed The value of the field.
	 */
	protected function getGFieldValue( $entry, $input_id ) {
		if ( in_array( $input_id, [ 'date_created', 'payment_date' ] ) ) {
			$lead_gmt_time   = mysql2date( 'G', $entry[ $input_id ] );
			$lead_local_time = \GFCommon::get_local_timestamp( $lead_gmt_time );

			return date_i18n( 'Y-m-d H:i:s', $lead_local_time, true );
		}

		// Prevent Type Errors from fields.
		try {
			$value = $this->field->get_value_export( $entry, $input_id, $use_text = false, $is_csv = false );
		} catch ( Throwable $error ) {
			GravityExportAddon::get_instance()->log_error( $error->getMessage() );
			$value = null;
		}

		if ( is_string( $value ) ) {
			$value = html_entity_decode( $value );
		}

		// add gform export filters to get the same results as a normal export
		return apply_filters( 'gform_export_field_value', $value, $this->field->formId, $input_id, $entry );
	}

	/**
	 * Whether the use of the admin label is allowed.
	 * @since 2.1.0
	 * @return bool
	 */
	protected function useAdminLabels(): bool {
		return gf_apply_filters( [
			'gk/gravityexport/field/use-admin-label',
			$this->field->formId,
			$this->field->id,
		],
			GravityExportAddon::get_instance()->useAdminLabels(),
			$this->field
		);
	}
}
