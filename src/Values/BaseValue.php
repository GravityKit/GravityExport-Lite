<?php


namespace GFExcel\Values;


use GFExcel\Field\AbstractField;

abstract class BaseValue
{
    const TYPE_STRING = 'string';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_BOOL = 'bool';

    protected $value = '';
    protected $is_numeric = false;
    protected $is_bool = false;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param AbstractField $field
     * @param string $value
     * @param \GF_Field $gf_field
     * @return BaseValue
     */
    public static function getValueObject(AbstractField $field, $value, \GF_Field $gf_field)
    {
        $type = gf_apply_filters(
            array(
                "gfexcel_value_type",
                $gf_field->get_input_type(),
                $gf_field->formId,
                $gf_field->id
            ),
            $field->getValueType());

        $typeClass = 'GFExcel\\Values\\' . ucfirst($type) . "Value";
        if (class_exists($typeClass)) {
            return new $typeClass($value);
        }

        return new StringValue($value);
    }

    public function __toString()
    {
        return (string) $this->value;
    }

    public function getValue()
    {
        return (string) $this->value;
    }

    public function isNumeric()
    {
        return (bool) $this->is_numeric;
    }

    public function isBool()
    {
        return (bool) $this->is_bool;
    }
}