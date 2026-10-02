<?php

namespace GFExcel\Tests\Field;

use GFExcel\Field\FieldInterface;
use GFExcel\Field\RepeaterField;
use GFExcel\Tests\TestCase;
use GFExcel\Transformer\Combiner;
use GFExcel\Transformer\Transformer;
use GFExcel\Values\BaseValue;
use GFExcel\Values\StringValue;

/**
 * Unit tests for {@see RepeaterField}.
 *
 * The form used throughout mirrors a Gravity Forms Repeater (field 10) holding, in order:
 * a nested repeater "Phones" (13, with sub-field 14), a text field "Role" (11) and a
 * two-input Name field (12.3 / 12.6). Entries are in the hydrated shape `GF_Query` returns.
 *
 * @since TBD
 */
final class RepeaterFieldTest extends TestCase {
	/**
	 * Glue the `gfexcel_combiner_glue` filter returns; null keeps the default.
	 * @since TBD
	 * @var string|null
	 */
	private static $combiner_glue;

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	public function setUp(): void {
		parent::setUp();

		self::$combiner_glue = null;

		\WP_Mock::userFunction( 'gf_apply_filters', [
			'return' => static function ( array $hooks, $value ) {
				return 'gfexcel_combiner_glue' === $hooks[0] && null !== self::$combiner_glue ? self::$combiner_glue : $value;
			},
		] );
	}

	/**
	 * Creates a Gravity Forms field double.
	 *
	 * Keep the method list identical to {@see \GFExcel\Tests\Transformer\TransformerTest}: PHPUnit
	 * reuses whichever `GF_Field` mock class is declared first.
	 *
	 * @since TBD
	 *
	 * @param array $properties Public properties to set on the field.
	 *
	 * @return \GF_Field The field double.
	 */
	private function gfField( array $properties ): \GF_Field {
		$field = $this->getMockBuilder( \stdClass::class )
		              ->setMockClassName( 'GF_Field' )
		              ->setMethods( [ 'get_input_type', 'get_entry_inputs' ] )
		              ->getMock();

		$field->method( 'get_input_type' )->willReturn( $properties['type'] ?? 'text' );
		$field->formId = 1;

		foreach ( $properties as $key => $value ) {
			$field->{$key} = $value;
		}

		return $field;
	}

	/**
	 * Builds the repeater under test, with a nested repeater as its first sub-field.
	 *
	 * @since TBD
	 *
	 * @return RepeaterField The repeater transformer.
	 */
	private function repeater(): RepeaterField {
		$phone  = $this->gfField( [ 'id' => 14, 'label' => 'Phone', 'inputIds' => [ '14' ] ] );
		$phones = $this->gfField( [ 'id' => 13, 'type' => 'repeater', 'label' => 'Phones', 'fields' => [ $phone ] ] );
		$role   = $this->gfField( [ 'id' => 11, 'label' => 'Role', 'inputIds' => [ '11' ] ] );
		$name   = $this->gfField( [ 'id' => 12, 'type' => 'name', 'label' => 'Name', 'inputIds' => [ '12.3', '12.6' ] ] );

		$people = $this->gfField( [
			'id'     => 10,
			'type'   => 'repeater',
			'label'  => 'People',
			'fields' => [ $phones, $role, $name ],
		] );

		$repeater = ( new RepeaterTestTransformer() )->transform( $people );
		self::assertInstanceOf( RepeaterField::class, $repeater );

		return $repeater;
	}

	/**
	 * Reads the string values out of a list of cells.
	 *
	 * @since TBD
	 *
	 * @param BaseValue[] $cells The cells.
	 *
	 * @return string[] The values.
	 */
	private static function values( array $cells ): array {
		return array_map( static function ( BaseValue $cell ): string {
			return $cell->getValue();
		}, array_values( $cells ) );
	}

	/**
	 * A row with no items in a nested repeater still fills the nested repeater's column, so the
	 * values after it stay under their own headers.
	 *
	 * @since TBD
	 */
	public function testRowsKeepColumnsWhenANestedRepeaterIsEmpty(): void {
		$field = $this->repeater();

		$rows = $field->getRows( [
			10 => [
				0 => [ 13 => [ [ 14 => '111' ], [ 14 => '222' ] ], 11 => 'Lead', '12.3' => 'Ada', '12.6' => 'Lovelace' ],
				// Row indexes can have gaps on entries written through the API.
				2 => [ 11 => 'Dev', '12.3' => 'Alan', '12.6' => 'Turing' ],
			],
		] );

		self::assertCount( 4, $field->getColumns() );
		self::assertCount( 2, $rows );
		self::assertSame( [ '111, 222', 'Lead', 'Ada', 'Lovelace' ], self::values( $rows[0] ) );
		self::assertSame( [ '', 'Dev', 'Alan', 'Turing' ], self::values( $rows[1] ) );
	}

	/**
	 * Without multi-row splitting every column joins its own rows' values.
	 *
	 * @since TBD
	 */
	public function testCellsJoinEachColumnsOwnValues(): void {
		$cells = $this->repeater()->getCells( [
			10 => [
				[ 13 => [ [ 14 => '111' ] ], 11 => 'Lead', '12.3' => 'Ada', '12.6' => 'Lovelace' ],
				[ 11 => 'Dev', '12.3' => 'Alan', '12.6' => 'Turing' ],
			],
		] );

		self::assertSame(
			[ "111\n---\n", "Lead\n---\nDev", "Ada\n---\nAlan", "Lovelace\n---\nTuring" ],
			self::values( $cells )
		);
	}

	/**
	 * The export joins repeater rows with one separator in every column, whatever the sub-field type,
	 * so the Nth value in each column belongs to the same repeater row.
	 *
	 * @since TBD
	 */
	public function testCombinerJoinsRepeaterRowsWithTheRepeaterSeparator(): void {
		// A sub-field type with its own combiner glue, as the Checkbox transformer registers.
		self::$combiner_glue = ', ';

		$combiner = new Combiner();
		$combiner->parseEntry( [ $this->repeater() ], [
			10 => [
				[ 11 => 'Lead', '12.3' => 'Ada', '12.6' => 'Lovelace' ],
				[ 11 => 'Dev', '12.3' => 'Alan', '12.6' => 'Turing' ],
			],
		] );

		$rows = iterator_to_array( $combiner->getRows() );

		self::assertCount( 1, $rows );
		self::assertSame( [ '', "Lead\n---\nDev", "Ada\n---\nAlan", "Lovelace\n---\nTuring" ], self::values( $rows[0] ) );
	}

	/**
	 * An empty repeater still returns one (empty) cell per column.
	 *
	 * @since TBD
	 */
	public function testEmptyRepeaterReturnsOneEmptyCellPerColumn(): void {
		$field = $this->repeater();

		self::assertSame( [], $field->getRows( [ 10 => [] ] ) );
		self::assertSame( [ '', '', '', '' ], self::values( $field->getCells( [ 10 => [] ] ) ) );
		self::assertSame( [ '', '', '', '' ], self::values( $field->getCells( [] ) ) );
	}

	/**
	 * A repeater value that is not a list of rows is treated as empty instead of causing a fatal error.
	 *
	 * @since TBD
	 */
	public function testNonArrayValueIsTreatedAsEmpty(): void {
		$field = $this->repeater();

		self::assertSame( [], $field->getRows( [ 10 => '' ] ) );
		self::assertSame( [], $field->getRows( [ 10 => [ 'not a row' ] ] ) );
	}
}

/**
 * Transformer that returns {@see RepeaterTestField} for plain fields and a {@see RepeaterField} for repeaters.
 * @since TBD
 */
final class RepeaterTestTransformer extends Transformer {
	/**
	 * @inheritDoc
	 * @since TBD
	 */
	public function transform( \GF_Field $field ) {
		if ( 'repeater' !== $field->get_input_type() ) {
			return new RepeaterTestField( $field );
		}

		// Skip the constructor: it reads plugin settings that need Gravity Forms.
		$repeater = ( new \ReflectionClass( RepeaterField::class ) )->newInstanceWithoutConstructor();

		\Closure::bind( function () use ( $field ) {
			/** @var \GF_Field_Repeater $field A `GF_Field` double standing in for the repeater. */
			$this->field = $field;
		}, $repeater, RepeaterField::class )();

		\Closure::bind( function () {
			$this->transformer = new RepeaterTestTransformer();
		}, $repeater, RepeaterField::class )();

		return $repeater;
	}
}

/**
 * A sub-field transformer with one column per input id, reading the value straight from the row.
 * @since TBD
 */
final class RepeaterTestField implements FieldInterface {
	/**
	 * The field.
	 * @since TBD
	 * @var \GF_Field
	 */
	private $field;

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	public function __construct( \GF_Field $field ) {
		$this->field = $field;
	}

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	public function getColumns() {
		return array_map( function ( string $input_id ): BaseValue {
			return new StringValue( $this->field->label . ' ' . $input_id, $this->field );
		}, $this->field->inputIds );
	}

	/**
	 * @inheritDoc
	 * @since TBD
	 */
	public function getCells( $entry ) {
		return array_map( function ( string $input_id ) use ( $entry ): BaseValue {
			return new StringValue( $entry[ $input_id ] ?? '', $this->field );
		}, $this->field->inputIds );
	}
}
