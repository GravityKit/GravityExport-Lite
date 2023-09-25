<?php

namespace GFExcel\Field;

/**
 * Field transformer for `checkbox` fields.
 * @since 1.8.8
 */
class CheckboxField extends BaseField implements RowsInterface {
	/**
	 * @inheritdoc
	 *
	 * Overwritten for phpdoc
	 *
	 * @var \GF_Field_Checkbox
	 */
	protected $field;

	/**
	 * @inheritdoc
	 * @since 1.8.8
	 */
	public function __construct( \GF_Field $field ) {
		parent::__construct( $field );

		// Normal glue
		add_filter( 'gfexcel_combiner_glue_checkbox', static function () {
			return ', ';
		} );
	}

	/**
	 * @inheritdoc
	 * @since 1.8.8
	 */
	public function getRows( ?array $entry = null ): iterable {
		$inputs = $this->field->get_entry_inputs();

		if ( ! is_array( $inputs ) ) {
			$value = \GFCommon::selection_display(
				rgar( $entry, $this->field->id ),
				$this->field,
				rgar( $entry, 'currency' )
			);
			yield $this->wrap( [ $value ] );
		} else {
			foreach ( $inputs as $input ) {
				$index = (string) $input['id'];

				if ( ! rgempty( $index, $entry ) ) {
					$value = $this->filter_value( $this->getFieldValue( $entry, $index ), $entry );

					yield $this->wrap( [ $value ] );
				}
			}
		}
	}
}
