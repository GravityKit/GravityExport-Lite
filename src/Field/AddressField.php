<?php

namespace GFExcel\Field;

class AddressField extends BaseField
{
    private $seperated_fields = false;

    /**
     * Array of needed column names for this field.
     * @return array
     */
    public function getColumns()
    {
        if ($this->useSeperatedFields()) {
            return $this->getSeperatedColumns();
        }

        return parent::getColumns();
    }

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry)
    {
        $fields = $this->getSeperatedFields($entry);
        $fields = gf_apply_filters(
            array(
                "gfexcel_field_address_fields",
                $this->field->formId,
                $this->field->id
            ),
            $fields, $entry);

        if ($this->useSeperatedFields()) {
            return $this->wrap($fields);
        }

        $value = implode("\n", array_filter($fields));

        $value = gf_apply_filters(
            array(
                "gfexcel_field_value",
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ),
            $value, $entry, $this->field);

        return $this->wrap($value);
    }

    private function getSeperatedFields($entry)
    {
        $entry_keys = array_keys($this->getSeperatedColumns());

        $field = $this->field;

        return array_map(function ($key) use ($entry, $field) {
            return $field->get_value_export($entry, $key);
        }, $entry_keys);
    }

    private function useSeperatedFields()
    {
        return gf_apply_filters(
            array(
                "gfexcel_field_address_seperated",
                $this->field->formId,
                $this->field->id
            ),
            $this->seperated_fields);
    }

    private function getSeperatedColumns()
    {
        $result = array();
        $fields = $this->getVisibleSubfields();
        foreach ($fields as $field) {
            $result[$field['id']] = $field['label'];
        }
        return $this->wrap($result, true);
    }

    private function getVisibleSubfields()
    {
        return array_filter($this->field->inputs, function ($subfield) {
            return isset($subfield['isHidden']) ? !$subfield['isHidden'] : true;
        });
    }
}