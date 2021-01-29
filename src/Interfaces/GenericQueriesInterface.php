<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use Exception;

interface GenericQueriesInterface
{
    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function byId($id): array;

    /**
     * @return array
     * @throws Exception
     */
    public function all(): array;

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     */
    public function byField(string $fieldName, $fieldValue) : array;

    /**
     * @param $id
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadById($id): array;

    /**
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadAll(): array;

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadByField(string $fieldName, $fieldValue) : array;

    /**
     * @return int
     * @throws Exception
     */
    public function count(): int;
}