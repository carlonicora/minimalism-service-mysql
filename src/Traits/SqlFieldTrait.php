<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldType;

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

    /**
     * @return string
     */
    public function getFieldName(
    ): string {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->getTableName() . '.' . $this->value;
    }

    /**
     * @return string
     */
    public function getFieldType(
    ): string
    {
        $response = 's';

        /** @noinspection PhpUndefinedMethodInspection */
        $fieldDefinition = $this->getFieldDefinition();

        if (($fieldDefinition & FieldType::Integer->value) > 0){
            $response = 'i';
        } elseif (($fieldDefinition & FieldType::Double->value) > 0){
            $response = 'd';
        } elseif (($fieldDefinition & FieldType::Blob->value) > 0){
            $response = 'b';
        }

        return $response;
    }
}