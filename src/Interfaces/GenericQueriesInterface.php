<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Exceptions\RecordNotFoundException;
use Exception;

interface GenericQueriesInterface
{
    /**
     * @param $id
     * @return array
     * @throws RecordNotFoundException
     * @throws Exception
     */
    public function loadById($id): array;

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