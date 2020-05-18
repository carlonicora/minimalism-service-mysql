<?php
namespace CarloNicora\Minimalism\Services\MySQL\Events;

use CarloNicora\Minimalism\Services\Logger\LogMessages\InfoLogMessage;

class MySQLInfoEvents extends InfoLogMessage
{
    /** @var string  */
    protected string $serviceName = 'mysql';
}