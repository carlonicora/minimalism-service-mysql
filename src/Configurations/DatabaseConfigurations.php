<?php
namespace CarloNicora\Minimalism\Services\MySQL\Configurations;

use CarloNicora\Minimalism\core\Services\Abstracts\AbstractServiceConfigurations;
use mysqli;

class DatabaseConfigurations extends abstractServiceConfigurations {
    /** @var array */
    private array $databases = [];

    /** @var array */
    public array $databaseConnectionStrings = [];

    /** @var array */
    public array $tableManagers = [];

    /**
     * databaseConfigurations constructor.
     */
    public function __construct() {
        $dbNames = getenv('MINIMALISM_SERVICE_MYSQL');
        if (!empty($dbNames)) {
            $dbNames = explode(',', $dbNames);
            foreach ($dbNames ?? [] as $dbName) {
                $dbName = trim($dbName);
                $dbConnection = getenv(trim($dbName));
                $dbConf = [];
                [$dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']] = explode(',', $dbConnection);

                if (!array_key_exists($dbName, $this->databaseConnectionStrings)) {
                    $this->databaseConnectionStrings[$dbName] = $dbConf;
                }
            }
        }
    }

    /**
     * @param string $databaseName
     * @return mysqli|null
     */
    public function getDatabase(string $databaseName): ?mysqli {
        $response = null;

        if ($this->databases !== null && array_key_exists($databaseName, $this->databases)){
            $response = $this->databases[$databaseName];
        }

        return $response;
    }

    /**
     * @param string $databaseName
     * @return null|array
     */
    public function getDatabaseConnectionString(string $databaseName): ?array {
        $response = null;

        if ($this->databaseConnectionStrings !== null && array_key_exists($databaseName, $this->databaseConnectionStrings)){
            $response = $this->databaseConnectionStrings[$databaseName];
        }

        return $response;
    }

    /**
     * @param string $databaseName
     * @param mysqli $database
     */
    public function setDatabase(string $databaseName, mysqli $database): void {
        $this->databases[$databaseName] = $database;
    }

    /**
     *
     */
    public function resetDatabases() : void {
        /**
         * @var string $databaseKey
         * @var mysqli $connection
         */
        foreach ($this->databases as $databaseKey=>$connection){
            if ($connection !== null && $connection->ping()){
                $connection->close();
            }
        }

        $this->databases = [];
        $this->tableManagers = [];
    }
}