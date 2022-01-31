<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;

interface SqlStatementCommandInterface
{
    /**
     * @param DataObjectInterface $object
     */
    public function __construct(
        DataObjectInterface $object,
    );

    /**
     * @return string
     */
    public function getSql(
    ): string;

    /**
     * @return array
     */
    public function getParameters(
    ): array;
}