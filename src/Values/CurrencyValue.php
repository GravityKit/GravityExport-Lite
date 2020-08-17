<?php

namespace GFExcel\Values;

/**
 * Value object that represents a currency.
 * @since 1.8.0
 */
class CurrencyValue extends NumericValue
{
    /**
     * @inheritdoc
     * @since 1.8.1
     */
    protected $format = NumericValue::FORMAT_CURRENCY_NONE;

    /**
     * Currency formatting with symbol first.
     * @since 1.8.0
     */
    public const FORMAT_CURRENCY_FIRST = '"%s" #,##0.00_-';

    /**
     * Currency formatting with symbol last.
     * @since 1.8.0
     */
    public const FORMAT_CURRENCY_LAST = '#,##0.00_- "%s"';

    /**
     * The currency symbol.
     * @since 1.8.0
     * @var string
     */
    protected $symbol = '$';

    /**
     * Returns the currency symbol.
     * @since 1.8.0
     * @return string The symbol.
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Sets the symbol.
     * @since 1.8.0
     * @param string $symbol The symbol.
     */
    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function getFormat(): string
    {
        return sprintf(parent::getFormat(), $this->getSymbol());
    }
}
