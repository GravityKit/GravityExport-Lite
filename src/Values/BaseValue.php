<?php

namespace GFExcel\Values;

use GFExcel\Exception\WrongValueException;
use GFExcel\Field\AbstractField;

abstract class BaseValue
{
    public const TYPE_BOOL = 'bool';

    public const TYPE_NUMERIC = 'numeric';

    public const TYPE_STRING = 'string';

    public const TYPE_CURRENCY = 'currency';

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

    /**
     * Creates a BaseValue instance.
     * @since 1.3.0
     * @param string $value The original value.
     * @param \GF_Field $gf_field The GF_Field instance.
     */
    public function __construct($value, \GF_Field $gf_field)
    {
        $this->value = $value;
        $this->gf_field = $gf_field;
    }

    /**
     * Fetch a value object for the field
     * @since 1.3.0
     * @param AbstractField $field The field.
     * @param string $value The value from the entry.
     * @param \GF_Field $gf_field The original GF Field instance.
     * @param bool $is_label Whether this value object is a label.
     * @return BaseValue The value object.
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

        // Labels are always parsed as a string.
        if ($is_label) {
            $type = BaseValue::TYPE_STRING;
        }

        $typeClass = 'GFExcel\\Values\\' . ucfirst($type) . 'Value';
        if (!class_exists($typeClass)) {
            // fall back to StringValue
            $typeClass = StringValue::class;
        }

        $valueObject = new $typeClass($value, $gf_field);

        // Changes to the value object should be done by reference, so we replace change the object itself.
        gf_apply_filters([
            'gfexcel_value_object',
            $gf_field->get_input_type(),
            $gf_field->formId,
            $gf_field->id
        ], $valueObject, $gf_field, $is_label);

        return $valueObject;
    }

    /**
     * A string representation of the value object.
     * @since 1.3.0
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Returns the (string) value of this instance.
     * @since 1.3.0
     * @return string
     */
    public function getValue()
    {
        return (string) $this->value;
    }

    /**
     * Returns whether this value is numeric.
     * @since 1.3.0
     * @return bool Whether this value object is numeric.
     */
    public function isNumeric()
    {
        return (bool) $this->is_numeric;
    }

    /**
     * Returns whether this value is a boolean.
     * @since 1.3.0
     * @return bool
     */
    public function isBool()
    {
        return (bool) $this->is_bool;
    }

    /**
     * Returns whether this value is bold.
     * @since 1.4.0
     * @return bool
     */
    public function isBold()
    {
        return (bool) $this->is_bold;
    }

    /**
     * Returns whether this value is italic.
     * @since 1.4.0
     * @return bool
     */
    public function isItalic()
    {
        return (bool) $this->is_italic;
    }

    /**
     * Returns the text color (in HEX) of the value.
     * @since 1.4.0
     * @return string|null The color.
     * @throws WrongValueException When the provided color was incorrect.
     */
    public function getColor()
    {
        if (!$this->color) {
            return null;
        }

        if ($this->color[0] !== '#' || strlen($this->color) !== 7) {
            throw new WrongValueException(
                'The color should receive a full 6-diget hex-color and a pound sign. eg. #000000.'
            );
        }

        return substr($this->color, 1);
    }

    /**
     * * Returns the color (in HEX) of the background.
     * @since 1.4.0
     * @return string|null The color.
     * @throws WrongValueException When the provided color was incorrect.
     */
    public function getBackgroundColor()
    {
        if (!$this->background_color) {
            return null;
        }

        if ($this->background_color[0] !== '#' || strlen($this->background_color) !== 7) {
            throw new WrongValueException(
                'The background color should receive a full 6 diget hex-color and a pound sign. eg. #000000.'
            );
        }

        return substr($this->background_color, 1);
    }

    /**
     * Returns the url of the cell if provided.
     * @since 1.3.0
     * @return string|null The url.
     */
    public function getUrl()
    {
        if (!$this->url) {
            return null;
        }

        return trim(strip_tags($this->url));
    }

    /**
     * Set the url of the value
     * @since 1.3.0
     * @param string $url The url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set the color of the value
     * @since 1.4.0
     * @param string $hexcode The color.
     */
    public function setColor($hexcode = '#000000')
    {
        $this->color = $hexcode;
    }

    /**
     * Set the color of the cell background.
     * @since 1.4.0
     * @param string $color The hex color.
     */
    public function setBackgroundColor($color = '')
    {
        $this->background_color = $color;
    }

    /**
     * Set the value to bold.
     * @since 1.4.0
     * @param bool $bold Whether the text should be bold.
     */
    public function setBold($bold = true)
    {
        $this->is_bold = (boolean) $bold;
    }

    /**
     * Set the value to italic.
     * @since 1.4.0
     * @param bool $italic Whether the text should be italic.
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
     * @since 1.5.5
     * @return \GF_Field The GF_Field object.
     */
    public function getField()
    {
        return $this->gf_field;
    }

    /**
     * Get the name of the field type.
     * @since 1.5.5
     * @return string The field type.
     */
    public function getFieldType()
    {
        return $this->getField()->get_input_type();
    }

    /**
     * Get the ID for this field
     * @since 1.5.5
     * @return string|null The id.
     */
    public function getFieldId()
    {
        if (!$this->getField()) {
            return null;
        }

        return (string) $this->getField()->id;
    }
}
