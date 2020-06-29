<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface RecordFacadeInterface
{
    /**
     * @param $record
     * @return int
     */
    public static function getStatus($record): int;

    /**
     * @param array $record
     */
    public static function setOriginalValues(&$record): void;
}