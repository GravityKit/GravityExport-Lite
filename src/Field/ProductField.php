<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;
use GFExcel\Values\CurrencyValue;
use GFExcel\Values\NumericValue;
use GFExcel\Values\StringValue;

class ProductField extends SeparableField
{
    /** @var string */
    public const SETTING_KEY = 'numeric_price_enabled';

    /**
     * {@inheritdoc}
     * Usual code, but with a prepended string for quantity and price for single field rendering.
     */
    protected function getSeparatedFields($entry)
    {
        $entry_keys = array_keys($this->getSeparatedColumns());

        return array_map(function ($key) use ($entry) {
            $prepend = '';
            if (!$this->isSeparationEnabled()) {
                if ($this->getSubFieldId($key) === 3) {
                    // add QTY to the field for clarity.
                    $prepend = esc_html__('Qty: ', 'gravityforms');
                }
                if ($this->getSubFieldId($key) === 2) {
                    // add QTY to the field for clarity.
                    $prepend = esc_html__('Price: ', 'gravityforms');
                }
            }
            $prepend = gf_apply_filters([
                'gfexcel_field_' . $this->field->get_input_type() . '_prepend',
                $this->field->formId,
                $key,
            ], $prepend, $key);

            return $prepend . $this->getFieldValue($entry, $key);
        }, $entry_keys);
    }

    /**
     * {@inheritdoc}
     * Set quantity to 0, if value is empty.
     */
    protected function getFieldValue($entry, $input_id = '')
    {
        $value = parent::getFieldValue($entry, $input_id);
        if (empty($value) && $this->getSubFieldId($input_id) === 3) {
            $value = 0;
        }

        return $value;
    }

    /**
     * Return the last part of the input_id
     * @param string $key The field input key.
     * @return int The subfield key.
     */
    private function getSubFieldId($key)
    {
        $key_parts = explode('.', $key);

        return (int) end($key_parts);
    }

    /**
     * {@inheritdoc}
     * Format as numeric when necessary.
     */
    public function getValueType()
    {
        if (!$this->hasNumericPrice()) {
            return parent::getValueType();
        }

        return BaseValue::TYPE_CURRENCY;
    }

    /**
     * {@inheritdoc}
     * Reformat to a number value when necessary.
     */
    protected function getGFieldValue($entry, $input_id)
    {
        $value = parent::getGFieldValue($entry, $input_id);
        if (!$this->hasNumericPrice() || !strpos($input_id, '.2')) {
            return $value;
        }

        return \GFCommon::to_number($value, \rgar($entry, 'currency'));
    }

	/**
	 * Whether the product field has a numeric value / price.
	 * @return bool.
	 */
	protected function hasNumericPrice(): bool {
		$plugin = GravityExportAddon::get_instance();

		return gf_apply_filters( [
			'gfexcel_numeric_price',
			$this->field->get_input_type(),
			$this->field->formId,
			$this->field->id,
		], (bool) $plugin->get_plugin_setting( self::SETTING_KEY ), $this->field );
	}

    /**
     * @inheritdoc
     *
     * Values should be wrapped as 3 different types.
     *
     * @since 1.8.2
     */
	protected function wrap( array $values, bool $is_label = false ): array {
		$wrapping = [
			StringValue::class,
			CurrencyValue::class,
			NumericValue::class,
		];

		if ( ! $is_label ) {
			$wrapped = [];

			// make sure the type is correct.
			foreach ( $values as $key => $value ) {
				$wrapped[] = new $wrapping[ $key ]( $value, $this->field );
			}

			return $wrapped;
		}

		return parent::wrap( $values, $is_label );
	}
}
