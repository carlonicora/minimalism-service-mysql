<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlOrderByInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlQueryFactory;
use UnitEnum;

class SqlOrderByObject implements SqlOrderByInterface
{
    /**
     * @param UnitEnum $field
     * @param bool $isDesc
     */
    public function __construct(
        private UnitEnum $field,
        private bool $isDesc=false,
    )
    {
    }

    /**
     * @return SqlFieldInterface
     * @throws MinimalismException
     */
    public function getField(
    ): SqlFieldInterface
    {
        return SqlQueryFactory::create(get_class($this->field))->getTable()->getFieldByName($this->field->name);
    }

    /**
     * @return bool
     */
    public function isDesc(
    ): bool
    {
        return $this->isDesc;
    }
}