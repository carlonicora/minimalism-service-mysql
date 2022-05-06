<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlComparison;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;

class SqlComparisonObject
{
    /**
     * @param SqlFieldInterface|string $field
     * @param SqlComparison $comparison
     */
    public function __construct(
        private readonly SqlFieldInterface|string $field,
        private readonly SqlComparison $comparison,
    )
    {
    }

    /**
     * @return SqlFieldInterface|string
     */
    public function getField(
    ): SqlFieldInterface|string
    {
        return $this->field;
    }

    /**
     * @return SqlComparison
     */
    public function getComparison(
    ): SqlComparison
    {
        return $this->comparison;
    }
}