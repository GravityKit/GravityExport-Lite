<?php

namespace GFExcel\Values;

/**
 * Value object that represents a currency.
 * @since $ver$
 */
class CurrencyValue extends NumericValue
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
     * The currency format of the cell.
     * @since $ver$
     * @var string
     */
    protected $format = self::FORMAT_CURRENCY_NONE;

    /**
     * The currency symbol.
     * @since $ver$
     * @var string
     */
    protected $symbol = '$';

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
     * Returns the currency symbol.
     * @since $ver$
     * @return string The symbol.
     */
    public function getSymbol(): string
    {
        return $this->symbol;
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

    /**
     * Sets the symbol.
     * @since $ver$
     * @param string $symbol The symbol.
     */
    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }
}
