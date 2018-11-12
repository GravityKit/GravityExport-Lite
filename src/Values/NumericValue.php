<?php

namespace GFExcel\Values;

class NumericValue extends BaseValue
{
    protected $is_numeric = true;

    public function getValue()
    {
        return $this->value;
    }
}
