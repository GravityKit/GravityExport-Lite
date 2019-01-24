<?php

namespace GFExcel\Transformer;

use GF_Field;
use GFExcel\Field\BaseField;
use GFExcel\Field\FieldInterface;
use GFExcel\Field\SeparableField;

class Transformer implements TransformerInterface
{
    /**
     * List of specific field classes
     * @var array
     */
    protected $fields = [
        'calculation' => 'GFExcel\Field\ProductField',
        'checkbox' => 'GFExcel\Field\BaseField', //hard reset, this format makes more sense.
        'date' => 'GFExcel\Field\DateField',
        'fileupload' => 'GFExcel\Field\FileUploadField',
        'list' => 'GFExcel\Field\ListField',
        'meta' => 'GFExcel\Field\MetaField',
        'name' => 'GFExcel\Field\SeparableField',
        'notes' => 'GFExcel\Field\NotesField',
        'number' => 'GFExcel\Field\NumberField',
        'singleproduct' => 'GFExcel\Field\ProductField',
        'section' => 'GFExcel\Field\SectionField',
    ];

    /**
     * Transform GF_Field instance to a GFExcel Field (FieldInterface)
     * @param GF_Field $field
     * @return FieldInterface
     */
    public function transform(GF_Field $field)
    {
        $type = $field->get_input_type();

        // do we have a predfined type?
        if ($fieldClass = $this->getField($type, $field)) {
            return $fieldClass;
        }

        // maybe is separable, maybe it's maybaline!
        if (is_array($field->get_entry_inputs())) {
            return new SeparableField($field);
        }

        // Ya basic!
        return new BaseField($field);
    }

    /**
     * Get Field class if it exists
     * @param string $type
     * @param GF_Field $field
     * @return false|FieldInterface
     */
    private function getField($type, GF_Field $field)
    {
        $fields = $this->getFields();
        if (array_key_exists($type, $fields)) {
            return new $fields[$type]($field);
        }
        return false;
    }

    /**
     * Get the list of fields, but hooked so we can append.
     * @return array
     */
    private function getFields()
    {
        return (array) gf_apply_filters([
            "gfexcel_transformer_fields",
        ], $this->fields);
    }
}
