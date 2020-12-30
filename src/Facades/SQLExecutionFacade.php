<?php /** @noinspection PhpDocRedundantThrowsInspection */

namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\ConnectivityInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlTableInterface;
use mysqli;
use mysqli_stmt;
use Exception;
use RuntimeException;

class SQLExecutionFacade implements SQLExecutionFacadeInterface, ConnectivityInterface
{
    /** @var mysqli|null */
    private ?mysqli $connection=null;

    /** @var string|null  */
    private ?string $databaseName=null;

    /**
     * SQLExecutionFacade constructor.
     * @param ConnectionFactory $connectionFactory
     * @param MySqlTableInterface $table
     */
    public function __construct(private ConnectionFactory $connectionFactory, private MySqlTableInterface $table){}

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
        $this->connectionFactory->keepalive($this->connection, $this->databaseName);
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
            throw new RuntimeException('Error connecting to the database', 503);
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
                throw new RuntimeException('MySQL statement execution failed.', 500);
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
     * @return int|null
     */
    public function getInsertedId(): ?int
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
     * @throws Exception
     */
    public function toggleAutocommit(bool $enabled = true): void
    {
        if (false === $this->connection->autocommit($enabled)) {
            throw new RuntimeException('Autocommit failed', 500);
        }
    }

    /**
     * @param mysqli_stmt $statement
     * @throws Exception
     */
    public function closeStatement(mysqli_stmt $statement) : void
    {
        if (false === $statement->close()) {
            throw new RuntimeException('MySQL failed to close statement', 500);
        }
    }

    /**
     * @param string $sql
     * @return mysqli_stmt
     * @throws Exception
     */
    public function prepareStatement(string $sql): mysqli_stmt
    {
        $response = $this->connection->prepare($sql);

        if ($response === false) {
            throw new RuntimeException('MySQL statement preparation failed', 500);
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