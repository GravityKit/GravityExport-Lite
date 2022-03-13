<?php

namespace GFExcel\Field;

use GFExcel\Transformer\Combiner;
use GFExcel\Transformer\Transformer;
use GFExcel\Transformer\TransformerAwareInterface;

/**
 * A field transformer for {@see \GP_Nested_Form_Field}.
 * @since 1.10
 */
class NestedFormField extends SeparableField implements RowsInterface, TransformerAwareInterface {
	/**
	 * The transformer instance.
	 * @since $ver$
	 * @var Transformer
	 */
	private $transformer;

	/**
	 * Micro cache for fields.
	 * @since $ver$
	 * @var FieldInterface[]|false
	 */
	private $fields = false;

	/**
	 * @inheritdoc
	 * @since 1.10
	 */
	public function getRows( ?array $entry = null ): iterable {
		if ( ! class_exists( 'GP_Nested_Forms' ) ) {
			yield [];
		} else {
			$value   = $entry[ $this->field->id ] ?? null;
			$nested_entries = \GP_Nested_Forms::get_instance()->get_entries( $value );

			$combiner = new Combiner();

			foreach ( $nested_entries as $nested_entry ) {
				$combiner->parseEntry( $this->getNestedFields(), $nested_entry );
			}

			yield from $combiner->getRows();
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
		$fields = array_map( function ( FieldInterface $field ): array {
			return $field->getColumns();
		}, array_values( $this->getNestedFields() ) );

		return array_merge( [], ...$fields );
	}

	/**
	 * @inerhitDoc
	 * @since $ver$
	 */
	public function setTransformer( Transformer $transformer ): void {
		$this->transformer = $transformer;
	}

	/**
	 * Helper method to return the transformed fields from the nested form.
	 * @return FieldInterface[] The fields.
	 * @since $ver$
	 */
	private function getNestedFields(): array {
		if ( ! class_exists( 'GP_Nested_Forms' ) ) {
			return [];
		}

		if ( $this->fields === false ) {
			$nested_form = \GFAPI::get_form( $this->field->gpnfForm );

			if ( ! $nested_form ) {
				return $this->fields = [];
			}

			// Cache the results.
			$this->fields = array_reduce( $nested_form['fields'], function ( array $fields, \GF_Field $field ) {
				if ( in_array( $field->id, $this->field->gpnfFields ?? [], false ) ) {
					$fields[ $field->id ] = $this->transformer->transform( $field );
				}

				return $fields;
			}, [] );
		}

		return $this->fields;
	}
}
