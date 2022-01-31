<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlCreateStatementCommand extends AbstractSqlStatementCommand
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

        $this->factory->insert($object->getTableInterfaceClass()::tableName);

        foreach (array_merge($this->primaryKeys, $this->regularFields) as $field){
            if ($field !== $this->autoIncrementField){
                $this->factory->addParameter($field, $data[$field->getFieldName()]);
            }
        }
    }
}