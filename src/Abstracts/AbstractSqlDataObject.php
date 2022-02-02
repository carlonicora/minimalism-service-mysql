<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\DbTable;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlTableFactory;
use ReflectionClass;

abstract class AbstractSqlDataObject implements SqlDataObjectInterface
{
    /**
     * @return string
     */
    final public function getTableClass(
    ): string
    {
        $reflection = new ReflectionClass(self::class);
        return $reflection->getAttributes(DbTable::class)[0]->getArguments()['tableClass'];
    }

    /**
     * @return SqlTableInterface
     * @throws MinimalismException
     */
    final public function getTable(
    ): SqlTableInterface
    {
        return SqlTableFactory::create($this->getTableClass());
    }
}