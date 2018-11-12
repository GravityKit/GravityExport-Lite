<?php

namespace GFExcel\Field;

use GFExcel\GFExcelAdmin;
use GFExcel\Values\BaseValue;

class SeparableField extends BaseField
{
    const SETTING_KEY = 'field_separation_enabled';

    /**
     * {@inheritdoc}
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if (!$this->isSeparationEnabled()) {
            return parent::getColumns();
        }

        return $this->wrap($this->getSeparatedColumns(), true);
    }

    /**
     * {@inheritdoc}
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        $fields = gf_apply_filters([
            'gfexcel_field_' . $this->field->get_input_type() . '_fields',
            $this->field->formId,
            $this->field->id
        ], $this->getSeparatedFields($entry), $entry);

        if ($this->isSeparationEnabled()) {
            return $this->wrap($fields);
        }

        $value = implode("\n", array_filter($fields));

        $value = gf_apply_filters([
            'gfexcel_field_value',
            $this->field->get_input_type(),
            $this->field->formId,
            $this->field->id
        ], $value, $entry, $this->field);

        return $this->wrap($value);
    }

    /**
     * Get the separated fields to go along with the columns.
     * @param $entry
     * @return array
     */
    protected function getSeparatedFields($entry)
    {
        $entry_keys = array_keys($this->getSeparatedColumns());

        $field = $this->field;

        return array_map(function ($key) use ($entry, $field) {
            return $field->get_value_export($entry, $key);
        }, $entry_keys);
    }

    /**
     * Should we use separated fields.
     * @return bool
     */
    protected function isSeparationEnabled()
    {
        $plugin = GFExcelAdmin::get_instance();

        $bool = $plugin->get_plugin_setting(self::SETTING_KEY);
        if ($bool === null) {
            // backwards compatible with earlier setting
            $bool = $plugin->get_plugin_setting('field_address_split_enabled');
        }

        return gf_apply_filters([
            'gfexcel_field_separated',
            $this->field->get_input_type(),
            $this->field->formId,
            $this->field->id
        ], !!$bool, $this->field);
    }

    /**
     * Get the column names for every active subfield.
     * @return array
     */
    protected function getSeparatedColumns()
    {
        return array_reduce($this->getVisibleSubfields(), function ($carry, $field) {
            $carry[$field['id']] = gf_apply_filters([
                'gfexcel_field_label',
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ], $this->getSubLabel($field), $this->field);

            return $carry;
        }, []);
    }

    /**
     * Retrieve all active subfields for this field.
     * @return array
     */
    protected function getVisibleSubfields()
    {
        return array_filter((array) $this->field->get_entry_inputs(), function ($subfield) {
            return isset($subfield['isHidden']) ? !$subfield['isHidden'] : true;
        });
    }

    /**
     * Get the label for a subfield.
     * @param $field
     * @return string
     */
    protected function getSubLabel($field)
    {
        if (!array_key_exists('customLabel', $field) || empty(trim($field['customLabel']))) {
            return $field['label'];
        }

        return $field['customLabel'];
    }
}
