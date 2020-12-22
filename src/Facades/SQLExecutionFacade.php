<?php /** @noinspection PhpDocRedundantThrowsInspection */

namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Events\MySQLErrorEvents;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\ConnectivityInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use JsonException;
use mysqli;
use mysqli_stmt;
use Exception;

class SQLExecutionFacade implements SQLExecutionFacadeInterface, ConnectivityInterface
{
    /** @var ServicesFactory  */
    private ServicesFactory $services;

    /** @var MySQL  */
    private MySQL $mysql;

    /** @var mysqli|null */
    private ?mysqli $connection=null;

    /** @var TableInterface  */
    private TableInterface $table;

    /** @var string|null  */
    private ?string $databaseName=null;

    /**
     * SQLExecutionFacade constructor.
     * @param ServicesFactory $services
     * @param TableInterface $table
     * @throws Exception
     */
    public function __construct(ServicesFactory $services, TableInterface $table)
    {
        $this->services = $services;
        $this->mysql = $this->services->service(MySQL::class);
        $this->table = $table;
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->connection->ping()){
            $this->connection->close();
        }
        $this->connection = null;
    }

    /**
     * @param string $databaseName
     */
    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    /**
     * @throws Exception
     */
    public function keepaliveConnection(): void
    {
        if (!$this->connection->ping()){
            $this->connection = $this->mysql->connect($this->databaseName);
        }
    }

    /**
     * @return string
     */
    public function getDbToUse(): string
    {
        return $this->table->getDbToUse();
    }

    /**
     * @param mysqli $connection
     */
    public function setConnection(mysqli $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @param array $connectionString
     * @throws Exception
     */
    public function setStandaloneConnection(array $connectionString): void
    {
        $this->connection = new mysqli(
            $connectionString['host'],
            $connectionString['username'],
            $connectionString['password'],
            $connectionString['dbName'],
            $connectionString['port']);

        $this->connection->connect(
            $connectionString['host'],
            $connectionString['username'],
            $connectionString['password'],
            $connectionString['dbName'],
            $connectionString['port']);

        if ($this->connection->connect_errno) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_CONNECTION_ERROR($connectionString['dbName'], $this->connection->connect_errno, $this->connection->connect_error))
                ->throw(ConfigurationException::class, 'Error connecting to the database');
        }

        $this->connection->set_charset('utf8mb4');
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @param int $retry
     * @return mysqli_stmt
     * @throws Exception
     */
    public function executeQuery(string $sql, array $parameters = [], int $retry=0): mysqli_stmt
    {
        $statement = $this->prepareStatement($sql);

        if (false === empty($parameters)) {
            call_user_func_array([$statement, 'bind_param'], $this->refValues($parameters));
        }

        if (false === $statement->execute()) {
            if ($retry<10 && $this->connection->errno===1213){
                $retry++;
                usleep(100000);
                $this->executeQuery($sql, $parameters, $retry);
            } else {
                try {
                    $jsonParameters = json_encode($parameters, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $jsonParameters = '';
                }
                $this->services->logger()->error()
                    ->log(MySQLErrorEvents::ERROR_STATEMENT_EXECUTION($sql, $jsonParameters, $statement->errno, $statement->error))
                    ->throw(DbSqlException::class, 'MySQL statement execution failed.');
            }
        }

        return $statement;
    }

    /**
     *
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * @return mixed|void
     */
    public function getInsertedId()
    {
        return $this->connection->insert_id;
    }


    /**
     * @param mysqli_stmt $statement
     * @return string
     */
    public function getStatementErrors(mysqli_stmt $statement): string
    {
        $errorDetails = 'Error ' . $statement->errno . ' ' . $statement->sqlstate . ': ' . $statement->error . PHP_EOL;
        foreach ($statement->error_list as $error) {
            $errorDetails .= 'Error ' . $error['errno'] . ' ' . $error['sqlstate'] . ': ' . $error['error'] . PHP_EOL;
        }

        return 'Error ' . $statement->errno . ': ' . $statement->error . PHP_EOL . $errorDetails;
    }

    /**
     * @param bool $enabled
     * @throws Exception|DbSqlException
     */
    public function toggleAutocommit(bool $enabled = true): void
    {
        if (false === $this->connection->autocommit($enabled)) {
            $this->services->logger()->error()
                ->log($enabled
                    ? MySQLErrorEvents::ERROR_ENABLE_AUTOCOMMIT($this->connection->errno, $this->connection->sqlstate, $this->connection->error)
                    : MySQLErrorEvents::ERROR_DISABLE_AUTOCOMMIT($this->connection->errno, $this->connection->sqlstate, $this->connection->error))
                ->throw(DbSqlException::class, 'Autocommit failed');
        }
    }

    /**
     * @param mysqli_stmt $statement
     * @throws Exception|DbSqlException
     */
    public function closeStatement(mysqli_stmt $statement) : void
    {
        if (false === $statement->close()) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_CLOSE_STATEMENT($this->getStatementErrors($statement)))
                ->throw(DbSqlException::class, 'MySQL failed to close statement.');
        }
    }

    /**
     * @param string $sql
     * @return mysqli_stmt
     * @throws Exception|DbSqlException
     */
    public function prepareStatement(string $sql): mysqli_stmt
    {
        $response = $this->connection->prepare($sql);

        if ($response === false) {
            $this->services->logger()->error()
                ->log(MySQLErrorEvents::ERROR_STATEMENT_PREPARATION($sql, $this->connection->errno, $this->connection->sqlstate, $this->connection->error))
                ->throw(DbSqlException::class, 'MySQL statement preparation failed.');
        }

        return $response;
    }

    /**
     * @param $arr
     * @return array
     */
    private function refValues($arr): array
    {
        $refs = [];

        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }

        return $refs;
    }
}