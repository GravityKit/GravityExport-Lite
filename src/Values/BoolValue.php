<?php

namespace GFExcel\Values;

/**
 * Value object that represents a boolean.
 * @since 1.3.0
 */
class BoolValue extends BaseValue
{
    /**
     * @inheritdoc
     * @since 1.3.0
     */
    protected $is_bool = true;

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public function getValue()
    {
        return (bool) $this->value;
    }
}
