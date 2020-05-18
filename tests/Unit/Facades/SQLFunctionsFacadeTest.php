<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Facades\RecordFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLFunctionsFacade;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts\AbstractTestCase;
use mysqli_result;
use mysqli_stmt;
use PHPUnit\Framework\MockObject\MockObject;

class SQLFunctionsFacadeTest extends AbstractTestCase
{
    /** @var MockObject|TableInterface|null  */
    private ?MockObject $table=null;

    /** @var MockObject|null|SQLExecutionFacadeInterface  */
    private ?MockObject $executor=null;

    /** @var SQLFunctionsFacade|null  */
    private ?SQLFunctionsFacade $facade=null;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = $this->getMockBuilder(TableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->table
            ->method('getSql')
            ->willReturn('SELECT * FROM tablename WHERE id=?;');

        $this->table
            ->method('getAutoIncrementField')
            ->willReturn('id');

        $this->table
            ->method('getParameters')
            ->willReturn(['i', 1]);

        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executor
            ->method('getInsertedId')
            ->willReturn(2);

        /** @var MockObject|mysqli_result $result */
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement->method('get_result')
            ->willReturn($result);

        $this->executor
            ->method('executeQuery')
            ->willReturn($statement);

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);
    }

    /**
     * @throws DbSqlException
     */
    public function testRunSql() : void
    {
        $this->facade->runSql();

        $this->assertEquals(1,1);
    }

    /**
     * @throws DbSqlException
     */
    public function testRunSqlThrowsException() : void
    {
        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->executor
            ->method('toggleAutoCommit')
            ->willThrowException(new DbSqlException('',1));

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        $this->expectExceptionCode(1);

        $this->facade->runSql();

        $this->assertEquals(1,1);
    }

    /**
     * @throws DbSqlException
     */
    public function testRunRead() : void
    {
        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject|mysqli_result $result */
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result
            ->expects($this->at(0))
            ->method('fetch_assoc')
            ->willReturn($this->record);

        $result
            ->expects($this->at(1))
            ->method('fetch_assoc')
            ->willReturn(false);

        $statement = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement->method('get_result')
            ->willReturn($result);

        $this->executor
            ->method('executeQuery')
            ->willReturn($statement);

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        RecordFacade::setOriginalValues($this->record);

        $this->assertEquals(
            [$this->record],
            $this->facade->runRead()
        );
    }

    /**
     * @throws DbSqlException
     */
    public function testRunReadSingle() : void
    {
        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject|mysqli_result $result */
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result
            ->expects($this->at(0))
            ->method('fetch_assoc')
            ->willReturn($this->record);

        $result
            ->expects($this->at(1))
            ->method('fetch_assoc')
            ->willReturn(false);

        $statement = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement->method('get_result')
            ->willReturn($result);

        $this->executor
            ->method('executeQuery')
            ->willReturn($statement);

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        RecordFacade::setOriginalValues($this->record);

        $this->assertEquals(
            $this->record,
            $this->facade->runReadSingle()
        );
    }

    /**
     * @throws DbSqlException
     */
    public function testRunReadSingleNoResult() : void
    {
        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject|mysqli_result $result */
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result
            ->expects($this->at(0))
            ->method('fetch_assoc')
            ->willReturn(false);

        $statement = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement->method('get_result')
            ->willReturn($result);

        $this->executor
            ->method('executeQuery')
            ->willReturn($statement);

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        $this->expectException(DbRecordNotFoundException::class);

        $this->facade->runReadSingle();
    }

    /**
     * @throws DbSqlException
     */
    public function testRunReadSingleMultipleResults() : void
    {
        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject|mysqli_result $result */
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result
            ->expects($this->at(0))
            ->method('fetch_assoc')
            ->willReturn($this->record);

        $result
            ->expects($this->at(1))
            ->method('fetch_assoc')
            ->willReturn($this->record);

        $result
            ->expects($this->at(2))
            ->method('fetch_assoc')
            ->willReturn(false);

        $statement = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement->method('get_result')
            ->willReturn($result);

        $this->executor
            ->method('executeQuery')
            ->willReturn($statement);

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        $this->expectException(DbRecordNotFoundException::class);

        $this->facade->runReadSingle();
    }

    /**
     * @throws DbSqlException
     */
    public function testRunUpdateWithoutCorrectSQLCreated() : void
    {
        $records = [$this->record];
        $this->facade->runUpdate($records);

        $this->assertEquals(1,1);
    }

    /**
     * @throws DbSqlException
     */
    public function testRunUpdateWithCorrectSQLCreated() : void
    {
        $record = $this->record;
        $record['_sql']['statement'] = 'valid statement';
        $record['_sql']['parameters'] = ['i', 10];
        $record['_sql']['status'] = RecordFacade::RECORD_STATUS_NEW;
        $records = [$record];
        $this->facade->runUpdate($records);

        $this->assertEquals(1,1);
    }

    /**
     * @throws DbSqlException
     */
    public function testRunUpdateTriggeringException() : void
    {
        $this->table = $this->getMockBuilder(TableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executor = $this->getMockBuilder(SQLExecutionFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executor
            ->method('toggleAutocommit')
            ->willThrowException(new DbSqlException('exception', 123));

        $this->facade = new SQLFunctionsFacade($this->table, $this->executor);

        $records = [$this->record];

        $this->expectExceptionCode(123);

        $this->facade->runUpdate($records);
    }
}