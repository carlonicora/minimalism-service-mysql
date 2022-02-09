<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use Attribute;
use UnitEnum;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldType;
use IntBackedEnum;

#[Attribute]
class SqlField implements SqlFieldInterface
{
    /** @var UnitEnum  */
    private UnitEnum $identifier;

    /**
     * @param IntBackedEnum|FieldType $fieldType
     * @param IntBackedEnum|FieldOption|null $fieldOption
     * @param string $name
     * @param string $tableName
     * @param string $databaseName
     */
    public function __construct(
        private IntBackedEnum|FieldType $fieldType=FieldType::String,
        private IntBackedEnum|FieldOption|null $fieldOption=null,
        private string $name='',
        private string $tableName='',
        private string $databaseName='',
    )
    {
    }

    /**
     * @return UnitEnum
     */
    public function getIdentifier(
    ): UnitEnum
    {
        return $this->identifier;
    }

    /**
     * @param UnitEnum $identifier
     * @return void
     */
    public function setIdentifier(
        UnitEnum $identifier,
    ): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getName(
    ): string
    {
        return $this->name;
    }

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

        if (($this->fieldType->value & FieldType::Integer->value) > 0){
            $response = 'i';
        } elseif (($this->fieldType->value & FieldType::Double->value) > 0){
            $response = 'd';
        } elseif (($this->fieldType->value & FieldType::Blob->value) > 0){
            $response = 'b';
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(
    ): bool
    {
        return (($this->fieldOption?->value & FieldOption::PrimaryKey->value) > 0);
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(
    ): bool
    {
        return (($this->fieldOption?->value & FieldOption::AutoIncrement->value) > FieldOption::PrimaryKey->value);
    }
}