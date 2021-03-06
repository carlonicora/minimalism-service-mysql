<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use CarloNicora\Minimalism\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;

interface MySqlTableInterface extends ConnectivityInterface, TableInterface
{
    /**
     * abstractDatabaseManager constructor.
     * @param LoggerInterface $logger
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionFactory $connectionFactory
    );

    /**
     * @param array $connectionParameters
     */
    public function initialiseAttributes(array $connectionParameters=[]) : void;

    /**
     * @return array
     */
    public function getTableFields() : array;

    /**
     * @return string|null
     */
    public function getSql() : ?string;

    /**
     * @return array
     */
    public function getParameters() : array;

    /**
     * @return array|null
     */
    public function getPrimaryKey() : ?array;

    /**
     * @return string|null
     */
    public function getAutoIncrementField() : ?string;

    /**
     * @return string
     */
    public function getInsertIgnore() : string;
}