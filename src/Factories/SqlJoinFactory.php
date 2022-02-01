<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlJoinType;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlJoinFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;

class SqlJoinFactory implements SqlJoinFactoryInterface
{
    /**
     * @param SqlTableInterface $joinedTable
     * @param SqlFieldInterface $primaryKey
     * @param SqlFieldInterface $foreignKey
     * @param SqlJoinType|null $joinType
     */
    public function __construct(
        private SqlTableInterface $joinedTable,
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
        return ($this->joinType !== null ? $this->joinType->value . ' JOIN' : 'JOIN')
            . ' ' . $this->joinedTable->getName()
            . ' ON ' . $this->primaryKey->getFieldName() . '=' . $this->foreignKey->getFieldName();
    }
}