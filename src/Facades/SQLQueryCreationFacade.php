<?php
namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLQueryCreationFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class SQLQueryCreationFacade implements SQLQueryCreationFacadeInterface
{
    /** @var TableInterface  */
    private TableInterface $table;

    /**
     * SQLQueryCreationFacade constructor.
     * @param TableInterface $table
     */
    public function __construct(TableInterface $table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function generateSelectStatement(): string {
        $response = 'SELECT * FROM ' . $this->table->getTableName() ;

        if ($this->table->getPrimaryKey() !== null) {
            $response .= ' WHERE ';

            foreach ($this->table->getPrimaryKey() as $fieldName => $fieldType) {
                $response .= $fieldName . '=? AND ';
            }

            $response = substr($response, 0, -5);
        }

        $response .= ';';

        return $response;
    }

    /**
     * @param int|string $fieldType
     * @return string
     */
    public function convertFieldType($fieldType): string {
        if (is_int($fieldType)){
            if (($fieldType & TableInterface::INTEGER) > 0){
                $fieldType = 'i';
            } elseif (($fieldType & TableInterface::DOUBLE) > 0){
                $fieldType = 'd';
            } elseif (($fieldType & TableInterface::STRING) > 0){
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
    public function generateSelectParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->table->getPrimaryKey() as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function canUseInsertOnDuplicate(): bool {
        if ($this->table->getPrimaryKey() !== null) {
            foreach ($this->table->getTableFields() as $fieldName => $fieldType) {
                if (!array_key_exists($fieldName, $this->table->getPrimaryKey())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function generateInsertOnDuplicateUpdateStart(): string {
        $response = 'INSERT INTO ' . $this->table->getTableName() . ' (';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
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
    public function generateInsertOnDuplicateUpdateRecord(array $record): string {
        $response = '(';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
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
    public function generateInsertOnDuplicateUpdateEnd(): string {
        $response = ' ON DUPLICATE KEY UPDATE ';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->table->getPrimaryKey() ?? [])) {
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
    public function generateInsertStatement(): string {
        $response = 'INSERT' . $this->table->getInsertIgnore() . ' INTO ' . $this->table->getTableName() . ' (';

        $parameterList = '';
        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
            $response .= $fieldName . ',';
            $parameterList .= '?,';
        }

        $response = substr($response, 0, -1);
        $parameterList = substr($parameterList, 0, -1);

        $response .= ') VALUES (' . $parameterList . ');';

        return $response;
    }

    /**
     * @return array
     */
    public function generateInsertParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return string
     */
    public function generateDeleteStatement(): string {
        $response = 'DELETE FROM ' . $this->table->getTableName() . ' WHERE ';

        foreach ($this->table->getPrimaryKey() as $fieldName=>$fieldType){
            $response .= $fieldName . '=? AND ';
        }

        $response = substr($response, 0, -5);

        $response .= ';';

        return $response;
    }

    /**
     * @return array
     */
    public function generateDeleteParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->table->getPrimaryKey() ?? [] as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }

    /**
     * @return string
     */
    public function generateUpdateStatement(): string {
        $response = 'UPDATE ' . $this->table->getTableName() . ' SET ';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->table->getPrimaryKey() ?? [])){
                $response .= $fieldName . '=?,';
            }
        }

        $response = substr($response, 0, -1);

        if ($this->table->getPrimaryKey() !== null) {
            $response .= ' WHERE ';

            foreach ($this->table->getPrimaryKey() as $fieldName => $fieldType) {
                $response .= $fieldName . '=? AND ';
            }

            $response = substr($response, 0, -5);
        }

        $response .= ';';

        return $response;
    }

    /**
     * @return array
     */
    public function generateUpdateParameters(): array {
        $response = array();

        $response[] = '';

        foreach ($this->table->getTableFields() as $fieldName=>$fieldType){
            if (!array_key_exists($fieldName, $this->table->getPrimaryKey() ?? [])) {
                $fieldType = $this->convertFieldType($fieldType);
                $response[0] .= $fieldType;
                $response[] = $fieldName;
            }
        }

        foreach ($this->table->getPrimaryKey() ?? [] as $fieldName=>$fieldType){
            $fieldType = $this->convertFieldType($fieldType);
            $response[0] .= $fieldType;
            $response[] = $fieldName;
        }

        return $response;
    }
}