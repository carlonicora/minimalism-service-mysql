<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface RecordFacadeInterface
{
    /**
     * @param array $record
     * @return int
     */
    public static function getStatus(array $record): int;

    /**
     * @param array $record
     */
    public static function setOriginalValues(array &$record): void;
}