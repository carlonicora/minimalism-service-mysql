<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\core\Services\Exceptions\configurationException;
use CarloNicora\Minimalism\core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\core\Services\Exceptions\serviceNotFoundException;
use CarloNicora\Minimalism\core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\core\Services\Interfaces\serviceConfigurationsInterface;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\aabstractDatabaseManager;
use CarloNicora\Minimalism\Services\MySQL\Configurations\DDatabaseConfigurations;
use CarloNicora\Minimalism\Services\MySQL\errors\EErrors;
use mysqli;

class MySQL extends abstractService {
    /** @var DDatabaseConfigurations  */
    private DDatabaseConfigurations $configData;

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
     * @return aabstractDatabaseManager
     * @throws configurationException
     */
    public function create(string $dbReader): aabstractDatabaseManager {
        if (array_key_exists($dbReader, $this->configData->tableManagers)) {
            return $this->configData->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            $this->loggerWriteError(
                EErrors::ERROR_READER_CLASS_NOT_FOUND,
                'Database reader class ' . $dbReader . ' does not exist.',
                EErrors::LOGGER_SERVICE_NAME
            );
            throw new configurationException(self::class, 'reader class missing', EErrors::ERROR_READER_CLASS_NOT_FOUND);
        }

        /** @var aabstractDatabaseManager $response */
        $response = new $dbReader($this->services);

        $databaseName = $response->getDbToUse();
        $connection = $this->configData->getDatabase($databaseName);

        if (!isset($connection)) {
            $dbConf = $this->configData->getDatabaseConnectionString($databaseName);

            if (empty($dbConf)) {
                $this->loggerWriteError(
                    EErrors::ERROR_MISSING_CONNECTION_DETAILS,
                    'Missing connection details for ' . $databaseName,
                    EErrors::LOGGER_SERVICE_NAME
                );
                throw new configurationException(self::class, 'connection details missing', EErrors::ERROR_MISSING_CONNECTION_DETAILS);
            }

            $connection = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

            $connection->connect($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

            if ($connection->connect_errno) {
                $this->loggerWriteError(
                    EErrors::ERROR_CONNECTION_ERROR,
                    $databaseName . '  database connection error ' . $connection->connect_error . ': ' . $connection->connect_error,
                    EErrors::LOGGER_SERVICE_NAME
                );
                throw new configurationException(self::class, 'error connecting to the database',EErrors::ERROR_CONNECTION_ERROR);
            }

            $connection->set_charset('utf8mb4');

            $this->configData->setDatabase($databaseName, $connection);
        }

        $response->setConnection($connection);

        $this->configData->tableManagers[$dbReader] = $response;

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