<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use Exception;
use Throwable;

class TableNameFactory
{
    /**
     * @param string $tableClass
     * @param array $dbNames
     * @return string
     */
    public static function getDatabaseName(
        string $tableClass,
        array $dbNames,
    ): string
    {
        try {
            $dbIdentifier = null;
            $fullNameParts = explode('\\', $tableClass);
            if (isset($fullNameParts[count($fullNameParts) - 1]) && strtolower($fullNameParts[count($fullNameParts) - 2]) === 'tables') {
                $dbIdentifier = $fullNameParts[count($fullNameParts) - 3];
            }
            if (($dbIdentifier !== null) && array_key_exists($dbIdentifier, $dbNames)) {
                $response = $dbNames[$dbIdentifier] . '.';
            }
        } catch (Exception|Throwable) {
            $response = '';
        }

        return $response;
    }
}