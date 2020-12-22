<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use mysqli;

interface ConnectivityInterface
{
    /**
     * @return string
     */
    public function getDbToUse(): string;

    /**
     * @param mysqli $connection
     */
    public function setConnection(mysqli $connection): void;

    /**
     * @param array $connectionString
     */
    public function setStandaloneConnection(array $connectionString): void;
}