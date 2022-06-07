<?php
namespace CarloNicora\Minimalism\Services\MySQL\Data;

use Attribute;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldType;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlTableFactory;
use ReflectionEnum;
use ReflectionException;
use UnitEnum;

#[Attribute]
class SqlTable implements SqlTableInterface
{
    /** @var string  */
    private string $databaseName;

    /** @var SqlFieldInterface[]  */
    private array $fields=[];

    /**
     * @param string $name
     * @param string $databaseIdentifier
     * @param bool $insertIgnore
     */
    public function __construct(
        private readonly string $name,
        private string $databaseIdentifier,
        private readonly bool $insertIgnore=false,
    )
    {
        $this->databaseName = SqlTableFactory::getDatabaseName($databaseIdentifier);
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
                $arguments = $case->getAttributes(SqlField::class)[0]->getArguments();

                $field = new SqlField(
                    fieldType: $arguments['fieldType'] ?? FieldType::String,
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
    public function getDatabaseIdentifier(
    ): string
    {
        return $this->databaseIdentifier;
    }

    /**
     * @param string $databaseIdentifier
     */
    public function setDatabaseIdentifier(
        string $databaseIdentifier,
    ): void
    {
        $this->databaseIdentifier = $databaseIdentifier;
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
        return ($this->databaseName !== '' ? $this->databaseName . '.' : '') . $this->name;
    }

    /**
     * @return string
     */
    public function getDatabaseName(
    ): string
    {
        return $this->databaseName;
    }

    /**
     * @return bool
     */
    public function isInsertIgnore(
    ): bool
    {
        return $this->insertIgnore;
    }

    /**
     * @return SqlFieldInterface|null
     */
    public function getAutoIncrementField(
    ): ?SqlFieldInterface
    {
        foreach ($this->fields as $field){
            if ($field->isAutoIncrement()){
                return $field;
            }
        }

        return null;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public function getFields(
    ): array
    {
        return $this->fields;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public function getPrimaryKeyFields(
    ): array
    {
        $response = [];

        foreach ($this->fields as $field){
            if ($field->isPrimaryKey()){
                $response[] = $field;
            }
        }

        return $response;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public function getRegularFields(
    ): array
    {
        $response = [];

        foreach ($this->fields as $field){
            if (!$field->isPrimaryKey()){
                $response[] = $field;
            }
        }

        return $response;
    }

    /**
     * @param string $fieldName
     * @return SqlFieldInterface
     * @throws MinimalismException
     */
    public function getFieldByName(
        string $fieldName,
    ): SqlFieldInterface
    {
        if (array_key_exists($fieldName, $this->fields)){
            return $this->fields[$fieldName];
        }

        throw new MinimalismException(
            HttpCode::InternalServerError
        );
    }

    /**
     * @param UnitEnum $field
     * @return SqlFieldInterface
     * @throws MinimalismException
     */
    public function getField(
        UnitEnum $field,
    ): SqlFieldInterface
    {
        return $this->getFieldByName($field->name);
    }
}