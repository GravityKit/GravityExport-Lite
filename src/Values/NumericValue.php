<?php

namespace GFExcel\Values;

/**
 * Value object that represents a numeric cell.
 * @since 1.3.0
 */
class NumericValue extends BaseValue
{
    /**
     * Default formatting for numeric value.
     * @since 1.8.1
     */
    public const FORMAT_DEFAULT = 'General';

    /**
     * Currency formatting without symbol.
     * @since 1.8.0
     */
    public const FORMAT_CURRENCY_NONE = '#,##0.00_-';

    /**
     * The currency format of the cell.
     * @since 1.8.0
     * @var string
     */
    protected $format = self::FORMAT_DEFAULT;

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
        if (!is_numeric($this->value)) {
            return null;
        }

        return $this->value;
    }

    /**
     * Returns the currency format.
     * @since 1.8.0
     * @return string The format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Sets the format.
     * @since 1.8.0
     * @param string $format The format.
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }
}
