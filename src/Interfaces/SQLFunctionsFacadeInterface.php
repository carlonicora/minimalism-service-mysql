<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use Exception;

interface SQLFunctionsFacadeInterface
{
    /**
     * SQLFunctionsFacadeInterface constructor.
     * @param MySqlTableInterface $table
     * @param SQLExecutionFacadeInterface $executor
     */
    public function __construct(
        MySqlTableInterface $table,
        SQLExecutionFacadeInterface $executor,
    );

    /**
     * @return array
     * @throws Exception
     */
    public function runRead() : array;

    /**
     * @throws Exception
     */
    public function runSql(): void;

    /**
     * @param array $objects
     * @throws Exception
     */
    public function runUpdate(array &$objects): void;
}