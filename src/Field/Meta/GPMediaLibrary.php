<?php

namespace GFExcel\Field\Meta;

use GFExcel\Field\MetaField;
use GFExcel\Field\RowsInterface;

/**
 * Meta field transformer for GP Media Library ID field.
 * @since 2.0.0
 */
final class GPMediaLibrary extends MetaField implements RowsInterface {
	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	protected function getFieldValue( $entry, $input_id = '' ) {
		$field_id = $this->field->id;
		$value    = parent::getFieldValue( $entry, $input_id );

		if ( ! is_array( $value ) ) {
			return parent::getFieldValue( $entry, $input_id );
		}

		if ( ! function_exists( 'gp_media_library' ) ) {
			return implode( ', ', $value );
		}

		return gp_media_library()->format_entry_meta_for_display(
			$value,
			$this->field->form_id,
			$field_id,
			$entry
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function getRows( ?array $entry = null ): iterable {
		$value = parent::getFieldValue( $entry );

		if ( ! is_array( $value ) ) {
			yield parent::getCells( $entry );
		} else {
			foreach ( $value as $id ) {
				yield $this->wrap( [ $id ] );
			}
		}
	}
}
