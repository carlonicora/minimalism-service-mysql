<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use Exception;

interface TableInterface extends ConnectivityInterface
{
    /**
     * abstractDatabaseManager constructor.
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(ConnectionFactory $connectionFactory);

    /**
     * @param array $connectionParameters
     */
    public function initialiseAttributes(array $connectionParameters=[]) : void;

    /**
     * @return string
     */
    public function getTableName() : string;

    /**
     * @return array
     */
    public function getTableFields() : array;

    /**
     * @return string|null
     */
    public function getSql() : ?string;

    /**
     * @return array
     */
    public function getParameters() : array;

    /**
     * @return array|null
     */
    public function getPrimaryKey() : ?array;

    /**
     * @return string|null
     */
    public function getAutoIncrementField() : ?string;

    /**
     * @return string
     */
    public function getInsertIgnore() : string;

    /**
     * @param array $records
     * @param bool $delete
     * @throws Exception
     */
    public function update(array &$records, bool $delete=false): void;

    /**
     * @param array $records
     * @throws Exception
     */
    public function delete(array $records): void;

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     */
    public function loadByField(string $fieldName, $fieldValue) : array;

    /**
     * @param string $joinedTableName
     * @param string $joinedTablePrimaryKeyName
     * @param string $joinedTableForeignKeyName
     * @param int $joinedTablePrimaryKeyValue
     * @return array|null
     * @throws Exception
     */
    public function getFirstLevelJoin(string $joinedTableName, string $joinedTablePrimaryKeyName, string $joinedTableForeignKeyName, int $joinedTablePrimaryKeyValue) : ?array;
}