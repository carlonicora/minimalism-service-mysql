<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldTypeInterface;

trait SqlTableTrait
{
    /**
     * @return string
     */
    public function getName(
    ): string
    {
        /** @noinspection PhpUndefinedClassConstantInspection */
        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return self::$tableName;
    }

    /**
     * @return SqlFieldInterface|null
     */
    public function getAutoIncrementField(
    ): ?SqlFieldInterface
    {
        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case !== self::tableName) && ($case->getFieldDefinition() & FieldTypeInterface::AutoIncrement) > 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public function getPrimaryKeyFields(
    ): array
    {
        $response = [];

        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case !== self::tableName) && ($case->getFieldDefinition() & FieldTypeInterface::PrimaryKey) > 0) {
                $response[] = $case;
            }
        }

        return $response;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public function getRegularFields(
    ): array
    {
        $response = [];

        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case !== self::tableName) && ($case->getFieldDefinition() & FieldTypeInterface::PrimaryKey) === 0) {
                $response[] = $case;
            }
        }

        return $response;
    }
}