<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use CarloNicora\Minimalism\Services\MySQL\Facades\RecordFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLExecutionFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLFunctionsFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLQueryCreationFacade;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\GenericQueriesInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLFunctionsFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLQueryCreationFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlTableInterface;
use Exception;
use LogicException;
use mysqli;
use RuntimeException;

abstract class AbstractMySqlTable implements MySqlTableInterface, GenericQueriesInterface
{
    /** @var string|null  */
    protected ?string $sql=null;

    /** @var array  */
    protected array $parameters=[];

    /** @var string */
    protected static string $tableName;

    /** @var array */
    protected static array $fullTableNames = [];

    /** @var array */
    protected static array $fields;

    /** @var array|null */
    protected ?array $primaryKey;

    /** @var string|null */
    protected ?string $autoIncrementField=null;

    /** @var string  */
    private string $dbToUse;

    /** @var string */
    protected string $insertIgnore = '';

    /** @var SQLExecutionFacadeInterface  */
    protected SQLExecutionFacadeInterface $executor;

    /** @var SQLFunctionsFacadeInterface  */
    protected SQLFunctionsFacadeInterface $functions;

    /** @var SQLQueryCreationFacadeInterface  */
    protected SQLQueryCreationFacadeInterface $query;

    /**
     * AbstractTable constructor.
     * @param ConnectionFactory $connectionFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private ConnectionFactory $connectionFactory,
        ?LoggerInterface $logger=null,
    )
    {
        if (!isset(static::$fields)) {
            throw new LogicException(get_class($this) . ' must have a $fields');
        }

        $this->executor = new SQLExecutionFacade($connectionFactory, $this, $logger);
        $this->functions = new SQLFunctionsFacade($this, $this->executor);
        $this->query = new SQLQueryCreationFacade($this);
    }

    /**
     * @param array $connectionParameters
     */
    public function initialiseAttributes(array $connectionParameters=[]) : void
    {
        $fullName = get_class($this);
        $fullNameParts = explode('\\', $fullName);

        if (!isset($this->dbToUse) && isset($fullNameParts[count($fullNameParts)-1]) && strtolower($fullNameParts[count($fullNameParts)-2]) === 'tables'){
            $this->dbToUse = $fullNameParts[count($fullNameParts)-3];
        }

        if (! isset(static::$fullTableNames[static::class])) {
            $connectionDetails = $this->connectionFactory->getDatabaseConnectionString($this->dbToUse);

            if ($connectionDetails !== null){
                if (str_contains(static::$tableName, '.')){
                    static::$tableName = explode('.', static::$tableName)[1];
                }

                static::$fullTableNames[static::class] = $connectionDetails['dbName'] . '.' .  static::$tableName;
            }
        }


        $this->executor->setDatabaseName($this->dbToUse);

        if (!isset($this->primaryKey)){
            foreach (static::$fields as $fieldName=>$fieldFlags){
                /** @noinspection SuspiciousBinaryOperationInspection */
                if (($fieldFlags & FieldInterface::PRIMARY_KEY) > 0){
                    if (!isset($this->primaryKey)){
                        $this->primaryKey = [];
                    }
                    $this->primaryKey[$fieldName]=$fieldFlags;
                }
            }
        }

        foreach (static::$fields as $fieldName=>$fieldFlags){
            /** @noinspection SuspiciousBinaryOperationInspection */
            if (($fieldFlags & FieldInterface::AUTO_INCREMENT) > 0){
                $this->autoIncrementField = $fieldName;
                break;
            }
        }
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        if (! isset(static::$tableName)) {
            throw new LogicException(static::class . ' must have a $tableName');
        }

        return static::$fullTableNames[static::class] ?? static::$tableName;
    }

    /**
     * @return array
     */
    public static function getTableFields(): array
    {
        if (! isset(static::$fields)) {
            throw new LogicException(static::class . ' must have a $fields');
        }

        return static::$fields;
    }

    /**
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array|null
     */
    public function getPrimaryKey(): ?array
    {
        return $this->primaryKey;
    }

    /**
     * @return string|null
     */
    public function getAutoIncrementField(): ?string
    {
        return $this->autoIncrementField;
    }

    /**
     * @return string
     */
    public function getInsertIgnore(): string
    {
        return $this->insertIgnore;
    }

    /**
     * @return string
     */
    public function getDbToUse(): string
    {
        return $this->dbToUse;
    }

    /**
     * @param mysqli $connection
     */
    public function setConnection(mysqli $connection): void
    {
        $this->executor->setConnection($connection);
    }

    /**
     * @param array $connectionString
     * @throws Exception
     */
    public function setStandaloneConnection(array $connectionString): void
    {
        $this->executor->setStandaloneConnection($connectionString);
    }

    /**
     * @param string $fieldName
     * @param int $status
     * @return bool
     */
    private function isTimingField(string $fieldName, int $status) : bool
    {
        $fieldFlags = static::$fields[$fieldName];
        /** @noinspection SuspiciousBinaryOperationInspection */
        return
            ($status === RecordFacade::RECORD_STATUS_NEW && ($fieldFlags & FieldInterface::TIME_CREATE))
            ||
            ($fieldFlags & FieldInterface::TIME_UPDATE);
    }

    /**
     * @param array $records
     * @param bool $delete
     * @param bool $avoidSingleInsert
     * @throws Exception
     */
    public function update(
        array &$records,
        bool $delete=false,
        bool $avoidSingleInsert=false
    ): void
    {
        $isSingle = false;

        if (count($records) > 0){
            if (!array_key_exists(0, $records)){
                $isSingle = true;
                $records = [$records];
            }

            if (count($records) === 1){
                $isSingle = true;
            }

            $atLeastOneUpdatedRecord = false;
            $onlyInsertOrUpdate = true;
            $oneSql = $this->query->generateInsertOnDuplicateUpdateStart();
            foreach ($records as $recordKey=>$record) {
                if ($delete){
                    $status = RecordFacade::RECORD_STATUS_DELETED;
                } else {
                    $status = RecordFacade::getStatus($record);
                }

                if ($status !== RecordFacade::RECORD_STATUS_UNCHANGED){
                    $atLeastOneUpdatedRecord = true;
                }

                if ($status !== RecordFacade::RECORD_STATUS_UNCHANGED) {
                    $records[$recordKey]['_sql'] = [];
                    $records[$recordKey]['_sql']['status'] = $status;

                    $parameters = [];
                    $parametersToUse = null;

                    switch ($status) {
                        case RecordFacade::RECORD_STATUS_NEW:
                            $records[$recordKey]['_sql']['statement'] = $this->query->generateInsertStatement();
                            $parametersToUse = $this->query->generateInsertParameters();
                            break;
                        case RecordFacade::RECORD_STATUS_UPDATED:
                            $records[$recordKey]['_sql']['statement'] = $this->query->generateUpdateStatement();
                            $parametersToUse = $this->query->generateUpdateParameters();
                            break;
                        case RecordFacade::RECORD_STATUS_DELETED:
                            $onlyInsertOrUpdate = false;
                            $records[$recordKey]['_sql']['statement'] = $this->query->generateDeleteStatement();
                            $parametersToUse = $this->query->generateDeleteParameters();
                            break;
                    }

                    foreach ($parametersToUse as $parameter){
                        if (count($parameters) === 0) {
                            $parameters[] = $parameter;
                        } elseif ($this->isTimingField($parameter, $status)) {
                            $record[$parameter] = date('Y-m-d H:i:s');
                            $parameters[] = $record[$parameter];
                        } elseif (array_key_exists($parameter, $record)){
                            $parameters[] = $record[$parameter];
                        } else {
                            $parameters[] = null;
                        }
                    }

                    $oneSql .= $this->query->generateInsertOnDuplicateUpdateRecord($record);
                    $records[$recordKey]['_sql']['parameters'] = $parameters;
                }
            }

            $oneSql = substr($oneSql, 0, -1);
            $oneSql .= $this->query->generateInsertOnDuplicateUpdateEnd();

            if ($atLeastOneUpdatedRecord) {
                if (!$avoidSingleInsert && $onlyInsertOrUpdate && !$isSingle && $this->query->canUseInsertOnDuplicate()) {
                    $this->parameters = [];
                    $this->sql = $oneSql;
                    $this->functions->runSql();
                } else {
                    $this->functions->runUpdate($records);
                }
            }
        }

        if ($isSingle){
            $records = $records[0];
        }
    }

    /**
     * @param array $records
     * @throws Exception
     */
    public function delete(array $records): void
    {
        $this->update($records, true);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function readAll(): array
    {
        $this->sql = 'SELECT * FROM ' . static::getTableName() . ';';
        $this->parameters = [];

        return $this->functions->runRead();
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function readById($id): array
    {
        $this->sql = $this->query->generateSelectStatement();
        $this->parameters = $this->query->generateSelectParameters();
        $this->parameters[1] = $id;

        return $this->functions->runRead();
    }

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     */
    public function readByField(string $fieldName, $fieldValue) : array
    {
        $this->sql = 'SELECT * FROM ' . static::getTableName() . ' WHERE ' . $fieldName . '=?;';
        $this->parameters = [$this->query->convertFieldType(static::$fields[$fieldName]), $fieldValue];

        return $this->functions->runRead();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function count(): int
    {
        $this->sql = 'SELECT count(*) as counter FROM ' . static::getTableName() . ';';
        $this->parameters = [];

        $responseArray = $this->functions->runRead();

        if (array_key_exists(1, $responseArray)){
            throw new RuntimeException('Count query returns more than one result', 500);
        }
        return $responseArray[0]['counter'];

    }

    /**
     * @param string $joinedTableName
     * @param string $joinedTablePrimaryKeyName
     * @param string $joinedTableForeignKeyName
     * @param int $joinedTablePrimaryKeyValue
     * @return array|null
     * @throws Exception
     */
    public function getFirstLevelJoin(
        string $joinedTableName,
        string $joinedTablePrimaryKeyName,
        string $joinedTableForeignKeyName,
        int $joinedTablePrimaryKeyValue
    ) : ?array
    {
        if (count($this->primaryKey) > 1){
            return null;
        }

        $primaryKey = array_key_first($this->primaryKey);

        $this->sql = 'SELECT ' . $joinedTableName . '.*, ' . static::getTableName() . '.* '
            . 'FROM ' . static::getTableName() . ' '
            . 'JOIN ' . $joinedTableName . ' ON ' . static::getTableName() . '.' . $primaryKey . '=' . $joinedTableName . '.' . $joinedTableForeignKeyName . ' '
            . 'WHERE ' . $joinedTableName . '.' . $joinedTablePrimaryKeyName . '=?;';

        $this->parameters = ['i', $joinedTablePrimaryKeyValue];

        return $this->functions->runRead();
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return array|null
     * @throws Exception
     */
    public function runSQL(
        string $sql,
        array $parameters,
    ): array|null
    {
        $this->sql = $sql;
        $this->parameters = $parameters;

        return $this->functions->runRead();
    }







    /**
     * @param $id
     * @return array
     * @throws Exception
     * @deprecated
     * @noinspection OverridingDeprecatedMethodInspection
     */
    public function byId($id): array
    {
        return $this->readById($id);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadById($id): array
    {
        return $this->readById($id);
    }

    /**
     * @return array
     * @throws Exception
     * @deprecated
     * @noinspection OverridingDeprecatedMethodInspection
     */
    public function all(): array
    {
        return $this->readAll();
    }

    /**
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadAll(): array
    {
        return $this->readAll();
    }

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     * @deprecated
     * @noinspection OverridingDeprecatedMethodInspection
     */
    public function byField(string $fieldName, $fieldValue) : array
    {
        return $this->readByField($fieldName, $fieldValue);
    }

    /**
     * @param string $fieldName
     * @param $fieldValue
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function loadByField(string $fieldName, $fieldValue) : array
    {
        return $this->readByField($fieldName, $fieldValue);
    }
}