<?php
namespace CarloNicora\Minimalism\Services\MySQL\Abstracts;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Facades\RecordFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLExecutionFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLFunctionsFacade;
use CarloNicora\Minimalism\Services\MySQL\Facades\SQLQueryCreationFacade;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\GenericQueriesInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLFunctionsFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLQueryCreationFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use mysqli;

abstract class AbstractTable implements TableInterface, GenericQueriesInterface
{
    /** @var string|null  */
    protected ?string $sql=null;

    /** @var array  */
    protected array $parameters=[];

    /** @var string */
    protected ?string $autoIncrementField=null;

    /** @var array */
    protected array $fields;

    /** @var array */
    protected ?array $primaryKey;

    /** @var string */
    protected string $tableName;

    /** @var string  */
    private string $dbToUse;

    /** @var string */
    protected string $insertIgnore = '';

    /** @var SQLExecutionFacadeInterface  */
    private SQLExecutionFacadeInterface $executor;

    /** @var SQLFunctionsFacadeInterface  */
    private SQLFunctionsFacadeInterface $functions;

    /** @var SQLQueryCreationFacadeInterface  */
    private SQLQueryCreationFacadeInterface $query;

    /**
     * AbstractTable constructor.
     * @param ServicesFactory $services
     */
    public function __construct(ServicesFactory $services)
    {
        $this->executor = new SQLExecutionFacade($services, $this);
        $this->functions = new SQLFunctionsFacade($this, $this->executor);
        $this->query = new SQLQueryCreationFacade($this);
    }

    /**
     *
     */
    public function initialiseAttributes() : void
    {
        $fullName = get_class($this);
        $fullNameParts = explode('\\', $fullName);

        if (!isset($this->tableName)){
            $this->tableName = end($fullNameParts);
        }

        if (!isset($this->dbToUse) && isset($fullNameParts[count($fullNameParts)-1]) && $fullNameParts[count($fullNameParts)-2] === 'tables'){
            $this->dbToUse = (string)$fullNameParts[count($fullNameParts)-3];
        }

        if (!isset($this->primaryKey)){
            foreach ($this->fields as $fieldName=>$fieldFlags){
                if (($fieldFlags & self::PRIMARY_KEY) > 0){
                    /** @noinspection NotOptimalIfConditionsInspection */
                    if (!isset($this->primaryKey)){
                        $this->primaryKey = [];
                    }
                    $this->primaryKey[$fieldName]=$fieldFlags;
                }
            }
        }

        foreach ($this->fields as $fieldName=>$fieldFlags){
            if (($fieldFlags & self::AUTO_INCREMENT) > 0){
                $this->autoIncrementField = $fieldName;
                break;
            }
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return array
     */
    public function getTableFields(): array
    {
        return $this->fields;
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
     * @param array $records
     * @param bool $delete
     * @throws DbSqlException
     */
    public function update(array &$records, bool $delete=false): void {
        $isSingle = false;

        if (isset($records) && count($records) > 0){
            if (!array_key_exists(0, $records)){
                $isSingle = true;
                $records = [$records];
            }

            $onlyInsertOrUpdate = true;
            $oneSql = $this->query->generateInsertOnDuplicateUpdateStart();
            foreach ($records as $recordKey=>$record) {
                if ($delete){
                    $status = RecordFacade::RECORD_STATUS_DELETED;
                } else {
                    $status = RecordFacade::getStatus($record);
                }

                if ($status !== RecordFacade::RECORD_STATUS_UNCHANGED) {
                    $oneSql .= $this->query->generateInsertOnDuplicateUpdateRecord($record);

                    $records[$recordKey]['_sql'] = array();
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
                        if (count($parameters) === 0){
                            $parameters[] = $parameter;
                        } elseif (array_key_exists($parameter, $record)){
                            $parameters[] = $record[$parameter];
                        } else {
                            $parameters[] = null;
                        }
                    }
                    $records[$recordKey]['_sql']['parameters'] = $parameters;
                }
            }

            $oneSql = substr($oneSql, 0, -1);
            $oneSql .= $this->query->generateInsertOnDuplicateUpdateEnd();

            if ($onlyInsertOrUpdate && !$isSingle && $this->query->canUseInsertOnDuplicate()) {
                $this->sql = $oneSql;
                $this->functions->runSql();
            } else {
                $this->functions->runUpdate($records);
            }
        }

        if ($isSingle){
            $records = $records[0];
        }
    }

    /**
     * @param array $records
     * @throws DbSqlException
     */
    public function delete(array $records): void {
        $this->update($records, true);
    }

    /**
     * @param $id
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadFromId($id): array {
        $this->sql = $this->query->generateSelectStatement();
        $this->parameters = $this->query->generateSelectParameters();
        $this->parameters[1] = $id;

        return $this->functions->runReadSingle();
    }

    /**
     * @return array
     * @throws DbSqlException
     */
    public function loadAll(): array {
        $this->sql = 'SELECT * FROM ' . $this->tableName . ';';

        return $this->functions->runRead();
    }

    /**
     * @return int
     * @throws DbSqlException
     */
    public function count(): int {
        $this->sql = 'SELECT count(*) as counter FROM ' . $this->tableName . ';';

        try {
            $responseArray = $this->functions->runReadSingle();
            $response = $responseArray['counter'];
        } catch (DbRecordNotFoundException $e) {
            $response = 0;
        }

        return $response;
    }
}