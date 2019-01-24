<?php

namespace GFExcel\Values;

class BoolValue extends BaseValue
{
    protected $is_bool = true;

    public function getValue()
    {
        return (bool) $this->value;
    }
}
