<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Mocks;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class tablename extends AbstractTable
{
    /** @var array  */
    protected array $fields = [
        'id' => TableInterface::INTEGER
            + TableInterface::PRIMARY_KEY
            + TableInterface::AUTO_INCREMENT,
        'name' => TableInterface::STRING,
        'double' => TableInterface::DOUBLE,
        'blob' => TableInterface::BLOB,
        'bool' => TableInterface::INTEGER
    ];
}