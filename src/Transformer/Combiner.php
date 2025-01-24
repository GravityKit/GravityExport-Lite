<?php

namespace GFExcel\Transformer;

use GF_Field;
use GFExcel\Field\FieldInterface;
use GFExcel\Field\RowsInterface;
use GFExcel\Values\BaseValue;
use GFExcel\Values\StringValue;

/**
 * Combines multiple fields into one row for an entry.
 * @since 1.8.0
 */
class Combiner implements CombinerInterface {
	/**
	 * Holds an array of arrays that contains the cells data for every row.
	 * @since 1.8.0
	 * @var mixed[][] The rows with the cell values.
	 */
	protected $rows = [];

	/**
	 * @inheritDoc
	 * @since 1.8.0
	 */
	public function parseEntry( array $fields, array $entry ): void {
		// always start at zero.
		$column_index = 0;

		// Keep columns in internal array, so we only merge once.
		$combined_row = [];

		foreach ( $fields as $field ) {
			$rows     = $this->getFieldRows( $field, $entry );
			$has_rows = false;
			foreach ( $rows as $cells ) {
				$has_rows = true;
				$i        = 0;
				foreach ( $cells as $cell ) {
					$combined_row[ $column_index + $i ][] = $cell;
					++ $i;
				}
			}

			// Make sure the row is filled with empty values.
			if ( ! $has_rows ) {
				foreach ( $field->getColumns() as $i => $_ ) {
					$combined_row[ $column_index + $i ][] = null;
				}
			}

			$column_index += count( $field->getColumns() );
		}

		foreach ( $combined_row as $column => $values ) {
			// only one row, so we can keep the value types.
			if ( count( $values ) === 1 ) {
				$combined_row[ $column ] = reset( $values );
				continue;
			}

			$combined = array_reduce( $values, static function ( string $output, ?BaseValue $value ): string {
				if ( ! $value ) {
					return $output;
				}

				if ( $output !== '' ) {
					$output .= gf_apply_filters( [
						'gfexcel_combiner_glue',
						$value->getFieldType(),
						$value->getFieldId(),
					], "\n---\n", $value );
				}

				$output .= $value->getValue();

				return $output;
			}, '' );

			$non_empty_values = array_filter( $values );
			$gf_field         = $non_empty_values ? reset( $non_empty_values )->getField() : new GF_Field();

			$combined_row[ $column ] = new StringValue( $combined, $gf_field );
		}

		$this->rows[] = $combined_row;
	}

	/**
	 * @inheritdoc
	 * @since 1.8.0
	 */
	public function getRows( ?array $entry = null ): iterable {
		yield from $this->rows;
	}

	/**
	 * Retrieves the rows for a field.
	 * @since 1.8.0
	 *
	 * @param FieldInterface $field The field transformer.
	 * @param array $entry The entry object.
	 *
	 * @return iterable|\Generator|BaseValue[][] Rows containing cells.
	 */
	protected function getFieldRows( FieldInterface $field, array $entry ): iterable {
		// make sure custom legacy fields are processed as well.
		if ( ! $field instanceof RowsInterface ) {
			yield $field->getCells( $entry );
		} else {
			yield from $field->getRows( $entry );
		}
	}
}
