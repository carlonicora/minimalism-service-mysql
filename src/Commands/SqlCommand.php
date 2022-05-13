<?php
namespace CarloNicora\Minimalism\Services\MySQL\Commands;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlQueryFactoryInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use CarloNicora\Minimalism\Services\MySQL\Enums\SqlOptions;
use CarloNicora\Minimalism\Services\MySQL\Factories\ExceptionFactory;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use Exception;
use mysqli;

class SqlCommand
{
    /** @var mysqli  */
    private mysqli $connection;

    /**
     * @param ConnectionFactory $connectionFactory
     * @param SqlQueryFactoryInterface|SqlDataObjectInterface $factory
     * @param SqlOptions[] $options
     * @throws MinimalismException
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        SqlQueryFactoryInterface|SqlDataObjectInterface $factory,
        private readonly array $options,
    )
    {
        $this->connection = $connectionFactory->create($factory->getTable());
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
     * @param SqlQueryFactoryInterface|SqlDataObjectInterface $queryFactory
     * @param int $retry
     * @return array|null
     * @throws MinimalismException|Exception
     */
    public function execute(
        DatabaseOperationType                           $databaseOperationType,
        SqlQueryFactoryInterface|SqlDataObjectInterface $queryFactory,
        int                                             $retry=0,
    ): ?array
    {
        $response = null;

        $this->connection->autocommit(false);
        $this->runOptions();

        $interfaces = class_implements($queryFactory);
        if (array_key_exists(SqlQueryFactoryInterface::class, $interfaces)){
            $sqlFactory = $queryFactory;
        } else {
            $sqlFactory = $databaseOperationType->getSqlStatementCommand($queryFactory);
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
                $this->execute($databaseOperationType, $queryFactory, $retry);
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
            $response = $sqlFactory->getInsertedArray();

            if ($sqlFactory->getTable()->getAutoIncrementField() !== null){
                $response[$sqlFactory->getTable()->getAutoIncrementField()->getName()] = $this->getInsertedId();
            }

            $this->setOriginalValues($response);
        }

        if (false === $statement->close()) {
            throw ExceptionFactory::MySQLCloseFailed->create($statement->error);
        }

        $this->runOptions(on: false);

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

    /**
     * @param bool $on
     * @return void
     */
    public function runOptions(
        bool $on=true,
    ): void
    {
        foreach ($this->options as $option){
            /** @noinspection UnusedFunctionResultInspection */
            $this->connection->query(query: ($on ? $option->on() : $option->off()));
        }
    }
}