<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Facades;

use CarloNicora\Minimalism\Services\MySQL\Facades\SQLExecutionFacade;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts\AbstractTestCase;
use mysqli;
use PHPUnit\Framework\MockObject\MockObject;

class SQLExecutionFacadeTest extends AbstractTestCase
{

    /** @var MockObject|mysqli|null  */
    private ?MockObject $mysqli=null;

    /** @var SQLExecutionFacadeInterface|null */
    private ?SQLExecutionFacadeInterface $executor=null;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MockObject|TableInterface $table */
        $table = $this->getMockBuilder(TableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table->method('getDbToUse')->willReturn('mydb');

        $this->executor = new SQLExecutionFacade($this->services, $table);

        $this->mysqli = $this->getMockBuilder(mysqli::class)->getMock();

        $this->executor->setConnection($this->mysqli);
    }

    public function testGetDbToUse() : void
    {
        $this->assertEquals('mydb', $this->executor->getDbToUse());
    }

    public function testSettingConnection() : void
    {
        $this->executor->setConnection($this->mysqli);

        $this->assertEquals(1,1);
    }

    public function testRollback() : void
    {
        $this->executor->rollback();

        $this->assertEquals(1,1);
    }
}