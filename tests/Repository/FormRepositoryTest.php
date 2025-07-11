<?php

namespace GFExcel\Tests\Repository;

use GFExcel\Repository\FormRepository;
use GFExcel\Routing\Router;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see FormRepository}.
 * @since 2.4.0
 */
class FormRepositoryTest extends TestCase {
	/**
	 * The class under test.
	 * @since 2.4.0
	 * @var FormRepository
	 */
	private $repository;

	/**
	 * Mocked GFAPI instance.
	 * @since 2.4.0
	 * @var \GFAPI|MockObject
	 */
	private $api;

	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->api = $this->getMockBuilder( \stdClass::class )
		                  ->setMockClassName( 'GFAPI' )
		                  ->addMethods( [ 'get_entries' ] )
		                  ->getMock();

		$this->repository = new FormRepository( $this->api, $this->createMock( Router::class ) );
	}

	/**
	 * Test case for {@see FormRepository::getEntries}.
	 * @since 2.4.0
	 */
	public function testGetEntries(): void {
		$this->api
			->expects( $this->once() )
			->method( 'get_entries' )
			->with( 1, [ 'active' => true ], [ 'field' => 'ASC' ] )
			->willReturn( $results = [ [ 'result' ] ] );

		$generator = $this->repository->getEntries( 1, [ 'active' => true ], [ 'field' => 'ASC' ] );
		$this->assertInstanceOf( \Generator::class, $generator );
		$this->assertSame( $results, iterator_to_array( $generator ) );
	}
}
