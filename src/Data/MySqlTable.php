<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlFieldAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlTableAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldType;
use CarloNicora\Minimalism\Services\MySQL\Factories\MySqlTableFactory;
use ReflectionEnum;
use ReflectionException;

class MySqlTable extends SqlTableAttribute
{
    /**
     * @param string $name
     * @param string $databaseIdentifier
     * @param bool $insertIgnore
     */
    public function __construct(
        string $name,
        string $databaseIdentifier,
        bool $insertIgnore=false,
    )
    {
        parent::__construct($name, $databaseIdentifier);
        $this->databaseName = MySqlTableFactory::getDatabaseName($databaseIdentifier);
    }

    /**
     * @param string $tableClass
     * @return void
     * @throws MinimalismException
     */
    public function initialise(
        string $tableClass,
    ): void
    {
        try {
            foreach ((new ReflectionEnum($tableClass))->getCases() as $case) {
                $arguments = $case->getAttributes(SqlFieldAttribute::class)[0]->getArguments();

                $field = new MySqlField(
                    fieldType: $arguments['fieldType'] ?? SqlFieldType::String,
                    fieldOption: $arguments['fieldOption'] ?? null,
                    name: $case->getName(),
                    tableName: $this->name,
                    databaseName: $this->databaseName,
                );

                $field->setIdentifier($case->getValue());

                $this->fields[$case->getName()] = $field;
            }
        } catch (ReflectionException) {
            throw new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Failed to create table from attributes (' . $tableClass . ')',
            );
        }
    }

    /**
     * @return string
     */
    public function getFullName(
    ): string
    {
        return ($this->databaseName !== '' ? $this->databaseName . '.' : '') . $this->name;
    }
}