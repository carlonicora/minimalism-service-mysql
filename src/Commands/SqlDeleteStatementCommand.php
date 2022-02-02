<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractSqlStatementCommand;

class SqlDeleteStatementCommand extends AbstractSqlStatementCommand
{
    /**
     * @param SqlDataObjectInterface $object
     * @throws MinimalismException
     */
    public function __construct(
        SqlDataObjectInterface $object,
    )
    {
        parent::__construct($object);

        $data = $object->export();

        /** @noinspection UnusedFunctionResultInspection */
        $this->factory->delete();

        foreach ($this->factory->getTable()->getPrimaryKeyFields() as $field){
            /** @noinspection UnusedFunctionResultInspection */
            $this->factory->addParameter($field->getIdentifier(), $data[$field->getName()]);
        }
    }
}