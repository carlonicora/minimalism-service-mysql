<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Interfaces\DataInterface;
use CarloNicora\Minimalism\Interfaces\ServiceInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use Exception;
use RuntimeException;

class MySQL implements ServiceInterface, DataInterface
{
    /** @var array */
    private array $tableManagers = [];

    /**
     * @var ConnectionFactory
     */
    private ConnectionFactory $connectionFactory;

    /**
     * MySQL constructor.
     * @param string $MINIMALISM_SERVICE_MYSQL
     */
    public function __construct(string $MINIMALISM_SERVICE_MYSQL)
    {
        $this->connectionFactory = new ConnectionFactory($MINIMALISM_SERVICE_MYSQL);
    }

    /**
     * @param string $dbReader
     * @return TableInterface
     * @throws Exception
     */
    public function create(string $dbReader): TableInterface
    {
        if (array_key_exists($dbReader, $this->tableManagers)) {
            return $this->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            throw new RuntimeException('Database reader class missing', 500);
        }

        /** @var TableInterface $response */
        $response = new $dbReader($this->connectionFactory);
        $response->initialiseAttributes();

        $databaseName = $response->getDbToUse();

        $connection = $this->connectionFactory->getDatabase($databaseName);

        $response->setConnection($connection);

        $this->tableManagers[$dbReader] = $response;

        return $response;
    }

    /**
     *
     */
    public function initialise(): void {}

    /**
     *
     */
    public function destroy(): void
    {
        $this->connectionFactory->resetDatabases();
        $this->tableManagers = [];
    }
}