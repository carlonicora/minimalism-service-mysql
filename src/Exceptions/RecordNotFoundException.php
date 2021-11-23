<?php
namespace CarloNicora\Minimalism\Services\MySQL\Exceptions;

use Exception;
use Throwable;

class RecordNotFoundException extends Exception
{
    /**
     * DbRecordNotFoundException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code!==0?$code:404, $previous);
    }
}