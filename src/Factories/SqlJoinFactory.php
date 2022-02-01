<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlJoinType;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlJoinFactoryInterface;

class SqlJoinFactory implements SqlJoinFactoryInterface
{
    /** @var array  */
    private array $dbNames=[];

    /**
     * @param SqlFieldInterface $primaryKey
     * @param SqlFieldInterface $foreignKey
     * @param SqlJoinType|null $joinType
     */
    public function __construct(
        private SqlFieldInterface $primaryKey,
        private SqlFieldInterface $foreignKey,
        private ?SqlJoinType $joinType=null,
    )
    {
    }

    /**
     * @return string
     */
    public function getSql(
    ): string
    {
        $primaryKeyDatabaseName = TableNameFactory::getDatabaseName(get_class($this->primaryKey), $this->dbNames);
        $foreignKeyDatabaseName = TableNameFactory::getDatabaseName(get_class($this->foreignKey), $this->dbNames);
        /** @noinspection PhpUndefinedClassConstantInspection */
        return ($this->joinType !== null ? $this->joinType->value . ' JOIN' : 'JOIN')
            . ' ' . $foreignKeyDatabaseName . $this->foreignKey::tableName
            . ' ON ' . $primaryKeyDatabaseName . $this->primaryKey->getFieldName() . '=' . $foreignKeyDatabaseName . $this->foreignKey->getFieldName();
    }

    /**
     * @param array $dbNames
     */
    public function setDbNames(
        array $dbNames,
    ): void
    {
        $this->dbNames = $dbNames;
    }
}