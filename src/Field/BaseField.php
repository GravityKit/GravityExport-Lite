<?php

namespace GFExcel\Field;

class BaseField extends AbstractField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry)
    {
        $value = $this->field->get_value_export($entry);

        $value = gf_apply_filters(
            array(
                "gfexcel_field_value",
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ),
            $value, $entry);

        return array($value);
    }

}