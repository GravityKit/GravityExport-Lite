<?php

namespace GFExcel\Field;

/**
 * A field transformer for {@see \GP_Nested_Form_Field}.
 * @since 1.10
 */
class NestedFormField extends SeparableField implements RowsInterface {
	/**
	 * @inheritdoc
	 * @since 1.10
	 */
	public function getRows( ?array $entry = null ): iterable {
		if ( ! class_exists( 'GP_Nested_Forms' ) ) {
			yield [];
		} else {
			$value = $entry[ $this->field->id ] ?? null;
			$keys  = array_flip( $this->field->gpnfFields ?? [] );

			// Map all entries to filter out the unwanted values.
			yield from array_map( function ( array $entry ) use ( $keys ): array {
				// Retrieve only the fields that are set on the form field.
				return $this->wrap( array_values( array_intersect_key( $entry, $keys ) ) );
			}, \GP_Nested_Forms::get_instance()->get_entries( $value ) );
		}
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten to retrieve the columns from the nested form.
	 *
	 * @since 1.10
	 */
	protected function getSeparatedColumns(): array {
		if ( ! class_exists( 'GP_Nested_Forms' ) || ! $nested_form = \GFAPI::get_form( $this->field->gpnfForm ) ) {
			return [];
		}

		$fields = array_filter( $nested_form['fields'], function ( \GF_Field $field ) {
			return in_array( $field->id, $this->field->gpnfFields ?? [], false );
		} );

		return array_map( function ( \GF_Field $field ) {
			return gf_apply_filters( [
				'gfexcel_field_label',
				$field->get_input_type(),
				$field->formId,
				$field->id,
			], sprintf(
				'%s (%s)', // Nested field label (Wrapper label)
				$this->getSubLabel( $field ),
				$this->getSubLabel( $this->field )
			), $field );
		}, $fields );
	}
}
