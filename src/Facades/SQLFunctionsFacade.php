<?php
namespace CarloNicora\Minimalism\Services\MySQL\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLExecutionFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\SQLFunctionsFacadeInterface;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlTableInterface;
use Exception;

class SQLFunctionsFacade implements SQLFunctionsFacadeInterface
{
    /** @var MySqlTableInterface  */
    private MySqlTableInterface $table;

    /** @var SQLExecutionFacadeInterface  */
    private SQLExecutionFacadeInterface $executor;

    /**
     * SQLFunctionsFacade constructor.
     * @param MySqlTableInterface $table
     * @param SQLExecutionFacadeInterface $executor
     */
    public function __construct(MySqlTableInterface $table, SQLExecutionFacadeInterface $executor)
    {
        $this->table = $table;
        $this->executor = $executor;
    }

    /**
     * @throws Exception
     */
    public function runSql(): void
    {
        $this->executor->keepaliveConnection();
        try {
            $this->executor->toggleAutocommit(false);
            $statement = $this->executor->executeQuery($this->table->getSql(), $this->table->getParameters());
            $this->executor->closeStatement($statement);
            $this->executor->toggleAutocommit(true);
        } catch (Exception $exception) {
            $this->executor->rollback();
            throw $exception;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function runRead(): array
    {
        $this->executor->keepaliveConnection();

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
     * @throws Exception
     */
    public function runReadSingle(): array
    {
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
     * @throws Exception
     */
    public function runUpdate(array &$objects): void
    {
        $this->executor->keepaliveConnection();

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
        } catch (Exception $exception) {
            $this->executor->rollback();
            throw $exception;
        }
    }
}