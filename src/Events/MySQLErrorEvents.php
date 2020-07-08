<?php
namespace CarloNicora\Minimalism\Services\MySQL\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractErrorEvent;
use CarloNicora\Minimalism\Core\Events\Interfaces\EventInterface;
use CarloNicora\Minimalism\Core\Modules\Interfaces\ResponseInterface;
use mysqli_stmt;

class MySQLErrorEvents extends AbstractErrorEvent
{
    /** @var string  */
    protected string $serviceName = 'mysql';

    public static function ERROR_READER_CLASS_NOT_FOUND(string $className) : EventInterface
    {
        return new self(1, ResponseInterface::HTTP_STATUS_500,'Database reader class %s does not exist.', [$className]);
    }

    public static function ERROR_MISSING_CONNECTION_DETAILS(string $databaseName) : EventInterface
    {
        return new self(2, ResponseInterface::HTTP_STATUS_500, 'Missing connection details for %s', [$databaseName]);
    }

    public static function ERROR_CONNECTION_ERROR(string $databaseName, string $errorNumber, string $error) : EventInterface
    {
        return new self(3, ResponseInterface::HTTP_STATUS_500, '%s database connection error %s: %s', [$databaseName, $errorNumber, $error]);
    }

    public static function ERROR_DISABLE_AUTOCOMMIT(string $errorNumber, string $sqlState, string $error) : EventInterface
    {
        return new self(4, ResponseInterface::HTTP_STATUS_500, 'MySQL failed to enable autocommit. Error %s %s: %s', [$errorNumber, $sqlState, $error]);
    }

    public static function ERROR_CLOSE_STATEMENT(string $statement) : EventInterface
    {
        return new self(5, ResponseInterface::HTTP_STATUS_500, 'MySQL failed to close statement: %s', [$statement]);
    }

    public static function ERROR_ENABLE_AUTOCOMMIT(string $errorNumber, string $sqlState, string $error) : EventInterface
    {
        return new self(6, ResponseInterface::HTTP_STATUS_500, 'MySQL failed to enable autocommit. Error %s %s: %s', [$errorNumber, $sqlState, $error]);
    }

    public static function ERROR_STATEMENT_PREPARATION(string $sql, string $errorNumber, string $sqlState, string $error) : EventInterface
    {
        return new self(7, ResponseInterface::HTTP_STATUS_500, 'MySQL statement (%s) preparation failed. Error %s %s: %s', [$sql, $errorNumber, $sqlState, $error]);
    }

    public static function ERROR_STATEMENT_EXECUTION(string $sql, string $parameters, mysqli_stmt $statement) : EventInterface
    {
        $errorDetails = [];
        foreach ($statement->error_list as $error) {
            $errorDetails []= sprintf('Error %s %s: %s', $error['errno'], $error['sqlstate'], $error['error']);
        }

        $message = 'MySQL statement execution failed. %s Query: %s Parameters %s';
        return new self(8, ResponseInterface::HTTP_STATUS_500, $message, [implode('.', $errorDetails), $sql, $parameters]);
    }
}