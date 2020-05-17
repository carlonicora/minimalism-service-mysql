<?php
namespace CarloNicora\Minimalism\Services\MySQL\errors;

use CarloNicora\Minimalism\Services\logger\Abstracts\AbstractErrors;

class errors extends abstractErrors {
    /** @var string  */
    public const LOGGER_SERVICE_NAME = 'minimalism-service-mysql';

    /** @var int  */
    public const ERROR_READER_CLASS_NOT_FOUND = 1;
    public const ERROR_MISSING_CONNECTION_DETAILS = 2;
    public const ERROR_CONNECTION_ERROR = 3;
    public const ERROR_DISABLE_AUTOCOMMIT = 4;
    public const ERROR_CLOSE_STATEMENT = 5;
    public const ERROR_ENABLE_AUTOCOMMIT = 6;
    public const ERROR_STATEMENT_PREPARATION = 7;
    public const ERROR_STATEMENT_EXECUTION = 8;
}