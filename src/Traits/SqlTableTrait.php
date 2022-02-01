<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFieldInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;

trait SqlTableTrait
{
    /**
     * @return string
     */
    public function getTableName(
    ): string
    {
        /** @noinspection PhpUndefinedClassConstantInspection */
        return self::tableName;
    }

    /**
     * @return SqlFieldInterface|null
     */
    public static function getAutoIncrementField(
    ): ?SqlFieldInterface
    {
        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case->getFieldDefinition() & FieldOption::AutoIncrement->value) > 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public static function getPrimaryKeyFields(
    ): array
    {
        $response = [];

        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case->getFieldDefinition() & FieldOption::PrimaryKey->value) > 0) {
                $response[] = $case;
            }
        }

        return $response;
    }

    /**
     * @return SqlFieldInterface[]
     */
    public static function getRegularFields(
    ): array
    {
        $response = [];

        /** @noinspection PhpAccessingStaticMembersOnTraitInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (self::cases() as $case){
            /** @noinspection PhpUndefinedClassConstantInspection */
            if (($case->getFieldDefinition() & FieldOption::PrimaryKey->value) === 0) {
                $response[] = $case;
            }
        }

        return $response;
    }
}