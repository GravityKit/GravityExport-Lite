<?php

namespace GFExcel\Tests\Transformer;

use GFExcel\Field\FieldInterface;
use GFExcel\Field\RowsInterface;
use GFExcel\Tests\TestCase;
use GFExcel\Transformer\Combiner;
use GFExcel\Transformer\CombinerInterface;
use GFExcel\Values\BaseValue;
use GFExcel\Values\BoolValue;
use GFExcel\Values\NumericValue;
use GFExcel\Values\StringValue;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Mock\Functions;

/**
 * Unit tests for {@see Combiner}.
 * @since 1.8.0
 */
class CombinerTest extends TestCase
{
    /**
     * A mocked field instance.
     * @since 1.8.0
     * @var \GF_Field|MockObject
     */
    protected $gf_field;

    /**
     * The class under test.
     * @since 1.8.0
     * @var Combiner
     */
    private $combiner;

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->gf_field =
            $this->getMockBuilder(\stdClass::class)
                ->setMockClassName('GF_Field')
                ->setMethods(['get_value_export', 'get_input_type', 'get_field_label'])
                ->getMock();
        $this->combiner = new Combiner();
    }

    /**
     * Test case for {@see Combiner}'s interface.
     * @since 1.8.0
     */
    public function testInterface(): void
    {
        $this->assertInstanceOf(CombinerInterface::class, $this->combiner);
    }

    /**
     * Test case for {@see Combiner::parseEntry()} with a single row and no {@see RowsInterface].
     * @since 1.8.0
     */
    public function testParseEntryWithOneValue(): void
    {
        $this->gf_field->id = 1;
        $this->gf_field->formId = 1;

        $field = $this->createMock(FieldInterface::class);
        $field->expects($this->once())->method('getColumns')->willReturn(['first', 'last']);
        $field->expects($this->once())->method('getCells')->with([])->willReturn([
            new BoolValue(true, $this->gf_field),
            new NumericValue('2', $this->gf_field),
        ]);

        $this->combiner->parseEntry([$field], []);
        $rows = iterator_to_array($this->combiner->getRows());
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows[0]);

        $this->assertInstanceOf(BoolValue::class, $rows[0][0]);
        $this->assertInstanceOf(NumericValue::class, $rows[0][1]);
    }

    /**
     * Test case for {@see Combiner::parseEntry()} with multiple values.
     * @since 1.8.0
     */
    public function testParseEntryWithMultipleValues(): void
    {
        $this->gf_field->id = 1;
        $this->gf_field->formId = 1;

        $field = $this->createMock([RowsInterface::class, FieldInterface::class]);
        $field->expects($this->exactly(2))->method('getRows')->with([])->willReturn([
            [new StringValue('Jane', $this->gf_field), new StringValue('Doe', $this->gf_field)],
            [new StringValue('John', $this->gf_field), new StringValue('Jones', $this->gf_field)],
            [new BoolValue(true, $this->gf_field), new NumericValue('12', $this->gf_field)],
        ]);
        $field->expects($this->exactly(2))->method('getColumns')->willReturn(['first', 'last']);

        $fields = [
            $field,
            $field,
        ];

        \WP_Mock::userFunction('gf_apply_filters', [
            'args' => [
                ['gfexcel_combiner_glue', null, '1'],
                Functions::type('string'),
                Functions::type(BaseValue::class),
            ],
            'return' => '-',
        ]);
        $this->combiner->parseEntry($fields, []);
        $rows = iterator_to_array($this->combiner->getRows());

        $this->assertCount(1, $rows);
        $this->assertCount(4, $rows[0]);
        $expected_values = [
            'Jane-John-1',
            'Doe-Jones-12',
            'Jane-John-1',
            'Doe-Jones-12',
        ];

        foreach ($expected_values as $i => $expected_value) {
            $this->assertInstanceOf(StringValue::class, $rows[0][$i]);
            $this->assertSame($expected_value, $rows[0][$i]->getValue());
        }
    }
}
