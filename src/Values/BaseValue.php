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

    protected $url;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Fetch a value object for the field
     *
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
            $field->getValueType(),
            $gf_field);

        $valueObject = new StringValue($value);

        $typeClass = 'GFExcel\\Values\\' . ucfirst($type) . "Value";
        if (class_exists($typeClass)) {
            $valueObject = new $typeClass($value);
        }

        gf_apply_filters(
            array(
                "gfexcel_value_object",
                $gf_field->get_input_type(),
                $gf_field->formId,
                $gf_field->id
            ),
            $valueObject,
            $gf_field);

        return $valueObject;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return (string) $this->value;
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return (bool) $this->is_numeric;
    }

    /**
     * @return bool
     */
    public function isBool()
    {
        return (bool) $this->is_bool;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (!$this->url) {
            return false;
        }

        return trim(strip_tags($this->url));
    }

    /**
     * Set the url of the value
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}