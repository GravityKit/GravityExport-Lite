<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

/**
 * Field transformer that represents a List field
 * @since 1.3.0
 */
class ListField extends BaseField implements RowsInterface {
	private $columns;

	/**
	 * @inheritdoc
	 * @since 1.8.10
	 */
	public function __construct( \GF_Field $field ) {
		parent::__construct( $field );

		// Normal glue
		add_filter( 'gfexcel_combiner_glue_list', static function () {
			return "\n";
		} );
	}

	/**
	 * Array of needed column names for this field.
	 * @return BaseValue[]|string[]
	 */
	public function getColumns() {
		if ( ! $this->columns ) {
			if ( ! $this->field['enableColumns'] ) {
				//no columns, so we can just use the field name, and a single column
				$this->columns = parent::getColumns(); //micro caching

				return $this->columns;
			}

			//Multiple columns, so lets get their names, and return multiple columns.
			$this->columns = array_map( function ( $choice ) {
				return gf_apply_filters( [
					'gfexcel_field_label',
					$this->field->get_input_type(),
					$this->field->formId,
					$this->field->id
				], $choice['value'], $this->field );
			}, (array) $this->field['choices'] );
		}

		return $this->wrap( $this->columns, true );
	}

	/**
	 * Array of needed cell values for this field
	 *
	 * @param array $entry
	 *
	 * @return BaseValue[]
	 */
	public function getCells( $entry ) {
		$rows   = iterator_to_array( $this->getRows( $entry ) );
		$result = array_reduce( $rows, static function ( array $combined, array $row ): array {
			foreach ( $row as $i => $cell ) {
				$combined[ $i ][] = $cell->getValue();
			}

			return $combined;
		}, [] );

		// Every value on its own line for readability.
		return $this->wrap( array_map( static function ( array $column ) {
			return implode( "\n", $column );
		}, $result ) );
	}

	/**
	 * @inheritdoc
	 * @since 1.8.0
	 */
	public function getRows( ?array $entry = null ): iterable {
		if ( ! $this->field['enableColumns'] ) {
			// One column, so the input is a single string
			yield parent::getCells( $entry );
		} else {
			$value   = $this->getFieldValue( $entry );
			$columns = array_column( $this->field['choices'], 'value' );
			if ( ! $result = json_decode( $value, true ) ) {
				yield $this->wrap( array_map( static function () {
					return '';
				}, $columns ) );
			} else {
				foreach ( $result as $row ) {
					$result = [];
					foreach ( $columns as $column ) {
						$result[ $column ] = $row[ $column ] ?? null;
					}
					yield $this->wrap( array_values( $result ) );
				}
			}
		}
	}
}
