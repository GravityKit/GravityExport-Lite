<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;

/**
 * A field transformer that serves as the base for every field that has subfields.
 * @since 1.6.0
 */
class SeparableField extends BaseField
{
    /** @var string */
    public const SETTING_KEY = 'field_separation_enabled';

    /**
     * @inheritdoc
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if (!$this->isSeparationEnabled()) {
            return parent::getColumns();
        }

        return $this->wrap($this->getSeparatedColumns(), true);
    }

	/**
	 * @inheritdoc
	 * @return BaseValue[]
	 */
	public function getCells( $entry ) {
		$fields = gf_apply_filters( [
			'gfexcel_field_' . $this->field->get_input_type() . '_fields',
			$this->field->formId,
			$this->field->id,
		], $this->getSeparatedFields( $entry ), $entry );

		if ( $this->isSeparationEnabled() ) {
			$fields = array_map( function ( $value ) use ( $entry ) {
				return $this->filter_value( $value, $entry );
			}, $fields );

			return $this->wrap( $fields );
		}

		$value = $this->filter_value( implode( "\n", array_filter( $fields ) ), $entry );

		return $this->wrap( [ $value ] );
	}

    /**
     * Get the separated fields to go along with the columns.
     * @param mixed[] $entry The entry object.
     * @return mixed[] The field values.
     */
    protected function getSeparatedFields($entry)
    {
        $entry_keys = array_keys($this->getSeparatedColumns());

        return array_map(function ($key) use ($entry) {
            return $this->getFieldValue($entry, $key);
        }, $entry_keys);
    }

    /**
     * Whether we should use separated fields.
     * @return bool Whether we should use separated fields.
     */
    protected function isSeparationEnabled()
    {
	    $plugin = GravityExportAddon::get_instance();

	    return gf_apply_filters( [
		    'gfexcel_field_separated',
		    $this->field->get_input_type(),
		    $this->field->formId,
		    $this->field->id,
	    ], (bool) $plugin->get_plugin_setting( self::SETTING_KEY ), $this->field );
    }

    /**
     * Get the column names for every active subfield.
     * @return array
     */
    protected function getSeparatedColumns()
    {
        return array_reduce($this->getVisibleSubfields(), function ($carry, $field) {
            $field_id = (string) $field['id'];
            $carry[$field_id] = gf_apply_filters([
                'gfexcel_field_label',
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ], $this->getSubLabel($field), $this->field);
            return $carry;
        }, []);
    }

    /**
     * Retrieve all active subfields for this field.
     * @return array
     */
    protected function getVisibleSubfields()
    {
        return array_filter((array) $this->field->get_entry_inputs(), function ($subfield) {
            return ! isset( $subfield['isHidden'] ) || ! $subfield['isHidden'];
        });
    }

	/**
	 * Get the label for a subfield.
	 *
	 * @param array|\ArrayAccess $field The field object.
	 *
	 * @return string The sub label.
	 */
	protected function getSubLabel( $field ) {
		if ( empty( trim( $field['customLabel'] ?? '' ) ) ) {
			return $field['label'];
		}

		return $field['customLabel'];
	}
}
