<?php

namespace GFExcel\Tests\Transformer;

use GFExcel\Field\FieldInterface;
use GFExcel\Field\SeparableField;
use GFExcel\Tests\TestCase;
use GFExcel\Transformer\Transformer;
use GFExcel\Transformer\TransformerAwareInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see Transformer}.
 * @since 1.1.11
 */
final class TransformerTest extends TestCase {
	/**
	 * The mocked field object.
	 * @since 1.1.11
	 * @var MockObject
	 */
	private $field;

	/**
	 * @inheritDoc
	 * @since 1.1.11
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		// Create on constructor, because this classname can only be declared once.
		$this->field = $this->getMockBuilder( \stdClass::class )
		                    ->setMockClassName( 'GF_Field' )
		                    ->setMethods( [ 'get_input_type', 'get_entry_inputs' ] )
		                    ->getMock();
	}

	/**
	 * Test case for {@see Transformer::getField()} with a custom provided field.
	 * @return void
	 */
	public function testTransformWithCustomField(): void {
		$transformer = new TestTransformer();

		$this->field->method( 'get_input_type' )->willReturn( 'test' );

		$field = $transformer->transform( $this->field );

		self::assertInstanceOf( TestField::class, $field );
		self::assertSame( $transformer, $field->transformer );
	}

	/**
	 * Test case for {@see Transformer::getField()} with a separable field.
	 * @return void
	 */
	public function testTransformWithSeparableField(): void {
		$transformer = new TestTransformer();

		$this->field->method( 'get_input_type' )->willReturn( 'separable' );
		$this->field->method( 'get_entry_inputs' )->willReturn( [ '1', '2' ] );

		$field = $transformer->transform( $this->field );

		self::assertInstanceOf( SeparableField::class, $field );
	}
}

/**
 * Helper test field.
 * @since 1.1.11
 */
final class TestField implements FieldInterface, TransformerAwareInterface {
	public $transformer;

	public function __construct( \GF_Field $field ) {
	}

	public function getColumns() {
	}

	public function getCells( $entry ) {
	}

	public function setTransformer( Transformer $transformer ): void {
		$this->transformer = $transformer;
	}
}

final class TestSeparableField extends SeparableField {
	protected function useAdminLabels(): bool {
		return false;
	}
}

/**
 * Test transformer that overwrites the default fields list.
 * @since 1.1.11
 */
final class TestTransformer extends Transformer {
	protected $fields = [
		'test'      => TestField::class,
		'separable' => TestSeparableField::class,
	];
}
