<?php
namespace carlonicora\minimalism\services\MySQL;

use carlonicora\minimalism\core\services\exceptions\configurationException;
use carlonicora\minimalism\core\services\abstracts\abstractService;
use carlonicora\minimalism\core\services\exceptions\serviceNotFoundException;
use carlonicora\minimalism\core\services\factories\servicesFactory;
use carlonicora\minimalism\core\services\interfaces\serviceConfigurationsInterface;
use carlonicora\minimalism\services\logger\traits\logger;
use carlonicora\minimalism\services\MySQL\abstracts\abstractDatabaseManager;
use carlonicora\minimalism\services\MySQL\configurations\databaseConfigurations;
use carlonicora\minimalism\services\MySQL\errors\errors;
use mysqli;

class MySQL extends abstractService {
    use logger;

    /** @var databaseConfigurations  */
    private databaseConfigurations $configData;

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

        $this->loggerInitialise($services);
    }

    /**
     * @param string $dbReader
     * @return abstractDatabaseManager
     * @throws configurationException
     */
    public function create(string $dbReader): abstractDatabaseManager {
        if (array_key_exists($dbReader, $this->configData->tableManagers)) {
            return $this->configData->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            $this->loggerWriteError(
                errors::ERROR_READER_CLASS_NOT_FOUND,
                'Database reader class ' . $dbReader . ' does not exist.',
                errors::LOGGER_SERVICE_NAME
            );
            throw new configurationException(self::class, 'reader class missing', errors::ERROR_READER_CLASS_NOT_FOUND);
        }

        /** @var abstractDatabaseManager $response */
        $response = new $dbReader($this->services);

        $databaseName = $response->getDbToUse();
        $connection = $this->configData->getDatabase($databaseName);

        if (!isset($connection)) {
            $dbConf = $this->configData->getDatabaseConnectionString($databaseName);

            if (empty($dbConf)) {
                $this->loggerWriteError(
                    errors::ERROR_MISSING_CONNECTION_DETAILS,
                    'Missing connection details for ' . $databaseName,
                    errors::LOGGER_SERVICE_NAME
                );
                throw new configurationException(self::class, 'connection details missing', errors::ERROR_MISSING_CONNECTION_DETAILS);
            }

            $connection = new mysqli($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

            $connection->connect($dbConf['host'], $dbConf['username'], $dbConf['password'], $dbConf['dbName'], $dbConf['port']);

            if ($connection->connect_errno) {
                $this->loggerWriteError(
                    errors::ERROR_CONNECTION_ERROR,
                    $databaseName . '  database connection error ' . $connection->connect_error . ': ' . $connection->connect_error,
                    errors::LOGGER_SERVICE_NAME
                );
                throw new configurationException(self::class, 'error connecting to the database',errors::ERROR_CONNECTION_ERROR);
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