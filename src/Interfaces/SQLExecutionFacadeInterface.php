<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use Exception;
use mysqli_stmt;

interface SQLExecutionFacadeInterface
{
    /**
     * SQLExecutionFacadeInterface constructor.
     * @param ConnectionFactory $connectionFactory
     * @param MySqlTableInterface $table
     */
    public function __construct(ConnectionFactory $connectionFactory, MySqlTableInterface $table);

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
     * @param int $retry
     * @return mysqli_stmt
     * @throws Exception
     */
    public function executeQuery(string $sql, array $parameters = [], int $retry=0): mysqli_stmt;

    /**
     *
     */
    public function rollback() : void;

    /**
     * @return int|null
     */
    public function getInsertedId(): ?int;

    /**
     * @param mysqli_stmt $statement
     * @return string
     */
    public function getStatementErrors(mysqli_stmt $statement): string;

    /**
     * @param bool $enabled
     * @throws Exception
     */
    public function toggleAutocommit(bool $enabled = true): void;

    /**
     * @param mysqli_stmt $statement
     * @throws Exception
     */
    public function closeStatement(mysqli_stmt $statement) : void;

    /**
     * @param string $sql
     * @return mysqli_stmt
     * @throws Exception
     */
    public function prepareStatement(string $sql) : mysqli_stmt;
}