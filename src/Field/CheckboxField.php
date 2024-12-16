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
			$has_values = false;
			foreach ( $inputs as $input ) {
				$index = (string) $input['id'];

				if ( ! rgempty( $index, $entry ) ) {
					$has_values = true;
					$value      = $this->filter_value( $this->getFieldValue( $entry, $index ), $entry );
					yield $this->wrap( [ $value ] );
				}
			}

			if ( ! $has_values ) {
				$empty_value = gf_apply_filters(
					[
						'gfexcel_field_checkbox_empty',
						$this->field->formId,
						$this->field->id,
					],
					'',
					$entry,
					$this->field
				);

				if ( '' !== $empty_value ) {
					yield $this->wrap( [ $empty_value ] );
				}
			}
		}
	}
}
