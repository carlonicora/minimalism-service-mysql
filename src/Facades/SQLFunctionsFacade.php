<?php
namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLFunctionsFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class SQLFunctionsFacade implements SQLFunctionsFacadeInterface
{
    /** @var TableInterface  */
    private TableInterface $table;

    /** @var SQLExecutionFacadeInterface  */
    private SQLExecutionFacadeInterface $executor;

    /**
     * SQLFunctionsFacade constructor.
     * @param TableInterface $table
     * @param SQLExecutionFacadeInterface $executor
     */
    public function __construct(TableInterface $table, SQLExecutionFacadeInterface $executor)
    {
        $this->table = $table;
        $this->executor = $executor;
    }

    /**
     * @throws DbSqlException
     */
    public function runSql(): void {
        try {
            $this->executor->toggleAutocommit(false);
            $statement = $this->executor->executeQuery($this->table->getSql(), $this->table->getParameters());
            $this->executor->closeStatement($statement);
            $this->executor->toggleAutocommit(true);
        } catch (DbSqlException $exception) {
            $this->executor->rollback();
            throw $exception;
        }
    }

    /**
     * @return array
     * @throws DbSqlException
     */
    public function runRead(): array {
        $response = [];

        $statement = $this->executor->executeQuery($this->table->getSql(), $this->table->getParameters());
        $results = $statement->get_result();

        if ($results !== false){
            while ($record = $results->fetch_assoc()){
                RecordFacade::setOriginalValues($record);
                $response[] = $record;
            }
        }

        $this->executor->closeStatement($statement);

        return $response;
    }

    /**
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function runReadSingle(): array {
        $response = $this->runRead();

        if (count($response) === 0) {
            throw new DbRecordNotFoundException('Record not found');
        }

        if (count($response) > 1) {
            throw new DbRecordNotFoundException('Multiple records found');
        }

        return $response[0];
    }

    /**
     * @param array $objects
     * @throws DbSqlException
     */
    public function runUpdate(array &$objects): void {
        try {
            $this->executor->toggleAutocommit(false);

            foreach ($objects as $objectKey => $object) {
                if (array_key_exists('_sql', $object)) {
                    $statement = $this->executor->executeQuery($object['_sql']['statement'], $object['_sql']['parameters']);

                    $this->executor->closeStatement($statement);

                    if ($object['_sql']['status'] === RecordFacade::RECORD_STATUS_NEW && $this->table->getAutoIncrementField() !== null) {
                        $objects[$objectKey][$this->table->getAutoIncrementField()] = $this->executor->getInsertedId();
                    }

                    unset($objects[$objectKey]['_sql']);

                    RecordFacade::setOriginalValues($objects[$objectKey]);
                }
            }

            $this->executor->toggleAutocommit(true);
        } catch (DbSqlException $exception) {
            $this->executor->rollback();
            throw $exception;
        }
    }
}