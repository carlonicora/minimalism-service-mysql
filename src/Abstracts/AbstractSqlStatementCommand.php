<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SqlStatementCommandInterface;

abstract class AbstractSqlStatementCommand implements SqlStatementCommandInterface
{
    /** @var SqlFactory  */
    protected SqlFactory $factory;

    /** @var SqlFieldInterface|null  */
    protected ?SqlFieldInterface $autoIncrementField;

    /** @var SqlFieldInterface[]  */
    protected array $primaryKeys;

    /** @var SqlFieldInterface[]  */
    protected array $regularFields;

    /**
     * @param DataObjectInterface $object
     */
    public function __construct(
        DataObjectInterface $object,
    )
    {
        $this->factory = new SqlFactory($object->getTableInterfaceClass()::tableName);

        /** @noinspection PhpUndefinedMethodInspection */
        $this->autoIncrementField = ($object->getTableInterfaceClass())->getAutoIncrementField();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->primaryKeys = ($object->getTableInterfaceClass())->getPrimaryKeyFields();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->regularFields = ($object->getTableInterfaceClass())->getRegularFields();
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