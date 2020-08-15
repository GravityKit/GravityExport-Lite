<?php

namespace GFExcel\Tests\Values;

use GFExcel\Exception\WrongValueException;
use GFExcel\Field\AbstractField;
use GFExcel\Values\BaseValue;
use GFExcel\Values\BoolValue;
use GFExcel\Values\StringValue;
use WP_Mock\Functions;

/**
 * Unit tests for {@see BaseValue}.
 * @since 1.8.0
 */
class BaseValueTest extends AbstractValueTestCase
{
    /**
     * The class under test.
     * @since 1.8.0
     * @var BaseValue
     */
    private $value_object;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setup(): void
    {
        parent::setup();
        $this->gf_field->id = 112;
        $this->value_object = new ConcreteBaseValue(1000, $this->gf_field);
    }

    /**
     * Test case for {@see BaseValue::getValue()}.
     * @since 1.8.0
     */
    public function testGetValue(): void
    {
        $this->assertSame('1000', $this->value_object->getValue());
    }

    /**
     * Test case for {@see BaseValue::__toString()}.
     * @since 1.8.0
     */
    public function testToString(): void
    {
        $this->assertSame('1000', (string) $this->value_object);
    }

    /**
     * Test case for {@see BaseValue::isNumeric()}.
     * @since 1.8.0
     */
    public function testIsNumeric(): void
    {
        $this->assertTrue($this->value_object->isNumeric());
    }

    /**
     * Test case for {@see BaseValue::isBool()}.
     * @since 1.8.0
     */
    public function testIsBool(): void
    {
        $this->assertTrue($this->value_object->isBool());
    }

    /**
     * Test case for {@see BaseValue::isBold()}.
     * @since 1.8.0
     */
    public function testIsBold(): void
    {
        $this->assertFalse($this->value_object->isBold());
        $this->assertSame($this->value_object, $this->value_object->setBold(true));
        $this->assertTrue($this->value_object->isBold());
    }

    /**
     * Test case for {@see BaseValue::isItalic()}.
     * @since 1.8.0
     */
    public function testIsItalic(): void
    {
        $this->assertFalse($this->value_object->isItalic());
        $this->assertSame($this->value_object, $this->value_object->setItalic(true));
        $this->assertTrue($this->value_object->isItalic());
    }

    /**
     * Test case for {@see BaseValue::getColor()}, {@see BaseValue::setColor()}, {@see BaseValue::getBackgroundColor()} and {@see BaseValue::setBackgroundColor()}.
     * @since 1.8.0
     */
    public function testGetColors(): void
    {
        $this->assertNull($this->value_object->getColor());
        $this->assertNull($this->value_object->getBackgroundColor());
        $this->assertSame($this->value_object, $this->value_object->setColor('#123456'));
        $this->assertSame($this->value_object, $this->value_object->setBackgroundColor('#123456'));
        $this->assertSame('123456', $this->value_object->getColor());
        $this->assertSame('123456', $this->value_object->getBackgroundColor());
    }

    /**
     * Test case for {@see BaseValue::getColor()} and {@see BaseValue::setColor()} with an invalid color.
     * @since 1.8.0
     */
    public function testGetColorException(): void
    {
        $this->value_object->setColor('fake');
        $this->expectExceptionObject(
            new WrongValueException(
                'The color should receive a full 6-digit hex-color and a pound sign. eg. #000000.'
            )
        );
        $this->value_object->getColor();
    }

    /**
     * Test case for {@see BaseValue::getBackgroundColor()} and {@see BaseValue::setBackgroundColor()} with an invalid color.
     * @since 1.8.0
     */
    public function testGetBackgroundColorException(): void
    {
        $this->value_object->setBackgroundColor('fake');
        $this->expectExceptionObject(
            new WrongValueException(
                'The background color should receive a full 6-digit hex-color and a pound sign. eg. #000000.'
            )
        );
        $this->value_object->getBackgroundColor();
    }

    /**
     * Test case for {@see BaseValue::getUrl()} and {@see BaseValue::setUrl()}.
     * @since 1.8.0
     */
    public function testGetUrl(): void
    {
        $this->assertNull($this->value_object->getUrl());
        $this->value_object->setUrl('test.url/stuff');
        $this->assertSame('test.url/stuff', $this->value_object->getUrl());
    }

    /**
     * Test case for {@see BaseValue::getFontSize()} and {@see BaseValue::setFontSize()}.
     * @since 1.8.0
     */
    public function testGetFontSize(): void
    {
        $this->assertNull($this->value_object->getFontSize());
        $this->assertSame($this->value_object, $this->value_object->setFontSize(11.111));
        $this->assertSame(11.111, $this->value_object->getFontSize());
        $this->assertNull($this->value_object->setFontSize(null)->getFontSize());
    }

    /**
     * Test case for {@see BaseValue::getField()}.
     * @since 1.8.0
     */
    public function testGetField(): void
    {
        $this->assertSame($this->gf_field, $this->value_object->getField());
    }

    /**
     * Test case for {@see BaseValue::getFieldId()}.
     * @since 1.8.0
     */
    public function testGetFieldId(): void
    {
        $this->assertSame('112', $this->value_object->getFieldId());
    }

    /**
     * Test case for {@see BaseValue::getFieldType()}
     * @since 1.8.0
     */
    public function testGetFieldType(): void
    {
        $this->gf_field->expects($this->once())->method('get_input_type')->willReturn('some_type');
        $this->assertSame('some_type', $this->value_object->getFieldType());
    }

    /**
     * Data provider for {@see BaseValueTest::testGetValueObject()}.
     * @since 1.8.0
     * @return mixed[] The provided data.
     */
    public function dataProviderForGetValueObject(): array
    {
        return [
            ['bool', BoolValue::class, false],
            ['bool', StringValue::class, true],
            ['invalid', StringValue::class, false],
        ];
    }

    /**
     * Test case for {@see BaseValue::getValueObject()}.
     * @since 1.8.0
     * @dataProvider dataProviderForGetValueObject The data provider.
     * @param string $type The field type.
     * @param string $expected_type_class The expected type class.
     * @param bool $is_label Whether the field is a label.
     */
    public function testGetValueObject(string $type, string $expected_type_class, bool $is_label): void
    {
        $field = $this->createMock(AbstractField::class);
        $this->gf_field->formId = 1;
        $this->gf_field->expects($this->exactly(2))->method('get_input_type')->willReturn('input_type');
        $field->expects($this->once())->method('getValueType')->willReturn($type);

        \WP_Mock::userFunction('gf_apply_filters', [
            'args' => [
                ['gfexcel_value_type', 'input_type', 1, 112],
                $type,
                $this->gf_field,
                $is_label
            ],
            'times' => 1,
            'return' => $type,
        ]);

        \WP_Mock::userFunction('gf_apply_filters', [
            'args' => [
                ['gfexcel_value_object', 'input_type', 1, 112],
                Functions::type($expected_type_class),
                $this->gf_field,
                $is_label
            ],
            'times' => 1,
            'return' => new $expected_type_class('value', $this->gf_field),
        ]);

        $this->assertEquals(
            new $expected_type_class('value', $this->gf_field),
            BaseValue::getValueObject($field, 'value', $this->gf_field, $is_label)
        );
    }

    /**
     * Test case for {@see BaseValue::hasBorder()}.
     * @since 1.8.0
     */
    public function testHasBorder(): void
    {
        $this->assertFalse($this->value_object->hasBorder());
        $this->assertSame($this->value_object, $this->value_object->setBorder('#CCCCCC'));
        $this->assertTrue($this->value_object->hasBorder());
        $this->assertFalse($this->value_object->removeBorder()->hasBorder());
    }

    /**
     * Test case for {@see BaseValue::setBorder()}, {@see BaseValue::getBorderColor()} and {@see BaseValue::getBorderPosition()}.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testSetBorder(): void
    {
        $this->assertSame($this->value_object, $this->value_object->setBorder('#CCCCCC', 'left'));
        $this->assertSame('CCCCCC', $this->value_object->getBorderColor());
        $this->assertSame('left', $this->value_object->getBorderPosition());
    }

    /**
     * Test case for {@see BaseValue::removeBorder()}.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testRemoveBorder(): void
    {
        $this->value_object->setBorder('#CCCCCC', 'left');
        $this->value_object->removeBorder();
        $this->assertNull($this->value_object->getBorderColor());
        $this->assertNull($this->value_object->getBorderPosition());
    }

    /**
     * Test case for {@see BaseValue::getBorderPosition()}.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testGetBorderPosition(): void
    {
        $this->assertNull($this->value_object->getBorderPosition());
        $this->assertSame($this->value_object, $this->value_object->setBorder('#CCCCCC'));
        $this->assertSame('allBorders', $this->value_object->getBorderPosition());
        $this->assertSame($this->value_object, $this->value_object->setBorder('#CCCCCC', 'left'));
        $this->assertSame('left', $this->value_object->getBorderPosition());
    }

    /**
     * Test case for {@see BaseValue::getBorderPosition()} with an invalid position.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testGetBorderPositionWithException(): void
    {
        $this->value_object->setBorder('#CCCCCC', 'invalid');
        $this->expectExceptionObject(
            new WrongValueException('The border position "invalid" is invalid. It should be one of: left, right, top, bottom, allBorders.')
        );
        $this->value_object->getBorderPosition();
    }

    /**
     * Test case for {@see BaseValue::getBorderColor()}.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testGetBorderColor(): void
    {
        $this->assertNull($this->value_object->getBorderColor());
        $this->assertNull($this->value_object->setBorder()->getBorderColor());
        $this->assertSame('BADA55', $this->value_object->setBorder('#BADA55')->getBorderColor());
    }

    /**
     * Test case for {@see BaseValue::getBorderColor()} with an invalid color.
     * @since 1.8.0
     * @throws WrongValueException
     */
    public function testGetBorderColorWithException(): void
    {
        $this->assertNull($this->value_object->getBorderColor());
        $this->value_object->setBorder('invalid');
        $this->expectExceptionObject(
            new WrongValueException('The color should receive a full 6-digit hex-color and a pound sign. eg. #000000.')
        );
        $this->value_object->getBorderColor();
    }
}

/**
 * @since 1.8.0
 */
class ConcreteBaseValue extends BaseValue
{
    protected $is_bool = true;

    protected $is_numeric = true;
}
