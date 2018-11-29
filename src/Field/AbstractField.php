<?php

namespace GFExcel\Field;

use GF_Field;
use GFExcel\Values\BaseValue;

abstract class AbstractField implements FieldInterface
{
    /** @var GF_Field */
    protected $field;

    /**
     * AbstractField constructor.
     * @param GF_Field $field
     */
    public function __construct(GF_Field $field)
    {
        $this->field = $field;
    }

    /**
     * Array of needed column names for this field.
     * @return BaseValue[]
     */
    public function getColumns()
    {
        $label = gf_apply_filters([
            "gfexcel_field_label",
            $this->field->get_input_type(),
            $this->field->formId,
            $this->field->id
        ], $this->field->get_field_label(true, ''), $this->field);

        return $this->wrap([$label], true);
    }

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    abstract public function getCells($entry);

    /**
     * Get the type of this value object
     * @return string
     */
    public function getValueType()
    {
        return BaseValue::TYPE_STRING;
    }

    /**
     * Wrap a value within a value Object to get more info when rendering it.
     *
     * @param array $values
     * @param bool $is_label
     * @return BaseValue[]
     */
    protected function wrap($values, $is_label = false)
    {
        $class = $this; //legacy support
        return array_map(function ($value) use ($class, $is_label) {
            return BaseValue::getValueObject($class, $value, $class->field, $is_label);
        }, (array) $values);
    }

    /**
     * Internal function to get the Field Value for an entry, and maybe override it.
     *
     * @param array $entry
     * @param string $input_id
     * @return array|string
     */
    protected function getFieldValue($entry, $input_id = '')
    {
        $input_id = $input_id ?: $this->field->id;
        $value = $this->field->get_value_export($entry, $input_id, $use_text = false, $is_csv = false);
        $value = html_entity_decode($value);

        // add gform export filters to get the same results as a normal export
        $gform_value = apply_filters('gform_export_field_value', $value, $this->field->formId, $input_id, $entry);

        // and our own filters!
        return gf_apply_filters([
            'gfexcel_export_field_value',
            $this->field->get_input_type(),
            $input_id,
        ], $gform_value, $this->field->formId, $input_id, $entry);
    }
}
