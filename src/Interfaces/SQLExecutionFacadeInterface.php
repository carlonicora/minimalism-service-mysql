<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use mysqli_stmt;

interface SQLExecutionFacadeInterface
{
    /**
     * SQLExecutionFacadeInterface constructor.
     * @param ServicesFactory $services
     * @param TableInterface $table
     */
    public function __construct(ServicesFactory $services, TableInterface $table);

    /**
     * @param string $databaseName
     */
    public function setDatabaseName(string $databaseName): void;

    /**
     *
     */
    public function keepaliveConnection(): void;

    /**
     * @param string $sql
     * @param array $parameters
     * @return mysqli_stmt
     * @throws DbSqlException
     */
    public function executeQuery(string $sql, array $parameters = []): mysqli_stmt;

    /**
     *
     */
    public function rollback() : void;

    /**
     * @return mixed
     */
    public function getInsertedId();

    /**
     * @param mysqli_stmt $statement
     * @return string
     */
    public function getStatementErrors(mysqli_stmt $statement): string;

    /**
     * @param bool $enabled
     * @throws DbSqlException
     */
    public function toggleAutocommit(bool $enabled = true): void;

    /**
     * @param mysqli_stmt $statement
     * @throws DbSqlException
     */
    public function closeStatement(mysqli_stmt $statement) : void;

    /**
     * @param string $sql
     * @return mysqli_stmt
     * @throws DbSqlException
     */
    public function prepareStatement(string $sql) : mysqli_stmt;
}