<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlUpdateStatementCommand extends AbstractSqlStatementCommand
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
        $this->factory->update($this->table);

        foreach ($this->regularFields as $field){
            /** @noinspection UnusedFunctionResultInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            $this->factory->addParameter($field, $data[$field->value]);
        }

        foreach ($this->primaryKeys as $field){
            /** @noinspection UnusedFunctionResultInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            $this->factory->addParameter($field, $data[$field->value]);
        }
    }
}