<?php

namespace GFExcel\Values;

/**
 * Value object that represents a numeric cell.
 * @since 1.3.0
 */
class NumericValue extends BaseValue
{
    /**
     * Currency formatting without symbol.
     * @since $ver$
     */
    public const FORMAT_CURRENCY_NONE = '#,##0.00_-';

    /**
     * The currency format of the cell.
     * @since $ver$
     * @var string
     */
    protected $format = self::FORMAT_CURRENCY_NONE;

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

    /**
     * Returns the currency format.
     * @since $ver$
     * @return string The format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Sets the format.
     * @since $ver$
     * @param string $format The format.
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }
}
