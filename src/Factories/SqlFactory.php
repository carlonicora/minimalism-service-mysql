<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlJoinFactoryInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use Exception;

class SqlFactory implements SqlFactoryInterface
{
    /** @var DatabaseOperationType  */
    private DatabaseOperationType $databaseOperationType;

    /** @var array  */
    private static array $dbNames=[];

    /** @var string  */
    private string $databasePrefix='';

    /** @var string|null  */
    private ?string $sql=null;

    /** @var array  */
    private array $parameters=[];

    /** @var string  */
    private string $operandAndFields;

    /** @var string  */
    private string $from;

    /** @var SqlJoinFactory[] */
    private array $join=[];

    /** @var SqlFieldInterface[]  */
    private array $where=[];

    /** @var SqlFieldInterface[] */
    private array $groupBy=[];

    /** @var SqlFieldInterface[]  */
    private array $having=[];

    /** @var array{SqlFieldInterface,bool} */
    private array $orderBy=[];

    /**
     * @param string $tableClass
     */
    public function __construct(
        private string $tableClass,
    )
    {
        try {
            $dbIdentifier = null;
            $fullNameParts = explode('\\', $tableClass);
            if (isset($fullNameParts[count($fullNameParts) - 1]) && strtolower($fullNameParts[count($fullNameParts) - 2]) === 'tables') {
                $dbIdentifier = $fullNameParts[count($fullNameParts) - 3];
            }
            if (($dbIdentifier !== null) && array_key_exists($dbIdentifier, self::$dbNames)) {
                $this->databasePrefix = self::$dbNames[$dbIdentifier] . '.';
            }
        } catch (Exception) {
            $this->databasePrefix = '';
        }
    }

    /**
     * @param array $connectionStrings
     * @return void
     */
    public static function initialise(
        array $connectionStrings,
    ): void
    {
        self::$dbNames = [];

        foreach ($connectionStrings as $dbIdentifier => $dbConnectionString){
            self::$dbNames[$dbIdentifier] = $dbConnectionString['dbName'];
        }
    }

    /**
     * @param string $tableClass
     * @return SqlFactoryInterface
     */
    public static function create(
        string $tableClass,
    ): SqlFactoryInterface
    {
        return new self($tableClass);
    }

    /**
     * @return SqlFactoryInterface
     */
    public function selectAll(
    ): SqlFactoryInterface
    {
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT *';
        $this->from = 'FROM ' . $this->databasePrefix . $this->tableClass::tableName;

        return $this;
    }

    /**
     * @param SqlFieldInterface[] $fields
     * @return SqlFactoryInterface
     */
    public function selectFields(
        array $fields,
    ): SqlFactoryInterface
    {
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT ';

        foreach ($fields as $field){
            $this->operandAndFields .= $field->getFieldName();
        }

        $this->operandAndFields = substr($this->operandAndFields, 0, -1);

        $this->from = 'FROM ' . $this->databasePrefix . $this->tableClass::tableName;

        return $this;
    }

    /**
     * @return SqlFactoryInterface
     */
    public function delete(
    ): SqlFactoryInterface
    {
        $this->databaseOperationType = DatabaseOperationType::Delete;
        $this->operandAndFields = 'DELETE';
        $this->from = 'FROM ' . $this->databasePrefix . $this->tableClass::tableName;

        return $this;
    }

    /**
     * @return SqlFactoryInterface
     */
    public function update(
    ): SqlFactoryInterface
    {
        $this->databaseOperationType = DatabaseOperationType::Update;
        $this->operandAndFields = 'UPDATE';
        $this->from = $this->databasePrefix . $this->tableClass::tableName;

        return $this;
    }

    /**
     * @return SqlFactoryInterface
     */
    public function insert(
    ): SqlFactoryInterface
    {
        $this->databaseOperationType = DatabaseOperationType::Create;
        $this->operandAndFields = 'INSERT INTO';
        $this->from = $this->databasePrefix . $this->tableClass::tableName;

        return $this;
    }

    /**
     * @param SqlJoinFactoryInterface $join
     * @return SqlFactoryInterface
     */
    public function addJoin(
        SqlJoinFactoryInterface $join
    ): SqlFactoryInterface
    {
        $this->join[] = $join;

        return $this;
    }

    /**
     * @param SqlFieldInterface[] $fields
     * @return SqlFactoryInterface
     */
    public function addGroupByFields(
        array $fields,
    ): SqlFactoryInterface
    {
        $this->groupBy = $fields;

        return $this;
    }

    /**
     * @param array{SqlFieldInterface,bool} $fields
     * @return SqlFactoryInterface
     */
    public function addOrderByFields(
        array $fields,
    ): SqlFactoryInterface
    {
        $this->orderBy = $fields;

        return $this;
    }

    /**
     * @param string $sql
     * @return SqlFactoryInterface
     */
    public function setSql(
        string $sql,
    ): SqlFactoryInterface
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     * @param SqlFieldInterface $field
     * @param mixed $value
     * @return SqlFactoryInterface
     */
    public function addParameter(
        SqlFieldInterface $field,
        mixed $value,
    ): SqlFactoryInterface
    {
        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->where[] = $field;
        $this->parameters[0] .= $field->getFieldType();
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @param SqlFieldInterface $field
     * @param mixed $value
     * @return SqlFactoryInterface
     */
    public function addHavingParameter(
        SqlFieldInterface $field,
        mixed $value,
    ): SqlFactoryInterface
    {
        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->having[] = $field;
        $this->parameters[0] .= $field->getFieldType();
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTableClass(
    ): string
    {
        return $this->tableClass;
    }

    /**
     * @return string
     */
    public function getSql(
    ): string
    {
        if ($this->sql !== null){
            return $this->sql;
        }

        $response = $this->operandAndFields . ' '. $this->from;

        if ($this->databaseOperationType === DatabaseOperationType::Read) {
            foreach ($this->join as $join) {
                $response .= ' ' . $join->getSql();
            }
        }

        if ($this->databaseOperationType === DatabaseOperationType::Create){
            $additionalSql = '';
            $response .= ' (';

            foreach ($this->where as $field){
                $response .= $this->databasePrefix . $field->getFieldName() . ',';
                $additionalSql .= '?,';
            }

            $response = substr($response, 0, -1);
            $additionalSql = substr($additionalSql, 0, -1);

            $response .= ') VALUES (' . $additionalSql . ')';
        } elseif  ($this->databaseOperationType === DatabaseOperationType::Update) {
            $response .= ' SET ';
            $additionalSql = '';

            $isFirstWhere = true;
            foreach ($this->where as $field) {
                if ($field->isPrimaryKey()){
                    $additionalSql .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $this->databasePrefix . $field->getFieldName() . '=?';
                    $isFirstWhere = false;
                } else {
                    $response .= $field->getFieldName() . '=?,';
                }
            }

            $response = substr($response, 0, -1);

            $response .= $additionalSql;
        } else {
            $isFirstWhere = true;
            foreach ($this->where as $field) {
                $response .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $this->databasePrefix . $field->getFieldName() . '=?';
                $isFirstWhere = false;
            }

            if ($this->databaseOperationType === DatabaseOperationType::Read) {
                $isFirstGroupBy = true;
                foreach ($this->groupBy as $field) {
                    $response .= ' ' . ($isFirstGroupBy ? 'GROUP BY ' : ',') . $this->databasePrefix . $field->getFieldName();
                    $isFirstGroupBy = false;
                }

                $isFirstHaving = true;
                foreach ($this->having as $field) {
                    $response .= ' ' . ($isFirstHaving ? 'HAVING' : 'AND') . ' ' . $this->databasePrefix . $field->getFieldName() . '=?';
                    $isFirstHaving = false;
                }

                $isFirstOrderBy = true;
                foreach ($this->orderBy as $field) {
                    $response .= ' ' . ($isFirstOrderBy ? 'ORDER BY ' : ',') . $this->databasePrefix . $field[0]->getFieldName() . ($field[1] ? ' DESC' : '');
                    $isFirstOrderBy = false;
                }
            }
        }

        $response .= ';';

        return $response;
    }

    /**
     * @return array
     */
    public function getParameters(
    ): array
    {
        return $this->parameters;
    }
}