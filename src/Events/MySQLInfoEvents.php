<?php
namespace CarloNicora\Minimalism\Services\MySQL\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractInfoEvent;

class MySQLInfoEvents extends AbstractInfoEvent
{
    /** @var string  */
    protected string $serviceName = 'mysql';
}