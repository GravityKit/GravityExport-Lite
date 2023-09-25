<?php

namespace GFExcel\Field;

use GFExcel\GFExcel;
use GFExcel\Transformer\Transformer;
use GFExcel\Transformer\TransformerAwareInterface;

/**
 * A field transformer for {@see \GP_Nested_Form_Field}.
 * @since 1.10
 */
class NestedFormField extends SeparableField implements RowsInterface, TransformerAwareInterface {
	/**
	 * The transformer instance.
	 * @since 1.11.1
	 * @var Transformer
	 */
	private $transformer;

	/**
	 * Micro cache for fields.
	 * @since 1.11.1
	 * @var FieldInterface[]|false
	 */
	private $fields = false;

	/**
	 * @inheritdoc
	 * @since 1.10
	 */
	public function getRows( ?array $entry = null ): iterable {
		if ( ! class_exists( 'GP_Nested_Forms' ) || ! ( $this->field->gpnfForm ?? false ) ) {
			return;
		}

		$value = $entry[ $this->field->id ] ?? '';
		// Validate if the entries are from the connected form.
		$ids = \GFAPI::get_entry_ids( $this->field->gpnfForm ?? 0, [
			'field_filters' => [
				[
					'key'      => 'id',
					'operator' => 'IN',
					'value'    => array_map( 'trim', explode( ',', $value ) ),
				]
			]
		] );

		$nested_entries = \GP_Nested_Forms::get_instance()->get_entries( $ids );

		if ( ! $nested_entries ) {
			return;
		}

		$combiner = GFExcel::getCombiner( $this->field->formId );
		foreach ( $nested_entries as $nested_entry ) {
			$combiner->parseEntry( $this->getNestedFields(), $nested_entry );
		}

		yield from $combiner->getRows();
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten to retrieve the columns from the nested form.
	 *
	 * @since 1.10
	 */
	protected function getSeparatedColumns(): array {
		$fields = array_map( function ( FieldInterface $field ): array {
			return $field->getColumns();
		}, array_values( $this->getNestedFields() ) );

		return array_merge( [], ...$fields );
	}

	/**
	 * @inerhitDoc
	 * @since 1.11.1
	 */
	public function setTransformer( Transformer $transformer ): void {
		$this->transformer = $transformer;
	}

	/**
	 * Helper method to return the transformed fields from the nested form.
	 * @since 1.11.1
	 * @return FieldInterface[] The fields.
	 */
	private function getNestedFields(): array {
		if ( ! class_exists( 'GP_Nested_Forms' ) ) {
			return [];
		}

		if ( $this->fields !== false ) {
			return $this->fields;
		}

		$nested_form = \GFAPI::get_form( $this->field->gpnfForm );

		if ( ! $nested_form ) {
			return $this->fields = [];
		}

		// Cache the results.
		$this->fields = array_reduce(
			$nested_form['fields'],
			function ( array $fields, \GF_Field $field ) use ( $nested_form ) {
				if ( in_array( $field->id, $this->getExportFields( $nested_form ), false ) ) {
					$fields[ $field->id ] = $this->transformer->transform( $field );
				}

				return $fields;
			},
			[]
		);

		return $this->fields;
	}

	/**
	 * Returns the field keys to export from the nested form. Defaults to the visible fields.
	 * @since 2.1.0
	 *
	 * @param array $form The nested form.
	 *
	 * @return array The field keys.
	 */
	private function getExportFields( array $form ): array {
		$fields = $this->field->gpnfFields ?? [];

		return gf_apply_filters( [
			'gk/gravityexport/field/nested-form/export-field',
			$this->field->formId,
			$this->field->id,
		],
			$fields,
			$this->field,
			$form
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.1.0
	 */
	protected function isSeparationEnabled(): bool {
		return true;
	}
}
