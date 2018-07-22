<?php


namespace GFExcel\Values;


use GFExcel\Exception\WrongValueException;
use GFExcel\Field\AbstractField;

abstract class BaseValue
{
    const TYPE_STRING = 'string';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_BOOL = 'bool';

    protected $value = '';
    protected $is_numeric = false;
    protected $color = '#000000';
    protected $is_bold = false;
    protected $background_color = '';

    protected $is_italic = false;
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
     * @return bool
     */
    public function isBold()
    {
        return (bool) $this->is_bold;
    }

    /**
     * @return bool
     */
    public function isItalic()
    {
        return (bool) $this->is_bold;
    }

    /**
     * @return string
     * @throws WrongValueException
     */
    public function getColor()
    {
        if (!$this->color) {
            return false;
        }
        if (substr($this->color, 0, 1) !== "#" || strlen($this->color) != 7) {
            throw new WrongValueException('The color should receive a full 6 diget hex-color and a pound sign. eg. #000000.');
        }

        return substr($this->color, 1);
    }

    /**
     * @return bool|string
     * @throws WrongValueException
     */
    public function getBackgroundColor()
    {
        if (!$this->background_color) {
            return false;
        }
        if (substr($this->background_color, 0, 1) !== "#" || strlen($this->background_color) != 7) {
            throw new WrongValueException('The background color should receive a full 6 diget hex-color and a pound sign. eg. #000000.');
        }

        return substr($this->background_color, 1);
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

    /**
     * Set the color of the value
     * @param string $hexcode
     */
    public function setColor($hexcode = "#000000")
    {
        $this->color = $hexcode;
    }

    public function setBackgroundColor($color = '')
    {
        $this->background_color = $color;
    }

    /**
     * Set the value to Bold
     * @param bool $bold
     */
    public function setBold($bold = true)
    {
        $this->is_bold = (boolean) $bold;
    }

    /**
     * Set the value to Bold
     * @param bool $italic
     */
    public function setItalic($italic = true)
    {
        $this->is_italic = (boolean) $italic;
    }
}