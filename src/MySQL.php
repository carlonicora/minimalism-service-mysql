<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Services\MySQL\Configurations\DatabaseConfigurations;
use CarloNicora\Minimalism\Services\MySQL\Events\MySQLErrorEvents;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use mysqli;
use Exception;

class MySQL extends abstractService {
    /** @var DatabaseConfigurations  */
    private DatabaseConfigurations $configData;

    /**
     * abstractApiCaller constructor.
     * @param serviceConfigurationsInterface $configData
     * @param servicesFactory $services
     * @throws serviceNotFoundException
     */
    public function __construct(serviceConfigurationsInterface $configData, servicesFactory $services) {
        parent::__construct($configData, $services);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->configData = $configData;
    }

    /**
     * @param string $dbReader
     * @param bool $createStandaloneConnection
     * @return TableInterface
     * @throws Exception
     */
    public function create(string $dbReader, bool $createStandaloneConnection=false): TableInterface {
        if (!$createStandaloneConnection && array_key_exists($dbReader, $this->configData->tableManagers)) {
            return $this->configData->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_READER_CLASS_NOT_FOUND($dbReader))
                ->throw(ConfigurationException::class, 'Reader class missing');
        }

        /** @var TableInterface $response */
        $response = new $dbReader($this->services);
        $response->initialiseAttributes();

        $databaseName = $response->getDbToUse();

        if ($createStandaloneConnection){
            $connectionString = $this->configData->getDatabaseConnectionString($databaseName);

            $response->setStandaloneConnection($connectionString);
        } else {
            $connection = $this->configData->getDatabase($databaseName);

            if (!isset($connection)) {
                $connection = $this->connect($databaseName);
            }

            $response->setConnection($connection);
            $this->configData->setDatabase($databaseName, $connection);

            $this->configData->tableManagers[$dbReader] = $response;
        }


        return $response;
    }

    /**
     *
     */
    public function resetDatabases(): void
    {
        $this->configData->resetDatabases();
    }

    /**
     * @param string $databaseName
     * @return mysqli
     * @throws Exception
     */
    public function connect(string $databaseName): mysqli
    {
        $dbConf = $this->configData->getDatabaseConnectionString($databaseName);

        if (empty($dbConf)) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_MISSING_CONNECTION_DETAILS($databaseName))
                ->throw(ConfigurationException::class, 'Connection details missing');
        }

        $response = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

        $response->connect($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

        if ($response->connect_errno) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_CONNECTION_ERROR($databaseName, $response->connect_errno, $response->connect_error))
                ->throw(ConfigurationException::class, 'Error connecting to the database');
        }

        $response->set_charset('utf8mb4');

        $this->configData->setDatabase($databaseName, $response);

        return $response;
    }

    /**
     * @param string $databaseConnectionName
     * @return bool
     */
    public function hasConfiguration(string $databaseConnectionName): bool {
        return !empty($this->configData->databaseConnectionStrings[$databaseConnectionName]);
    }

    /**
     *
     */
    public function cleanNonPersistentVariables(): void {
        $this->configData->resetDatabases();
    }
}