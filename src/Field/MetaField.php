<?php

namespace GFExcel\Field;

use GF_Field;

class MetaField extends BaseField
{
    protected $subfields = array(
        'date_created' => 'GFExcel\Field\Meta\DateCreated',
    );

    public function __construct(GF_Field $field)
    {
        parent::__construct($field);
    }

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

        $value = $this->field->get_value_export($entry);
        $value = gf_apply_filters(
            array(
                "gfexcel_meta_value",
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ),
            $value, $entry);

        return array($value);
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