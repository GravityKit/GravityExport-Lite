<?php

namespace GFExcel\Field;

/**
 * A field transformer for the `likert` field from the Gravity Forms Survey Plugin.
 * @since 1.11
 * @see \GF_Field_Likert
 */
class SurveyLikertField extends SeparableField {
	/**
	 * @inheritdoc
	 * @var \GF_Field_Likert
	 */
	protected $field;

	/**
	 * @inheritdoc
	 * @since 1.11
	 */
	public function getFieldValue( $entry, $input_id = '' ) {
		if ( ! $this->useScoreOutput() ) {
			if ( $this->isSeparationEnabled() ) {
				return parent::getFieldValue( $entry, $input_id );
			}

			return $this->field->gsurveyLikertEnableMultipleRows
				? $this->field->get_column_text( false, $entry, $input_id, true )
				: $this->field->get_value_export( $entry, $input_id );
		}

		if ( $input_id ) {
			$entry_key = $input_id;
		} else {
			$entry_key = $this->field->id;
		}

		$value = $entry[ $entry_key ] ?? '';

		// If this is not a multi row value; add a colon, so we can still get $column.
		if ( strpos( $value, ':' ) === false ) {
			$value = ':' . $value;
		}

		// There will always be a colon; there will always be a $column set.
		[ , $column ] = explode( ':', $value );

		foreach ( $this->field->choices as $choice ) {

			$choice_value = $choice['value'] ?? '';

			if ( $choice_value === $column ) {
				return $choice['score'];
			}
		}

		return 0; // choice not found, so value is 0
	}

	/**
	 * Whether to use the score instead of the text value.
	 * @since 1.11
	 * @return bool Whether to use the score as output.
	 */
	private function useScoreOutput(): bool {
		return gf_apply_filters( [
			'gfexcel_field_likert_use_score',
			$this->field->formId,
			$this->field->id,
		], false, $this->field );
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten so single row fields will still return the correct columns.
	 *
	 * @since 1.11
	 */
	protected function isSeparationEnabled() {
		return parent::isSeparationEnabled() && $this->hasMultipleRows();
	}

	/**
	 * Whether this field has multiple rows.
	 * @since 1.11
	 */
	private function hasMultipleRows(): bool {
		return $this->field->gsurveyLikertEnableMultipleRows ?? false;
	}

	/**
	 * @inheritdoc
	 *
	 * Overwritten so single row values will also be retrieved.
	 *
	 * @since 1.11
	 */
	public function getCells( $entry ) {
		return $this->hasMultipleRows()
			? parent::getCells( $entry )
			: BaseField::getCells( $entry );
	}
}
