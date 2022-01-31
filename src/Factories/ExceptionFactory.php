<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;

enum ExceptionFactory: int
{
    /** @var int */
    private const exceptionId=100110000;

    case MissingTableClass=1;
    case TryingToUpdateNewObject=2;
    case DatabaseConnectionStringMissing=3;
    case ErrorConnectingToTheDatabase=4;
    case MisplacedTableInterfaceClass=5;
    case MySQLCloseFailed=6;
    case MySQLStatementPreparationFailed=7;
    case MySQLStatementExecutionFailed=8;
    case ReadDoesNotHaveSqlStatementCommand=9;

    /**
     * @param string|null $additionalInformation
     * @return MinimalismException
     */
    public function create(
        ?string $additionalInformation=null,
    ): MinimalismException
    {
        return match ($this) {
            self::MissingTableClass => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Table class not found in project: ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::TryingToUpdateNewObject => new MinimalismException(
                status: HttpCode::BadRequest,
                message: 'Trying to run an UPDATE command on a new record (' . $additionalInformation . ')',
                code: $this->value +  self::exceptionId,
            ),
            self::DatabaseConnectionStringMissing => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Database connection string missing from environment (' . $additionalInformation . ')',
                code: $this->value +  self::exceptionId,
            ),
            self::ErrorConnectingToTheDatabase => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Error connecting to the database ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::MisplacedTableInterfaceClass => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Table class is not correctly placed in the file system ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::MySQLCloseFailed => new MinimalismException(
                status: HttpCode::NotAcceptable,
                message: 'MySQL failed to close statement: ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::MySQLStatementPreparationFailed => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'MySQL failed to prepare statement: ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::MySQLStatementExecutionFailed => new MinimalismException(
                status: HttpCode::NotAcceptable,
                message: 'MySQL failed to execute statement: ' . $additionalInformation,
                code: $this->value +  self::exceptionId,
            ),
            self::ReadDoesNotHaveSqlStatementCommand => new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Read statements cannot require a Statement Command',
                code: $this->value +  self::exceptionId,
            ),
        };
    }
}