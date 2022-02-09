<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SqlStatementCommandInterface;

abstract class AbstractSqlStatementCommand implements SqlStatementCommandInterface
{
    /** @var SqlFactory  */
    protected SqlFactory $factory;

    /** @var SqlTableInterface  */
    protected SqlTableInterface $table;

    /**
     * @param SqlDataObjectInterface $object
     * @throws MinimalismException
     */
    public function __construct(
        SqlDataObjectInterface $object,
    )
    {
        $this->factory = SqlFactory::create($object->getTableClass());
    }

    /**
     * @return string
     * @throws MinimalismException
     */
    final public function getSql(
    ): string
    {
        return $this->factory->getSql();
    }

    /**
     * @return array
     */
    final public function getParameters(
    ): array
    {
        return $this->factory->getParameters();
    }

    /**
     * @return array
     * @throws MinimalismException
     */
    public function getInsertedArray(
    ): array
    {
        return $this->factory->getInsertedArray();
    }

    /**
     * @return SqlTableInterface
     */
    public function getTable(
    ): SqlTableInterface
    {
        return $this->factory->getTable();
    }
}