<?php
namespace CarloNicora\Minimalism\Services\MySQL\Events;

use CarloNicora\Minimalism\Services\Logger\Interfaces\LogMessageInterface;
use CarloNicora\Minimalism\Services\Logger\LogMessages\ErrorLogMessage;

class MySQLErrorEvents extends ErrorLogMessage
{
    /** @var string  */
    protected string $serviceName = 'mysql';

    public static function ERROR_READER_CLASS_NOT_FOUND(string $className) : LogMessageInterface
    {
        return new self(1, 'Database reader class %s does not exist.', [$className]);
    }

    public static function ERROR_MISSING_CONNECTION_DETAILS(string $databaseName) : LogMessageInterface
    {
        return new self(2, 'Missing connection details for %s', [$databaseName]);
    }

    public static function ERROR_CONNECTION_ERROR(string $databaseName, string $errorNumber, string $error) : LogMessageInterface
    {
        return new self(3, '%s database connection error %s: %s', [$databaseName, $errorNumber, $error]);
    }

    public static function ERROR_DISABLE_AUTOCOMMIT(string $errorNumber, string $sqlState, string $error) : LogMessageInterface
    {
        return new self(4, 'MySQL failed to enable autocommit. Error %s %s: %s', [$errorNumber, $sqlState, $error]);
    }

    public static function ERROR_CLOSE_STATEMENT(string $statement) : LogMessageInterface
    {
        return new self(5, 'MySQL failed to close statement: %s', [$statement]);
    }

    public static function ERROR_ENABLE_AUTOCOMMIT(string $errorNumber, string $sqlState, string $error) : LogMessageInterface
    {
        return new self(6, 'MySQL failed to enable autocommit. Error %s %s: %s', [$errorNumber, $sqlState, $error]);
    }

    public static function ERROR_STATEMENT_PREPARATION(string $sql, string $errorNumber, string $sqlState, string $error) : LogMessageInterface
    {
        return new self(7, 'MySQL statement (%s) preparation failed. Error %s %s: %s', [$sql, $errorNumber, $sqlState, $error]);
    }

    public static function ERROR_STATEMENT_EXECUTION(string $sql, string $parameters) : LogMessageInterface
    {
        return new self(8, 'MySQL statement (%s) execution (%s) failed.', [$sql, $parameters]);
    }
}