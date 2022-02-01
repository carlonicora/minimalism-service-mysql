<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlTableInterface;
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
     * @param SqlTableInterface $tableInterface
     * @return string
     * @throws MinimalismException
     */
    public function getDatabaseName(
        SqlTableInterface $tableInterface,
    ): string
    {
        $identifier = $this->getDatabaseIdentifier($tableInterface);
        if (!array_key_exists($identifier, $this->databaseConnectionStrings)){
            throw ExceptionFactory::DatabaseConnectionStringMissing->create($identifier);
        }
        $dbConf = $this->databaseConnectionStrings[$identifier];

        return $dbConf['dbName'];
    }

    /**
     * @param SqlTableInterface $tableInterface
     * @return string
     * @throws MinimalismException
     */
    private function getDatabaseIdentifier(
        SqlTableInterface $tableInterface,
    ): string
    {
        $className = get_class($tableInterface);
        $fullNameParts = explode('\\', $className);

        if (isset($fullNameParts[count($fullNameParts)-1]) && strtolower($fullNameParts[count($fullNameParts)-2]) === 'tables'){
            return $fullNameParts[count($fullNameParts)-3];
        }

        throw ExceptionFactory::MisplacedTableInterfaceClass->create($className);
    }

    /**
     * @param SqlTableInterface $tableInterface
     * @return mysqli
     * @throws MinimalismException
     */
    public function create(
        SqlTableInterface $tableInterface,
    ): mysqli
    {
        $databaseName = $this->getDatabaseIdentifier($tableInterface);

        if (array_key_exists($databaseName, $this->databases)){
            return $this->databases[$databaseName];
        }

        if (!array_key_exists($databaseName, $this->databaseConnectionStrings)){
            throw ExceptionFactory::DatabaseConnectionStringMissing->create($databaseName);
        }

        $dbConf = $this->databaseConnectionStrings[$databaseName];

        $response = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

        if ($response->connect_errno) {
            throw ExceptionFactory::ErrorConnectingToTheDatabase->create($dbConf['name']);
        }

        $response->set_charset('utf8mb4');
        $this->databases[$databaseName] = $response;

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