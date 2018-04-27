<?php


namespace GFExcel\Field;


use GFExcel\Values\BaseValue;

class NumberField extends BaseField
{
    public function getValueType()
    {
        return BaseValue::TYPE_NUMERIC;
    }
}