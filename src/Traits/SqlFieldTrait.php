<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldTypeInterface;

trait SqlFieldTrait
{
    /**
     * @return bool
     */
    public function isPrimaryKey(
    ): bool
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return (($this->getFieldDefinition() & FieldTypeInterface::AutoIncrement) > 0);
    }

    /**
     * @return string
     */
    public function getFieldName(
    ): string {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->getTableName() . '.' . $this->name;
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

        if (($fieldDefinition & FieldTypeInterface::Integer) > 0){
            $response = 'i';
        } elseif (($fieldDefinition & FieldTypeInterface::Double) > 0){
            $response = 'd';
        } elseif (($fieldDefinition & FieldTypeInterface::Blob) > 0){
            $response = 'b';
        }

        return $response;
    }
}