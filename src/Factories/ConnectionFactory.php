<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use Exception;
use mysqli;
use RuntimeException;
use Throwable;

class ConnectionFactory
{
    /** @var array */
    private array $databases = [];

    /** @var array */
    public array $databaseConnectionStrings = [];

    public function __construct(
        string $databaseConfigurations,
        private ?LoggerInterface $logger=null,
    )
    {
        if (!empty($databaseConfigurations)) {
            $databaseNames = explode(',', $databaseConfigurations);
            foreach ($databaseNames ?? [] as $databaseName) {
                if ($this->getDatabaseConnectionString($databaseName) === null) {
                    $databaseConnectionString = $_ENV[trim($databaseName)];
                    $databaseConnectionParameters = [];
                    [
                        $databaseConnectionParameters['host'],
                        $databaseConnectionParameters['username'],
                        $databaseConnectionParameters['password'],
                        $databaseConnectionParameters['dbName'],
                        $databaseConnectionParameters['port']
                    ] = explode(',', $databaseConnectionString);

                    $this->setDatabaseConnectionString($databaseName, $databaseConnectionParameters);

                    $this->logger->info(
                        message: 'Database connection read',
                        domain: 'mysql',
                        context: ['database name'=>$databaseConnectionParameters['dbName']]
                    );
                }
            }
        }
    }

    /**
     * @param mysqli $connection
     * @param string $databaseName
     * @throws Exception
     */
    public function keepalive(mysqli &$connection, string $databaseName): void
    {
        try {
            if (!$connection->ping()) {
                $connection = $this->connect($databaseName);
            }
        } catch (Exception|Throwable) {
            $connection = $this->connect($databaseName);
        }
    }

    /**
     * @param string $databaseName
     * @return mysqli
     * @throws Exception
     */
    private function connect(string $databaseName): mysqli
    {
        $dbConf = $this->getDatabaseConnectionString($databaseName);

        if (empty($dbConf)) {
            $this->logger->emergency(
                message: 'Database connection details missing',
                domain: 'mysql',
                context: ['database name'=>$databaseName]
            );
            throw new RuntimeException('Connection details missing', 500);
        }

        $response = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

        if ($response->connect_errno) {
            $this->logger->error(
                message: 'Error connecting to the database',
                domain: 'mysql',
                context: ['database name'=>$dbConf['dbName']]
            );
            throw new RuntimeException('Error connecting to the database', 500);
        }

        $response->set_charset('utf8mb4');

        $this->setDatabase($databaseName, $response);

        return $response;
    }

    /**
     * @param string $databaseConnectionName
     * @return bool
     */
    public function hasConfiguration(string $databaseConnectionName): bool
    {
        return !empty($this->configData->databaseConnectionStrings[$databaseConnectionName]);
    }

    /**
     * @param string $databaseName
     * @return mysqli
     * @throws Exception
     */
    public function getDatabase(string $databaseName): mysqli
    {
        if (!array_key_exists($databaseName, $this->databases)) {
            $response = $this->connect($databaseName);
        } else {
            $response = $this->databases[$databaseName];

            if (!isset($response)) {
                $response = $this->connect($databaseName);
            }
        }

        $this->setDatabase($databaseName, $response);

        return $response;
    }

    /**
     * @param string $databaseName
     * @return null|array
     */
    public function getDatabaseConnectionString(string $databaseName): ?array
    {
        return $this->databaseConnectionStrings[$databaseName] ?? null;
    }

    /**
     * @param string $databaseName
     * @param array $databaseConnectionParameters
     */
    private function setDatabaseConnectionString(string $databaseName, array $databaseConnectionParameters): void
    {
        $this->databaseConnectionStrings[$databaseName] = $databaseConnectionParameters;
    }

    /**
     * @param string $databaseName
     * @param mysqli $database
     */
    private function setDatabase(string $databaseName, mysqli $database): void
    {
        $this->databases[$databaseName] = $database;
    }

    /**
     *
     */
    public function resetDatabases() : void
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