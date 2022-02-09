<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;

interface SqlStatementCommandInterface
{
    /**
     * @param SqlDataObjectInterface $object
     */
    public function __construct(
        SqlDataObjectInterface $object,
    );

    /**
     * @return string
     */
    public function getSql(
    ): string;

    /**
     * @return array
     */
    public function getParameters(
    ): array;

    /**
     * @return array
     */
    public function getInsertedArray(
    ): array;

    /**
     * @return SqlTableInterface
     */
    public function getTable(
    ): SqlTableInterface;
}