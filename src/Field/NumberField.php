<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

class NumberField extends BaseField
{
    /**
     * {@inheritdoc}
     * @return string
     */
    public function getValueType()
    {
        return BaseValue::TYPE_NUMERIC;
    }
}
