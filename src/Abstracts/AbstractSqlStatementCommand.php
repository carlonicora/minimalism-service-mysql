<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SqlStatementCommandInterface;

abstract class AbstractSqlStatementCommand implements SqlStatementCommandInterface
{
    /** @var SqlFactory  */
    protected SqlFactory $factory;

    /** @var SqlTableInterface  */
    protected SqlTableInterface $table;

    /** @var SqlFieldInterface|null  */
    protected ?SqlFieldInterface $autoIncrementField;

    /** @var SqlFieldInterface[]  */
    protected array $primaryKeys;

    /** @var SqlFieldInterface[]  */
    protected array $regularFields;

    /**
     * @param SqlDataObjectInterface $object
     */
    public function __construct(
        SqlDataObjectInterface $object,
    )
    {
        $this->table = $object->getTable();

        $this->factory = SqlFactory::create($this->table);

        /** @noinspection PhpUndefinedMethodInspection */
        $this->autoIncrementField = $this->table->getAutoIncrementField();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->primaryKeys = $this->table->getPrimaryKeyFields();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->regularFields = $this->table->getRegularFields();
    }

    /**
     * @return string
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
}