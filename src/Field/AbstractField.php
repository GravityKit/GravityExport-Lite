<?php

namespace GFExcel\Field;

use GF_Field;

abstract class AbstractField implements FieldInterface
{

    protected $field;

    /**
     * AbstractField constructor.
     * @param GF_Field $field
     */
    public function __construct(GF_Field $field)
    {
        $this->field = $field;
    }

    /**
     * Array of needed column names for this field.
     * @return array
     */
    public function getColumns()
    {
        $label = $this->field->get_field_label(true, '');
        $label = gf_apply_filters(
            array(
                "gfexcel_field_label",
                $this->field->get_input_type(),
                $this->field->formId,
                $this->field->id
            ),
            $label, $this->field);
        return array($label);
    }

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    abstract public function getCells($entry);

    /**
     * @internal Get values of combined fields like address
     * @param array $entry
     * @return array
     */
    protected function getSubfields($entry)
    {
        $subfields = array();
        foreach ($entry as $entry_key => $subfield) {
            if (preg_match("/^" . $this->field->id . "\./is", $entry_key)) {
                $subfields[] = $subfield;
            }
        }
        return $subfields;
    }
}