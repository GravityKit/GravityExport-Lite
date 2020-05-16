<?php

namespace GFExcel\Field;

use GFExcel\GFExcelAdmin;
use GFExcel\Values\BaseValue;

class ProductField extends SeparableField
{
    /** @var string */
    const SETTING_KEY = 'numeric_price_enabled';

    /**
     * {@inheritdoc}
     * Usual code, but with a prepend for quantity and price for single field rendering.
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
     * Set quantity to 1, if none is given.
     */
    protected function getFieldValue($entry, $input_id = '')
    {
        $value = parent::getFieldValue($entry, $input_id);
        if ($this->getSubFieldId($input_id) === 3 && empty($value)) {
            $value = 1;
        }

        return $value;
    }


    /**
     * Return the last part of the input_id
     * @param $key
     * @return int
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

        return \GFCommon::to_number($value, rgar($entry, 'currency'));
    }

    /**
     * @return bool
     */
    protected function hasNumericPrice()
    {
        $plugin = GFExcelAdmin::get_instance();
        $bool = $plugin->get_plugin_setting(self::SETTING_KEY);

        return gf_apply_filters([
            'gfexcel_numeric_price',
            $this->field->get_input_type(),
            $this->field->formId,
            $this->field->id
        ], (bool) $bool, $this->field);
    }
}
