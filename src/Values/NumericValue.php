<?php

namespace GFExcel\Values;

/**
 * Value object that represents a numeric cell.
 * @since 1.3.0
 */
class NumericValue extends BaseValue
{
    /**
     * @inheritdoc
     * @since 1.3.0
     */
    protected $is_numeric = true;

    /**
     * @inheritdoc
     *
     * Overwritten because it isn't a string in this case, but could also be float or integer.
     *
     * @since 1.3.0
     */
    public function getValue()
    {
        return $this->value;
    }
}
