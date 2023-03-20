<?php

namespace GFExcel\Values;

use GFExcel\Exception\WrongValueException;
use GFExcel\Field\AbstractField;

/**
 * @since 1.3.0
 */
abstract class BaseValue {
	/**
	 * Variable representing a boolean cell.
	 * @since 1.3.0
	 * @var string
	 */
	public const TYPE_BOOL = 'bool';

	/**
	 * Variable representing a numeric cell.
	 * @since 1.3.0
	 * @var string
	 */
	public const TYPE_NUMERIC = 'numeric';

	/**
	 * Variable representing a string cell (default).
	 * @since 1.3.0
	 * @var string
	 */
	public const TYPE_STRING = 'string';

	/**
	 * Variable representing a boolean cell.
	 * @since 1.8.0
	 * @var string
	 */
	public const TYPE_CURRENCY = 'currency';

	/**
	 * The value of the cell.
	 * @since 1.3.0
	 * @var mixed
	 */
	protected $value = '';

	/**
	 * The gravity forms field for this value.
	 * @since 1.3.0
	 * @var \GF_Field
	 */
	protected $gf_field;

	/**
	 * Whether this is a numeric value.
	 * @since 1.3.0
	 * @var bool
	 */
	protected $is_numeric = false;

	/**
	 * The text color of the cell.
	 * @since 1.4.1
	 * @var string
	 */
	protected $color = '';

	/**
	 * The background color of the cell.
	 * @since 1.4.1
	 * @var string
	 */
	protected $background_color = '';

	/**
	 * Whether the value is bold.
	 * @since 1.4.0
	 * @var bool
	 */
	protected $is_bold = false;

	/**
	 * Whether the value is italic.
	 * @since 1.4.0
	 * @var bool
	 */
	protected $is_italic = false;

	/**
	 * Whether the value is a boolean value.
	 * @since 1.3.0
	 * @var bool
	 */
	protected $is_bool = false;

	/**
	 * The url of the cell.
	 * @since 1.4.0
	 * @var string
	 */
	protected $url;

	/**
	 * The specific font size for this value.
	 * @since 1.8.0
	 * @var null|float
	 */
	protected $font_size;

	/**
	 * The color of the border of the cell.
	 * @since 1.8.0
	 * @var string
	 */
	protected $border_color = '';

	/**
	 * The position of the border.
	 * @since 1.8.0
	 * @var string
	 */
	protected $border_position = '';

	/**
	 * Creates a BaseValue instance.
	 * @since 1.3.0
	 *
	 * @param mixed $value The original value.
	 * @param \GF_Field $gf_field The GF_Field instance.
	 */
	public function __construct( $value, \GF_Field $gf_field ) {
		$this->value    = $value;
		$this->gf_field = $gf_field;
	}

	/**
	 * Fetch a value object for the field
	 * @since 1.3.0
	 *
	 * @param AbstractField $field The field.
	 * @param mixed $value The value from the entry.
	 * @param \GF_Field $gf_field The original GF Field instance.
	 * @param bool $is_label Whether this value object is a label.
	 *
	 * @return BaseValue The value object.
	 */
	public static function getValueObject( AbstractField $field, $value, \GF_Field $gf_field, $is_label = false ) {
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
		if ( $is_label ) {
			$type = BaseValue::TYPE_STRING;
		}

		$typeClass = 'GFExcel\\Values\\' . ucfirst( $type ) . 'Value';
		if ( ! class_exists( $typeClass ) ) {
			// fall back to StringValue
			$typeClass = StringValue::class;
		}

		$valueObject = new $typeClass( $value, $gf_field );

		// Changes to the value object should be done by reference, so we replace change the object itself.
		gf_apply_filters( [
			'gfexcel_value_object',
			$gf_field->get_input_type(),
			$gf_field->formId,
			$gf_field->id
		], $valueObject, $gf_field, $is_label );

		return $valueObject;
	}

	/**
	 * A string representation of the value object.
	 * @since 1.3.0
	 * @return string
	 */
	public function __toString() {
		return (string) $this->getValue();
	}

	/**
	 * Returns the (string) value of this instance.
	 * @since 1.3.0
	 * @return mixed
	 */
	public function getValue() {
		if ( ! is_array( $this->value ) ) {
			return (string) $this->value;
		}

		// Flatten array.
		$value = [];
		array_walk_recursive( $this->value, function ( $a ) use ( &$value ) {
			$value[] = (string) $a;
		} );

		return implode( ', ', $value );
	}

	/**
	 * Returns whether this value is numeric.
	 * @since 1.3.0
	 * @return bool Whether this value object is numeric.
	 */
	public function isNumeric() {
		return (bool) $this->is_numeric;
	}

	/**
	 * Returns whether this value is a boolean.
	 * @since 1.3.0
	 * @return bool
	 */
	public function isBool() {
		return (bool) $this->is_bool;
	}

	/**
	 * Returns whether this value is bold.
	 * @since 1.4.0
	 * @return bool
	 */
	public function isBold() {
		return (bool) $this->is_bold;
	}

	/**
	 * Returns whether this value is italic.
	 * @since 1.4.0
	 * @return bool
	 */
	public function isItalic() {
		return (bool) $this->is_italic;
	}

	/**
	 * Returns the text color (in HEX) of the value.
	 * @since 1.4.0
	 * @return string|null The color.
	 * @throws WrongValueException When the provided color was incorrect.
	 */
	public function getColor() {
		if ( ! $this->color ) {
			return null;
		}

		if ( $this->color[0] !== '#' || strlen( $this->color ) !== 7 ) {
			throw new WrongValueException(
				'The color should receive a full 6-digit hex-color and a pound sign. eg. #000000.'
			);
		}

		return substr( $this->color, 1 );
	}

	/**
	 * * Returns the color (in HEX) of the background.
	 * @since 1.4.0
	 * @return string|null The color.
	 * @throws WrongValueException When the provided color was incorrect.
	 */
	public function getBackgroundColor() {
		if ( ! $this->background_color ) {
			return null;
		}

		if ( $this->background_color[0] !== '#' || strlen( $this->background_color ) !== 7 ) {
			throw new WrongValueException(
				'The background color should receive a full 6-digit hex-color and a pound sign. eg. #000000.'
			);
		}

		return substr( $this->background_color, 1 );
	}

	/**
	 * Returns the url of the cell if provided.
	 * @since 1.3.0
	 * @return string|null The url.
	 */
	public function getUrl() {
		if ( ! $this->url ) {
			return null;
		}

		return trim( strip_tags( $this->url ) );
	}

	/**
	 * Set the url of the value.
	 * @since 1.3.0
	 *
	 * @param string $url The url.
	 *
	 * @return self This instance.
	 */
	public function setUrl( $url ) {
		$this->url = $url;

		return $this;
	}

	/**
	 * Set the color of the value
	 * @since 1.4.0
	 *
	 * @param string $hexcode The color.
	 *
	 * @return self This instance.
	 */
	public function setColor( $hexcode = '#000000' ) {
		$this->color = $hexcode;

		return $this;
	}

	/**
	 * Set the color of the cell background.
	 * @since 1.4.0
	 *
	 * @param string $color The hex color.
	 *
	 * @return self This instance.
	 */
	public function setBackgroundColor( $color = '' ) {
		$this->background_color = $color;

		return $this;
	}

	/**
	 * Set the value to bold.
	 * @since 1.4.0
	 *
	 * @param bool $bold Whether the text should be bold.
	 *
	 * @return self This instance.
	 */
	public function setBold( $bold = true ) {
		$this->is_bold = (boolean) $bold;

		return $this;
	}

	/**
	 * Set the value to italic.
	 * @since 1.4.0
	 *
	 * @param bool $italic Whether the text should be italic.
	 *
	 * @return self This instance.
	 */
	public function setItalic( $italic = true ) {
		$this->is_italic = (boolean) $italic;

		return $this;
	}

	/**
	 * Sets the font size of this value.
	 * @since 1.8.0
	 *
	 * @param null|float $font_size The font size in pt.
	 *
	 * @return self This instance.
	 */
	public function setFontSize( ?float $font_size ): self {
		$this->font_size = $font_size;

		return $this;
	}

	/**
	 * Returns the font size in pt.
	 * @since 1.8.0
	 * @return float|null The font size in pt.
	 */
	public function getFontSize(): ?float {
		return $this->font_size;
	}

	/**
	 * Get the GF_Field object
	 * @since 1.5.5
	 * @return \GF_Field The GF_Field object.
	 */
	public function getField() {
		return $this->gf_field;
	}

	/**
	 * Get the name of the field type.
	 * @since 1.5.5
	 * @return string The field type.
	 */
	public function getFieldType() {
		return $this->getField()->get_input_type();
	}

	/**
	 * Get the ID for this field
	 * @since 1.5.5
	 * @return string|null The id.
	 */
	public function getFieldId() {
		return (string) $this->getField()->id;
	}

	/**
	 * Whether this cell has a border.
	 * @since 1.8.0
	 * @return bool Whether this cell has a border.
	 */
	public function hasBorder(): bool {
		return ! empty( $this->border_position );
	}

	/**
	 * Returns the color of the border.
	 * @since 1.8.0
	 * @return string|null The color of the border.
	 * @throws WrongValueException when the color is nog valid.
	 */
	public function getBorderColor(): ?string {
		if ( empty( $this->border_color ) || ! $this->hasBorder() ) {
			return null;
		}

		if ( $this->border_color[0] !== '#' || strlen( $this->border_color ) !== 7 ) {
			throw new WrongValueException(
				'The color should receive a full 6-digit hex-color and a pound sign. eg. #000000.'
			);
		}

		return substr( $this->border_color, 1 );
	}

	/**
	 * The position of the border.
	 * @since 1.8.0
	 * @return string|null The position of the border. (left, right, top, bottom, allBorders).
	 * @throws WrongValueException When an invalid position was provided.
	 */
	public function getBorderPosition(): ?string {
		if ( ! $this->hasBorder() ) {
			return null;
		}

		$positions = [ 'left', 'right', 'top', 'bottom', 'allBorders' ];
		if ( ! in_array( $this->border_position, $positions, true ) ) {
			throw new WrongValueException( sprintf(
				'The border position "%s" is invalid. It should be one of: %s.',
				$this->border_position,
				implode( ', ', $positions )
			) );
		}

		return $this->border_position;
	}

	/**
	 * Sets the border for a cell.
	 * @since 1.8.0
	 *
	 * @param string $color The color of the border.
	 * @param string $position The position of the border.
	 *
	 * @return self This instance.
	 */
	public function setBorder( string $color = '', string $position = 'allBorders' ): self {
		$this->border_color    = $color;
		$this->border_position = $position;

		return $this;
	}

	/**
	 * Remove the border from a cell.
	 * @since 1.8.0
	 * @return self This instance.
	 */
	public function removeBorder(): self {
		$this->border_color    = '';
		$this->border_position = '';

		return $this;
	}
}
