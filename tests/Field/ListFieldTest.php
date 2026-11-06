<?php

namespace GFExcel\Tests\Field;

use Closure;
use GF_Field;
use GFExcel\Field\ListField;
use GFExcel\Values\BaseValue;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WP_Mock;

/**
 * Unit tests for {@see ListField} with columns.
 *
 * Runs in a separate process so it can load a `GF_Field` with array access, and extends PHPUnit's
 * TestCase because WP_Mock 0.4's TestCase prints a deprecation on PHP 8.4, which fails every
 * separate-process test.
 *
 * @since 2.7.7
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ListFieldTest extends TestCase {
	/**
	 * @inheritDoc
	 * @since 2.7.7
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__ ) . '/stubs/gf-field.php';

		WP_Mock::setUp();
		WP_Mock::userFunction( 'gf_apply_filters', [
			'return' => static function ( array $hooks, $value ) {
				return $value;
			},
		] );
	}

	/**
	 * @inheritDoc
	 * @since 2.7.7
	 */
	protected function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Returns the cell values of every row the field exports when Gravity Forms returns `$value`.
	 *
	 * @since 2.7.7
	 *
	 * @param mixed $value The value `GF_Field::get_value_export()` returns.
	 *
	 * @return string[][] The rows.
	 */
	private function exportedRows( $value ): array {
		$gf_field                = new GF_Field();
		$gf_field->id            = 2;
		$gf_field->formId        = 1;
		$gf_field->type          = 'list';
		$gf_field->enableColumns = true;
		$gf_field->choices       = [
			[ 'text' => 'Item', 'value' => 'Item' ],
			[ 'text' => 'Qty', 'value' => 'Qty' ],
		];
		$gf_field->export_value  = $value;

		// Skip the constructor: it reads plugin settings that need Gravity Forms.
		$field = ( new ReflectionClass( ListField::class ) )->newInstanceWithoutConstructor();
		Closure::bind( function () use ( $gf_field ) {
			$this->field = $gf_field;
		}, $field, ListField::class )();

		$rows = [];
		foreach ( $field->getRows( [ 'id' => 1, 'form_id' => 1 ] ) as $row ) {
			$rows[] = array_map( static function ( BaseValue $cell ): string {
				return $cell->getValue();
			}, $row );
		}

		return $rows;
	}

	/**
	 * A List saved as `a:0:{}` (the REST API stores that for `[]`) comes back from Gravity Forms as
	 * an empty array, and exports one empty row.
	 *
	 * @since 2.7.7
	 */
	public function testEmptyArrayExportsOneEmptyRow(): void {
		self::assertSame( [ [ '', '' ] ], $this->exportedRows( [] ) );
	}

	/**
	 * Rows that are already decoded are exported as they are.
	 *
	 * @since 2.7.7
	 */
	public function testDecodedRowsAreExported(): void {
		$rows = [
			[ 'Item' => 'Apple', 'Qty' => '3' ],
			[ 'Item' => 'Pear', 'Qty' => '2' ],
		];

		self::assertSame( [ [ 'Apple', '3' ], [ 'Pear', '2' ] ], $this->exportedRows( $rows ) );
	}

	/**
	 * The JSON string Gravity Forms returns for a filled List is exported row by row.
	 *
	 * @since 2.7.7
	 */
	public function testJsonRowsAreExported(): void {
		$json = json_encode( [ [ 'Item' => 'Apple', 'Qty' => '3' ] ] );

		self::assertSame( [ [ 'Apple', '3' ] ], $this->exportedRows( $json ) );
	}

	/**
	 * A value that holds no rows exports one empty row.
	 *
	 * @since 2.7.7
	 *
	 * @dataProvider valuesWithoutRowsProvider
	 *
	 * @param mixed $value The value Gravity Forms returns.
	 */
	public function testValueWithoutRowsExportsOneEmptyRow( $value ): void {
		self::assertSame( [ [ '', '' ] ], $this->exportedRows( $value ) );
	}

	/**
	 * Data provider for {@see self::testValueWithoutRowsExportsOneEmptyRow()}.
	 *
	 * @since 2.7.7
	 *
	 * @return array[]
	 */
	public function valuesWithoutRowsProvider(): array {
		return [
			'empty string'      => [ '' ],
			'null'              => [ null ],
			'JSON empty array'  => [ '[]' ],
			'JSON scalar'       => [ '"text"' ],
			'invalid JSON'      => [ 'not json' ],
		];
	}
}
