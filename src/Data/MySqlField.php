<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlFieldAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldType;

class MySqlField extends SqlFieldAttribute
{
    /**
     * @return string
     */
    public function getFullName(
    ): string
    {
        $response = '';

        if ($this->databaseName !== ''){
            $response = $this->databaseName . '.';
        }

        $response .= $this->tableName . '.' . $this->name;

        return $response;
    }

    /**
     * @return string
     */
    public function getType(
    ): string
    {
        $response = 's';

        if (($this->fieldType->value & SqlFieldType::Integer->value) > 0){
            $response = 'i';
        } elseif (($this->fieldType->value & SqlFieldType::Double->value) > 0){
            $response = 'd';
        } elseif (($this->fieldType->value & SqlFieldType::Blob->value) > 0){
            $response = 'b';
        }

        return $response;
    }
}