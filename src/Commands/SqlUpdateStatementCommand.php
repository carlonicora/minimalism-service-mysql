<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlUpdateStatementCommand extends AbstractSqlStatementCommand
{
    /**
     * @param DataObjectInterface $object
     */
    public function __construct(
        DataObjectInterface $object,
    )
    {
        parent::__construct($object);

        $data = $object->export();

        /** @noinspection UnusedFunctionResultInspection */
        $this->factory->update($this->table);

        foreach ($this->regularFields as $field){
            /** @noinspection UnusedFunctionResultInspection */
            $this->factory->addParameter($field, $data[$field->value]);
        }

        foreach ($this->primaryKeys as $field){
            /** @noinspection UnusedFunctionResultInspection */
            $this->factory->addParameter($field, $data[$field->value]);
        }
    }
}