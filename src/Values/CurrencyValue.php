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
    public const FORMAT_CURRENCY_FIRST = '"%s" #,##0.00_-';

    /**
     * Currency formatting with symbol last.
     * @since $ver$
     */
    public const FORMAT_CURRENCY_LAST = '#,##0.00_- "%s"';

    /**
     * The currency symbol.
     * @since $ver$
     * @var string
     */
    protected $symbol = '$';

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
     * Sets the symbol.
     * @since $ver$
     * @param string $symbol The symbol.
     */
    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function getFormat(): string
    {
        return sprintf(parent::getFormat(), $this->getSymbol());
    }
}
