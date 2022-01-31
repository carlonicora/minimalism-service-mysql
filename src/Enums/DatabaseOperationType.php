<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Commands\SqlCreateStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Commands\SqlDeleteStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Commands\SqlUpdateStatementCommand;
use CarloNicora\Minimalism\Services\MySQL\Factories\ExceptionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SqlStatementCommandInterface;

enum DatabaseOperationType
{
    case Create;
    case Read;
    case Update;
    case Delete;

    /**
     * @param DataObjectInterface $object
     * @return SqlStatementCommandInterface
     * @throws MinimalismException
     */
    public function getSqlStatementCommand(
        DataObjectInterface $object,
    ): SqlStatementCommandInterface
    {
        return match ($this){
            self::Create => new SqlCreateStatementCommand($object),
            self::Update => new SqlUpdateStatementCommand($object),
            self::Delete => new SqlDeleteStatementCommand($object),
            default => throw ExceptionFactory::ReadDoesNotHaveSqlStatementCommand->create(),
        };
    }
}