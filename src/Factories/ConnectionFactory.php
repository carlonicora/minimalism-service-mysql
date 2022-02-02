<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Services\MySQL\Data\SqlTable;
use Exception;
use mysqli;
use Throwable;

class ConnectionFactory
{
    /** @var mysqli[] */
    private array $databases = [];

    /** @var array */
    private array $databaseConnectionStrings = [];

    /**
     * @param string $databaseConfigurations
     */
    public function __construct(
        string $databaseConfigurations,
    )
    {
        if (!empty($databaseConfigurations)) {
            $databaseNames = explode(',', $databaseConfigurations);
            foreach ($databaseNames ?? [] as $databaseName) {
                if (!array_key_exists($databaseName, $this->databaseConnectionStrings)) {
                    $databaseConnectionString = $_ENV[trim($databaseName)];
                    $databaseConnectionParameters = [];
                    [
                        $databaseConnectionParameters['host'],
                        $databaseConnectionParameters['username'],
                        $databaseConnectionParameters['password'],
                        $databaseConnectionParameters['dbName'],
                        $databaseConnectionParameters['port']
                    ] = explode(',', $databaseConnectionString);

                    $this->databaseConnectionStrings[$databaseName] = $databaseConnectionParameters;
                }
            }
        }
    }

    /**
     *
     */
    public function __destruct(
    )
    {
        $this->resetDatabases();
    }

    /**
     * @return array
     */
    public function getConfigurations(
    ): array
    {
        return $this->databaseConnectionStrings;
    }

    /**
     * @param string $tableClass
     * @return string
     * @throws MinimalismException
     */
    private function getDatabaseIdentifier(
        string $tableClass,
    ): string
    {
        $fullNameParts = explode('\\', $tableClass);

        if (isset($fullNameParts[count($fullNameParts)-1]) && strtolower($fullNameParts[count($fullNameParts)-2]) === 'tables'){
            return $fullNameParts[count($fullNameParts)-3];
        }

        throw ExceptionFactory::MisplacedTableInterfaceClass->create($tableClass);
    }

    /**
     * @param SqlTable $table
     * @return mysqli
     * @throws MinimalismException
     */
    public function create(
        SqlTable $table,
    ): mysqli
    {
        if (!array_key_exists($table->getDatabaseIdentifier(), $this->databaseConnectionStrings)){
            throw ExceptionFactory::DatabaseConnectionStringMissing->create($table->getDatabaseIdentifier());
        }

        $dbConf = $this->databaseConnectionStrings[$table->getDatabaseIdentifier()];

        $response = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

        if ($response->connect_errno) {
            throw ExceptionFactory::ErrorConnectingToTheDatabase->create($dbConf['name']);
        }

        $response->set_charset('utf8mb4');
        $this->databases[$table->getDatabaseIdentifier()] = $response;

        return $response;
    }

    /**
     *
     */
    public function resetDatabases(
    ) : void
    {
        /**
         * @var string $databaseKey
         * @var mysqli $connection
         */
        foreach ($this->databases as $connection){
            try {
                if ($connection !== null && $connection->ping()) {
                    $connection->close();
                }
            } catch (Exception|Throwable) {
            }
        }

        $this->databases = [];
    }
}