<?php

namespace GFExcel\Field;

use GF_Field;
use GFExcel\Values\BaseValue;

class MetaField extends BaseField
{
    protected $subfields = array(
        'date_created' => 'GFExcel\Field\Meta\DateCreated',
    );

    public function getColumns()
    {
        if ($subfield = $this->getSubField()) {
            return $subfield->getColumns();
        }

        return parent::getColumns();
    }

    public function getCells($entry)
    {
        if ($subfield = $this->getSubField()) {
            return $subfield->getCells($entry);
        }

        $value = $this->getFieldValue($entry);
        $value = gf_apply_filters(
            array(
                "gfexcel_meta_value",
                $this->field->id,
                $this->field->formId,
            ),
            $value, $entry, $this->field);

        return $this->wrap(array($value));
    }

    public function getValueType()
    {
        if (in_array($this->field->id, array(
            'id',
            'form_id',
            'created_by'
        ))) {
            return BaseValue::TYPE_NUMERIC;
        }
        return BaseValue::TYPE_STRING;
    }

    private function getSubFieldsClasses()
    {
        return gf_apply_filters(
            array(
                "gfexcel_transformer_subfields",
            ),
            $this->subfields
        );
    }

    /**
     * @return FieldInterface|false
     */
    private function getSubField()
    {
        $fields = $this->getSubFieldsClasses();
        if (array_key_exists($this->field->id, $fields)) {
            return new $fields[$this->field->id]($this->field);
        }

        return false;
    }
}