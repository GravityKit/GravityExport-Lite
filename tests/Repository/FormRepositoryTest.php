<?php

namespace GFExcel\Tests\Repository;

use GFExcel\Repository\FormRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see FormRepository}.
 * @since $ver$
 */
class FormRepositoryTest extends TestCase
{
    /**
     * The class under test.
     * @since $ver$
     * @var FormRepository
     */
    private $repository;

    /**
     * Mocked GFAPI instance.
     * @since $ver$
     * @var \GFAPI|MockObject
     */
    private $api;

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('GFAPI')
            ->addMethods(['get_entries'])
            ->getMock();

        $this->repository = new FormRepository($this->api);
    }

    /**
     * Test case for {@see FormRepository::getEntries}.
     * @since $ver$
     */
    public function testGetEntries(): void
    {
        $this->api
            ->expects($this->once())
            ->method('get_entries')
            ->with(1, ['active' => true], ['field' => 'ASC'])
            ->willReturn($results = [['result']]);

        $generator = $this->repository->getEntries(1, ['active' => true], ['field' => 'ASC']);
        $this->assertInstanceOf(\Generator::class, $generator);
        $this->assertSame($results, iterator_to_array($generator));
    }
}
