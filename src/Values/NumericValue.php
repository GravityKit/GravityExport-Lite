<?php

namespace GFExcel\Values;

/**
 * Value object that represents a numeric cell.
 * @since 1.3.0
 */
class NumericValue extends BaseValue
{
    /**
     * Currency formatting with symbol first.
     * @since $ver$
     */
    public const FORMAT_CURRENCY_FIRST = '"%s"#,##0.00_-';
    /**
     * Currency formatting with symbol last.
     * @since $ver$
     */
    public const FORMAT_CURRENCY_LAST = '#,##0.00_-"%s"';
    /**
     * Currency formatting without symbol.
     * @since $ver$
     */
    public const FORMAT_CURRENCY_NONE = '#,##0.00_-';

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
