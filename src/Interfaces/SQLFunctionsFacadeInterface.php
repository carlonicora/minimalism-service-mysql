<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;

interface SQLFunctionsFacadeInterface
{
    /**
     * SQLFunctionsFacadeInterface constructor.
     * @param TableInterface $table
     * @param SQLExecutionFacadeInterface $executor
     */
    public function __construct(TableInterface $table, SQLExecutionFacadeInterface $executor);

    /**
     * @return array
     * @throws DbSqlException
     */
    public function runRead() : array;

    /**
     * @return array
     * @throws DbSqlException
     */
    public function runReadSingle() : array;

    /**
     * @throws DbSqlException
     */
    public function runSql(): void;

    /**
     * @param array $objects
     * @throws DbSqlException
     */
    public function runUpdate(array &$objects): void;
}