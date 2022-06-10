<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Commands\MySqlCreateStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Commands\MySqlDeleteStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Commands\MySqlUpdateStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Factories\MySqlExceptionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlStatementCommandInterface;

enum MySqlDatabaseOperationType
{
    case Create;
    case Read;
    case Update;
    case Delete;

    /**
     * @param SqlDataObjectInterface $object
     * @return MySqlStatementCommandInterface
     * @throws MinimalismException
     */
    public function getSqlStatementCommand(
        SqlDataObjectInterface $object,
    ): MySqlStatementCommandInterface
    {
        return match ($this){
            self::Create => new MySqlCreateStatementCommand($object),
            self::Update => new MySqlUpdateStatementCommand($object),
            self::Delete => new MySqlDeleteStatementCommand($object),
            default => throw MySqlExceptionFactory::ReadDoesNotHaveSqlStatementCommand->create(),
        };
    }
}