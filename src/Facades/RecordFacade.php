<?php
namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Services\MySQL\Interfaces\RecordFacadeInterface;

class RecordFacade implements RecordFacadeInterface
{
    /** @var int  */
    public const RECORD_STATUS_NEW = 1;
    public const RECORD_STATUS_UNCHANGED = 2;
    public const RECORD_STATUS_UPDATED = 3;
    public const RECORD_STATUS_DELETED = 4;

    /**
     * @param $record
     * @return int
     */
    public static function getStatus($record): int
    {
        if (array_key_exists('originalValues', $record)){
            $response = self::RECORD_STATUS_UNCHANGED;
            foreach ($record['originalValues'] as $fieldName=>$originalValue){
                if ($originalValue !== $record[$fieldName]){
                    $response = self::RECORD_STATUS_UPDATED;
                    break;
                }
            }
        } else {
            $response = self::RECORD_STATUS_NEW;
        }

        return $response;
    }

    /**
     * @param array $record
     */
    public static function setOriginalValues(array &$record): void
    {
        $originalValues = [];
        foreach($record as $fieldName=>$fieldValue){
            $originalValues[$fieldName] = $fieldValue;
        }
        $record['originalValues'] = $originalValues;
    }


}