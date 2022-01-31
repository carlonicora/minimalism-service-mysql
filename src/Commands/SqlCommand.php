<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFactoryInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use CarloNicora\Minimalism\Services\MySQL\Factories\ExceptionFactory;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use mysqli;

class SqlCommand
{
    /** @var mysqli  */
    private mysqli $connection;

    /**
     * @param ConnectionFactory $connectionFactory
     * @param SqlFactoryInterface|DataObjectInterface $factory
     * @throws MinimalismException
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        SqlFactoryInterface|DataObjectInterface $factory,
    )
    {
        $this->connection = $connectionFactory->create($factory->getTableInterfaceClass());
    }

    /**
     *
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     *
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * @return int|null
     */
    public function getInsertedId(
    ): ?int
    {
        return $this->connection->insert_id;
    }

    /**
     * @param DatabaseOperationType $databaseOperationType
     * @param SqlFactoryInterface|DataObjectInterface $factory
     * @param int $retry
     * @return array|null
     * @throws MinimalismException
     */
    public function execute(
        DatabaseOperationType $databaseOperationType,
        SqlFactoryInterface|DataObjectInterface $factory,
        int $retry=0,
    ): ?array
    {
        $response = null;

        $this->connection->autocommit(false);

        $interfaces = class_implements($factory);
        if (array_key_exists(SqlFactoryInterface::class, $interfaces)){
            $sqlFactory = $factory;
        } else {
            $sqlFactory = $databaseOperationType->getSqlStatementCommand($factory);
        }
        $sql = $sqlFactory->getSql();
        $parameters = $sqlFactory->getParameters();

        $statement = $this->connection->prepare($sql);

        if ($statement === false) {
            throw ExceptionFactory::MySQLStatementPreparationFailed->create($sql . '(' . $this->connection->error . ')');
        }

        if (!empty($parameters)) {
            call_user_func_array([$statement, 'bind_param'], $this->refValues($parameters));
        }

        if (!$statement->execute()) {
            if ($retry<10 && $this->connection->errno===1213){
                $retry++;
                usleep(100000);
                /** @noinspection UnusedFunctionResultInspection */
                $this->execute($databaseOperationType, $factory, $retry);
            } else {
                throw ExceptionFactory::MySQLStatementExecutionFailed->create($sql . '(' . $statement->error. ')');
            }
        }

        if ($databaseOperationType === DatabaseOperationType::Read) {
            $results = $statement->get_result();

            $response = [];
            if ($results !== false) {
                while ($record = $results->fetch_assoc()) {
                    $this->setOriginalValues($record);
                    $response[] = $record;
                }
            }
        } elseif ($databaseOperationType === DatabaseOperationType::Create) {
            $response = $factory->export();

            if (($factory->getTableInterfaceClass())->getAutoIncrementField() !== null){
                $response[($factory->getTableInterfaceClass())->getAutoIncrementField()->value] = $this->getInsertedId();
            }

            $this->setOriginalValues($response);
        }

        /** CHANGE HERE */

        if (false === $statement->close()) {
            throw ExceptionFactory::MySQLCloseFailed->create($statement->error);
        }

        return $response;
    }

    /**
     * @param $arr
     * @return array
     */
    private function refValues($arr): array
    {
        $refs = [];

        foreach ($arr as $key => $value) {
            /** @noinspection PhpArrayAccessCanBeReplacedWithForeachValueInspection */
            $refs[$key] = &$arr[$key];
        }

        return $refs;
    }

    /**
     * @param array $record
     */
    public function setOriginalValues(array &$record): void
    {
        $originalValues = [];
        foreach($record as $fieldName=>$fieldValue){
            $originalValues[$fieldName] = $fieldValue;
        }
        $record['originalValues'] = $originalValues;
    }
}