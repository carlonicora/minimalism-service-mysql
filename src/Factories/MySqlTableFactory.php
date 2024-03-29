<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlTableAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
use CarloNicora\Minimalism\Services\MySQL\Data\MySqlTable;
use ReflectionClass;
use ReflectionException;

class MySqlTableFactory implements SqlTableFactoryInterface
{
    /** @var array  */
    private static array $dbNames=[];

    /**
     * @param array $connectionStrings
     * @return void
     */
    public static function initialise(
        array $connectionStrings,
    ): void
    {
        self::$dbNames = [];

        foreach ($connectionStrings as $dbIdentifier => $dbConnectionString){
            self::$dbNames[$dbIdentifier] = $dbConnectionString['dbName'];
        }
    }

    /**
     * @param string $databaseIdentifier
     * @return string
     */
    public static function getDatabaseName(
        string $databaseIdentifier,
    ): string
    {
        $response = '';

        if (array_key_exists($databaseIdentifier, self::$dbNames)){
            $response = self::$dbNames[$databaseIdentifier];
        }

        return $response;
    }

    /**
     * @param string $tableClass
     * @param string|null $overrideDatabaseIdentifier
     * @return SqlTableInterface
     * @throws MinimalismException
     */
    public static function create(
        string $tableClass,
        ?string $overrideDatabaseIdentifier=null,
    ): SqlTableInterface
    {
        try {
            /** @var MySqlTable $response */
            $parameters = (new ReflectionClass($tableClass))->getAttributes(SqlTableAttribute::class)[0]->getArguments();
            $response = new MySqlTable(...$parameters);
            //$response = (new ReflectionClass($tableClass))->getAttributes(MySqlTable::class)[0]->newInstance();

            if ($overrideDatabaseIdentifier !== null){
                $response->setDatabaseIdentifier($overrideDatabaseIdentifier);
            }
            $response->initialise(tableClass: $tableClass);
            return $response;
        } catch (ReflectionException) {
            throw new MinimalismException(
                status: HttpCode::InternalServerError,
                message: 'Failed to create table from attributes (' . $tableClass . ')',
            );
        }
    }
}