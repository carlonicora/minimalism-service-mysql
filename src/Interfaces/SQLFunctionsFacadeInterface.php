<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use Exception;

interface SQLFunctionsFacadeInterface
{
    /**
     * SQLFunctionsFacadeInterface constructor.
     * @param LoggerInterface $logger
     * @param MySqlTableInterface $table
     * @param SQLExecutionFacadeInterface $executor
     */
    public function __construct(
        LoggerInterface $logger,
        MySqlTableInterface $table,
        SQLExecutionFacadeInterface $executor
    );

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