<?php
namespace CarloNicora\Minimalism\Services\MySQL\Exceptions;

use Exception;
use JetBrains\PhpStorm\Pure;
use Throwable;

class DbRecordNotFoundException extends Exception
{
    /**
     * DbRecordNotFoundException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code!==0?$code:404, $previous);
    }
}