<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Facades;

use CarloNicora\Minimalism\Services\MySQL\Facades\SQLQueryCreationFacade;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLQueryCreationFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SQLQueryCreationFacadeTest extends AbstractTestCase
{
    /** @var MockObject|TableInterface|null  */
    private ?MockObject $table=null;

    /** @var SQLQueryCreationFacadeInterface|null  */
    private ?SQLQueryCreationFacadeInterface $SQLQueryCreationFacade=null;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = $this->generateTableMock();

        $this->table
            ->method('getPrimaryKey')
            ->willReturn($this->primaryKey);

        $this->SQLQueryCreationFacade = new SQLQueryCreationFacade($this->table);
    }

    private function generateTableMock() : MockObject
    {
        $response = $this->getMockBuilder(TableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getTableName')
            ->willReturn('tablename');

        $response
            ->method('getTableFields')
            ->willReturn($this->fields);

        return $response;
    }

    public function testSelectStatement() : void
    {
        $this->assertEquals(
            'SELECT * FROM tablename WHERE id=?;',
            $this->SQLQueryCreationFacade->generateSelectStatement()
        );
    }

    public function testSelectParameters() : void
    {
        $this->assertEquals(
            ['i', 'id'],
            $this->SQLQueryCreationFacade->generateSelectParameters()
        );
    }

    public function testCanUseInsertOnDuplicate() : void
    {
        $this->assertTrue(
            $this->SQLQueryCreationFacade->canUseInsertOnDuplicate()
        );
    }

    public function testCannotUseInsertOnDuplicate() : void
    {
        $this->table = $this->generateTableMock();

        $this->table
            ->method('getPrimaryKey')
            ->willReturn(null);

        $this->SQLQueryCreationFacade = new SQLQueryCreationFacade($this->table);

        $this->assertFalse(
            $this->SQLQueryCreationFacade->canUseInsertOnDuplicate()
        );
    }

    public function testGenerateInsertOnDuplicateUpdateStart() : void
    {
        $this->assertEquals(
            'INSERT INTO tablename (id,name,double,blob,bool) VALUES ',
            $this->SQLQueryCreationFacade->generateInsertOnDuplicateUpdateStart()
        );
    }

    public function testGenerateInsertOnDuplicateUpdateEnd() : void
    {
        $this->assertEquals(
            ' ON DUPLICATE KEY UPDATE name=VALUES(name),double=VALUES(double),blob=VALUES(blob),bool=VALUES(bool);',
            $this->SQLQueryCreationFacade->generateInsertOnDuplicateUpdateEnd()
        );
    }

    public function testGenerateInsertOnDuplicateUpdateRecord() : void
    {
        $this->assertEquals(
            '(1,\'Carlo\',1.2,\'phlow\',1),',
            $this->SQLQueryCreationFacade->generateInsertOnDuplicateUpdateRecord($this->record)
        );
    }

    public function testGenerateInsertStatement() : void
    {
        $this->assertEquals(
            'INSERT INTO tablename (id,name,double,blob,bool) VALUES (?,?,?,?,?);',
            $this->SQLQueryCreationFacade->generateInsertStatement()
        );
    }

    public function testGenerateInsertParameters() : void
    {
        $this->assertEquals(
            ['isdbi','id','name','double','blob','bool'],
            $this->SQLQueryCreationFacade->generateInsertParameters()
        );
    }

    public function testGenerateDeleteStatement() : void
    {
        $this->assertEquals(
            'DELETE FROM tablename WHERE id=?;',
            $this->SQLQueryCreationFacade->generateDeleteStatement()
        );
    }

    public function testGenerateDeleteParameters() : void
    {
        $this->assertEquals(
            ['i','id'],
            $this->SQLQueryCreationFacade->generateDeleteParameters()
        );
    }

    public function testGenerateUpdateStatement() : void
    {

        $this->assertEquals(
            'UPDATE tablename SET name=?,double=?,blob=?,bool=? WHERE id=?;',
            $this->SQLQueryCreationFacade->generateUpdateStatement()
        );
    }

    public function testGenerateUpdateParameters() : void
    {
        $this->assertEquals(
            ['sdbii','name','double','blob','bool','id'],
            $this->SQLQueryCreationFacade->generateUpdateParameters()
        );
    }
}