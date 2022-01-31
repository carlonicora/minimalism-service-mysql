<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;

trait SqlFieldTrait
{
    /**
     * @return bool
     */
    public function isPrimaryKey(
    ): bool
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return (($this->getFieldDefinition() & FieldOption::AutoIncrement->value) > 0);
    }
}