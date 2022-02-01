<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlDeleteStatementCommand extends AbstractSqlStatementCommand
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
        $this->factory->delete($this->table);

        foreach ($this->primaryKeys as $field){
            /** @noinspection UnusedFunctionResultInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            $this->factory->addParameter($field, $data[$field->value]);
        }
    }
}