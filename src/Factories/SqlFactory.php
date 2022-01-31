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
     * @return SqlTableInterface
     */
    public function getTable(
    ): SqlTableInterface
    {
        return $this->table;
    }

    /**
     * @param SqlTableInterface $table
     * @return void
     */
    public function selectAll(
        SqlTableInterface $table,
    ): void
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT *';
        $this->from = 'FROM ' . $table->getTableName();
    }

    /**
     * @param SqlTableInterface $table
     * @param SqlFieldInterface[] $fields
     * @return void
     */
    public function selectFields(
        SqlTableInterface $table,
        array $fields,
    ): void
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Read;
        $this->operandAndFields = 'SELECT ';

        foreach ($fields as $field){
            $this->operandAndFields .= $field->getFieldName();
        }

        $this->operandAndFields = substr($this->operandAndFields, 0, -1);

        $this->from = 'FROM ' . $table->getTableName();
    }

    /**
     * @param SqlTableInterface $table
     * @return void
     */
    public function delete(
        SqlTableInterface $table,
    ): void
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Delete;
        $this->operandAndFields = 'DELETE';
        $this->from = 'FROM ' . $table->getTableName();
    }

    /**
     * @param SqlTableInterface $table
     * @return void
     */
    public function update(
        SqlTableInterface $table,
    ): void
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Update;
        $this->operandAndFields = 'UPDATE';
        $this->from = $table->getTableName();
    }

    /**
     * @param SqlTableInterface $table
     * @return void
     */
    public function insert(
        SqlTableInterface $table,
    ): void
    {
        $this->table = $table;
        $this->databaseOperationType = DatabaseOperationType::Create;
        $this->operandAndFields = 'INSERT INTO';
        $this->from = $table->getTableName();
    }

    /**
     * @param SqlJoinFactoryInterface $join
     * @return void
     */
    public function addJoin(
        SqlJoinFactoryInterface $join
    ): void
    {
        $this->join[] = $join;
    }

    /**
     * @param SqlFieldInterface[] $fields
     * @return void
     */
    public function addGroupByFields(
        array $fields,
    ): void
    {
        $this->groupBy = $fields;
    }

    /**
     * @param array{SqlFieldInterface,bool} $fields
     * @return void
     */
    public function addOrderByFields(
        array $fields,
    ): void
    {
        $this->orderBy = $fields;
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
     * @param string $sql
     */
    public function setSql(
        string $sql,
    ): void
    {
        $this->sql = $sql;
    }

    /**
     * @return array
     */
    public function getParameters(
    ): array
    {
        return $this->parameters;
    }

    /**
     * @param SqlFieldInterface $field
     * @param mixed $value
     * @return void
     */
    public function addParameter(
        SqlFieldInterface $field,
        mixed $value,
    ): void
    {
        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->where[] = $field;
        $this->parameters[0] .= $field->getFieldType();
        $this->parameters[] = $value;
    }

    /**
     * @param SqlFieldInterface $field
     * @param mixed $value
     * @return void
     */
    public function addHavingParameter(
        SqlFieldInterface $field,
        mixed $value,
    ): void
    {
        if ($this->parameters === []){
            $this->parameters[] = '';
        }

        $this->having[] = $field;
        $this->parameters[0] .= $field->getFieldType();
        $this->parameters[] = $value;
    }
}