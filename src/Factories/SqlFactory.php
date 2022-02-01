<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlJoinFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;

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

    /** @var SqlFieldInterface[]  */
    private array $where=[];

    /** @var SqlFieldInterface[] */
    private array $groupBy=[];

    /** @var SqlFieldInterface[]  */
    private array $having=[];

    /** @var array{SqlFieldInterface,bool} */
    private array $orderBy=[];

    /**
     * @return SqlFactoryInterface
     */
    public static function create(
    ): SqlFactoryInterface
    {
        return new self();
    }

    /**
     * @param SqlTableInterface $table
     * @return SqlFactoryInterface
     */
    public function selectAll(
        SqlTableInterface $table,
    ): SqlFactoryInterface
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT *';
        $this->from = 'FROM ' . $table->getTableName();

        return $this;
    }

    /**
     * @param SqlTableInterface $table
     * @param SqlFieldInterface[] $fields
     * @return SqlFactoryInterface
     */
    public function selectFields(
        SqlTableInterface $table,
        array $fields,
    ): SqlFactoryInterface
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT ';

        foreach ($fields as $field){
            $this->operandAndFields .= $field->getFieldName();
        }

        $this->operandAndFields = substr($this->operandAndFields, 0, -1);

        $this->from = 'FROM ' . $table->getTableName();

        return $this;
    }

    /**
     * @param SqlTableInterface $table
     * @return SqlFactoryInterface
     */
    public function delete(
        SqlTableInterface $table,
    ): SqlFactoryInterface
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Delete;
        $this->operandAndFields = 'DELETE';
        $this->from = 'FROM ' . $table->getTableName();

        return $this;
    }

    /**
     * @param SqlTableInterface $table
     * @return SqlFactoryInterface
     */
    public function update(
        SqlTableInterface $table,
    ): SqlFactoryInterface
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Update;
        $this->operandAndFields = 'UPDATE';
        $this->from = $table->getTableName();

        return $this;
    }

    /**
     * @param SqlTableInterface $table
     * @return SqlFactoryInterface
     */
    public function insert(
        SqlTableInterface $table,
    ): SqlFactoryInterface
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Create;
        $this->operandAndFields = 'INSERT INTO';
        $this->from = $table->getTableName();

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
     * @param SqlTableInterface $table
     * @param string $sql
     * @return SqlFactoryInterface
     */
    public function setSql(
        SqlTableInterface $table,
        string $sql,
    ): SqlFactoryInterface
    {
        $this->table = $table;
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
                $response .= $field->getFieldName() . ',';
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
                    $additionalSql .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $field->getFieldName() . '=?';
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
                $response .= ' ' . ($isFirstWhere ? 'WHERE' : 'AND') . ' ' . $field->getFieldName() . '=?';
                $isFirstWhere = false;
            }

            if ($this->databaseOperationType === DatabaseOperationType::Read) {
                $isFirstGroupBy = true;
                foreach ($this->groupBy as $field) {
                    $response .= ' ' . ($isFirstGroupBy ? 'GROUP BY ' : ',') . $field->getFieldName();
                    $isFirstGroupBy = false;
                }

                $isFirstHaving = true;
                foreach ($this->having as $field) {
                    $response .= ' ' . ($isFirstHaving ? 'HAVING' : 'AND') . ' ' . $field->getFieldName() . '=?';
                    $isFirstHaving = false;
                }

                $isFirstOrderBy = true;
                foreach ($this->orderBy as $field) {
                    $response .= ' ' . ($isFirstOrderBy ? 'ORDER BY ' : ',') . $field[0]->getFieldName() . ($field[1] ? ' DESC' : '');
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