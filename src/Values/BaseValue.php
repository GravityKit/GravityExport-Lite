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

    protected $gf_field;

    /**
     * Whether this is a numeric value.
     * @since 1.3.0
     * @var bool
     */
    protected $is_numeric = false;

    protected $color = '';

    protected $background_color = '';

    protected $is_bold = false;

    protected $is_italic = false;

    protected $is_bool = false;

    protected $url;

    /**
     * The specific font size for this value.
     * @since $ver$
     * @var null|float
     */
    protected $font_size;

    public function __construct($value, \GF_Field $gf_field)
    {
        $this->value = $value;
        $this->gf_field = $gf_field;
    }

    /**
     * Fetch a value object for the field
     *
     * @param AbstractField $field
     * @param string $value
     * @param \GF_Field $gf_field
     * @param bool $is_label
     * @return BaseValue
     */
    public static function getValueObject(AbstractField $field, $value, \GF_Field $gf_field, $is_label = false)
    {
        $type = gf_apply_filters(
            [
                'gfexcel_value_type',
                $gf_field->get_input_type(),
                $gf_field->formId,
                $gf_field->id
            ],
            $field->getValueType(),
            $gf_field,
            $is_label
        );

        if ($is_label) {
            $type = BaseValue::TYPE_STRING;
        }

        $typeClass = 'GFExcel\\Values\\' . ucfirst($type) . "Value";
        if (!class_exists($typeClass)) {
            //fall back to StringValue
            $typeClass = StringValue::class;
        }

        $valueObject = new $typeClass($value, $gf_field);

        gf_apply_filters(
            [
                "gfexcel_value_object",
                $gf_field->get_input_type(),
                $gf_field->formId,
                $gf_field->id
            ],
            $valueObject,
            $gf_field,
            $is_label
        );

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
        return (bool) $this->is_italic;
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
            throw new WrongValueException(
                'The color should receive a full 6 diget hex-color and a pound sign. eg. #000000.'
            );
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
            throw new WrongValueException(
                'The background color should receive a full 6 diget hex-color and a pound sign. eg. #000000.'
            );
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

    /**
     * Sets the font size of this value.
     * @since $ver$
     * @param null|float $font_size The font size in pt.
     */
    public function setFontSize(?float $font_size): void
    {
        $this->font_size = $font_size;
    }

    /**
     * Returns the font size in pt.
     * @since $ver$
     * @return float|null The font size in pt.
     */
    public function getFontSize(): ?float
    {
        return $this->font_size;
    }

    /**
     * Get the GF_Field object
     * @return \GF_Field
     */
    public function getField()
    {
        return $this->gf_field;
    }

    /**
     * Get the name of the field type
     * @return string
     */
    public function getFieldType()
    {
        if (!$this->getField()) {
            return 'unknown type';
        }

        return $this->getField()->get_input_type();
    }

    /**
     * Get the ID for this field
     * @return mixed
     */
    public function getFieldId()
    {
        if (!$this->getField()) {
            return null;
        }

        return $this->getField()->id;
    }
}
