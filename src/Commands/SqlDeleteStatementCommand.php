<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlDeleteStatementCommand extends AbstractSqlStatementCommand
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

        $this->factory->delete($object->getTableInterfaceClass()::tableName);

        foreach ($this->primaryKeys as $field){
            $this->factory->addParameter($field, $data[$field->getFieldName()]);
        }
    }
}