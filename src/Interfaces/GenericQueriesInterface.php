<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use Exception;

interface GenericQueriesInterface
{
    /**
     * @param $id
     * @return array
     * @throws DbRecordNotFoundException
     * @throws Exception
     */
    public function loadFromId($id): array;

    /**
     * @return array
     * @throws Exception
     */
    public function loadAll(): array;

    /**
     * @return int
     * @throws Exception
     */
    public function count(): int;
}