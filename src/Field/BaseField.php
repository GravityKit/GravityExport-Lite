<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

class BaseField extends AbstractField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        $value = $this->getFieldValue($entry);

        $value = gf_apply_filters([
            'gfexcel_field_value',
            $this->field->get_input_type(),
            $this->field->formId,
            $this->field->id
        ], $value, $entry, $this->field);

        return $this->wrap([$value]);
    }
}
