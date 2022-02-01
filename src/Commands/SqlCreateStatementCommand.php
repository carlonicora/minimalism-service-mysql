<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlCreateStatementCommand extends AbstractSqlStatementCommand
{
    /**
     * @param SqlDataObjectInterface $object
     */
    public function __construct(
        SqlDataObjectInterface $object,
    )
    {
        parent::__construct($object);

        $data = $object->export();

        /** @noinspection UnusedFunctionResultInspection */
        $this->factory->insert($this->table);

        foreach (array_merge($this->primaryKeys, $this->regularFields) as $field){
            if ($field !== $this->autoIncrementField){
                /** @noinspection UnusedFunctionResultInspection */
                $this->factory->addParameter($field, $data[$field->value]);
            }
        }
    }
}