<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Factories\SqlDataObjectFactory;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractMyMySqlStatementCommand;
use Exception;

class MySqlUpdateStatementCommand extends AbstractMyMySqlStatementCommand
{
    /**
     * @param SqlDataObjectInterface $object
     * @throws MinimalismException
     * @throws Exception
     */
    public function __construct(
        SqlDataObjectInterface $object,
    )
    {
        parent::__construct($object);

        $data = SqlDataObjectFactory::createData(object: $object);

        /** @noinspection UnusedFunctionResultInspection */
        $this->factory->update();

        foreach ($this->factory->getTable()->getRegularFields() as $field){
            /** @noinspection UnusedFunctionResultInspection */
            $this->factory->addParameter($field->getIdentifier(), $data[$field->getName()]);
        }

        foreach ($this->factory->getTable()->getPrimaryKeyFields() as $field){
            /** @noinspection UnusedFunctionResultInspection */
            $this->factory->addParameter($field->getIdentifier(), $data[$field->getName()]);
        }
    }
}