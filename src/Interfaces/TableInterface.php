<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;

interface TableInterface extends ConnectivityInterface
{
    /** @var int  */
    public const INTEGER=0b1;
    public const DOUBLE=0b10;
    public const STRING=0b100;
    public const BLOB=0b1000;
    public const PRIMARY_KEY=0b10000;
    public const AUTO_INCREMENT=0b100000;
    public const TIME_CREATE=0b1000000;
    public const TIME_UPDATE=0b10000000;

    /** @var string  */
    public const INSERT_IGNORE = ' IGNORE';

    /**
     * abstractDatabaseManager constructor.
     * @param servicesFactory $services
     * @throws serviceNotFoundException
     */
    public function __construct(servicesFactory $services);

    /**
     *
     */
    public function initialiseAttributes() : void;

    /**
     * @return string
     */
    public function getTableName() : string;

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

    /**
     * @param array $records
     * @param bool $delete
     * @throws DbSqlException
     */
    public function update(array &$records, bool $delete=false): void;

    /**
     * @param array $records
     * @throws DbSqlException
     */
    public function delete(array $records): void;
}