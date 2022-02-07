<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlComparison;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlJoinFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use UnitEnum;

class SqlFactory implements SqlFactoryInterface
{
    /** @var DatabaseOperationType  */
    private DatabaseOperationType $databaseOperationType;

    /** @var SqlTableInterface  */
    private SqlTableInterface $table;

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

    /** @var array{SqlComparison,SqlFieldInterface}  */
    private array $where=[];

    /** @var SqlFieldInterface[] */
    private array $groupBy=[];

    /** @var SqlFieldInterface[]  */
    private array $having=[];

    /** @var array{SqlFieldInterface,bool} */
    private array $orderBy=[];

    /**
     * @param string $tableClass
     * @throws MinimalismException
     */
    public function __construct(
        private string $tableClass,
    )
    {
        $this->table = SqlTableFactory::create($this->tableClass);
    }

    /**
     * @param string $tableClass
     * @return SqlFactoryInterface
     * @throws MinimalismException
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
        $this->from = 'FROM ' . $this->table->getFullName();

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
            $this->operandAndFields .= $field->getFullName();
        }

        $this->operandAndFields = substr($this->operandAndFields, 0, -1);

        $this->from = 'FROM ' . $this->table->getFullName();

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
        $this->from = 'FROM ' . $this->table->getFullName();

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
        $this->from = $this->table->getFullName();

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
        $this->from = $this->table->getFullName();

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
     * @param UnitEnum[] $fields
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
     * @param array{UnitEnum,bool} $fields
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
     * @param UnitEnum $field
     * @param mixed $value
     * @param SqlComparison|null $comparison
     * @return SqlFactoryInterface
     */
    public function addParameter(
        UnitEnum $field,
        mixed $value,
        ?SqlComparison $comparison=SqlComparison::Equal,
    ): SqlFactoryInterface
    {
        $sqlField = $this->table->getFieldByName($field->name);

        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->where[] = [$comparison, $sqlField];
        $this->parameters[0] .= $sqlField->getType();
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @param UnitEnum $field
     * @param mixed $value
     * @return SqlFactoryInterface
     */
    public function addHavingParameter(
        UnitEnum $field,
        mixed $value,
    ): SqlFactoryInterface
    {
        $sqlField = $this->table->getFieldByName($field->name);

        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->having[] = $sqlField;
        $this->parameters[0] .= $sqlField->getType();
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @return SqlTableInterface
     */
    public function getTable(
    ): SqlTableInterface
    {
        return $this->table;
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
     * @throws MinimalismException
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
                $response .= $field[1]->getFullName() . ',';
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
                    $additionalSql .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $field[1]->getFullName() . $field[0]->value . '?';
                    $isFirstWhere = false;
                } else {
                    $response .= $field[1]->getFullName() . $field[0]->value . '?,';
                }
            }

            $response = substr($response, 0, -1);

            $response .= $additionalSql;
        } else {
            $isFirstWhere = true;
            foreach ($this->where as $field) {
                $response .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $field[1]->getFullName() . $field[0]->value . '?';
                $isFirstWhere = false;
            }

            if ($this->databaseOperationType === DatabaseOperationType::Read) {
                $isFirstGroupBy = true;
                foreach ($this->groupBy as $field) {
                    $response .= ' ' . ($isFirstGroupBy ? 'GROUP BY ' : ',') . $field->getFullName();
                    $isFirstGroupBy = false;
                }

                $isFirstHaving = true;
                foreach ($this->having as $field) {
                    $response .= ' ' . ($isFirstHaving ? 'HAVING' : 'AND') . ' ' . $field->getFullName() . '=?';
                    $isFirstHaving = false;
                }

                $isFirstOrderBy = true;
                foreach ($this->orderBy as $field) {
                    $response .= ' ' . ($isFirstOrderBy ? 'ORDER BY ' : ',') . $field[0]->getFullName() . ($field[1] ? ' DESC' : '');
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