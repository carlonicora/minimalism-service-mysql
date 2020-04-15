<?php
namespace carlonicora\minimalism\services\MySQL\abstracts;

use carlonicora\minimalism\services\MySQL\exceptions\dbRecordNotFoundException;
use carlonicora\minimalism\services\MySQL\exceptions\dbSqlException;
use mysqli;
use mysqli_stmt;

abstract class abstractDatabaseManager {
    public const RECORD_STATUS_NEW = 1;
    public const RECORD_STATUS_UNCHANGED = 2;
    public const RECORD_STATUS_UPDATED = 3;
    public const RECORD_STATUS_DELETED = 4;

    /**
     * New
     */
    public const INTEGER=0b1;
    public const DOUBLE=0b10;
    public const STRING=0b100;
    public const BLOB=0b1000;
    public const PRIMARY_KEY=0b10000;
    public const AUTO_INCREMENT=0b100000;

    public const INSERT_IGNORE = ' IGNORE';

    /** @var mysqli */
    private mysqli $connection;

    /** @var string */
    protected string $dbToUse;

    /** @var string */
    protected string $autoIncrementField;

    /** @var array */
    protected array $fields;

    /** @var array */
    protected ?array $primaryKey;

    /** @var string */
    protected string $tableName;

    /** @var string */
    protected string $insertIgnore = '';

    /**
     * abstractDatabaseManager constructor.
     */
    public function __construct() {
        $fullName = get_class($this);
        $fullNameParts = explode('\\', $fullName);

        if (!isset($this->tableName)){
            $this->tableName = end($fullNameParts);
        }

        if (!isset($this->dbToUse) && isset($fullNameParts[count($fullNameParts)-1]) && $fullNameParts[count($fullNameParts)-2] === 'tables'){
            $this->dbToUse = $fullNameParts[count($fullNameParts)-3];
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

        if (!isset($this->autoIncrementField)){
            foreach ($this->fields as $fieldName=>$fieldFlags){
                if (($fieldFlags & self::AUTO_INCREMENT) > 0){
                    $this->autoIncrementField = $fieldName;
                    break;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getDbToUse(): string {
        return $this->dbToUse;
    }

    /**
     * @param mysqli $connection
     */
    public function setConnection(mysqli $connection): void {
        $this->connection = $connection;
    }

    /**
     * @param array $records
     * @throws dbSqlException
     */
    public function delete(array $records): void {
        $this->update($records, true);
    }

    /**
     * @param $sql
     * @param null $parameters
     * @throws dbSqlException
     */
    public function runSql($sql, $parameters=null): void {
        try {
            if (false === $this->connection->autocommit(false)) {
                throw new dbSqlException('MySQL failed to disable autocommit. '
                    . 'Error ' . $this->connection->errno . ' ' . $this->connection->sqlstate . ': ' . $this->connection->error);
            }

            $statement = $this->executeStatement($sql, $parameters);
            if (false === $statement->close()) {
                throw new dbSqlException('MySQL filed to close statement. ' . $this->getStatementErrors($statement));
            }

            if (false === $this->connection->autocommit(true)) {
                throw new dbSqlException('MySQL failed to enable autocommit. '
                    . 'Error ' . $this->connection->errno . ' ' . $this->connection->sqlstate . ': ' . $this->connection->error);
            }
        } catch (dbSqlException $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    /**
     * @param array $records
     * @param bool $delete
     * @throws dbSqlException
     */
    public function update(array &$records, bool $delete=false): void {
        $isSingle = false;

        if (isset($records) && count($records) > 0){
            if (!array_key_exists(0, $records)){
                $isSingle = true;
                $records = [$records];
            }

            $onlyInsertOrUpdate = true;
            $oneSql = $this->generateInsertOnDuplicateUpdateStart();
            foreach ($records as $recordKey=>$record) {
                if ($delete){
                    $status = self::RECORD_STATUS_DELETED;
                } else {
                    $status = $this->status($record);
                }

                if ($status !== self::RECORD_STATUS_UNCHANGED) {
                    $oneSql .= $this->generateInsertOnDuplicateUpdateRecord($record);

                    $records[$recordKey]['_sql'] = array();
                    $records[$recordKey]['_sql']['status'] = $status;

                    $parameters = [];
                    $parametersToUse = null;

                    switch ($status) {
                        case self::RECORD_STATUS_NEW:
                            $records[$recordKey]['_sql']['statement'] = $this->generateInsertStatement();
                            $parametersToUse = $this->generateInsertParameters();
                            break;
                        case self::RECORD_STATUS_UPDATED:
                            $records[$recordKey]['_sql']['statement'] = $this->generateUpdateStatement();
                            $parametersToUse = $this->generateUpdateParameters();
                            break;
                        case self::RECORD_STATUS_DELETED:
                            $onlyInsertOrUpdate = false;
                            $records[$recordKey]['_sql']['statement'] = $this->generateDeleteStatement();
                            $parametersToUse = $this->generateDeleteParameters();
                            break;

                    }

                    foreach ($parametersToUse as $parameter){
                        if (count($parameters) === 0){
                            $parameters[] = $parameter;
                        } else if (array_key_exists($parameter, $record)){
                            $parameters[] = $record[$parameter];
                        } else {
                            $parameters[] = null;
                        }
                    }
                    $records[$recordKey]['_sql']['parameters'] = $parameters;
                }
            }

            $oneSql = substr($oneSql, 0, -1);
            $oneSql .= $this->generateInsertOnDuplicateUpdateEnd();

            if ($onlyInsertOrUpdate && !$isSingle && $this->canUseInsertOnDuplicate()) {
                $this->runSql($oneSql);
            } else {
                $this->runUpdate($records);
            }
        }

        if ($isSingle){
            $records = $records[0];
        }
    }

    /**
     * @param $sql
     * @param null $parameters
     * @return array
     * @throws dbSqlException
     */
    protected function runRead($sql, $parameters=null): array {
        $response = [];

        $statement = $this->executeStatement($sql, $parameters);
        $results = $statement->get_result();

        if ($results !== false && $results->num_rows > 0){
            while ($record = $results->fetch_assoc()){
                $this->addOriginalValues($record);
                $response[] = $record;
            }
        }

        if (false === $statement->close()) {
            throw new dbSqlException('MySQL filed to close statement. ' . $this->getStatementErrors($statement));
        }

        return $response;
    }

    /**
     * @param mysqli_stmt $statement
     * @return string
     */
    private function getStatementErrors(mysqli_stmt $statement): string {
        $errorDetails = 'Error ' . $statement->errno . ' ' . $statement->sqlstate . ': ' . $statement->error . PHP_EOL;
        foreach ($statement->error_list as $error) {
            $errorDetails .= 'Error ' . $error['errno'] . ' ' . $error['sqlstate'] . ': ' . $error['error'] . PHP_EOL;
        }

        return 'Error ' . $statement->errno . ': ' . $statement->error . PHP_EOL . $errorDetails;
    }

    /**
     * @param array $objects
     * @throws dbSqlException
     */
    protected function runUpdate(array &$objects): void
    {
        try {
            if (false === $this->connection->autocommit(false)) {
                throw new dbSqlException('MySQL failed to disable autocommit. '
                    . 'Error ' . $this->connection->errno . ' ' . $this->connection->sqlstate . ': ' . $this->connection->error);
            }

            foreach ($objects as $objectKey => $object) {
                if (array_key_exists('_sql', $object)) {
                    $statement = $this->executeStatement($object['_sql']['statement'], $object['_sql']['parameters']);

                    if (false === $statement->close()) {
                        throw new dbSqlException('MySQL filed to close statement. ' . $this->getStatementErrors($statement));
                    }

                    if (isset($this->autoIncrementField) && $object['_sql']['status'] === self::RECORD_STATUS_NEW) {
                        $objects[$objectKey][$this->autoIncrementField] = $this->connection->insert_id;
                    }

                    unset($objects[$objectKey]['_sql']);

                    $this->addOriginalValues($objects[$objectKey]);
                }
            }

            if (false === $this->connection->autocommit(true)) {
                throw new dbSqlException('MySQL failed to enable autocommit. '
                    . 'Error ' . $this->connection->errno . ' ' . $this->connection->sqlstate . ': ' . $this->connection->error);
            }
        } catch (dbSqlException $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return mysqli_stmt
     * @throws dbSqlException
     */
    protected function executeStatement(string $sql, array $parameters = []): mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new dbSqlException('MySQL statement preparation failed. '
                . 'Error ' . $this->connection->errno . ' ' . $this->connection->sqlstate . ': ' . $this->connection->error);
        }

        if (false === empty($parameters)) {
            call_user_func_array(array($statement, 'bind_param'), $this->refValues($parameters));
        }

        if (false === $statement->execute()) {
            throw new dbSqlException('MySql statement execution failed. ' . $this->getStatementErrors($statement));
        }

        return $statement;
    }

    /**
     * @param $sql
     * @param null $parameters
     * @return array
     * @throws dbRecordNotFoundException
     * @throws dbSqlException
     */
    protected function runReadSingle($sql, $parameters=null): array {
        $response = $this->runRead($sql, $parameters);

        if (count($response) === 0) {
            throw new dbRecordNotFoundException('Record not found');
        }

        if (count($response) > 1) {
            throw new dbRecordNotFoundException('Multiple records found');
        }

        return $response[0];
    }

    /**
     * @param $record
     * @return int
     */
    protected function status($record): int {
        if (array_key_exists('originalValues', $record)){
            $response = self::RECORD_STATUS_UNCHANGED;
            foreach ($record['originalValues'] as $fieldName=>$originalValue){
                if ($originalValue !== $record[$fieldName]){
                    $response = self::RECORD_STATUS_UPDATED;
                    break;
                }
            }
        } else {
            $response = self::RECORD_STATUS_NEW;
        }

        return $response;
    }

    /**
     * @param array $record
     */
    private function addOriginalValues(&$record): void {
        $originalValues = array();
        foreach($record as $fieldName=>$fieldValue){
            $originalValues[$fieldName] = $fieldValue;
        }
        $record['originalValues'] = $originalValues;
    }

    /**
     * @param $arr
     * @return array
     */
    private function refValues($arr): array {
        $refs = [];

        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }

        return $refs;
    }

    /**
     * @return string
     */
    private function generateSelectStatement(): string {
        $response = 'SELECT * FROM ' . $this->tableName . ' WHERE ';

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $response .= $fieldName . '=? AND ';
        }

        $response = substr($response, 0, -5);

        $response .= ';';

        return $response;
    }

    /**
     * @param int|string $fieldType
     * @return string
     */
    private function convertFieldType($fieldType): string {
        if (is_int($fieldType)){
            if (($fieldType & self::INTEGER) > 0){
                $fieldType = 'i';
            } else if (($fieldType & self::DOUBLE) > 0){
                $fieldType = 'd';
            } else if (($fieldType & self::STRING) > 0){
                $fieldType = 's';
            } else {
                $fieldType = 'b';
            }
        }

        return ($fieldType);
    }

    /**
     * @return array
     */
    private function generateSelectParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return bool
     */
    private function canUseInsertOnDuplicate(): bool {
        if (isset($this->primaryKey)) {
            foreach ($this->fields as $fieldName => $fieldType) {
                if (!array_key_exists($fieldName, $this->primaryKey)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return string
     */
    private function generateInsertOnDuplicateUpdateStart(): string {
        $response = 'INSERT INTO ' . $this->tableName . ' (';

        foreach ($this->fields as $fieldName=>$fieldType){
            $response .= $fieldName . ',';
        }

        $response = substr($response, 0, -1);

        $response .= ') VALUES ';

        return $response;
    }

    /**
     * @param array $record
     * @return string
     */
    private function generateInsertOnDuplicateUpdateRecord(array $record): string {
        $response = '(';

        foreach ($this->fields as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $fieldValue = 'NULL';
            if (array_key_exists($fieldName, $record) && $record[$fieldName] !== NULL) {
                $fieldValue = $record[$fieldName];
            }

            if ($fieldType === 'i' && (is_bool($fieldValue))){
                $fieldValue = $fieldValue ? 1 : 0;
            }

            if ($fieldValue !== 'NULL' && ($fieldType === 's' || $fieldType === 'b')){
                $response .= '\'' . $fieldValue . '\',';
            } else {
                $response .= $fieldValue . ',';
            }
        }
        $response = substr($response, 0, -1);
        $response .= '),';

        return $response;
    }

    /**
     * @return string
     */
    private function generateInsertOnDuplicateUpdateEnd(): string {
        $response = ' ON DUPLICATE KEY UPDATE ';

        foreach ($this->fields as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->primaryKey)) {
                $response .= $fieldName . '=VALUES(' . $fieldName . '),';
            }
        }
        $response = substr($response, 0, -1);

        $response .= ';';

        return ($response);
    }


    /**
     * @return string
     */
    private function generateInsertStatement(): string {
        $response = 'INSERT' . $this->insertIgnore . ' INTO ' . $this->tableName . ' (';

        $parameterList = '';
        foreach ($this->fields as $fieldName=>$fieldType){
            $response .= $fieldName . ', ';
            $parameterList .= '?, ';
        }

        $response = substr($response, 0, -2);
        $parameterList = substr($parameterList, 0, -2);

        $response .= ') VALUES (' . $parameterList . ');';

        return $response;
    }

    /**
     * @return array
     */
    private function generateInsertParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->fields as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return string
     */
    private function generateDeleteStatement(): string {
        $response = 'DELETE FROM ' . $this->tableName . ' WHERE ';

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $response .= $fieldName . '=? AND ';
        }

        $response = substr($response, 0, -5);

        $response .= ';';

        return $response;
    }

    /**
     * @return array
     */
    private function generateDeleteParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return string
     */
    private function generateUpdateStatement(): string {
        $response = 'UPDATE ' . $this->tableName . ' SET ';

        foreach ($this->fields as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->primaryKey)){
                $response .= $fieldName . '=?, ';
            }
        }

        $response = substr($response, 0, -2);

        $response .= ' WHERE ';

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $response .= $fieldName . '=? AND ';
        }

        $response = substr($response, 0, -5);

        $response .= ';';

        return $response;
    }

    /**
     * @return array
     */
    private function generateUpdateParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->fields as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->primaryKey)) {
                $fieldType = $this->convertFieldType($fieldType);
                $response[0] .= $fieldType;
                $response[] = $fieldName;
            }
        }

        foreach ($this->primaryKey as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @param $id
     * @return array
     * @throws dbRecordNotFoundException
     * @throws dbSqlException
     */
    public function loadFromId($id): array {
        $sql = $this->generateSelectStatement();
        $parameters = $this->generateSelectParameters();

        $parameters[1] = $id;

        return $this->runReadSingle($sql, $parameters);
    }

    /**
     * @return array
     * @throws dbSqlException
     */
    public function loadAll(): array {
        $sql = 'SELECT * FROM ' . $this->tableName . ';';

        return $this->runRead($sql);
    }

    /**
     * @return int
     * @throws dbSqlException
     */
    public function count(): int {
        $sql = 'SELECT count(*) as counter FROM ' . $this->tableName . ';';

        try {
            $responseArray = $this->runReadSingle($sql);
            $response = $responseArray['counter'];
        } catch (dbRecordNotFoundException $e) {
            $response = 0;
        }

        return $response;
    }
}