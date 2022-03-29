<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheInterface;
use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataFunctionInterface;
use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataInterface;
use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlTableInterface;
use Exception;
use RuntimeException;

class MySQL extends AbstractService implements DataInterface
{
    /** @var array */
    private array $tableManagers = [];

    /**
     * @var ConnectionFactory
     */
    private ConnectionFactory $connectionFactory;

    /**
     * MySQL constructor.
     * @param string $MINIMALISM_SERVICE_MYSQL
     * @param LoggerInterface|null $logger
     * @param CacheInterface|null $cache
     */
    public function __construct(
        string $MINIMALISM_SERVICE_MYSQL,
        private ?LoggerInterface $logger=null,
        private ?CacheInterface $cache=null,
    )
    {
        $this->connectionFactory = new ConnectionFactory(
            $MINIMALISM_SERVICE_MYSQL,
            $this->logger,
        );
    }

    /**
     * @return string|null
     */
    public static function getBaseInterface(
    ): ?string
    {
        return DataInterface::class;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCacheInterface(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @param string $dbReader
     * @return MySqlTableInterface
     * @throws Exception
     */
    public function create(string $dbReader): MySqlTableInterface
    {
        if (array_key_exists($dbReader, $this->tableManagers)) {
            return $this->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            $this->logger?->error(
                message: 'Database reader class missing: ' . $dbReader,
                domain: 'mysql'
            );
            throw new RuntimeException('Database reader class missing: ' . $dbReader, 500);
        }

        /** @var MySqlTableInterface $response */
        $response = new $dbReader($this->connectionFactory, $this->logger);
        $response->initialiseAttributes();

        $databaseName = $response->getDbToUse();

        $connection = $this->connectionFactory->getDatabase($databaseName);

        $response->setConnection($connection);

        $this->tableManagers[$dbReader] = $response;

        return $response;
    }

    /**
     *
     */
    public function destroy(): void
    {
        parent::destroy();
        $this->connectionFactory->resetDatabases();
        $this->tableManagers = [];
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function flattenArray(array $parameters): array
    {
        $response = [];

        foreach ($parameters ?? [] as $parameter) {
            $response[] = $parameter;
        }

        return $response;
    }

    /**
     * @param DataFunctionInterface $dataFunction
     * @return array
     * @throws Exception
     */
    public function readByDataFunction(
        DataFunctionInterface $dataFunction,
    ): array
    {
        return $this->read(
            tableInterfaceClassName: $dataFunction->getClassName(),
            functionName: $dataFunction->getFunctionName(),
            parameters: $dataFunction->getParameters(),
            cacheBuilder: $dataFunction->getCacheBuilder()
        );
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array
     * @throws Exception
     */
    public function read(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters,
        ?CacheBuilderInterface $cacheBuilder = null
    ): array
    {
        $response = null;
        if ($this->cache !== null
            &&
            $cacheBuilder !== null
            &&
            $this->cache->useCaching()
        ) {
            $response = $this->cache->readArray($cacheBuilder, CacheType::Data);
        }

        if ($response === null){
            $tableInterface = $this->create($tableInterfaceClassName);
            $parameters = $this->flattenArray($parameters);
            $response = $tableInterface->{$functionName}(...$parameters);

            if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
                $this->cache->saveArray($cacheBuilder, $response, CacheType::Data);
            }
        } elseif ($response !== [] && !array_key_exists(0, $response)){
            $response = [$response];
        }

        return $response;
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @return int
     * @throws Exception
     */
    public function count(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters
    ): int
    {
        return $this->create($tableInterfaceClassName)->{$functionName}(...$parameters);
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param bool $avoidSingleInsert
     * @throws Exception
     */
    public function update(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null,
        bool $avoidSingleInsert=false
    ): void
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update(
            records: $records,
            avoidSingleInsert: $avoidSingleInsert,
        );

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);
        }
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @throws Exception
     */
    public function delete(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null
    ): void
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update($records, true);

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);
        }
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param bool $avoidSingleInsert
     * @return array
     * @throws Exception
     */
    public function insert(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null,
        bool $avoidSingleInsert=false
    ): array
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update(
            records: $records,
            avoidSingleInsert: $avoidSingleInsert
        );

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);
        }

        return ($records);
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @return array|null
     * @throws Exception
     */
    public function run(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters,
    ): ?array
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $parameters = $this->flattenArray($parameters);
        return $tableInterface->{$functionName}(...$parameters);
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $sql
     * @param array $parameters
     * @return array|null
     * @throws Exception
     */
    public function runSQL(
        string $tableInterfaceClassName,
        string $sql,
        array $parameters=[],
    ): array|null{

        return $this->create($tableInterfaceClassName)->runSQL($sql, $parameters);
    }
}