<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;

interface GenericQueriesInterface
{
    /**
     * @param $id
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadFromId($id): array;

    /**
     * @return array
     * @throws DbSqlException
     */
    public function loadAll(): array;

    /**
     * @return int
     * @throws DbSqlException
     */
    public function count(): int;
}