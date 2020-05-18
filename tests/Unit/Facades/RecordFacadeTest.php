<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Facades;

use CarloNicora\Minimalism\Services\MySQL\Facades\RecordFacade;
use CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts\AbstractTestCase;

class RecordFacadeTest extends AbstractTestCase
{
    public function testGetStatusNew() : void
    {
        $record = [
            'id'=>'1',
            'name'=>'carlo'
        ];

        $this->assertEquals(RecordFacade::RECORD_STATUS_NEW, RecordFacade::getStatus($record));
    }

    public function testGetStatusUnchanged() : void
    {
        $record = [
            'id'=>'1',
            'name'=>'carlo',
            'originalValues' => [
                'id'=>'1',
                'name'=>'carlo'
            ]
        ];

        $this->assertEquals(RecordFacade::RECORD_STATUS_UNCHANGED, RecordFacade::getStatus($record));
    }

    public function testGetStatusUpdated() : void
    {
        $record = [
            'id'=>'1',
            'name'=>'Carlo',
            'originalValues' => [
                'id'=>'1',
                'name'=>'carlo'
            ]
        ];

        $this->assertEquals(RecordFacade::RECORD_STATUS_UPDATED, RecordFacade::getStatus($record));
    }

    public function testSetOriginalValues() : void
    {
        $originalRecord = [
            'id'=>'1',
            'name'=>'carlo'
        ];

        $record = [
            'id'=>'1',
            'name'=>'carlo',
            'originalValues' => [
                'id'=>'1',
                'name'=>'carlo'
            ]
        ];

        RecordFacade::setOriginalValues($originalRecord);

        $this->assertEquals($record, $originalRecord);
    }
}