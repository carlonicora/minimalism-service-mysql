<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use Exception;

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
     * @throws Exception
     */
    public function runRead() : array;

    /**
     * @return array
     * @throws Exception
     */
    public function runReadSingle() : array;

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